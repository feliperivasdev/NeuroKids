# API de Autenticación - Lectorix

Documentación completa de los endpoints de autenticación con JWT.

## Base URL

```
http://localhost:8000/api/auth
```

## Endpoints

### 1. Obtener Instituciones (Público)

**GET** `/instituciones`

Obtiene la lista de instituciones disponibles para el registro de estudiantes.

#### Respuesta exitosa (200):

```json
{
    "success": true,
    "data": {
        "instituciones": [
            {
                "id": 1,
                "nombre": "Universidad Nacional de Colombia"
            },
            {
                "id": 2,
                "nombre": "Universidad de los Andes"
            }
        ]
    }
}
```

---

### 2. Registro de Estudiante (Público)

**POST** `/register-student`

Registra un nuevo estudiante en el sistema.

#### Parámetros requeridos:

```json
{
    "nombre": "Juan Pérez",
    "correo": "juan@estudiante.com",
    "contrasena": "123456",
    "contrasena_confirmation": "123456",
    "institucion_id": 1
}
```

#### Respuesta exitosa (201):

```json
{
    "success": true,
    "message": "Estudiante registrado exitosamente",
    "data": {
        "usuario": {
            "id": 2,
            "nombre": "Juan Pérez",
            "correo": "juan@estudiante.com",
            "rol_id": 3,
            "institucion_id": 1,
            "estado": true
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

---

### 3. Login de Usuario

**POST** `/login`

Autentica un usuario y devuelve un token JWT.

#### Parámetros requeridos:

```json
{
    "correo": "juan@estudiante.com",
    "contrasena": "123456"
}
```

#### Respuesta exitosa (200):

```json
{
    "success": true,
    "message": "Login exitoso",
    "data": {
        "usuario": {
            "id": 2,
            "nombre": "Juan Pérez",
            "correo": "juan@estudiante.com",
            "rol_id": 3,
            "institucion_id": 1,
            "estado": true
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

#### Respuesta de error (401):

```json
{
    "success": false,
    "message": "Credenciales inválidas"
}
```

---

### 4. Obtener Información del Usuario

**GET** `/me`

Obtiene la información del usuario autenticado.

#### Headers requeridos:

```
Authorization: Bearer {token}
```

#### Respuesta exitosa (200):

```json
{
    "success": true,
    "data": {
        "usuario": {
            "id": 2,
            "nombre": "Juan Pérez",
            "correo": "juan@estudiante.com",
            "rol_id": 3,
            "institucion_id": 1,
            "estado": true,
            "fecha_creacion": "2024-01-15T10:30:00.000000Z"
        }
    }
}
```

---

### 5. Logout

**POST** `/logout`

Cierra la sesión del usuario y invalida el token.

#### Headers requeridos:

```
Authorization: Bearer {token}
```

#### Respuesta exitosa (200):

```json
{
    "success": true,
    "message": "Logout exitoso"
}
```

---

### 6. Refrescar Token

**POST** `/refresh`

Genera un nuevo token JWT válido.

#### Headers requeridos:

```
Authorization: Bearer {token}
```

#### Respuesta exitosa (200):

```json
{
    "success": true,
    "message": "Token refrescado exitosamente",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

---

### 7. Cambiar Contraseña

**POST** `/change-password`

Permite al usuario cambiar su contraseña.

#### Headers requeridos:

```
Authorization: Bearer {token}
```

#### Parámetros requeridos:

```json
{
    "contrasena_actual": "123456",
    "contrasena_nueva": "nueva123",
    "contrasena_nueva_confirmation": "nueva123"
}
```

#### Respuesta exitosa (200):

```json
{
    "success": true,
    "message": "Contraseña actualizada exitosamente"
}
```

#### Respuesta de error (400):

```json
{
    "success": false,
    "message": "La contraseña actual es incorrecta"
}
```

---

### 8. Crear Usuario (Solo Administradores)

**POST** `/create-user`

Crea un nuevo usuario (solo administradores).

#### Headers requeridos:

```
Authorization: Bearer {token_admin}
```

#### Parámetros requeridos:

```json
{
    "nombre": "Profesor García",
    "correo": "profesor@institucion.com",
    "contrasena": "123456",
    "rol_id": 2,
    "institucion_id": 1
}
```

#### Respuesta exitosa (201):

```json
{
    "success": true,
    "message": "Usuario creado exitosamente",
    "data": {
        "usuario": {
            "id": 3,
            "nombre": "Profesor García",
            "correo": "profesor@institucion.com",
            "rol_id": 2,
            "institucion_id": 1,
            "estado": true
        }
    }
}
```

---

### 9. Generar Token (Solo Administradores)

**POST** `/generate-token`

Genera un token para un usuario existente (solo administradores).

#### Headers requeridos:

```
Authorization: Bearer {token_admin}
```

#### Parámetros requeridos:

```json
{
    "usuario_id": 2
}
```

#### Respuesta exitosa (200):

```json
{
    "success": true,
    "message": "Token generado exitosamente",
    "data": {
        "usuario": {
            "id": 2,
            "nombre": "Juan Pérez",
            "correo": "juan@estudiante.com",
            "rol_id": 3,
            "institucion_id": 1
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

---

### 10. Listar Usuarios (Solo Administradores)

**GET** `/users`

Obtiene la lista de todos los usuarios (solo administradores).

#### Headers requeridos:

```
Authorization: Bearer {token_admin}
```

#### Respuesta exitosa (200):

```json
{
    "success": true,
    "data": {
        "usuarios": [
            {
                "id": 1,
                "nombre": "Administrador",
                "correo": "admin@lectorix.com",
                "rol_id": 1,
                "institucion_id": 1,
                "estado": true,
                "fecha_creacion": "2024-01-15T10:30:00.000000Z"
            },
            {
                "id": 2,
                "nombre": "Juan Pérez",
                "correo": "juan@estudiante.com",
                "rol_id": 3,
                "institucion_id": 1,
                "estado": true,
                "fecha_creacion": "2024-01-15T11:00:00.000000Z"
            }
        ]
    }
}
```

---

## Códigos de Estado HTTP

| Código | Descripción                                            |
| ------ | ------------------------------------------------------ |
| 200    | OK - Operación exitosa                                 |
| 201    | Created - Recurso creado exitosamente                  |
| 400    | Bad Request - Error en los datos enviados              |
| 401    | Unauthorized - No autenticado o credenciales inválidas |
| 403    | Forbidden - No tienes permisos para esta acción        |
| 422    | Unprocessable Entity - Error de validación             |
| 500    | Internal Server Error - Error interno del servidor     |

## Autenticación

La API utiliza **JWT (JSON Web Tokens)** para la autenticación. Para acceder a endpoints protegidos, incluye el token en el header:

```
Authorization: Bearer {tu_token_jwt}
```

## Roles del Sistema

-   **Rol ID 1**: Administrador (acceso total)
-   **Rol ID 2**: Profesor/Docente
-   **Rol ID 3**: Estudiante

## Validaciones

### Registro de Estudiante:

-   `nombre`: Requerido, máximo 255 caracteres
-   `correo`: Requerido, formato email válido, único en la tabla usuarios
-   `contrasena`: Requerido, mínimo 6 caracteres, debe coincidir con confirmation
-   `institucion_id`: Requerido, debe existir en la tabla instituciones

### Login:

-   `correo`: Requerido, formato email válido
-   `contrasena`: Requerido

### Crear Usuario (Admin):

-   `nombre`: Requerido, máximo 255 caracteres
-   `correo`: Requerido, formato email válido, único
-   `contrasena`: Requerido, mínimo 6 caracteres
-   `rol_id`: Requerido, debe existir en la tabla roles
-   `institucion_id`: Requerido, debe existir en la tabla instituciones

### Cambio de contraseña:

-   `contrasena_actual`: Requerido
-   `contrasena_nueva`: Requerido, mínimo 6 caracteres, debe coincidir con confirmation

## Ejemplo de Uso con cURL

### Obtener instituciones:

```bash
curl -X GET http://localhost:8000/api/auth/instituciones
```

### Registro de estudiante:

```bash
curl -X POST http://localhost:8000/api/auth/register-student \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan Pérez",
    "correo": "juan@estudiante.com",
    "contrasena": "123456",
    "contrasena_confirmation": "123456",
    "institucion_id": 1
  }'
```

### Login:

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "correo": "juan@estudiante.com",
    "contrasena": "123456"
  }'
```

### Obtener información del usuario:

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer {tu_token_jwt}"
```

## Notas Importantes

1. **Registro Público**: Los estudiantes pueden registrarse públicamente seleccionando su institución
2. **Control de Roles**: Solo administradores pueden crear usuarios y generar tokens
3. **Tokens JWT**: Los tokens incluyen información del rol y institución
4. **Seguridad**: Las contraseñas se hashean usando `Hash::make()` antes de almacenarse
5. **Validaciones**: Todos los endpoints incluyen validaciones completas
6. **Instituciones**: Se pueden obtener las instituciones disponibles sin autenticación
