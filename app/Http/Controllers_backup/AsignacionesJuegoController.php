<?php

namespace App\Http\Controllers;
use App\Models\AsignacionesJuego;
use App\Models\Usuario;
use App\Models\Juego;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Asignaciones de Juegos",
 *     description="API Endpoints de asignaciones de juegos"
 * )
 * 
 * @OA\Schema(
 *     schema="AsignacionJuego",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="usuario_id", type="integer"),
 *     @OA\Property(property="juego_id", type="integer"),
 *     @OA\Property(property="nivel_asignado", type="integer"),
 *     @OA\Property(property="completado", type="boolean"),
 *     @OA\Property(property="fecha_asignacion", type="string", format="date-time"),
 *     @OA\Property(property="fecha_completado", type="string", format="date-time", nullable=true)
 * )
 */
class AsignacionesJuegoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/asignaciones",
     *     summary="Obtener todas las asignaciones de juegos",
     *     tags={"Asignaciones de Juegos"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de asignaciones obtenida correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Asignaciones de juegos obtenidas correctamente"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
    public function index()
    {
        $asignaciones = DB::table('asignaciones_juegos')
            ->join('usuarios', 'asignaciones_juegos.usuario_id', '=', 'usuarios.id')
            ->join('juegos', 'asignaciones_juegos.juego_id', '=', 'juegos.id')
            ->select('asignaciones_juegos.*', 'usuarios.nombre as usuario_nombre', 'usuarios.apellido as usuario_apellido', 'juegos.titulo as juego_titulo')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Asignaciones de juegos obtenidas correctamente',
            'data' => $asignaciones
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/asignaciones/usuario/{usuario_id}",
     *     summary="Obtener asignaciones de un usuario específico",
     *     tags={"Asignaciones de Juegos"},
     *     @OA\Parameter(
     *         name="usuario_id",
     *         in="path",
     *         required=true,
     *         description="ID del usuario",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Asignaciones del usuario obtenidas correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Asignaciones del usuario obtenidas correctamente"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado"
     *     )
     * )
     */
    public function getByUser($usuario_id)
    {
        $usuario = Usuario::find($usuario_id);
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $asignaciones = DB::table('asignaciones_juegos')
            ->join('juegos', 'asignaciones_juegos.juego_id', '=', 'juegos.id')
            ->where('asignaciones_juegos.usuario_id', $usuario_id)
            ->select('asignaciones_juegos.*', 'juegos.titulo', 'juegos.descripcion', 'juegos.url_juego')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Asignaciones del usuario obtenidas correctamente',
            'data' => $asignaciones
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/asignaciones",
     *     summary="Crear una nueva asignación de juego",
     *     tags={"Asignaciones de Juegos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="usuario_id", type="integer", example=1),
     *             @OA\Property(property="juego_id", type="integer", example=1),
     *             @OA\Property(property="nivel_asignado", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Juego asignado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Juego asignado correctamente al usuario"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="El usuario ya tiene este juego asignado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'usuario_id' => 'required|integer|exists:usuarios,id',
            'juego_id' => 'required|integer|exists:juegos,id',
            'nivel_asignado' => 'required|integer|min:1|max:10',
        ]);

        // Verificar si el usuario ya tiene este juego asignado
        $existeAsignacion = AsignacionesJuego::where('usuario_id', $request->usuario_id)
            ->where('juego_id', $request->juego_id)
            ->first();

        if ($existeAsignacion) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya tiene este juego asignado'
            ], 400);
        }

        $asignacion = new AsignacionesJuego();
        $asignacion->usuario_id = $request->usuario_id;
        $asignacion->juego_id = $request->juego_id;
        $asignacion->nivel_asignado = $request->nivel_asignado;
        $asignacion->completado = false;
        $asignacion->fecha_asignacion = now();
        $asignacion->save();

        return response()->json([
            'success' => true,
            'message' => 'Juego asignado correctamente al usuario',
            'data' => $asignacion
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/asignaciones/{id}/completar",
     *     summary="Marcar una asignación como completada",
     *     tags={"Asignaciones de Juegos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la asignación",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Juego marcado como completado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Juego marcado como completado"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Asignación no encontrada"
     *     )
     * )
     */
    public function completar($id)
    {
        $asignacion = AsignacionesJuego::find($id);
        if (!$asignacion) {
            return response()->json([
                'success' => false,
                'message' => 'Asignación no encontrada'
            ], 404);
        }

        $asignacion->completado = true;
        $asignacion->fecha_completado = now();
        $asignacion->save();

        return response()->json([
            'success' => true,
            'message' => 'Juego marcado como completado',
            'data' => $asignacion
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/asignaciones/{id}",
     *     summary="Actualizar una asignación",
     *     tags={"Asignaciones de Juegos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la asignación",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nivel_asignado", type="integer", example=2),
     *             @OA\Property(property="completado", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Asignación actualizada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Asignación actualizada correctamente"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Asignación no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $asignacion = AsignacionesJuego::find($id);
        if (!$asignacion) {
            return response()->json([
                'success' => false,
                'message' => 'Asignación no encontrada'
            ], 404);
        }

        $this->validate($request, [
            'nivel_asignado' => 'integer|min:1|max:10',
            'completado' => 'boolean',
        ]);

        if ($request->has('nivel_asignado')) $asignacion->nivel_asignado = $request->nivel_asignado;
        if ($request->has('completado')) {
            $asignacion->completado = $request->completado;
            if ($request->completado && !$asignacion->fecha_completado) {
                $asignacion->fecha_completado = now();
            }
        }
        
        $asignacion->save();

        return response()->json([
            'success' => true,
            'message' => 'Asignación actualizada correctamente',
            'data' => $asignacion
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/asignaciones/{id}",
     *     summary="Eliminar una asignación",
     *     tags={"Asignaciones de Juegos"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la asignación",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Asignación eliminada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Asignación eliminada correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Asignación no encontrada"
     *     )
     * )
     */
    public function destroy($id)
    {
        $asignacion = AsignacionesJuego::find($id);
        if (!$asignacion) {
            return response()->json([
                'success' => false,
                'message' => 'Asignación no encontrada'
            ], 404);
        }

        $asignacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asignación eliminada correctamente'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/asignaciones/estadisticas/{usuario_id}",
     *     summary="Obtener estadísticas de progreso de un usuario",
     *     tags={"Asignaciones de Juegos"},
     *     @OA\Parameter(
     *         name="usuario_id",
     *         in="path",
     *         required=true,
     *         description="ID del usuario",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas obtenidas correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Estadísticas de progreso obtenidas correctamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_asignaciones", type="integer", example=10),
     *                 @OA\Property(property="juegos_completados", type="integer", example=5),
     *                 @OA\Property(property="juegos_pendientes", type="integer", example=5),
     *                 @OA\Property(property="porcentaje_completado", type="number", format="float", example=50.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado"
     *     )
     * )
     */
    public function estadisticasUsuario($usuario_id)
    {
        $usuario = Usuario::find($usuario_id);
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $totalAsignaciones = AsignacionesJuego::where('usuario_id', $usuario_id)->count();
        $completadas = AsignacionesJuego::where('usuario_id', $usuario_id)->where('completado', true)->count();
        $pendientes = $totalAsignaciones - $completadas;
        $porcentajeCompletado = $totalAsignaciones > 0 ? ($completadas / $totalAsignaciones) * 100 : 0;

        $estadisticas = [
            'total_asignaciones' => $totalAsignaciones,
            'juegos_completados' => $completadas,
            'juegos_pendientes' => $pendientes,
            'porcentaje_completado' => round($porcentajeCompletado, 2)
        ];

        return response()->json([
            'success' => true,
            'message' => 'Estadísticas de progreso obtenidas correctamente',
            'data' => $estadisticas
        ]);
    }
}