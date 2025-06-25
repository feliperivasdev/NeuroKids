<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsuariosInsignia extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'usuarios_insignias';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = ['usuario_id', 'insignia_id', 'fecha_otorgada'];

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
        'insignia_id' => 'integer',
        'fecha_otorgada' => 'datetime',
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
