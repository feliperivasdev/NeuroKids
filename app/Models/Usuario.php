<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Laravel\Lumen\Auth\Authorizable;

class Usuario extends Model implements JWTSubject
{
    use Authenticatable, Authorizable;

    protected $table = 'usuarios';
    protected $fillable = [
        'nombre', 'correo', 'contrasena_hash', 'rol_id', 'institucion_id', 'fecha_creacion', 'estado'
    ];

    protected $hidden = ['contrasena_hash'];

    public function getJWTIdentifier()
    {
        return $this-getKey();
    }
}