<?php

namespace App\Http\Controllers;

use App\Models\NivelesDificultad;
use Illuminate\Http\Request;

class NivelesDificultadController extends Controller
{
    public function index()
    {
        return response()->json(NivelesDificultad::all());
    }

    public function show($id)
    {
        return response()->json(NivelesDificultad::findOrFail($id));
    }

    public function store(Request $request)
    {
        $nivel = NivelesDificultad::create($request->all());
        return response()->json($nivel, 201);
    }

    public function update(Request $request, $id)
    {
        $nivel = NivelesDificultad::findOrFail($id);
        $nivel->update($request->all());
        return response()->json($nivel);
    }

    public function destroy($id)
    {
        NivelesDificultad::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}