<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsuariosJuego extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'usuarios_juegos';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = ['usuario_id', 'juego_id', 'completado', 'puntaje', 'intentos', 'tiempo_total_segundos', 'fecha_asignacion', 'fecha_ultimo_intento'];

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
        'usuario_id' => 'integer',
        'juego_id' => 'integer',
        'completado' => 'boolean',
        'puntaje' => 'float',
        'intentos' => 'integer',
        'tiempo_total_segundos' => 'integer',
        'fecha_asignacion' => 'datetime',
        'fecha_ultimo_intento' => 'datetime',
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
