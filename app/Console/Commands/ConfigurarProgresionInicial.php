<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PruebasLectura;
use App\Models\Insignia;
use App\Models\TestProgresion;
use App\Models\CondicionesInsignia;

class ConfigurarProgresionInicial extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'progresion:configurar-inicial 
                            {--reset : Resetear toda la configuración existente}
                            {--tests : Solo configurar progresión de tests}
                            {--insignias : Solo configurar condiciones de insignias}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura el sistema de progresión automática inicial estilo Duolingo';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('🚀 Configurando sistema de progresión automática...');

        if ($this->option('reset')) {
            $this->resetearConfiguracion();
        }

        if (!$this->option('tests') && !$this->option('insignias')) {
            // Si no se especifica opción, hacer ambas
            $this->configurarProgresionTests();
            $this->configurarCondicionesInsignias();
        } else {
            if ($this->option('tests')) {
                $this->configurarProgresionTests();
            }
            if ($this->option('insignias')) {
                $this->configurarCondicionesInsignias();
            }
        }

        $this->crearInsigniasEjemplo();
        $this->mostrarResumen();

        $this->info('✅ Sistema de progresión configurado correctamente!');
        $this->info('🎯 Los usuarios ahora pueden:');
        $this->info('   • Desbloquear tests automáticamente al completar otros');
        $this->info('   • Ganar insignias automáticamente según su progreso');
        $this->info('   • Elegir libremente juegos y lecturas disponibles');
        $this->info('   • Subir de nivel según su desempeño');
    }

    /**
     * Resetea toda la configuración existente
     */
    private function resetearConfiguracion()
    {
        if (!$this->confirm('⚠️  ¿Estás seguro de que quieres resetear toda la configuración de progresión?')) {
            return;
        }

        $this->info('🔄 Reseteando configuración existente...');
        
        DB::table('condiciones_insignia')->delete();
        DB::table('test_progresion')->delete();
        DB::table('tests_desbloqueados')->delete();
        DB::table('resultados_test')->delete();
        
        $this->info('✅ Configuración reseteada');
    }

    /**
     * Configura la progresión automática de tests
     */
    private function configurarProgresionTests()
    {
        $this->info('📚 Configurando progresión de tests...');

        // Obtener tests ordenados por nivel
        $tests = PruebasLectura::orderBy('nivel')->orderBy('id')->get();

        if ($tests->count() < 2) {
            $this->warn('⚠️  Se necesitan al menos 2 tests para configurar progresión automática');
            return;
        }

        $progresionesCreadas = 0;

        // Crear progresión secuencial básica
        for ($i = 1; $i < $tests->count(); $i++) {
            $testActual = $tests[$i];
            $testAnterior = $tests[$i - 1];

            // Verificar si ya existe la progresión
            $existeProgresion = TestProgresion::where('test_id', $testActual->id)
                ->where('test_prerequisito_id', $testAnterior->id)
                ->exists();

            if (!$existeProgresion) {
                TestProgresion::create([
                    'test_id' => $testActual->id,
                    'test_prerequisito_id' => $testAnterior->id,
                    'orden' => $i + 1,
                    'nivel_minimo_requerido' => $testAnterior->nivel,
                    'activo' => true
                ]);
                $progresionesCreadas++;
            }
        }

        // Configurar algunos tests avanzados con múltiples prerrequisitos
        $this->configurarProgresionAvanzada($tests);

        $this->info("✅ Creadas {$progresionesCreadas} progresiones de tests");
    }

    /**
     * Configura progresión avanzada con múltiples prerrequisitos
     */
    private function configurarProgresionAvanzada($tests)
    {
        // Tests de nivel 3+ pueden requerir completar 2 tests anteriores
        $testsAvanzados = $tests->where('nivel', '>=', 3);
        
        foreach ($testsAvanzados as $testAvanzado) {
            $testsAnteriores = $tests->where('nivel', '<', $testAvanzado->nivel)->take(2);
            
            foreach ($testsAnteriores as $testAnterior) {
                $existeProgresion = TestProgresion::where('test_id', $testAvanzado->id)
                    ->where('test_prerequisito_id', $testAnterior->id)
                    ->exists();

                if (!$existeProgresion && $testAnterior->id != $testAvanzado->id) {
                    TestProgresion::create([
                        'test_id' => $testAvanzado->id,
                        'test_prerequisito_id' => $testAnterior->id,
                        'orden' => $testAvanzado->nivel,
                        'nivel_minimo_requerido' => $testAnterior->nivel,
                        'activo' => true
                    ]);
                }
            }
        }
    }

    /**
     * Configura las condiciones automáticas para insignias
     */
    private function configurarCondicionesInsignias()
    {
        $this->info('🏆 Configurando condiciones automáticas de insignias...');

        $insignias = Insignia::all();
        $condicionesCreadas = 0;

        foreach ($insignias as $insignia) {
            // Evitar duplicar condiciones existentes
            if ($insignia->condiciones()->count() > 0) {
                continue;
            }

            $condiciones = $this->obtenerCondicionesPorNivel($insignia);
            
            foreach ($condiciones as $condicion) {
                CondicionesInsignia::create([
                    'insignia_id' => $insignia->id,
                    'tipo_condicion' => $condicion['tipo'],
                    'valor_requerido' => $condicion['valor'],
                    'descripcion' => $condicion['descripcion'],
                    'activo' => true
                ]);
                $condicionesCreadas++;
            }
        }

        $this->info("✅ Creadas {$condicionesCreadas} condiciones automáticas de insignias");
    }

    /**
     * Obtiene condiciones predeterminadas según el nivel de la insignia
     */
    private function obtenerCondicionesPorNivel($insignia)
    {
        $nivel = $insignia->nivel_requerido ?? 1;
        
        switch ($nivel) {
            case 1: // Principiante
                return [
                    ['tipo' => 'tests_completados', 'valor' => 3, 'descripcion' => 'Completar 3 tests'],
                    ['tipo' => 'puntuacion_minima', 'valor' => 70, 'descripcion' => 'Obtener al menos 70%']
                ];
                
            case 2: // Intermedio
                return [
                    ['tipo' => 'tests_completados', 'valor' => 8, 'descripcion' => 'Completar 8 tests'],
                    ['tipo' => 'puntuacion_minima', 'valor' => 75, 'descripcion' => 'Obtener al menos 75%'],
                    ['tipo' => 'juegos_completados', 'valor' => 3, 'descripcion' => 'Completar 3 juegos']
                ];
                
            case 3: // Intermedio-Avanzado
                return [
                    ['tipo' => 'tests_completados', 'valor' => 15, 'descripcion' => 'Completar 15 tests'],
                    ['tipo' => 'puntuacion_minima', 'valor' => 80, 'descripcion' => 'Obtener al menos 80%'],
                    ['tipo' => 'juegos_completados', 'valor' => 7, 'descripcion' => 'Completar 7 juegos']
                ];
                
            case 4: // Avanzado
                return [
                    ['tipo' => 'tests_completados', 'valor' => 25, 'descripcion' => 'Completar 25 tests'],
                    ['tipo' => 'puntuacion_minima', 'valor' => 85, 'descripcion' => 'Obtener al menos 85%'],
                    ['tipo' => 'nivel_alcanzado', 'valor' => 4, 'descripcion' => 'Alcanzar nivel 4']
                ];
                
            default: // Maestro/Experto
                return [
                    ['tipo' => 'tests_completados', 'valor' => 50, 'descripcion' => 'Completar 50 tests'],
                    ['tipo' => 'puntuacion_minima', 'valor' => 90, 'descripcion' => 'Obtener al menos 90%'],
                    ['tipo' => 'nivel_alcanzado', 'valor' => 8, 'descripcion' => 'Alcanzar nivel 8'],
                    ['tipo' => 'juegos_completados', 'valor' => 20, 'descripcion' => 'Completar 20 juegos']
                ];
        }
    }

    /**
     * Crea insignias de ejemplo si no existen
     */
    private function crearInsigniasEjemplo()
    {
        $insigniasEjemplo = [
            [
                'nombre' => 'Primer Paso',
                'descripcion' => 'Has comenzado tu aventura de aprendizaje',
                'categoria' => 'Inicio',
                'nivel_requerido' => 1,
                'url_icono' => 'https://via.placeholder.com/64/4CAF50/FFFFFF?text=1'
            ],
            [
                'nombre' => 'Lector Dedicado',
                'descripcion' => 'Demuestras constancia en tu aprendizaje',
                'categoria' => 'Progreso',
                'nivel_requerido' => 2,
                'url_icono' => 'https://via.placeholder.com/64/2196F3/FFFFFF?text=2'
            ],
            [
                'nombre' => 'Explorador de Conocimiento',
                'descripcion' => 'Has expandido significativamente tus habilidades',
                'categoria' => 'Avanzado',
                'nivel_requerido' => 3,
                'url_icono' => 'https://via.placeholder.com/64/FF9800/FFFFFF?text=3'
            ],
            [
                'nombre' => 'Maestro de la Lectura',
                'descripcion' => 'Has alcanzado un nivel excepcional de comprensión',
                'categoria' => 'Maestría',
                'nivel_requerido' => 5,
                'url_icono' => 'https://via.placeholder.com/64/9C27B0/FFFFFF?text=M'
            ]
        ];

        $insigniasCreadas = 0;
        foreach ($insigniasEjemplo as $insigniaData) {
            $existe = Insignia::where('nombre', $insigniaData['nombre'])->exists();
            if (!$existe) {
                Insignia::create($insigniaData);
                $insigniasCreadas++;
            }
        }

        if ($insigniasCreadas > 0) {
            $this->info("✅ Creadas {$insigniasCreadas} insignias de ejemplo");
        }
    }

    /**
     * Muestra un resumen de la configuración
     */
    private function mostrarResumen()
    {
        $this->info('');
        $this->info('📊 RESUMEN DE CONFIGURACIÓN:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $totalTests = PruebasLectura::count();
        $totalProgresiones = TestProgresion::count();
        $totalInsignias = Insignia::count();
        $totalCondiciones = CondicionesInsignia::count();
        
        $this->table(
            ['Elemento', 'Cantidad'],
            [
                ['Tests disponibles', $totalTests],
                ['Progresiones configuradas', $totalProgresiones],
                ['Insignias disponibles', $totalInsignias],
                ['Condiciones automáticas', $totalCondiciones]
            ]
        );

        $this->info('');
        $this->info('🎮 NUEVOS ENDPOINTS DISPONIBLES:');
        $this->info('  POST /api/progresion/completar-test');
        $this->info('  GET  /api/progresion/tests-disponibles');
        $this->info('  GET  /api/progresion/progreso-general');
        $this->info('  GET  /api/progresion/juegos-disponibles');
        $this->info('  POST /api/progresion/auto-asignar-juego');
        $this->info('  GET  /api/condiciones-insignia/tipos-condiciones');
    }
}

