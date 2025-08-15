<?php

namespace App\Http\Controllers;

use App\Models\UsuariosInsignia;
use App\Models\Usuario;
use App\Models\Insignia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuariosInsigniaController extends Controller
{
    /**
     * Obtener todas las asignaciones de insignias
     */
    public function index()
    {
        $usuariosInsignias = DB::table('usuarios_insignias')
            ->join('usuarios', 'usuarios_insignias.usuario_id', '=', 'usuarios.id')
            ->join('insignias', 'usuarios_insignias.insignia_id', '=', 'insignias.id')
            ->select('usuarios_insignias.*', 'usuarios.nombre as usuario_nombre', 'usuarios.apellido as usuario_apellido', 'insignias.nombre as insignia_nombre')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Asignaciones de insignias obtenidas correctamente',
            'data' => $usuariosInsignias
        ]);
    }

    /**
     * Obtener insignias de un usuario específico
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

        $insignias = DB::table('usuarios_insignias')
            ->join('insignias', 'usuarios_insignias.insignia_id', '=', 'insignias.id')
            ->where('usuarios_insignias.usuario_id', $usuario_id)
            ->select('insignias.*', 'usuarios_insignias.fecha_otorgada')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Insignias del usuario obtenidas correctamente',
            'data' => $insignias
        ]);
    }

    /**
     * Asignar una insignia a un usuario
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'usuario_id' => 'required|integer|exists:usuarios,id',
            'insignia_id' => 'required|integer|exists:insignias,id',
        ]);

        // Verificar si el usuario ya tiene esta insignia
        $existeAsignacion = UsuariosInsignia::where('usuario_id', $request->usuario_id)
            ->where('insignia_id', $request->insignia_id)
            ->first();

        if ($existeAsignacion) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya tiene esta insignia asignada'
            ], 400);
        }

        $usuarioInsignia = new UsuariosInsignia();
        $usuarioInsignia->usuario_id = $request->usuario_id;
        $usuarioInsignia->insignia_id = $request->insignia_id;
        $usuarioInsignia->fecha_otorgada = now();
        $usuarioInsignia->save();

        return response()->json([
            'success' => true,
            'message' => 'Insignia asignada correctamente al usuario',
            'data' => $usuarioInsignia
        ], 201);
    }

    /**
     * Remover una insignia de un usuario
     */
    public function destroy($id)
    {
        $usuarioInsignia = UsuariosInsignia::find($id);
        if (!$usuarioInsignia) {
            return response()->json([
                'success' => false,
                'message' => 'Asignación de insignia no encontrada'
            ], 404);
        }

        $usuarioInsignia->delete();

        return response()->json([
            'success' => true,
            'message' => 'Insignia removida del usuario correctamente'
        ]);
    }

    /**
     * Obtener estadísticas de insignias por usuario
     */
    public function estadisticas($usuario_id)
    {
        $usuario = Usuario::find($usuario_id);
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $totalInsignias = Insignia::count();
        $insigniasObtenidas = UsuariosInsignia::where('usuario_id', $usuario_id)->count();
        $porcentajeCompletado = $totalInsignias > 0 ? ($insigniasObtenidas / $totalInsignias) * 100 : 0;

        $estadisticas = [
            'total_insignias_disponibles' => $totalInsignias,
            'insignias_obtenidas' => $insigniasObtenidas,
            'porcentaje_completado' => round($porcentajeCompletado, 2),
            'insignias_pendientes' => $totalInsignias - $insigniasObtenidas
        ];

        return response()->json([
            'success' => true,
            'message' => 'Estadísticas de insignias obtenidas correctamente',
            'data' => $estadisticas
        ]);
    }
}