<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use Illuminate\Http\Request;

class LecturaController extends Controller
{
    public function index()
    {
        return response()->json(Lectura::all());
    }

    public function show($id)
    {
        return response()->json(Lectura::findOrFail($id));
    }

    public function store(Request $request)
    {
        $lectura = Lectura::create($request->all());
        return response()->json($lectura, 201);
    }

    public function update(Request $request, $id)
    {
        $lectura = Lectura::findOrFail($id);
        $lectura->update($request->all());
        return response()->json($lectura);
    }

    public function destroy($id)
    {
        Lectura::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function getByNivel($nivel)
    {
        return response()->json(Lectura::where('nivel', $nivel)->get());
    }
}