<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Constructor del controlador
     */
    public function __construct()
    {
        // Solo login es público para administradores e instituciones
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Login de usuario
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|string|email',
            'contrasena' => 'required|string',
        ], [
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe tener un formato válido',
            'contrasena.required' => 'La contraseña es obligatoria',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('correo', 'contrasena');

        try {
            // Buscar usuario por correo
            $usuario = Usuario::where('correo', $credentials['correo'])->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            // Verificar que no sea estudiante (rol_id = 3)
            if ($usuario->rol_id === 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los estudiantes no pueden acceder por este panel. Usa el panel de estudiantes.'
                ], 403);
            }

            // Verificar si el usuario está activo
            if (!$usuario->estado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta está desactivada'
                ], 401);
            }

            // Verificar contraseña
            if (!Hash::check($credentials['contrasena'], $usuario->contrasena_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            // Generar token JWT
            $token = JWTAuth::fromUser($usuario);

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nombre,
                        'correo' => $usuario->correo,
                        'rol_id' => $usuario->rol_id,
                        'institucion_id' => $usuario->institucion_id,
                        'estado' => $usuario->estado,
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
     * Crear nuevo usuario (solo administradores)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createUser(Request $request): JsonResponse
    {
        // Verificar que el usuario autenticado sea administrador (rol_id = 1)
        $currentUser = auth('api')->user();
        if ($currentUser->rol_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para crear usuarios'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|string|email|max:255|unique:usuarios',
            'contrasena' => 'required|string|min:6',
            'rol_id' => 'required|integer|exists:roles,id',
            'institucion_id' => 'required|integer|exists:instituciones,id',
        ], [
            'nombre.required' => 'El nombre es obligatorio',
            'correo.required' => 'El correo es obligatorio',
            'correo.email' => 'El correo debe tener un formato válido',
            'correo.unique' => 'El correo ya está registrado',
            'contrasena.required' => 'La contraseña es obligatoria',
            'contrasena.min' => 'La contraseña debe tener al menos 6 caracteres',
            'rol_id.required' => 'El rol es obligatorio',
            'rol_id.exists' => 'El rol seleccionado no existe',
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
            $usuario = Usuario::create([
                'nombre' => $request->nombre,
                'correo' => $request->correo,
                'contrasena_hash' => Hash::make($request->contrasena),
                'rol_id' => $request->rol_id,
                'institucion_id' => $request->institucion_id,
                'fecha_creacion' => Carbon::now(),
                'estado' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nombre,
                        'correo' => $usuario->correo,
                        'rol_id' => $usuario->rol_id,
                        'institucion_id' => $usuario->institucion_id,
                        'estado' => $usuario->estado,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar token para usuario existente (solo administradores)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateToken(Request $request): JsonResponse
    {
        // Verificar que el usuario autenticado sea administrador (rol_id = 1)
        $currentUser = auth('api')->user();
        if ($currentUser->rol_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para generar tokens'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'usuario_id' => 'required|integer|exists:usuarios,id',
        ], [
            'usuario_id.required' => 'El ID del usuario es obligatorio',
            'usuario_id.exists' => 'El usuario no existe',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $usuario = Usuario::find($request->usuario_id);

            if (!$usuario->estado) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario está desactivado'
                ], 400);
            }

            $token = JWTAuth::fromUser($usuario);

            return response()->json([
                'success' => true,
                'message' => 'Token generado exitosamente',
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nombre,
                        'correo' => $usuario->correo,
                        'rol_id' => $usuario->rol_id,
                        'institucion_id' => $usuario->institucion_id,
                    ],
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del usuario autenticado
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $usuario = auth('api')->user();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => [
                        'id' => $usuario->id,
                        'nombre' => $usuario->nombre,
                        'correo' => $usuario->correo,
                        'rol_id' => $usuario->rol_id,
                        'institucion_id' => $usuario->institucion_id,
                        'estado' => $usuario->estado,
                        'fecha_creacion' => $usuario->fecha_creacion,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout del usuario
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
     * Refrescar token JWT
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Token refrescado exitosamente',
                'data' => [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60
                ]
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al refrescar el token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar contraseña del usuario autenticado
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contrasena_actual' => 'required|string',
            'contrasena_nueva' => 'required|string|min:6|confirmed',
        ], [
            'contrasena_actual.required' => 'La contraseña actual es obligatoria',
            'contrasena_nueva.required' => 'La nueva contraseña es obligatoria',
            'contrasena_nueva.min' => 'La nueva contraseña debe tener al menos 6 caracteres',
            'contrasena_nueva.confirmed' => 'Las contraseñas no coinciden',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $usuario = auth('api')->user();

            // Verificar contraseña actual
            if (!Hash::check($request->contrasena_actual, $usuario->contrasena_hash)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 400);
            }

            // Actualizar contraseña
            $usuario->contrasena_hash = Hash::make($request->contrasena_nueva);
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la contraseña',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar usuarios (solo administradores)
     *
     * @return JsonResponse
     */
    public function listUsers(): JsonResponse
    {
        // Verificar que el usuario autenticado sea administrador (rol_id = 1)
        $currentUser = auth('api')->user();
        if ($currentUser->rol_id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver usuarios'
            ], 403);
        }

        try {
            $usuarios = Usuario::select('id', 'nombre', 'correo', 'rol_id', 'institucion_id', 'estado', 'fecha_creacion')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'usuarios' => $usuarios
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}

