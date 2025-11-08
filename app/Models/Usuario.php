<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Model implements AuthenticatableContract, JWTSubject
{
    use HasFactory, Authenticatable;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'usuarios';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'num_documento',
        'nombre',
        'apellido',
        'edad',
        'correo',
        'contrasena_hash',
        'codigo_estudiante',
        'rol_id',
        'institucion_id',
        'fecha_creacion',
        'estado',
        'nivel_actual',
        ];

    /**
     * Los atributos que deben ocultarse para arrays.
     *
     * @var array
     */
    protected $hidden = ['contrasena_hash'];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'rol_id' => 'integer',
        'institucion_id' => 'integer',
        'fecha_creacion' => 'datetime',
        'nivel_actual' => 'integer',
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
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Override the password attribute name for authentication
     */
    public function getAuthPassword()
    {
        return $this->contrasena_hash;
    }

    /**
     * Relación con institución
     */
    public function institucion()
    {
        return $this->belongsTo(Institucion::class, 'institucion_id');
    }

    /**
     * Generar código único de estudiante
     *
     * @param int $institucion_id
     * @param string $nombre
     * @param string $apellido
     * @return string
     */
    public static function generarCodigoEstudiante($institucion_id, $nombre, $apellido)
    {
        // Obtener código de institución (formato: INST + ID con padding)
        $codigoInstitucion = 'INST' . str_pad($institucion_id, 3, '0', STR_PAD_LEFT);
        
        // Limpiar nombre y apellido (sin espacios, acentos, solo caracteres alfanuméricos)
        $nombreLimpio = self::limpiarTexto($nombre);
        $apellidoLimpio = self::limpiarTexto($apellido);
        
        // Crear base del código
        $codigoBase = $codigoInstitucion . '_' . $nombreLimpio . '_' . $apellidoLimpio;
        
        // Verificar si ya existe y agregar contador si es necesario
        $contador = 1;
        $codigoFinal = $codigoBase;
        
        while (self::where('codigo_estudiante', $codigoFinal)->exists()) {
            $codigoFinal = $codigoBase . '_' . $contador;
            $contador++;
        }
        
        return $codigoFinal;
    }
    
    /**
     * Limpiar texto para código de estudiante
     *
     * @param string $texto
     * @return string
     */
    private static function limpiarTexto($texto)
    {
        // Convertir a mayúsculas
        $texto = strtoupper($texto);
        
        // Remover acentos y caracteres especiales
        $texto = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'Ñ' => 'N', 'Ü' => 'U'
        ]);
        
        // Mantener solo letras y números
        $texto = preg_replace('/[^A-Z0-9]/', '', $texto);
        
        return $texto;
    }

    /**
     * Verificar si es estudiante
     *
     * @return bool
     */
    public function esEstudiante()
    {
        return $this->rol_id === 3;
    }

    /**
     * Verificar si es admin
     *
     * @return bool
     */
    public function esAdmin()
    {
        return $this->rol_id === 1;
    }

    /**
     * Verificar si es institución
     *
     * @return bool
     */
    public function esInstitucion()
    {
        return $this->rol_id === 2;
    }

    /**
     * Relación con insignias del usuario
     */
    public function insignias()
    {
        return $this->belongsToMany(Insignia::class, 'usuarios_insignias', 'usuario_id', 'insignia_id')
                    ->withPivot('fecha_otorgada');
    }

    /**
     * Relación con resultados de tests
     */
    public function resultadosTests()
    {
        return $this->hasMany(ResultadosTest::class, 'usuario_id');
    }

    /**
     * Relación con asignaciones de juegos
     */
    public function asignacionesJuegos()
    {
        return $this->hasMany(AsignacionesJuego::class, 'usuario_id');
    }
}
