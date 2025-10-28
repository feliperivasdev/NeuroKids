<?php

namespace App\Http\Controllers;

use App\Models\RespuestasEvaluacion;
use Illuminate\Http\Request;

class RespuestasEvaluacionController extends Controller
{
    public function index()
    {
        return response()->json(RespuestasEvaluacion::all());
    }

    public function show($id)
    {
        return response()->json(RespuestasEvaluacion::findOrFail($id));
    }

    public function getByPregunta($pregunta_id)
    {
        return response()->json(RespuestasEvaluacion::where('pregunta_id', $pregunta_id)->get());
    }

    public function store(Request $request)
    {
        $respuesta = RespuestasEvaluacion::create($request->all());
        return response()->json($respuesta, 201);
    }

    public function update(Request $request, $id)
    {
        $respuesta = RespuestasEvaluacion::findOrFail($id);
        $respuesta->update($request->all());
        return response()->json($respuesta);
    }

    public function destroy($id)
    {
        RespuestasEvaluacion::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function verificarRespuesta($id, Request $request)
    {
        $respuesta = RespuestasEvaluacion::findOrFail($id);
        $esCorrecta = $respuesta->es_correcta;
        return response()->json(['es_correcta' => $esCorrecta]);
    }
}