<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Usuario;

class GenerarCodigosEstudiantes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estudiantes:generar-codigos {--force : Regenerar códigos incluso si ya existen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar códigos únicos para estudiantes que no los tengan';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $this->info('🔄 Iniciando generación de códigos de estudiante...');
        
        // Obtener estudiantes que necesitan código
        $query = Usuario::where('rol_id', 3); // Solo estudiantes
        
        if (!$force) {
            $query->where(function($q) {
                $q->whereNull('codigo_estudiante')
                  ->orWhere('codigo_estudiante', '');
            });
        }
        
        $estudiantes = $query->get();
        
        if ($estudiantes->isEmpty()) {
            $this->info('✅ No hay estudiantes que necesiten códigos.');
            return 0;
        }
        
        $this->info("📊 Encontrados {$estudiantes->count()} estudiantes para procesar");
        
        $procesados = 0;
        $errores = 0;
        
        foreach ($estudiantes as $estudiante) {
            try {
                // Si force está activado o no tiene código, generar uno nuevo
                if ($force || empty($estudiante->codigo_estudiante)) {
                    $codigoAnterior = $estudiante->codigo_estudiante;
                    
                    $nuevoCodigo = Usuario::generarCodigoEstudiante(
                        $estudiante->institucion_id,
                        $estudiante->nombre,
                        $estudiante->apellido
                    );
                    
                    $estudiante->codigo_estudiante = $nuevoCodigo;
                    $estudiante->save();
                    
                    if ($force && $codigoAnterior) {
                        $this->line("🔄 {$estudiante->nombre} {$estudiante->apellido}: {$codigoAnterior} → {$nuevoCodigo}");
                    } else {
                        $this->line("✅ {$estudiante->nombre} {$estudiante->apellido}: {$nuevoCodigo}");
                    }
                    
                    $procesados++;
                }
            } catch (\Exception $e) {
                $this->error("❌ Error procesando {$estudiante->nombre} {$estudiante->apellido}: {$e->getMessage()}");
                $errores++;
            }
        }
        
        $this->info("🎉 Proceso completado:");
        $this->info("   ✅ Procesados: {$procesados}");
        if ($errores > 0) {
            $this->warn("   ❌ Errores: {$errores}");
        }
        
        return 0;
    }
} 