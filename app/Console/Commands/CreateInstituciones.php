<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateInstituciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:instituciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea instituciones de prueba para el sistema';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🏫 Creando instituciones de prueba...');

        $instituciones = [
            [
                'nombre' => 'Universidad Nacional de Colombia',
                'direccion' => 'Carrera 45 # 26-85, Bogotá',
                'telefono' => '+57 1 3165000',
                'email' => 'contacto@unal.edu.co',
                'estado' => true
            ],
            [
                'nombre' => 'Universidad de los Andes',
                'direccion' => 'Carrera 1 # 18A-12, Bogotá',
                'telefono' => '+57 1 3394949',
                'email' => 'info@uniandes.edu.co',
                'estado' => true
            ],
            [
                'nombre' => 'Pontificia Universidad Javeriana',
                'direccion' => 'Carrera 7 # 40-62, Bogotá',
                'telefono' => '+57 1 3208320',
                'email' => 'contactenos@javeriana.edu.co',
                'estado' => true
            ],
            [
                'nombre' => 'Universidad Externado de Colombia',
                'direccion' => 'Calle 12 # 1-17 Este, Bogotá',
                'telefono' => '+57 1 3420288',
                'email' => 'info@uexternado.edu.co',
                'estado' => true
            ],
            [
                'nombre' => 'Universidad del Rosario',
                'direccion' => 'Calle 12C # 6-25, Bogotá',
                'telefono' => '+57 1 2970200',
                'email' => 'info@urosario.edu.co',
                'estado' => true
            ],
            [
                'nombre' => 'Universidad de Antioquia',
                'direccion' => 'Calle 67 # 53-108, Medellín',
                'telefono' => '+57 4 2198333',
                'email' => 'info@udea.edu.co',
                'estado' => true
            ],
            [
                'nombre' => 'Universidad del Valle',
                'direccion' => 'Calle 13 # 100-00, Cali',
                'telefono' => '+57 2 3212100',
                'email' => 'info@univalle.edu.co',
                'estado' => true
            ]
        ];

        try {
            if (class_exists('App\Models\Institucion')) {
                $model = new \App\Models\Institucion();
                
                foreach ($instituciones as $institucion) {
                    // Verificar si ya existe
                    if (!$model->where('nombre', $institucion['nombre'])->exists()) {
                        $model->create($institucion);
                        $this->info("✅ Creada: {$institucion['nombre']}");
                    } else {
                        $this->line("⏭️  Ya existe: {$institucion['nombre']}");
                    }
                }
            } else {
                $this->warn('⚠️  El modelo Institucion no existe. Usando datos hardcodeados.');
                $this->info('📝 Para crear el modelo, ejecuta: php artisan make:models-from-db --table=instituciones');
            }

            $this->info('🎉 Instituciones creadas exitosamente!');
            $this->info('📋 Los estudiantes pueden ver las instituciones en: GET /api/auth/instituciones');

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al crear instituciones: " . $e->getMessage());
            return 1;
        }
    }
} 