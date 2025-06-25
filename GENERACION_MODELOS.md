# Generación Automática de Modelos en Lumen

Este proyecto incluye un comando personalizado de Artisan para generar modelos Eloquent automáticamente basándose en las tablas de la base de datos.

## Comando Disponible

### `make:models-from-db`

Genera modelos Eloquent basándose en las tablas existentes en la base de datos.

## Opciones

-   `--table=nombre_tabla`: Genera un modelo para una tabla específica
-   `--all`: Genera modelos para todas las tablas de la base de datos

## Uso

### Generar modelo para una tabla específica

```bash
php artisan make:models-from-db --table=usuarios
```

### Generar modelos para todas las tablas

```bash
php artisan make:models-from-db --all
```

## Características del Comando

El comando automáticamente:

1. **Detecta la estructura de la tabla** usando `DESCRIBE`
2. **Genera el nombre del modelo** en formato StudlyCase y singular
3. **Configura los atributos fillable** excluyendo columnas como `id`, `created_at`, `updated_at`, `deleted_at`
4. **Configura los atributos hidden** para columnas sensibles como `password`, `contrasena`, `api_token`
5. **Configura los casts** basándose en los tipos de datos de las columnas:
    - `int` → `integer`
    - `decimal/float` → `float`
    - `datetime` → `datetime`
    - `date` → `date`
    - `json` → `array`
    - `boolean/tinyint(1)` → `boolean`
6. **Configura timestamps** automáticamente si la tabla tiene columnas `created_at`/`updated_at`
7. **Pregunta antes de sobrescribir** modelos existentes

## Ejemplo de Modelo Generado

Para una tabla `usuarios` con columnas:

-   `id` (int, primary key)
-   `nombre` (varchar)
-   `correo` (varchar)
-   `contrasena_hash` (varchar)
-   `fecha_creacion` (datetime)
-   `estado` (tinyint)

Se generará un modelo como:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre', 'correo', 'fecha_creacion', 'estado'
    ];

    protected $hidden = [
        'contrasena_hash'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'estado' => 'boolean'
    ];

    public $timestamps = false;

    public function getKeyName()
    {
        return 'id';
    }
}
```

## Requisitos

-   Lumen con Eloquent habilitado (`$app->withEloquent()`)
-   Facades habilitadas (`$app->withFacades()`)
-   Conexión a base de datos configurada en `.env`

## Notas

-   El comando funciona con MySQL/MariaDB
-   Los modelos se generan en `app/Models/`
-   Se incluye el trait `HasFactory` para factories
-   Se configuran automáticamente las relaciones si se detectan claves foráneas (futura mejora)
