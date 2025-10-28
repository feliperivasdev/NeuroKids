<?php

namespace App\Http\Controllers;

use App\Models\PreguntasEvaluacion;
use Illuminate\Http\Request;

class PreguntasEvaluacionController extends Controller
{
    public function index()
    {
        return response()->json(PreguntasEvaluacion::all());
    }

    public function show($id)
    {
        return response()->json(PreguntasEvaluacion::findOrFail($id));
    }

    public function getByEvaluacion($evaluacion_id)
    {
        return response()->json(PreguntasEvaluacion::where('evaluacion_id', $evaluacion_id)->get());
    }

    public function store(Request $request)
    {
        $pregunta = PreguntasEvaluacion::create($request->all());
        return response()->json($pregunta, 201);
    }

    public function update(Request $request, $id)
    {
        $pregunta = PreguntasEvaluacion::findOrFail($id);
        $pregunta->update($request->all());
        return response()->json($pregunta);
    }

    public function destroy($id)
    {
        PreguntasEvaluacion::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}