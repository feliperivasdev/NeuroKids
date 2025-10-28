<?php

namespace App\Http\Controllers;

use App\Models\PreguntasLectura;
use Illuminate\Http\Request;

class PreguntasLecturaController extends Controller
{
    public function index()
    {
        return response()->json(PreguntasLectura::all());
    }

    public function show($id)
    {
        $pregunta = PreguntasLectura::find($id);
        if (!$pregunta) {
            return response()->json(['success' => false, 'message' => 'Pregunta no encontrada'], 404);
        }
        return response()->json(['success' => true, 'message' => 'Pregunta obtenida correctamente', 'data' => $pregunta]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'prueba_id' => 'required|integer',
            'texto_pregunta' => 'required|string|max:255',
            'respuesta_correcta' => 'required|string|max:255',
            'orden' => 'required|integer',
        ]);

        $pregunta = new PreguntasLectura();
        $pregunta->prueba_id = $request->prueba_id;
        $pregunta->texto_pregunta = $request->texto_pregunta;
        $pregunta->respuesta_correcta = $request->respuesta_correcta;
        $pregunta->orden = $request->orden;
        $pregunta->save();

        return response()->json([
            'success' => true,
            'message' => 'Pregunta creada correctamente',
            'data' => $pregunta
        ], 201);
    }
}
