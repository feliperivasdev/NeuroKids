<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="AsignacionesJuego",
 *     description="Modelo de asignación de juegos",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="usuario_id", type="integer"),
 *     @OA\Property(property="juego_id", type="integer"),
 *     @OA\Property(property="nivel_asignado", type="integer"),
 *     @OA\Property(property="completado", type="boolean"),
 *     @OA\Property(property="fecha_asignacion", type="string", format="date-time"),
 *     @OA\Property(property="fecha_completado", type="string", format="date-time", nullable=true)
 * )
 */
class AsignacionesJuego extends Model
{
    protected $table = 'asignaciones_juegos';
    
    protected $fillable = [
        'usuario_id',
        'juego_id',
        'nivel_asignado',
        'completado',
        'fecha_asignacion',
        'fecha_completado'
    ];

    protected $casts = [
        'completado' => 'boolean',
        'fecha_asignacion' => 'datetime',
        'fecha_completado' => 'datetime'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function juego()
    {
        return $this->belongsTo(Juego::class);
    }
}