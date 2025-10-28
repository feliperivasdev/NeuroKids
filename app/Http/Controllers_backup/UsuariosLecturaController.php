<?php

namespace App\Http\Controllers;

use App\Models\UsuariosLectura;
use Illuminate\Http\Request;

class UsuariosLecturaController extends Controller
{
    public function index()
    {
        return response()->json(UsuariosLectura::all());
    }

    public function show($id)
    {
        return response()->json(UsuariosLectura::findOrFail($id));
    }

    public function getByUser($usuario_id)
    {
        return response()->json(UsuariosLectura::where('usuario_id', $usuario_id)->get());
    }

    public function store(Request $request)
    {
        $usuarioLectura = UsuariosLectura::create($request->all());
        return response()->json($usuarioLectura, 201);
    }

    public function update(Request $request, $id)
    {
        $usuarioLectura = UsuariosLectura::findOrFail($id);
        $usuarioLectura->update($request->all());
        return response()->json($usuarioLectura);
    }

    public function destroy($id)
    {
        UsuariosLectura::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function updateProgress(Request $request, $id)
    {
        $usuarioLectura = UsuariosLectura::findOrFail($id);
        $usuarioLectura->update([
            'completado' => $request->completado,
            'puntuacion' => $request->puntuacion,
            'tiempo_lectura_segundos' => $request->tiempo_lectura_segundos,
            'intentos' => $request->intentos,
            'fecha_completada' => now()
        ]);
        return response()->json($usuarioLectura);
    }
}