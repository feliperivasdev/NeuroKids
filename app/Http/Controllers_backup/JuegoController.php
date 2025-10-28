<?php

namespace App\Http\Controllers;

use App\Models\Juego;
use Illuminate\Http\Request;

class JuegoController extends Controller
{
    public function index()
    {
        return response()->json(Juego::all());
    }

    public function show($id)
    {
        $juego = Juego::find($id);
        if(!$juego){
            return response()->json(['success' => false, 'message' => 'Juego no encontrado'], 404);
        }
        return response()->json(['success' => true, 'message' => 'Juego obtenido correctamente', 'data' => $juego]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'nivel_dificultad' => 'required|integer',
            'url_juego' => 'required|string|max:255',
        ]);

        $juego = new Juego();
        $juego->titulo = $request->titulo;
        $juego->descripcion = $request->descripcion;
        $juego->nivel_dificultad = $request->nivel_dificultad;
        $juego->url_juego = $request->url_juego;
        $juego->save();

        return response()->json([
            'success' => true,
            'message' => 'Juego creado correctamente',
            'data' => $juego
        ], 201);
    }
}