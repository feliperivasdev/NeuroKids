<?php

namespace App\Http\Controllers;

use App\Models\ResultadosPregunta;
use Illuminate\Http\Request;

class ResultadosPreguntaController extends Controller
{
    public function index()
    {
        return response()->json(ResultadosPregunta::all());
    }

    public function show($id)
    {
        return response()->json(ResultadosPregunta::findOrFail($id));
    }

    public function getByUsuario($usuario_id)
    {
        return response()->json(ResultadosPregunta::where('usuario_id', $usuario_id)->get());
    }

    public function store(Request $request)
    {
        $resultado = ResultadosPregunta::create($request->all());
        return response()->json($resultado, 201);
    }

    public function update(Request $request, $id)
    {
        $resultado = ResultadosPregunta::findOrFail($id);
        $resultado->update($request->all());
        return response()->json($resultado);
    }

    public function destroy($id)
    {
        ResultadosPregunta::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function getEstadisticasUsuario($usuario_id)
    {
        $resultados = ResultadosPregunta::where('usuario_id', $usuario_id)->get();
        $estadisticas = [
            'total_preguntas' => $resultados->count(),
            'correctas' => $resultados->where('es_correcta', true)->count(),
            'incorrectas' => $resultados->where('es_correcta', false)->count(),
            'porcentaje_acierto' => $resultados->count() > 0 
                ? ($resultados->where('es_correcta', true)->count() / $resultados->count()) * 100 
                : 0
        ];
        return response()->json($estadisticas);
    }
}