<?php

namespace App\Http\Controllers;

use App\Models\AsignacionesJuego;
use App\Models\Usuario;
use App\Models\Juego;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsignacionesJuegoController extends Controller
{
    /**
     * Obtener todas las asignaciones de juegos
     */
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
     * Obtener asignaciones de un usuario específico
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
     * Crear una nueva asignación de juego
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
     * Marcar una asignación como completada
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
     * Actualizar una asignación
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
     * Eliminar una asignación
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
     * Obtener estadísticas de progreso de un usuario
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