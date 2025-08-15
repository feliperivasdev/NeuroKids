<?php

namespace App\Http\Controllers;

use App\Models\Insignia;
use Illuminate\Http\Request;

class InsigniaController extends Controller
{
    /**
     * Obtener todas las insignias
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Insignias obtenidas correctamente',
            'data' => Insignia::all()
        ]);
    }

    /**
     * Obtener una insignia específica
     */
    public function show($id)
    {
        $insignia = Insignia::find($id);
        if (!$insignia) {
            return response()->json([
                'success' => false,
                'message' => 'Insignia no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Insignia obtenida correctamente',
            'data' => $insignia
        ]);
    }

    /**
     * Crear una nueva insignia
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
            'url_icono' => 'required|string|max:255',
            'categoria' => 'required|string|max:100',
            'nivel_requerido' => 'required|integer|min:1',
        ]);

        $insignia = new Insignia();
        $insignia->nombre = $request->nombre;
        $insignia->descripcion = $request->descripcion;
        $insignia->url_icono = $request->url_icono;
        $insignia->categoria = $request->categoria;
        $insignia->nivel_requerido = $request->nivel_requerido;
        $insignia->save();

        return response()->json([
            'success' => true,
            'message' => 'Insignia creada correctamente',
            'data' => $insignia
        ], 201);
    }

    /**
     * Actualizar una insignia existente
     */
    public function update(Request $request, $id)
    {
        $insignia = Insignia::find($id);
        if (!$insignia) {
            return response()->json([
                'success' => false,
                'message' => 'Insignia no encontrada'
            ], 404);
        }

        $this->validate($request, [
            'nombre' => 'string|max:255',
            'descripcion' => 'string|max:500',
            'url_icono' => 'string|max:255',
            'categoria' => 'string|max:100',
            'nivel_requerido' => 'integer|min:1',
        ]);

        if ($request->has('nombre')) $insignia->nombre = $request->nombre;
        if ($request->has('descripcion')) $insignia->descripcion = $request->descripcion;
        if ($request->has('url_icono')) $insignia->url_icono = $request->url_icono;
        if ($request->has('categoria')) $insignia->categoria = $request->categoria;
        if ($request->has('nivel_requerido')) $insignia->nivel_requerido = $request->nivel_requerido;
        
        $insignia->save();

        return response()->json([
            'success' => true,
            'message' => 'Insignia actualizada correctamente',
            'data' => $insignia
        ]);
    }

    /**
     * Eliminar una insignia
     */
    public function destroy($id)
    {
        $insignia = Insignia::find($id);
        if (!$insignia) {
            return response()->json([
                'success' => false,
                'message' => 'Insignia no encontrada'
            ], 404);
        }

        $insignia->delete();

        return response()->json([
            'success' => true,
            'message' => 'Insignia eliminada correctamente'
        ]);
    }
}