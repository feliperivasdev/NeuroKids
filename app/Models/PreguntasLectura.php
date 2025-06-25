<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PreguntasLectura extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'preguntas_lectura';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = ['prueba_id', 'texto_pregunta', 'respuesta_correcta', 'orden'];

    /**
     * Los atributos que deben ocultarse para arrays.
     *
     * @var array
     */
    protected $hidden = [''];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'prueba_id' => 'integer',
        'orden' => 'integer',
    ];

    /**
     * Indica si el modelo debe ser timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Obtiene el nombre de la clave primaria.
     *
     * @return string
     */
    public function getKeyName()
    {
        return 'id';
    }
}
