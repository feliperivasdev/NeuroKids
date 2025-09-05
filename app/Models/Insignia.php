<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Insignia extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'insignias';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = ['nombre', 'descripcion', 'url_icono', 'categoria', 'nivel_requerido'];

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
        'nivel_requerido' => 'integer',
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

    /**
     * Relación con las condiciones de la insignia
     */
    public function condiciones()
    {
        return $this->hasMany(CondicionesInsignia::class, 'insignia_id');
    }

    /**
     * Relación con usuarios que tienen esta insignia
     */
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'usuarios_insignias', 'insignia_id', 'usuario_id')
                    ->withPivot('fecha_otorgada');
    }
}
