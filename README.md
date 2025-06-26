# Lectorix API

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![Lumen Version](https://img.shields.io/badge/Lumen-10.0+-green.svg)](https://lumen.laravel.com)
[![JWT Auth](https://img.shields.io/badge/JWT-Auth-orange.svg)](https://jwt-auth.readthedocs.io)

API REST privada para el sistema Lectorix, construida con Lumen y JWT Authentication. Sistema híbrido que permite registro público de estudiantes y control administrativo total.

## 🚀 Características

-   **🔐 Autenticación JWT** - Tokens seguros con expiración configurable
-   **👥 Sistema de Roles** - Administradores, Profesores y Estudiantes
-   **🏫 Gestión de Instituciones** - Múltiples instituciones educativas
-   **📝 Registro Público** - Estudiantes pueden registrarse seleccionando su institución
-   **🛡️ Control Administrativo** - Solo administradores pueden crear usuarios y generar tokens
-   **🔒 Rutas Protegidas** - Middleware de autenticación y roles
-   **📊 Base de Datos PostgreSQL** - Compatible con NeonDB
-   **⚡ Generación Automática de Modelos** - Comando personalizado para generar modelos desde la BD

## 📋 Requisitos

-   PHP 8.1 o superior
-   Composer
-   PostgreSQL (NeonDB compatible)
-   Extensiones PHP: `pdo_pgsql`, `openssl`, `mbstring`

## 🛠️ Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/feliperivasdev/Lectorix_API.git
cd Lectorix_API
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar variables de entorno

```bash
cp .env.example .env
```

Editar `.env` con tu configuración:

```env
APP_NAME=Lectorix
APP_ENV=local
APP_KEY=base64:tu-clave-aqui
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=UTC

DB_CONNECTION=pgsql
DB_HOST=tu-host
DB_PORT=5432
DB_DATABASE=tu-base-de-datos
DB_USERNAME=tu-usuario
DB_PASSWORD=tu-contraseña

JWT_SECRET=tu-jwt-secret
JWT_TTL=60
```

### 4. Generar clave de aplicación

```bash
php artisan key:generate
```

### 5. Generar secret JWT

```bash
php artisan jwt:secret
```

### 6. Generar modelos desde la base de datos

```bash
php artisan make:models-from-db --all
```

### 7. Crear usuario administrador inicial

```bash
php artisan make:admin
```

### 8. Crear instituciones de prueba

```bash
php artisan make:instituciones
```

## 🚀 Iniciar el servidor

```bash
php -S localhost:8000 -t public
```

## 📚 Documentación de la API

### Base URL

```
http://localhost:8000/api
```

### Endpoints Principales

#### 🔓 Rutas Públicas

-   `GET /auth/instituciones` - Obtener instituciones disponibles
-   `POST /auth/register-student` - Registro de estudiantes
-   `POST /auth/login` - Login de usuarios

#### 🛡️ Rutas Protegidas (requieren token)

-   `GET /auth/me` - Información del usuario autenticado
-   `POST /auth/logout` - Cerrar sesión
-   `POST /auth/refresh` - Refrescar token
-   `POST /auth/change-password` - Cambiar contraseña

#### 👑 Rutas de Administrador

-   `POST /auth/create-user` - Crear usuario
-   `POST /auth/generate-token` - Generar token para usuario
-   `GET /auth/users` - Listar usuarios

#### 👥 Gestión de Usuarios

-   `GET /usuarios` - Listar usuarios
-   `GET /usuarios/{id}` - Ver usuario específico
-   `PUT /usuarios/{id}` - Actualizar usuario
-   `POST /usuarios` - Crear usuario (solo admin)
-   `DELETE /usuarios/{id}` - Desactivar usuario (solo admin)

### Ejemplos de Uso

#### Registro de Estudiante

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

#### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "correo": "admin@lectorix.com",
    "contrasena": "admin123"
  }'
```

#### Ver información del usuario

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TU_TOKEN_JWT"
```

## 🏗️ Estructura del Proyecto

```
Lectorix_API/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CreateAdminUser.php          # Crear admin inicial
│   │       ├── CreateInstituciones.php      # Crear instituciones
│   │       └── GenerateModelsFromDatabase.php # Generar modelos
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── AuthController.php           # Controlador de autenticación
│   │   └── Middleware/
│   │       └── AdminMiddleware.php          # Middleware de admin
│   └── Models/
│       ├── Usuario.php                      # Modelo de usuario con JWT
│       └── Institucion.php                  # Modelo de institución
├── bootstrap/
│   └── app.php                              # Configuración de Lumen
├── config/
│   ├── auth.php                             # Configuración de autenticación
│   └── jwt.php                              # Configuración de JWT
├── routes/
│   └── web.php                              # Definición de rutas
└── README.md                                # Este archivo
```

## 🔐 Sistema de Roles

-   **Rol 1**: Administrador (acceso total)
-   **Rol 2**: Profesor/Docente
-   **Rol 3**: Estudiante (registro público)

## 🛠️ Comandos Artisan Disponibles

```bash
# Generar modelos desde la base de datos
php artisan make:models-from-db --all
php artisan make:models-from-db --table=nombre_tabla

# Crear usuario administrador
php artisan make:admin
php artisan make:admin --email=tu@email.com --password=tucontraseña --name="Tu Nombre"

# Crear instituciones de prueba
php artisan make:instituciones
```

## 🧪 Pruebas

Ejecuta el script de pruebas automatizadas:

```bash
php test_api.php
```

## 🔧 Configuración

### JWT Configuration

El archivo `config/jwt.php` contiene la configuración de JWT:

-   Tiempo de expiración de tokens
-   Algoritmo de encriptación
-   Configuración de blacklist

### Database Configuration

Configurado para PostgreSQL con soporte para NeonDB:

-   Conexión automática
-   Detección de tipos de datos
-   Generación automática de modelos

## 🚨 Seguridad

-   **Contraseñas hasheadas** con `Hash::make()`
-   **Tokens JWT** con expiración configurable
-   **Middleware de autenticación** en rutas protegidas
-   **Control de roles** para rutas administrativas
-   **Validaciones completas** en todos los endpoints
-   **Sanitización de datos** automática

## 📝 Notas Importantes

1. **API Privada**: Solo el login y registro de estudiantes son públicos
2. **Control de Acceso**: Solo administradores pueden crear usuarios y generar tokens
3. **Base de Datos**: Optimizado para PostgreSQL/NeonDB
4. **Modelos**: Se generan automáticamente desde la estructura de la BD
5. **Tokens**: Incluyen información del rol e institución del usuario

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

Para soporte técnico o preguntas sobre la API, contacta al equipo de desarrollo.

---

**Desarrollado con ❤️ para Lectorix**
