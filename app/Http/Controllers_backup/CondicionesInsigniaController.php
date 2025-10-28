<?php

namespace App\Http\Controllers;

use App\Models\CondicionesInsignia;
use App\Models\Insignia;
use Illuminate\Http\Request;

class CondicionesInsigniaController extends Controller
{
    /**
     * Obtener todas las condiciones de insignias
     */
    public function index()
    {
        $condiciones = CondicionesInsignia::with('insignia')->get();

        return response()->json([
            'success' => true,
            'message' => 'Condiciones de insignias obtenidas correctamente',
            'data' => $condiciones
        ]);
    }

    /**
     * Obtener condiciones de una insignia específica
     */
    public function getByInsignia($insignia_id)
    {
        $insignia = Insignia::find($insignia_id);
        if (!$insignia) {
            return response()->json([
                'success' => false,
                'message' => 'Insignia no encontrada'
            ], 404);
        }

        $condiciones = CondicionesInsignia::where('insignia_id', $insignia_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Condiciones de la insignia obtenidas correctamente',
            'data' => $condiciones
        ]);
    }

    /**
     * Crear una nueva condición de insignia
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'insignia_id' => 'required|integer|exists:insignias,id',
            'tipo_condicion' => 'required|string|in:tests_completados,puntuacion_minima,juegos_completados,lecturas_completadas,racha_dias,nivel_alcanzado,tiempo_total',
            'valor_requerido' => 'required|integer|min:1',
            'descripcion' => 'string|max:255'
        ]);

        $condicion = new CondicionesInsignia();
        $condicion->insignia_id = $request->insignia_id;
        $condicion->tipo_condicion = $request->tipo_condicion;
        $condicion->valor_requerido = $request->valor_requerido;
        $condicion->descripcion = $request->descripcion ?? '';
        $condicion->activo = true;
        $condicion->save();

        return response()->json([
            'success' => true,
            'message' => 'Condición de insignia creada correctamente',
            'data' => $condicion
        ], 201);
    }

    /**
     * Actualizar una condición de insignia
     */
    public function update(Request $request, $id)
    {
        $condicion = CondicionesInsignia::find($id);
        if (!$condicion) {
            return response()->json([
                'success' => false,
                'message' => 'Condición no encontrada'
            ], 404);
        }

        $this->validate($request, [
            'tipo_condicion' => 'string|in:tests_completados,puntuacion_minima,juegos_completados,lecturas_completadas,racha_dias,nivel_alcanzado,tiempo_total',
            'valor_requerido' => 'integer|min:1',
            'descripcion' => 'string|max:255',
            'activo' => 'boolean'
        ]);

        if ($request->has('tipo_condicion')) $condicion->tipo_condicion = $request->tipo_condicion;
        if ($request->has('valor_requerido')) $condicion->valor_requerido = $request->valor_requerido;
        if ($request->has('descripcion')) $condicion->descripcion = $request->descripcion;
        if ($request->has('activo')) $condicion->activo = $request->activo;
        
        $condicion->save();

        return response()->json([
            'success' => true,
            'message' => 'Condición actualizada correctamente',
            'data' => $condicion
        ]);
    }

    /**
     * Eliminar una condición de insignia
     */
    public function destroy($id)
    {
        $condicion = CondicionesInsignia::find($id);
        if (!$condicion) {
            return response()->json([
                'success' => false,
                'message' => 'Condición no encontrada'
            ], 404);
        }

        $condicion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Condición eliminada correctamente'
        ]);
    }

    /**
     * Obtener tipos de condiciones disponibles
     */
    public function tiposCondiciones()
    {
        return response()->json([
            'success' => true,
            'message' => 'Tipos de condiciones disponibles',
            'data' => CondicionesInsignia::TIPOS_CONDICION
        ]);
    }

    /**
     * Crear condiciones predeterminadas para una insignia
     */
    public function crearCondicionesPredeterminadas(Request $request)
    {
        $this->validate($request, [
            'insignia_id' => 'required|integer|exists:insignias,id',
            'tipo_insignia' => 'required|string|in:principiante,intermedio,avanzado,maestro'
        ]);

        $insignia_id = $request->insignia_id;
        $condicionesPredeterminadas = [];

        switch ($request->tipo_insignia) {
            case 'principiante':
                $condicionesPredeterminadas = [
                    ['tipo_condicion' => 'tests_completados', 'valor_requerido' => 3, 'descripcion' => 'Completar 3 tests'],
                    ['tipo_condicion' => 'puntuacion_minima', 'valor_requerido' => 70, 'descripcion' => 'Obtener al menos 70% en un test']
                ];
                break;
                
            case 'intermedio':
                $condicionesPredeterminadas = [
                    ['tipo_condicion' => 'tests_completados', 'valor_requerido' => 10, 'descripcion' => 'Completar 10 tests'],
                    ['tipo_condicion' => 'puntuacion_minima', 'valor_requerido' => 80, 'descripcion' => 'Obtener al menos 80% en un test'],
                    ['tipo_condicion' => 'juegos_completados', 'valor_requerido' => 5, 'descripcion' => 'Completar 5 juegos']
                ];
                break;
                
            case 'avanzado':
                $condicionesPredeterminadas = [
                    ['tipo_condicion' => 'tests_completados', 'valor_requerido' => 25, 'descripcion' => 'Completar 25 tests'],
                    ['tipo_condicion' => 'puntuacion_minima', 'valor_requerido' => 90, 'descripcion' => 'Obtener al menos 90% en un test'],
                    ['tipo_condicion' => 'nivel_alcanzado', 'valor_requerido' => 5, 'descripcion' => 'Alcanzar nivel 5']
                ];
                break;
                
            case 'maestro':
                $condicionesPredeterminadas = [
                    ['tipo_condicion' => 'tests_completados', 'valor_requerido' => 50, 'descripcion' => 'Completar 50 tests'],
                    ['tipo_condicion' => 'puntuacion_minima', 'valor_requerido' => 95, 'descripcion' => 'Obtener al menos 95% en un test'],
                    ['tipo_condicion' => 'nivel_alcanzado', 'valor_requerido' => 10, 'descripcion' => 'Alcanzar nivel máximo'],
                    ['tipo_condicion' => 'juegos_completados', 'valor_requerido' => 20, 'descripcion' => 'Completar 20 juegos']
                ];
                break;
        }

        $condicionesCreadas = [];
        foreach ($condicionesPredeterminadas as $condicionData) {
            $condicion = new CondicionesInsignia();
            $condicion->insignia_id = $insignia_id;
            $condicion->tipo_condicion = $condicionData['tipo_condicion'];
            $condicion->valor_requerido = $condicionData['valor_requerido'];
            $condicion->descripcion = $condicionData['descripcion'];
            $condicion->activo = true;
            $condicion->save();
            
            $condicionesCreadas[] = $condicion;
        }

        return response()->json([
            'success' => true,
            'message' => 'Condiciones predeterminadas creadas correctamente',
            'data' => $condicionesCreadas
        ], 201);
    }
}



