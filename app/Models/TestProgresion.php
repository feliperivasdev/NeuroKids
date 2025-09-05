<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TestProgresion extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'test_progresion';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'test_id', 
        'test_prerequisito_id', 
        'orden', 
        'nivel_minimo_requerido',
        'activo'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'test_id' => 'integer',
        'test_prerequisito_id' => 'integer',
        'orden' => 'integer',
        'nivel_minimo_requerido' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Indica si el modelo debe ser timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Relación con el test principal
     */
    public function test()
    {
        return $this->belongsTo(PruebasLectura::class, 'test_id');
    }

    /**
     * Relación con el test prerequisito
     */
    public function prerequisito()
    {
        return $this->belongsTo(PruebasLectura::class, 'test_prerequisito_id');
    }
}

