<?php

namespace App\Http\Controllers;

use App\Models\UsuariosJuego;
use Illuminate\Http\Request;

class UsuariosJuegoController extends Controller
{
    public function index()
    {
        return response()->json(UsuariosJuego::all());
    }

    public function show($id)
    {
        return response()->json(UsuariosJuego::findOrFail($id));
    }

    public function getByUser($usuario_id)
    {
        return response()->json(UsuariosJuego::where('usuario_id', $usuario_id)->get());
    }

    public function store(Request $request)
    {
        $usuarioJuego = UsuariosJuego::create($request->all());
        return response()->json($usuarioJuego, 201);
    }

    public function update(Request $request, $id)
    {
        $usuarioJuego = UsuariosJuego::findOrFail($id);
        $usuarioJuego->update($request->all());
        return response()->json($usuarioJuego);
    }

    public function destroy($id)
    {
        UsuariosJuego::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function updateProgress(Request $request, $id)
    {
        $usuarioJuego = UsuariosJuego::findOrFail($id);
        $usuarioJuego->update([
            'completado' => $request->completado,
            'puntaje' => $request->puntaje,
            'intentos' => $request->intentos,
            'tiempo_total_segundos' => $request->tiempo_total_segundos,
            'fecha_ultimo_intento' => now()
        ]);
        return response()->json($usuarioJuego);
    }
}