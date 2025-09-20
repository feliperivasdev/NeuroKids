<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\ResultadosTest;
use App\Models\TestProgresion;
use App\Models\PruebasLectura;
use App\Models\CondicionesInsignia;
use App\Models\Insignia;
use App\Models\UsuariosInsignia;
use App\Models\AsignacionesJuego;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgresionAutomaticaService
{
    /**
     * Procesa la progresión automática cuando un usuario completa un test
     */
    public function procesarCompletacionTest($usuario_id, $test_id, $resultado)
    {
        DB::beginTransaction();
        
        try {
            // 1. Desbloquear nuevos tests
            $this->desbloquearNuevosTests($usuario_id, $test_id, $resultado);
            
            // 2. Verificar y otorgar insignias automáticamente
            $this->verificarYOtorgarInsignias($usuario_id);
            
            // 3. Actualizar nivel del usuario si corresponde
            $this->actualizarNivelUsuario($usuario_id);
            
            DB::commit();
            
            return [
                'success' => true,
                'nuevos_tests_desbloqueados' => $this->obtenerTestsDesbloqueados($usuario_id, $test_id),
                'nuevas_insignias' => $this->obtenerNuevasInsignias($usuario_id),
                'mensaje' => 'Progresión procesada correctamente'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en progresión automática: ' . $e->getMessage());
            
            return [
                'success' => false,
                'mensaje' => 'Error al procesar la progresión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Desbloquea nuevos tests basado en prerequisitos
     */
    protected function desbloquearNuevosTests($usuario_id, $test_completado_id, $resultado)
    {
        // Solo desbloquear si el test fue aprobado (70% o más)
        if (!$resultado->aprobado()) {
            return;
        }

        // Buscar tests que tengan como prerequisito el test recién completado
        $testsADesbloquear = TestProgresion::where('test_prerequisito_id', $test_completado_id)
            ->where('activo', true)
            ->with('test')
            ->get();

        foreach ($testsADesbloquear as $progresion) {
            // Verificar si el usuario cumple el nivel mínimo requerido
            $usuario = Usuario::find($usuario_id);
            if ($usuario->nivel_actual >= $progresion->nivel_minimo_requerido) {
                
                // Verificar si el usuario ya tiene acceso a este test
                $yaDesbloqueado = DB::table('tests_desbloqueados')
                    ->where('usuario_id', $usuario_id)
                    ->where('test_id', $progresion->test_id)
                    ->exists();
                
                if (!$yaDesbloqueado) {
                    // Desbloquear el test
                    DB::table('tests_desbloqueados')->insert([
                        'usuario_id' => $usuario_id,
                        'test_id' => $progresion->test_id,
                        'desbloqueado_por' => $test_completado_id,
                        'fecha_desbloqueo' => now(),
                        'activo' => true
                    ]);
                }
            }
        }
    }

    /**
     * Verifica condiciones y otorga insignias automáticamente
     */
    protected function verificarYOtorgarInsignias($usuario_id)
    {
        // Obtener todas las insignias con condiciones activas
        $insigniasConCondiciones = Insignia::whereHas('condiciones', function($query) {
            $query->where('activo', true);
        })->with('condiciones')->get();

        foreach ($insigniasConCondiciones as $insignia) {
            // Verificar si el usuario ya tiene esta insignia
            $yaObtenida = UsuariosInsignia::where('usuario_id', $usuario_id)
                ->where('insignia_id', $insignia->id)
                ->exists();

            if ($yaObtenida) {
                continue;
            }

            // Verificar si cumple todas las condiciones
            $cumpleCondiciones = true;
            
            foreach ($insignia->condiciones as $condicion) {
                if (!$this->verificarCondicionInsignia($usuario_id, $condicion)) {
                    $cumpleCondiciones = false;
                    break;
                }
            }

            // Si cumple todas las condiciones, otorgar la insignia
            if ($cumpleCondiciones) {
                $usuarioInsignia = new UsuariosInsignia();
                $usuarioInsignia->usuario_id = $usuario_id;
                $usuarioInsignia->insignia_id = $insignia->id;
                $usuarioInsignia->fecha_otorgada = now();
                $usuarioInsignia->save();

                Log::info("Insignia otorgada automáticamente", [
                    'usuario_id' => $usuario_id,
                    'insignia_id' => $insignia->id,
                    'insignia_nombre' => $insignia->nombre
                ]);
            }
        }
    }

    /**
     * Verifica una condición específica para una insignia
     */
    protected function verificarCondicionInsignia($usuario_id, $condicion)
    {
        switch ($condicion->tipo_condicion) {
            case 'tests_completados':
                $count = ResultadosTest::where('usuario_id', $usuario_id)
                    ->where('completado', true)
                    ->where('porcentaje', '>=', 70)
                    ->count();
                return $count >= $condicion->valor_requerido;

            case 'puntuacion_minima':
                $mejorPuntuacion = ResultadosTest::where('usuario_id', $usuario_id)
                    ->where('completado', true)
                    ->max('porcentaje');
                return $mejorPuntuacion >= $condicion->valor_requerido;

            case 'juegos_completados':
                $count = AsignacionesJuego::where('usuario_id', $usuario_id)
                    ->where('completado', true)
                    ->count();
                return $count >= $condicion->valor_requerido;

            case 'nivel_alcanzado':
                $usuario = Usuario::find($usuario_id);
                return $usuario->nivel_actual >= $condicion->valor_requerido;

            // Agregar más tipos de condiciones según sea necesario
            default:
                return false;
        }
    }

    /**
     * Actualiza el nivel del usuario basado en su progreso
     */
    protected function actualizarNivelUsuario($usuario_id)
    {
        $usuario = Usuario::find($usuario_id);
        
        // Calcular nuevo nivel basado en tests completados
        $testsCompletados = ResultadosTest::where('usuario_id', $usuario_id)
            ->where('completado', true)
            ->where('porcentaje', '>=', 70)
            ->count();

        // Lógica de niveles (personalizable)
        $nuevoNivel = min(10, floor($testsCompletados / 3) + 1);
        
        if ($nuevoNivel > $usuario->nivel_actual) {
            $usuario->nivel_actual = $nuevoNivel;
            $usuario->save();
            
            Log::info("Usuario subió de nivel", [
                'usuario_id' => $usuario_id,
                'nivel_anterior' => $usuario->nivel_actual,
                'nuevo_nivel' => $nuevoNivel
            ]);
        }
    }

    /**
     * Obtiene los tests que fueron desbloqueados
     */
    protected function obtenerTestsDesbloqueados($usuario_id, $test_completado_id)
    {
        return DB::table('tests_desbloqueados')
            ->join('pruebas_lectura', 'tests_desbloqueados.test_id', '=', 'pruebas_lectura.id')
            ->where('tests_desbloqueados.usuario_id', $usuario_id)
            ->where('tests_desbloqueados.desbloqueado_por', $test_completado_id)
            ->where('tests_desbloqueados.fecha_desbloqueo', '>=', now()->subMinutes(5))
            ->select('pruebas_lectura.*', 'tests_desbloqueados.fecha_desbloqueo')
            ->get();
    }

    /**
     * Obtiene las insignias que fueron otorgadas recientemente
     */
    protected function obtenerNuevasInsignias($usuario_id)
    {
        return DB::table('usuarios_insignias')
            ->join('insignias', 'usuarios_insignias.insignia_id', '=', 'insignias.id')
            ->where('usuarios_insignias.usuario_id', $usuario_id)
            ->where('usuarios_insignias.fecha_otorgada', '>=', now()->subMinutes(5))
            ->select('insignias.*', 'usuarios_insignias.fecha_otorgada')
            ->get();
    }

    /**
     * Obtiene todos los tests disponibles para un usuario
     */
    public function obtenerTestsDisponibles($usuario_id)
    {
        // Tests desbloqueados específicamente para el usuario
        $testsDesbloqueados = DB::table('tests_desbloqueados')
            ->where('usuario_id', $usuario_id)
            ->where('activo', true)
            ->pluck('test_id')
            ->toArray();

        // Tests básicos (nivel 1 o sin prerequisitos)
        $testsBasicos = PruebasLectura::where('nivel', 1)
            ->orWhereNotIn('id', TestProgresion::pluck('test_id'))
            ->pluck('id')
            ->toArray();

        $todosLosTests = array_unique(array_merge($testsDesbloqueados, $testsBasicos));

        return PruebasLectura::whereIn('id', $todosLosTests)
            ->orderBy('nivel')
            ->orderBy('id')
            ->get();
    }

    /**
     * Verifica si un usuario puede acceder a un test específico
     */
    public function puedeAccederATest($usuario_id, $test_id)
    {
        $testsDisponibles = $this->obtenerTestsDisponibles($usuario_id);
        return $testsDisponibles->contains('id', $test_id);
    }
}


