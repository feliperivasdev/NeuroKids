# Guía de Pruebas - API Privada de Lectorix

Esta guía te ayudará a probar tu API privada localmente y verificar que el sistema de autenticación y control de roles funcione correctamente.

## 🚀 Configuración Inicial

### 1. Crear Usuario Administrador

Primero, crea un usuario administrador inicial:

```bash
php artisan make:admin
```

O con parámetros personalizados:

```bash
php artisan make:admin --email=tuadmin@ejemplo.com --password=tucontraseña --name="Tu Nombre"
```

### 2. Iniciar el Servidor

```bash
php -S localhost:8000 -t public
```

## 🔐 Endpoints Disponibles

### Rutas Públicas (sin autenticación)

-   `POST /api/auth/login` - Login de usuario

### Rutas Protegidas (requieren autenticación)

-   `GET /api/auth/me` - Información del usuario
-   `POST /api/auth/logout` - Logout
-   `POST /api/auth/refresh` - Refrescar token
-   `POST /api/auth/change-password` - Cambiar contraseña

### Rutas de Administrador (rol_id = 1)

-   `POST /api/auth/create-user` - Crear nuevo usuario
-   `POST /api/auth/generate-token` - Generar token para usuario
-   `GET /api/auth/users` - Listar usuarios

## 🧪 Pruebas Paso a Paso

### 1. Login de Administrador

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "correo": "admin@lectorix.com",
    "contrasena": "admin123"
  }'
```

**Respuesta esperada:**

```json
{
    "success": true,
    "message": "Login exitoso",
    "data": {
        "usuario": {
            "id": 1,
            "nombre": "Administrador",
            "correo": "admin@lectorix.com",
            "rol_id": 1,
            "institucion_id": 1,
            "estado": true
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

### 2. Crear Usuario (como Administrador)

```bash
curl -X POST http://localhost:8000/api/auth/create-user \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN_ADMIN" \
  -d '{
    "nombre": "Usuario Prueba",
    "correo": "usuario@prueba.com",
    "contrasena": "123456",
    "rol_id": 2,
    "institucion_id": 1
  }'
```

### 3. Generar Token para Usuario (como Administrador)

```bash
curl -X POST http://localhost:8000/api/auth/generate-token \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TU_TOKEN_ADMIN" \
  -d '{
    "usuario_id": 2
  }'
```

### 4. Login de Usuario Normal

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "correo": "usuario@prueba.com",
    "contrasena": "123456"
  }'
```

### 5. Probar Control de Roles

**Intentar acceder a rutas de admin con usuario normal:**

```bash
curl -X GET http://localhost:8000/api/auth/users \
  -H "Authorization: Bearer TOKEN_USUARIO_NORMAL"
```

**Respuesta esperada (403 Forbidden):**

```json
{
    "success": false,
    "message": "No tienes permisos para ver usuarios"
}
```

**Acceder con administrador:**

```bash
curl -X GET http://localhost:8000/api/auth/users \
  -H "Authorization: Bearer TOKEN_ADMIN"
```

**Respuesta esperada (200 OK):**

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
            }
        ]
    }
}
```

## 🛠️ Script de Pruebas Automatizadas

Ejecuta el script de pruebas automatizadas:

```bash
php test_api.php
```

Este script realizará todas las pruebas automáticamente y te mostrará los resultados.

## 📋 Checklist de Pruebas

-   [ ] ✅ Login de administrador funciona
-   [ ] ✅ Creación de usuarios (solo admin)
-   [ ] ✅ Generación de tokens (solo admin)
-   [ ] ✅ Login de usuarios normales
-   [ ] ✅ Control de acceso por roles
-   [ ] ✅ Rutas protegidas funcionan
-   [ ] ✅ Logout funciona
-   [ ] ✅ Refresh de token funciona
-   [ ] ✅ Cambio de contraseña funciona

## 🔧 Solución de Problemas

### Error: "No tienes permisos"

-   Verifica que el usuario tenga `rol_id = 1` para ser administrador
-   Asegúrate de que el token sea válido

### Error: "Credenciales inválidas"

-   Verifica que el usuario exista en la base de datos
-   Confirma que la contraseña sea correcta
-   Asegúrate de que el usuario esté activo (`estado = true`)

### Error: "Usuario no autenticado"

-   Verifica que el token JWT sea válido
-   Asegúrate de incluir el header `Authorization: Bearer TOKEN`

### Error de conexión a la base de datos

-   Verifica la configuración en `.env`
-   Asegúrate de que las tablas existan
-   Ejecuta `php artisan make:models-from-db --all` para generar modelos

## 🎯 Flujo de Trabajo Recomendado

1. **Configurar base de datos** con tablas necesarias
2. **Crear usuario administrador** con `php artisan make:admin`
3. **Iniciar servidor** con `php -S localhost:8000 -t public`
4. **Probar login de admin** y obtener token
5. **Crear usuarios de prueba** usando el token de admin
6. **Probar control de roles** con diferentes usuarios
7. **Verificar todas las funcionalidades** de la API

## 📝 Notas Importantes

-   **API Privada**: Solo el login es público, todo lo demás requiere autenticación
-   **Control de Roles**: Solo administradores pueden crear usuarios y generar tokens
-   **Tokens JWT**: Tienen tiempo de expiración configurable
-   **Seguridad**: Las contraseñas se hashean automáticamente
-   **Validaciones**: Todos los endpoints incluyen validaciones completas
