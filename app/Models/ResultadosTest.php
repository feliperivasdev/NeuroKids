<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResultadosTest extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'resultados_test';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'usuario_id',
        'test_id',
        'puntuacion',
        'puntuacion_maxima',
        'porcentaje',
        'completado',
        'fecha_inicio',
        'fecha_completado',
        'tiempo_total_segundos',
        'intentos'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'usuario_id' => 'integer',
        'test_id' => 'integer',
        'puntuacion' => 'decimal:2',
        'puntuacion_maxima' => 'decimal:2',
        'porcentaje' => 'decimal:2',
        'completado' => 'boolean',
        'fecha_inicio' => 'datetime',
        'fecha_completado' => 'datetime',
        'tiempo_total_segundos' => 'integer',
        'intentos' => 'integer',
    ];

    /**
     * Indica si el modelo debe ser timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Relación con usuario
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con test
     */
    public function test()
    {
        return $this->belongsTo(PruebasLectura::class, 'test_id');
    }

    /**
     * Verifica si el test fue aprobado
     */
    public function aprobado($porcentaje_minimo = 70)
    {
        return $this->completado && $this->porcentaje >= $porcentaje_minimo;
    }
}


