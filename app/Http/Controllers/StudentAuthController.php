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

class StudentAuthController extends Controller
{
    /**
     * Constructor del controlador
     */
    public function __construct()
    {
        // Todas las rutas de estudiantes son públicas excepto logout y me
        $this->middleware('auth:api', ['except' => ['loginStudent', 'registerStudent', 'getInstituciones']]);
    }

    /**
     * Login de estudiante usando nombre, apellido e institución
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loginStudent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50',
            'apellido' => 'required|string|max:50',
            'institucion_id' => 'required|integer|exists:instituciones,id',
        ], [
            'nombre.required' => 'Por favor escribe tu nombre',
            'apellido.required' => 'Por favor escribe tu apellido',
            'institucion_id.required' => 'Por favor selecciona tu escuela',
            'institucion_id.exists' => 'Esa escuela no existe',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ups, revisa que hayas escrito tu nombre, apellido y seleccionado tu escuela',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Buscar estudiante por nombre, apellido e institución
            $estudiante = Usuario::where('nombre', $request->nombre)
                ->where('apellido', $request->apellido)
                ->where('institucion_id', $request->institucion_id)
                ->where('rol_id', 3) // Solo estudiantes
                ->first();

            if (!$estudiante) {
                return response()->json([
                    'success' => false,
                    'message' => 'No encontramos tu nombre en esta escuela. ¿Verificaste que sea la escuela correcta? Si no tienes cuenta, puedes registrarte.'
                ], 404);
            }

            // Verificar si el estudiante está activo
            if (!$estudiante->estado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta está desactivada'
                ], 401);
            }

            // Generar token JWT
            $token = JWTAuth::fromUser($estudiante);

            return response()->json([
                'success' => true,
                'message' => '¡Hola! Has entrado exitosamente',
                'data' => [
                    'estudiante' => [
                        'id' => $estudiante->id,
                        'nombre' => $estudiante->nombre,
                        'apellido' => $estudiante->apellido,
                        'codigo_estudiante' => $estudiante->codigo_estudiante,
                        'institucion_id' => $estudiante->institucion_id,
                        'institucion' => $estudiante->institucion->nombre ?? null,
                        'estado' => $estudiante->estado,
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registro de nuevo estudiante
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerStudent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:50',
            'apellido' => 'required|string|max:50',
            'edad' => 'nullable|integer|min:7|max:18',
            'num_documento' => 'nullable|string|max:20',
            'institucion_id' => 'required|integer|exists:instituciones,id',
        ], [
            'nombre.required' => 'Por favor escribe tu nombre',
            'nombre.max' => 'Tu nombre es muy largo',
            'apellido.required' => 'Por favor escribe tu apellido',
            'apellido.max' => 'Tu apellido es muy largo',
            'edad.integer' => 'La edad debe ser un número',
            'edad.min' => 'Debes tener al menos 7 años',
            'edad.max' => 'Debes tener máximo 18 años',
            'institucion_id.required' => 'Por favor selecciona tu escuela',
            'institucion_id.exists' => 'Esa escuela no existe',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ups, hay algunos datos que necesitan corrección',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verificar si ya existe un estudiante con el mismo nombre, apellido e institución
            $estudianteExistente = Usuario::where('nombre', $request->nombre)
                ->where('apellido', $request->apellido)
                ->where('institucion_id', $request->institucion_id)
                ->where('rol_id', 3)
                ->first();

            if ($estudianteExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya hay un estudiante con tu nombre y apellido en esta escuela. Si eres tú, intenta hacer login.'
                ], 409);
            }

            // Generar código único de estudiante
            $codigoEstudiante = Usuario::generarCodigoEstudiante(
                $request->institucion_id,
                $request->nombre,
                $request->apellido
            );

            // Crear nuevo estudiante
            $estudiante = Usuario::create([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'edad' => $request->edad ?? 10, // Valor por defecto: 10 años si no se proporciona
                'num_documento' => $request->num_documento,
                'codigo_estudiante' => $codigoEstudiante,
                'rol_id' => 3, // Rol de estudiante
                'institucion_id' => $request->institucion_id,
                'fecha_creacion' => Carbon::now(),
                'estado' => true,
                // Para estudiantes: correo null, contraseña placeholder (no se usa nunca)
                'correo' => null,
                'contrasena_hash' => Hash::make('ESTUDIANTE_SIN_PASSWORD'), // Hash placeholder
            ]);

            // Generar token automáticamente después del registro
            $token = JWTAuth::fromUser($estudiante);

            return response()->json([
                'success' => true,
                'message' => '¡Bienvenido! Tu cuenta ha sido creada exitosamente',
                'data' => [
                    'estudiante' => [
                        'id' => $estudiante->id,
                        'nombre' => $estudiante->nombre,
                        'apellido' => $estudiante->apellido,
                        'codigo_estudiante' => $estudiante->codigo_estudiante,
                        'edad' => $estudiante->edad,
                        'num_documento' => $estudiante->num_documento,
                        'institucion_id' => $estudiante->institucion_id,
                        'institucion' => $estudiante->institucion->nombre ?? null,
                        'estado' => $estudiante->estado,
                    ],
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el estudiante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar estudiantes por nombre (para mostrar sugerencias)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchStudents(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|min:2',
            'institucion_id' => 'required|integer|exists:instituciones,id',
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres',
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
            $estudiantes = Usuario::where('rol_id', 3)
                ->where('institucion_id', $request->institucion_id)
                ->where('estado', true)
                ->where(function($query) use ($request) {
                    $query->where('nombre', 'LIKE', '%' . $request->nombre . '%')
                          ->orWhere('apellido', 'LIKE', '%' . $request->nombre . '%');
                })
                ->select('id', 'nombre', 'apellido', 'codigo_estudiante')
                ->orderBy('nombre')
                ->limit(10)
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

    /**
     * Obtener información del estudiante autenticado
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $estudiante = auth('api')->user();

            if (!$estudiante || !$estudiante->esEstudiante()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No eres un estudiante autenticado'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'estudiante' => [
                        'id' => $estudiante->id,
                        'nombre' => $estudiante->nombre,
                        'apellido' => $estudiante->apellido,
                        'codigo_estudiante' => $estudiante->codigo_estudiante,
                        'edad' => $estudiante->edad,
                        'num_documento' => $estudiante->num_documento,
                        'institucion_id' => $estudiante->institucion_id,
                        'institucion' => $estudiante->institucion->nombre ?? null,
                        'estado' => $estudiante->estado,
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

    /**
     * Logout del estudiante
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            auth('api')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Logout exitoso'
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al hacer logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener instituciones disponibles (público)
     *
     * @return JsonResponse
     */
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
} 