<?php

namespace App\Http\Controllers;

use App\Models\Evaluacione;
use Illuminate\Http\Request;

class EvaluacionController extends Controller
{
    public function index()
    {
        return response()->json(Evaluacione::all());
    }

    public function show($id)
    {
        return response()->json(Evaluacione::findOrFail($id));
    }

    public function store(Request $request)
    {
        $evaluacion = Evaluacione::create($request->all());
        return response()->json($evaluacion, 201);
    }

    public function update(Request $request, $id)
    {
        $evaluacion = Evaluacione::findOrFail($id);
        $evaluacion->update($request->all());
        return response()->json($evaluacion);
    }

    public function destroy($id)
    {
        Evaluacione::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}