<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\ResultadosTest;
use App\Models\PruebasLectura;
use App\Services\ProgresionAutomaticaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgresionController extends Controller
{
    protected $progresionService;

    public function __construct(ProgresionAutomaticaService $progresionService)
    {
        $this->progresionService = $progresionService;
    }

    /**
     * Completar un test y procesar progresión automática
     */
    public function completarTest(Request $request)
    {
        $this->validate($request, [
            'test_id' => 'required|integer|exists:pruebas_lectura,id',
            'puntuacion' => 'required|numeric|min:0',
            'puntuacion_maxima' => 'required|numeric|min:1',
            'tiempo_segundos' => 'integer|min:0'
        ]);

        $usuario_id = Auth::id();
        
        // Verificar que el usuario puede acceder a este test
        if (!$this->progresionService->puedeAccederATest($usuario_id, $request->test_id)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a este test'
            ], 403);
        }

        // Calcular porcentaje
        $porcentaje = ($request->puntuacion / $request->puntuacion_maxima) * 100;

        // Verificar si ya existe un resultado para este test
        $resultadoExistente = ResultadosTest::where('usuario_id', $usuario_id)
            ->where('test_id', $request->test_id)
            ->first();

        if ($resultadoExistente) {
            // Actualizar si es mejor puntuación
            if ($porcentaje > $resultadoExistente->porcentaje) {
                $resultadoExistente->puntuacion = $request->puntuacion;
                $resultadoExistente->puntuacion_maxima = $request->puntuacion_maxima;
                $resultadoExistente->porcentaje = $porcentaje;
                $resultadoExistente->fecha_completado = now();
                $resultadoExistente->tiempo_total_segundos = $request->tiempo_segundos ?? 0;
                $resultadoExistente->intentos += 1;
                $resultadoExistente->save();
                $resultado = $resultadoExistente;
            } else {
                // Solo actualizar intentos
                $resultadoExistente->intentos += 1;
                $resultadoExistente->save();
                $resultado = $resultadoExistente;
            }
        } else {
            // Crear nuevo resultado
            $resultado = new ResultadosTest();
            $resultado->usuario_id = $usuario_id;
            $resultado->test_id = $request->test_id;
            $resultado->puntuacion = $request->puntuacion;
            $resultado->puntuacion_maxima = $request->puntuacion_maxima;
            $resultado->porcentaje = $porcentaje;
            $resultado->completado = true;
            $resultado->fecha_inicio = now();
            $resultado->fecha_completado = now();
            $resultado->tiempo_total_segundos = $request->tiempo_segundos ?? 0;
            $resultado->intentos = 1;
            $resultado->save();
        }

        // Procesar progresión automática
        $progresion = $this->progresionService->procesarCompletacionTest($usuario_id, $request->test_id, $resultado);

        return response()->json([
            'success' => true,
            'message' => 'Test completado correctamente',
            'data' => [
                'resultado' => $resultado,
                'progresion' => $progresion
            ]
        ]);
    }

    /**
     * Obtener tests disponibles para el usuario autenticado
     */
    public function testsDisponibles()
    {
        $usuario_id = Auth::id();
        $tests = $this->progresionService->obtenerTestsDisponibles($usuario_id);

        // Agregar información de progreso para cada test
        $testsConProgreso = $tests->map(function ($test) use ($usuario_id) {
            $resultado = ResultadosTest::where('usuario_id', $usuario_id)
                ->where('test_id', $test->id)
                ->first();

            $test->progreso = $resultado ? [
                'completado' => $resultado->completado,
                'mejor_puntuacion' => $resultado->porcentaje,
                'intentos' => $resultado->intentos,
                'fecha_ultimo_intento' => $resultado->fecha_completado
            ] : null;

            return $test;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tests disponibles obtenidos correctamente',
            'data' => $testsConProgreso
        ]);
    }

    /**
     * Obtener progreso general del usuario
     */
    public function progresoGeneral()
    {
        $usuario_id = Auth::id();
        $usuario = Usuario::find($usuario_id);

        // Estadísticas de tests
        $totalTestsCompletados = ResultadosTest::where('usuario_id', $usuario_id)
            ->where('completado', true)
            ->count();

        $testsAprobados = ResultadosTest::where('usuario_id', $usuario_id)
            ->where('completado', true)
            ->where('porcentaje', '>=', 70)
            ->count();

        $promedioGeneral = ResultadosTest::where('usuario_id', $usuario_id)
            ->where('completado', true)
            ->avg('porcentaje');

        // Estadísticas de insignias
        $totalInsignias = $usuario->insignias()->count();
        
        // Estadísticas de juegos
        $juegosPendientes = $usuario->asignacionesJuegos()->where('completado', false)->count();
        $juegosCompletados = $usuario->asignacionesJuegos()->where('completado', true)->count();

        return response()->json([
            'success' => true,
            'message' => 'Progreso general obtenido correctamente',
            'data' => [
                'usuario' => [
                    'nombre' => $usuario->nombre . ' ' . $usuario->apellido,
                    'nivel_actual' => $usuario->nivel_actual ?? 1
                ],
                'tests' => [
                    'completados' => $totalTestsCompletados,
                    'aprobados' => $testsAprobados,
                    'promedio_general' => round($promedioGeneral ?? 0, 2)
                ],
                'insignias' => [
                    'obtenidas' => $totalInsignias
                ],
                'juegos' => [
                    'completados' => $juegosCompletados,
                    'pendientes' => $juegosPendientes
                ]
            ]
        ]);
    }

    /**
     * Obtener juegos disponibles (todos pueden elegir)
     */
    public function juegosDisponibles()
    {
        $juegos = DB::table('juegos')
            ->select('id', 'titulo', 'descripcion', 'url_juego', 'categoria', 'nivel_requerido')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Juegos disponibles obtenidos correctamente',
            'data' => $juegos
        ]);
    }

    /**
     * Obtener lecturas disponibles (todos pueden elegir)
     */
    public function lecturasDisponibles()
    {
        $lecturas = DB::table('pruebas_lectura')
            ->where('es_diagnostica', false)
            ->select('id', 'titulo', 'descripcion', 'nivel')
            ->orderBy('nivel')
            ->orderBy('titulo')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lecturas disponibles obtenidas correctamente',
            'data' => $lecturas
        ]);
    }

    /**
     * Auto-asignar un juego (el usuario lo elige)
     */
    public function autoAsignarJuego(Request $request)
    {
        $this->validate($request, [
            'juego_id' => 'required|integer|exists:juegos,id'
        ]);

        $usuario_id = Auth::id();

        // Verificar si ya lo tiene asignado
        $yaAsignado = DB::table('asignaciones_juegos')
            ->where('usuario_id', $usuario_id)
            ->where('juego_id', $request->juego_id)
            ->exists();

        if ($yaAsignado) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes este juego en tu lista'
            ], 400);
        }

        // Auto-asignar el juego
        DB::table('asignaciones_juegos')->insert([
            'usuario_id' => $usuario_id,
            'juego_id' => $request->juego_id,
            'nivel_asignado' => 1,
            'completado' => false,
            'fecha_asignacion' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Juego agregado a tu lista correctamente'
        ]);
    }
}



