# Sistema de Autenticación Separada

## Descripción General

Se ha implementado un sistema de autenticación separada que divide a los usuarios en dos categorías principales:

1. **Panel Administrativo**: Para administradores e instituciones (usan correo + contraseña)
2. **Panel de Estudiantes**: Para estudiantes (usan nombre + apellido + institución)

## Cambios Principales

### 1. Separación de Autenticación

-   **Administradores e Instituciones**: Continúan usando el sistema tradicional de correo electrónico y contraseña
-   **Estudiantes**: Nuevo sistema sin contraseña, usando solo nombre, apellido e institución

### 2. Códigos Únicos de Estudiante

Para evitar duplicados de nombres en diferentes instituciones, se implementó un sistema de códigos únicos:

**Formato**: `INST{ID}_NOMBRE_APELLIDO[_CONTADOR]`

**Ejemplos**:

-   `INST001_JUAN_PEREZ` (primer Juan Pérez en institución 1)
-   `INST001_JUAN_PEREZ_2` (segundo Juan Pérez en institución 1)
-   `INST002_JUAN_PEREZ` (Juan Pérez en institución 2)

### 3. Nuevos Endpoints

#### Panel Administrativo (`/api/admin/`)

```
POST /api/admin/login
GET  /api/admin/me
POST /api/admin/logout
POST /api/admin/refresh
POST /api/admin/change-password
POST /api/admin/create-user
POST /api/admin/generate-token
GET  /api/admin/users
```

#### Panel de Estudiantes (`/api/students/`)

```
POST /api/students/login
POST /api/students/register
GET  /api/students/search
GET  /api/students/instituciones
GET  /api/students/me
POST /api/students/logout
```

## Uso de la API

### Autenticación de Administradores/Instituciones

```bash
# Login
curl -X POST http://localhost/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "correo": "admin@institucion.com",
    "contrasena": "mi_contraseña"
  }'
```

### Autenticación de Estudiantes

```bash
# Registro de estudiante
curl -X POST http://localhost/api/students/register \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan",
    "apellido": "Pérez",
    "edad": 20,
    "institucion_id": 1
  }'

# Login de estudiante
curl -X POST http://localhost/api/students/login \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan",
    "apellido": "Pérez",
    "institucion_id": 1
  }'

# Buscar estudiantes (para sugerencias en el frontend)
curl -X GET "http://localhost/api/students/search?nombre=Juan&institucion_id=1"
```

### Obtener Instituciones

```bash
# Disponible para ambos paneles
curl -X GET http://localhost/api/students/instituciones
```

## Migración de Datos Existentes

Si ya tienes estudiantes en la base de datos sin códigos únicos, puedes usar el comando Artisan:

```bash
# Generar códigos para estudiantes que no los tengan
php artisan estudiantes:generar-codigos

# Regenerar todos los códigos (incluso si ya existen)
php artisan estudiantes:generar-codigos --force
```

## Estructura de Base de Datos

### Cambios en la tabla `usuarios`

Se agregó el campo `codigo_estudiante`:

```sql
ALTER TABLE usuarios ADD COLUMN codigo_estudiante VARCHAR(100) NULL;
```

### Roles

-   **1**: Administrador
-   **2**: Institución
-   **3**: Estudiante

## Validaciones de Seguridad

### Panel Administrativo

-   Solo permite login de usuarios con `rol_id` 1 (admin) o 2 (institución)
-   Los estudiantes (`rol_id` 3) son rechazados con mensaje específico
-   Requiere correo y contraseña válidos

### Panel de Estudiantes

-   Solo permite estudiantes (`rol_id` 3)
-   Valida que el estudiante exista en la institución seleccionada
-   No requiere contraseña
-   Genera códigos únicos automáticamente

## Beneficios del Nuevo Sistema

1. **Simplicidad para Estudiantes**: No necesitan recordar contraseñas
2. **Seguridad Mantenida**: Administradores e instituciones mantienen autenticación segura
3. **Identificación Única**: Sistema de códigos evita duplicados entre instituciones
4. **Separación Clara**: Interfaces distintas para diferentes tipos de usuarios
5. **Escalabilidad**: Fácil agregar nuevas funcionalidades específicas por tipo de usuario

## Consideraciones de Frontend

### Panel Administrativo

-   Formulario tradicional: correo + contraseña
-   Validaciones de email y contraseña segura
-   Manejo de roles para mostrar funcionalidades específicas

### Panel de Estudiantes

-   Formulario simplificado: nombre + apellido + selector de institución
-   Autocompletado/búsqueda de nombres existentes
-   Sin campos de contraseña
-   Confirmación visual del código de estudiante generado

## Troubleshooting

### Error: "Los estudiantes no pueden acceder por este panel"

-   El estudiante está intentando usar `/api/admin/login`
-   Solución: Usar `/api/students/login`

### Error: "Ya existe un estudiante con ese nombre y apellido"

-   Hay otro estudiante con el mismo nombre en la misma institución
-   Solución: Verificar datos o usar un nombre más específico

### Error: Estudiante no encontrado en login

-   Verificar que el estudiante esté registrado en esa institución
-   Verificar que el nombre y apellido coincidan exactamente
-   El estudiante podría estar desactivado (`estado = false`)

## Comandos Útiles

```bash
# Ver estudiantes sin código
php artisan tinker
>>> App\Models\Usuario::where('rol_id', 3)->whereNull('codigo_estudiante')->get();

# Generar códigos faltantes
php artisan estudiantes:generar-codigos

# Ver todos los códigos generados
php artisan tinker
>>> App\Models\Usuario::where('rol_id', 3)->pluck('codigo_estudiante', 'nombre');
```
