<?php

namespace App\Http\Controllers;

use App\Models\EvaluacionesUsuario;
use Illuminate\Http\Request;

class EvaluacionesUsuarioController extends Controller
{
    public function index()
    {
        return response()->json(EvaluacionesUsuario::all());
    }

    public function getByUser($usuario_id)
    {
        return response()->json(EvaluacionesUsuario::where('usuario_id', $usuario_id)->get());
    }

    public function store(Request $request)
    {
        $evaluacionUsuario = EvaluacionesUsuario::create($request->all());
        return response()->json($evaluacionUsuario, 201);
    }

    public function update(Request $request, $id)
    {
        $evaluacionUsuario = EvaluacionesUsuario::findOrFail($id);
        $evaluacionUsuario->update($request->all());
        return response()->json($evaluacionUsuario);
    }

    public function destroy($id)
    {
        EvaluacionesUsuario::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}