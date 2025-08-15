<?php

namespace App\Http\Controllers;

use App\Models\PruebasLectura;
use Illuminate\Http\Request;

class PruebasLecturaController extends Controller
{
    /**
     * Obtener todas las pruebas de lectura
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Pruebas de lectura obtenidas correctamente',
            'data' => PruebasLectura::all()
        ]);
    }

    /**
     * Obtener una prueba de lectura específica
     */
    public function show($id)
    {
        $prueba = PruebasLectura::find($id);
        if (!$prueba) {
            return response()->json([
                'success' => false,
                'message' => 'Prueba de lectura no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Prueba de lectura obtenida correctamente',
            'data' => $prueba
        ]);
    }

    /**
     * Crear una nueva prueba de lectura
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:1000',
            'nivel' => 'required|integer|min:1|max:10',
            'es_diagnostica' => 'boolean',
        ]);

        $prueba = new PruebasLectura();
        $prueba->titulo = $request->titulo;
        $prueba->descripcion = $request->descripcion;
        $prueba->nivel = $request->nivel;
        $prueba->es_diagnostica = $request->es_diagnostica ?? false;
        $prueba->fecha_creacion = now();
        $prueba->save();

        return response()->json([
            'success' => true,
            'message' => 'Prueba de lectura creada correctamente',
            'data' => $prueba
        ], 201);
    }

    /**
     * Actualizar una prueba de lectura existente
     */
    public function update(Request $request, $id)
    {
        $prueba = PruebasLectura::find($id);
        if (!$prueba) {
            return response()->json([
                'success' => false,
                'message' => 'Prueba de lectura no encontrada'
            ], 404);
        }

        $this->validate($request, [
            'titulo' => 'string|max:255',
            'descripcion' => 'string|max:1000',
            'nivel' => 'integer|min:1|max:10',
            'es_diagnostica' => 'boolean',
        ]);

        if ($request->has('titulo')) $prueba->titulo = $request->titulo;
        if ($request->has('descripcion')) $prueba->descripcion = $request->descripcion;
        if ($request->has('nivel')) $prueba->nivel = $request->nivel;
        if ($request->has('es_diagnostica')) $prueba->es_diagnostica = $request->es_diagnostica;
        
        $prueba->save();

        return response()->json([
            'success' => true,
            'message' => 'Prueba de lectura actualizada correctamente',
            'data' => $prueba
        ]);
    }

    /**
     * Eliminar una prueba de lectura
     */
    public function destroy($id)
    {
        $prueba = PruebasLectura::find($id);
        if (!$prueba) {
            return response()->json([
                'success' => false,
                'message' => 'Prueba de lectura no encontrada'
            ], 404);
        }

        $prueba->delete();

        return response()->json([
            'success' => true,
            'message' => 'Prueba de lectura eliminada correctamente'
        ]);
    }

    /**
     * Obtener pruebas de lectura por nivel
     */
    public function getByNivel($nivel)
    {
        $pruebas = PruebasLectura::where('nivel', $nivel)->get();

        return response()->json([
            'success' => true,
            'message' => 'Pruebas de lectura por nivel obtenidas correctamente',
            'data' => $pruebas
        ]);
    }

    /**
     * Obtener pruebas diagnósticas
     */
    public function getDiagnosticas()
    {
        $pruebas = PruebasLectura::where('es_diagnostica', true)->get();

        return response()->json([
            'success' => true,
            'message' => 'Pruebas diagnósticas obtenidas correctamente',
            'data' => $pruebas
        ]);
    }
}