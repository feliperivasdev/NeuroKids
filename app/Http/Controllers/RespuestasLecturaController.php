<?php

namespace App\Http\Controllers;

use App\Models\RespuestasLectura;
use Illuminate\Http\Request;

class RespuestasLecturaController extends Controller
{
    public function index()
    {
        return response()->json(RespuestasLectura::all());
    }

    public function show($id)
    {
        return response()->json(RespuestasLectura::findOrFail($id));
    }

    public function getByPregunta($pregunta_id)
    {
        return response()->json(RespuestasLectura::where('pregunta_id', $pregunta_id)->get());
    }

    public function store(Request $request)
    {
        $respuesta = RespuestasLectura::create($request->all());
        return response()->json($respuesta, 201);
    }

    public function update(Request $request, $id)
    {
        $respuesta = RespuestasLectura::findOrFail($id);
        $respuesta->update($request->all());
        return response()->json($respuesta);
    }

    public function destroy($id)
    {
        RespuestasLectura::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function verificarRespuesta($id, Request $request)
    {
        $respuesta = RespuestasLectura::findOrFail($id);
        $esCorrecta = $respuesta->es_correcta;
        return response()->json(['es_correcta' => $esCorrecta]);
    }
}