<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Institucion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Estudiantes",
 *     description="API Endpoints para autenticación y gestión de estudiantes"
 * )
 */
class EstudianteAuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/estudiantes/iniciar-sesion",
     *     summary="Iniciar sesión como estudiante",
     *     tags={"Estudiantes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nombre", "institucion_id"},
     *             @OA\Property(property="nombre", type="string", example="Juan Pérez"),
     *             @OA\Property(property="institucion_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login exitoso"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="estudiante", type="object"),
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Estudiante no encontrado"
     *     )
     * )
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['loginStudent', 'registerStudent', 'searchStudents', 'getInstituciones']]);
    }

    public function loginStudent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string',
            'institucion_id' => 'required|exists:instituciones,id',
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'institucion_id.required' => 'La institución es obligatoria',
            'institucion_id.exists' => 'La institución seleccionada no existe',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $estudiante = Usuario::where('nombre', 'LIKE', '%' . $request->nombre . '%')
                ->where('institucion_id', $request->institucion_id)
                ->where('rol_id', 3) // rol_id 3 es para estudiantes
                ->where('estado', true)
                ->first();

            // Añadir apellido y edad en la respuesta. También nombre institución para consistencia con registro
            $institucionNombre = null;
            if ($estudiante) {
                $institucion = Institucion::find($estudiante->institucion_id);
                $institucionNombre = $institucion?->nombre;
            }

            if (!$estudiante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estudiante no encontrado'
                ], 404);
            }

            $token = JWTAuth::fromUser($estudiante);

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'estudiante' => [
                        'id' => $estudiante->id,
                        'nombre' => $estudiante->nombre,
                        'apellido' => $estudiante->apellido,
                        'edad' => $estudiante->edad,
                        'codigo_estudiante' => $estudiante->codigo_estudiante,
                        'institucion_id' => $estudiante->institucion_id,
                        'institucion' => $institucionNombre,
                        'rol_id' => $estudiante->rol_id,
                        'estado' => (bool) $estudiante->estado,
                    ],
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60
                ]
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerStudent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'institucion_id' => 'required|exists:instituciones,id',
            // Alinear con frontend: rango 7-18
                'edad' => 'nullable|integer|min:7|max:18',
            'con_padres' => 'nullable|boolean',
            // Si viene con_padres=true, requerimos documento y correo
            'num_documento' => 'required_if:con_padres,true|nullable|string|max:50',
            'correo' => 'required_if:con_padres,true|nullable|email|max:100',
        ], [
            'apellido.required' => 'El apellido es obligatorio',
            'institucion_id.required' => 'La institución es obligatoria',
            'institucion_id.exists' => 'La institución seleccionada no existe',
                'edad.min' => 'La edad mínima es 7 años',
                'edad.max' => 'La edad máxima es 18 años',
            'num_documento.required_if' => 'El número de documento es obligatorio si está con un adulto',
            'correo.required_if' => 'El correo es obligatorio si está con un adulto',
            'correo.email' => 'El correo debe tener un formato válido',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $conPadres = filter_var($request->input('con_padres', false), FILTER_VALIDATE_BOOLEAN);

            // Generar código único para el estudiante
            $codigo = Usuario::generarCodigoEstudiante($request->institucion_id, $request->nombre, $request->apellido);

                // Edad puede ser nula si el estudiante no la recuerda
                $edadValue = $request->filled('edad') ? (int) $request->edad : null;

                // Generar una contraseña por defecto (no nula) usando nombre + edad cuando esté disponible
                $plainPassword = $request->nombre . ($edadValue !== null ? $edadValue : rand(1000, 9999));
                $passwordHash = Hash::make($plainPassword);

            $estudiante = Usuario::create([
                'num_documento' => $conPadres ? ($request->input('num_documento') ?? null) : null,
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                    'edad' => $edadValue,
                'correo' => $conPadres ? ($request->input('correo') ?? null) : null,
                'codigo_estudiante' => $codigo,
                'rol_id' => 3, // Estudiante
                'institucion_id' => (int) $request->institucion_id,
                'fecha_creacion' => Carbon::now(),
                'estado' => true,
                'nivel_actual' => 1,
                    'contrasena_hash' => $passwordHash,
            ]);

            $token = JWTAuth::fromUser($estudiante);

            // Obtener nombre de institución para responder como espera el frontend
            $institucionNombre = null;
            if ($estudiante->institucion_id) {
                $institucion = Institucion::find($estudiante->institucion_id);
                $institucionNombre = $institucion?->nombre;
            }

            return response()->json([
                'success' => true,
                'message' => 'Estudiante registrado exitosamente',
                'data' => [
                    'estudiante' => [
                        'id' => $estudiante->id,
                        'nombre' => $estudiante->nombre,
                        'apellido' => $estudiante->apellido,
                        'codigo_estudiante' => $estudiante->codigo_estudiante,
                        'edad' => $estudiante->edad ?? null, // Asegurar que edad se envíe aunque sea null
                        'num_documento' => $estudiante->num_documento,
                        'correo' => $estudiante->correo,
                        'institucion_id' => $estudiante->institucion_id,
                        'institucion' => $institucionNombre,
                        'estado' => (bool) $estudiante->estado,
                    ],
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchStudents(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string',
            'institucion_id' => 'required|exists:instituciones,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $estudiantes = Usuario::where('nombre', 'LIKE', '%' . $request->nombre . '%')
                ->where('institucion_id', $request->institucion_id)
                ->where('rol_id', 3)
                ->where('estado', true)
                ->select('id', 'nombre', 'edad')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'estudiantes' => $estudiantes
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar estudiantes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getInstituciones(): JsonResponse
    {
        try {
            $instituciones = Institucion::select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'instituciones' => $instituciones
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener instituciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me(): JsonResponse
    {
        try {
            $estudiante = auth('api')->user();

            if (!$estudiante || $estudiante->rol_id !== 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estudiante no autenticado'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'estudiante' => [
                        'id' => $estudiante->id,
                        'nombre' => $estudiante->nombre,
                        'institucion_id' => $estudiante->institucion_id,
                        'rol_id' => $estudiante->rol_id,
                        'edad' => $estudiante->edad,
                        'fecha_creacion' => $estudiante->fecha_creacion,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();
            
            if ($token) {
                try {
                    // Intentar invalidar el token
                    JWTAuth::invalidate($token);
                } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
                    // Si el token ya está en la lista negra, consideramos que ya está cerrada la sesión
                    return response()->json([
                        'success' => true,
                        'message' => 'Sesión ya cerrada previamente'
                    ], 200);
                }
                
                // Limpiar la autenticación
                JWTAuth::unsetToken();
            }

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente'
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}