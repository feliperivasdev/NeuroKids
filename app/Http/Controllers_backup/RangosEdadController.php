<?php

namespace App\Http\Controllers;

use App\Models\RangosEdad;
use Illuminate\Http\Request;

class RangosEdadController extends Controller
{
    public function index()
    {
        return response()->json(RangosEdad::all());
    }

    public function show($id)
    {
        return response()->json(RangosEdad::findOrFail($id));
    }

    public function store(Request $request)
    {
        $rangoEdad = RangosEdad::create($request->all());
        return response()->json($rangoEdad, 201);
    }

    public function update(Request $request, $id)
    {
        $rangoEdad = RangosEdad::findOrFail($id);
        $rangoEdad->update($request->all());
        return response()->json($rangoEdad);
    }

    public function destroy($id)
    {
        RangosEdad::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}