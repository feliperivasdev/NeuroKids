<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateAppKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:generate {--show : Mostrar la clave en lugar de modificar el archivo .env} {--force : Forzar la operación sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera una nueva clave de aplicación';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $key = $this->generateRandomKey();

        if ($this->option('show')) {
            $this->line('<comment>' . $key . '</comment>');
            return 0;
        }

        // Verificar si .env existe
        if (!file_exists(base_path('.env'))) {
            $this->error('Archivo .env no encontrado.');
            return 1;
        }

        // Verificar si ya existe APP_KEY y no se forzó
        if (!$this->option('force') && $this->laravel['config']['app.key']) {
            if (!$this->confirm('Esta acción sobrescribirá la clave existente. ¿Deseas continuar?')) {
                return 0;
            }
        }

        // Actualizar el archivo .env
        $this->setKeyInEnvironmentFile($key);

        $this->info("Clave de aplicación establecida exitosamente.");

        return 0;
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:' . base64_encode(
            random_bytes(32)
        );
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return void
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        $escaped = preg_quote('=' . env('APP_KEY'), '/');

        $envContent = preg_replace(
            "/^APP_KEY{$escaped}/m",
            'APP_KEY=' . $key,
            $envContent
        );

        // Si no existe APP_KEY, agregarlo
        if (!preg_match('/^APP_KEY=/m', $envContent)) {
            $envContent .= "\nAPP_KEY=" . $key;
        }

        file_put_contents($envPath, $envContent);
    }
} 