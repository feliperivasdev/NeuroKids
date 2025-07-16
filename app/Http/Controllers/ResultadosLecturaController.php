<?php

namespace App\Http\Controllers;

use App\Models\ResultadosLectura;
use Illuminate\Http\Request;

class ResultadosLecturaController extends Controller
{
    /**
     * Obtener todos los resultados de lectura
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Resultados de lectura obtenidos correctamente',
            'data' => [
                'resultados' => ResultadosLectura::all()
            ]
        ]);
    }

    /**
     * Obtener un resultado específico por ID
     */
    public function show($id)
    {
        $resultado = ResultadosLectura::find($id);

        if(!$resultado) {
            return response()->json([
                'success' => false, 
                'message' => 'Resultado de lectura no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Resultado de lectura obtenido correctamente',
            'data' => [
                'resultado' => $resultado
            ]
        ], 200);
    }

    /**
     * Crear un nuevo resultado de lectura
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'usuario_id' => 'required|integer|exists:usuarios,id',
            'prueba_id' => 'required|integer|exists:pruebas_lectura,id',
            'puntaje' => 'required|integer|min:0|max:100',
            'nivel_al_realizar' => 'required|integer|min:1',
            'intento' => 'required|integer|min:1',
            'fecha' => 'required|date'
        ]);

        $resultado = ResultadosLectura::create([
            'usuario_id' => $request->usuario_id,
            'prueba_id' => $request->prueba_id,
            'puntaje' => $request->puntaje,
            'nivel_al_realizar' => $request->nivel_al_realizar,
            'intento' => $request->intento,
            'fecha' => $request->fecha
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resultado de lectura creado correctamente',
            'data' => [
                'resultado' => $resultado
            ]
        ], 201);
    }

    /**
     * Actualizar un resultado de lectura existente
     */
    public function update(Request $request, $id)
    {
        $resultado = ResultadosLectura::find($id);

        if(!$resultado) {
            return response()->json([
                'success' => false,
                'message' => 'Resultado de lectura no encontrado'
            ], 404);
        }

        $this->validate($request, [
            'usuario_id' => 'sometimes|integer|exists:usuarios,id',
            'prueba_id' => 'sometimes|integer|exists:pruebas_lectura,id',
            'puntaje' => 'sometimes|integer|min:0|max:100',
            'nivel_al_realizar' => 'sometimes|integer|min:1',
            'intento' => 'sometimes|integer|min:1',
            'fecha' => 'sometimes|date'
        ]);

        $resultado->fill($request->only([
            'usuario_id', 'prueba_id', 'puntaje', 
            'nivel_al_realizar', 'intento', 'fecha'
        ]));
        $resultado->save();

        return response()->json([
            'success' => true,
            'message' => 'Resultado de lectura actualizado correctamente',
            'data' => [
                'resultado' => $resultado
            ]
        ], 200);
    }

    /**
     * Eliminar un resultado de lectura
     */
    public function destroy($id)
    {
        $resultado = ResultadosLectura::find($id);

        if(!$resultado) {
            return response()->json([
                'success' => false,
                'message' => 'Resultado de lectura no encontrado'
            ], 404);
        }

        $resultado->delete();

        return response()->json([
            'success' => true,
            'message' => 'Resultado de lectura eliminado correctamente'
        ], 200);
    }

    /**
     * Obtener todos los resultados de un usuario específico
     */
    public function getByUsuario($usuario_id)
    {
        $resultados = ResultadosLectura::where('usuario_id', $usuario_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Resultados del usuario obtenidos correctamente',
            'data' => [
                'resultados' => $resultados,
                'total' => $resultados->count()
            ]
        ], 200);
    }

    /**
     * Obtener todos los resultados de una prueba específica
     */
    public function getByPrueba($prueba_id)
    {
        $resultados = ResultadosLectura::where('prueba_id', $prueba_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Resultados de la prueba obtenidos correctamente',
            'data' => [
                'resultados' => $resultados,
                'total' => $resultados->count()
            ]
        ], 200);
    }

    /**
     * Obtener el mejor resultado de un usuario en una prueba específica
     */
    public function getMejorResultado($usuario_id, $prueba_id)
    {
        $mejorResultado = ResultadosLectura::where('usuario_id', $usuario_id)
            ->where('prueba_id', $prueba_id)
            ->orderBy('puntaje', 'desc')
            ->first();

        if(!$mejorResultado) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron resultados para este usuario y prueba'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mejor resultado obtenido correctamente',
            'data' => [
                'resultado' => $mejorResultado
            ]
        ], 200);
    }

    /**
     * Obtener estadísticas de resultados por usuario
     */
    public function getEstadisticasUsuario($usuario_id)
    {
        $resultados = ResultadosLectura::where('usuario_id', $usuario_id);
        
        $estadisticas = [
            'total_pruebas_realizadas' => $resultados->count(),
            'puntaje_promedio' => $resultados->avg('puntaje'),
            'mejor_puntaje' => $resultados->max('puntaje'),
            'peor_puntaje' => $resultados->min('puntaje'),
            'ultimo_resultado' => $resultados->orderBy('fecha', 'desc')->first()
        ];

        return response()->json([
            'success' => true,
            'message' => 'Estadísticas del usuario obtenidas correctamente',
            'data' => [
                'estadisticas' => $estadisticas
            ]
        ], 200);
    }
} 