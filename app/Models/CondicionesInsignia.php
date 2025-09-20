<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CondicionesInsignia extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'condiciones_insignia';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'insignia_id',
        'tipo_condicion',
        'valor_requerido',
        'descripcion',
        'activo'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'insignia_id' => 'integer',
        'valor_requerido' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Indica si el modelo debe ser timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Tipos de condiciones disponibles
     */
    const TIPOS_CONDICION = [
        'tests_completados' => 'Número de tests completados',
        'puntuacion_minima' => 'Puntuación mínima en tests',
        'juegos_completados' => 'Número de juegos completados',
        'lecturas_completadas' => 'Número de lecturas completadas',
        'racha_dias' => 'Días consecutivos de actividad',
        'nivel_alcanzado' => 'Nivel específico alcanzado',
        'tiempo_total' => 'Tiempo total dedicado (minutos)'
    ];

    /**
     * Relación con insignia
     */
    public function insignia()
    {
        return $this->belongsTo(Insignia::class, 'insignia_id');
    }
}


