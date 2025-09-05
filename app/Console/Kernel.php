<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\GenerateModelsFromDatabase::class,
        \App\Console\Commands\CreateAdminUser::class,
        \App\Console\Commands\CreateInstituciones::class,
        \App\Console\Commands\GenerateAppKey::class,
        \App\Console\Commands\GenerarCodigosEstudiantes::class,
        \App\Console\Commands\ConfigurarProgresionInicial::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
