# Lectorix API

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![Lumen Version](https://img.shields.io/badge/Lumen-10.0+-green.svg)](https://lumen.laravel.com)
[![Composer Version](https://img.shields.io/badge/Composer-2.4.0+-red.svg)](https://getcomposer.org)
[![JWT Auth](https://img.shields.io/badge/JWT-Auth-orange.svg)](https://jwt-auth.readthedocs.io)

API REST para el sistema Lectorix, construida con Lumen y JWT Authentication. Sistema de autenticación separada que divide a los usuarios en dos paneles: uno administrativo para admin/instituciones (correo+contraseña) y otro simplificado para estudiantes (nombre+apellido+institución).

## 🚀 Características

-   **🔐 Autenticación JWT Dual** - Tokens seguros con dos sistemas de autenticación
-   **👥 Sistema de Roles Separado** - Panel admin (correo+contraseña) y panel estudiantes (nombre+apellido)
-   **🏫 Gestión de Instituciones** - Múltiples instituciones educativas
-   **🎓 Autenticación Simplificada** - Estudiantes ingresan sin contraseña
-   **🔑 Códigos Únicos** - Sistema automático para evitar duplicados entre instituciones
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

### 9. Generar códigos únicos para estudiantes existentes

```bash
php artisan estudiantes:generar-codigos
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

### Sistema de Autenticación Separada

El sistema ahora está dividido en **dos paneles independientes**:

#### 🔐 Panel Administrativo (`/api/admin/`)

**Para administradores e instituciones (correo + contraseña)**

##### 🔓 Rutas Públicas

-   `POST /admin/login` - Login de admin/institución

##### 🛡️ Rutas Protegidas (requieren token)

-   `GET /admin/me` - Información del usuario autenticado
-   `POST /admin/logout` - Cerrar sesión
-   `POST /admin/refresh` - Refrescar token
-   `POST /admin/change-password` - Cambiar contraseña

##### 👑 Rutas de Administrador

-   `POST /admin/create-user` - Crear usuario
-   `POST /admin/generate-token` - Generar token para usuario
-   `GET /admin/users` - Listar usuarios

#### 👨‍🎓 Panel de Estudiantes (`/api/students/`)

**Para estudiantes (nombre + apellido + institución)**

##### 🔓 Rutas Públicas

-   `POST /students/login` - Login de estudiante (sin contraseña)
-   `POST /students/register` - Registro de estudiante
-   `GET /students/search` - Buscar estudiantes (para sugerencias)
-   `GET /students/instituciones` - Obtener instituciones disponibles

##### 🛡️ Rutas Protegidas (requieren token de estudiante)

-   `GET /students/me` - Información del estudiante autenticado
-   `POST /students/logout` - Cerrar sesión

#### 👥 Gestión de Usuarios (Panel Admin)

-   `GET /usuarios` - Listar usuarios
-   `GET /usuarios/{id}` - Ver usuario específico
-   `PUT /usuarios/{id}` - Actualizar usuario
-   `POST /usuarios` - Crear usuario (solo admin)
-   `DELETE /usuarios/{id}` - Desactivar usuario (solo admin)

### Ejemplos de Uso

#### 🔐 Panel Administrativo

##### Login de Administrador/Institución

```bash
curl -X POST http://localhost:8000/api/admin/login \
  -H "Content-Type: application/json" \
  -d '{
    "correo": "admin@lectorix.com",
    "contrasena": "admin123"
  }'
```

##### Ver información del usuario autenticado

```bash
curl -X GET http://localhost:8000/api/admin/me \
  -H "Authorization: Bearer TU_TOKEN_JWT"
```

#### 👨‍🎓 Panel de Estudiantes

##### Registro de Estudiante (SIN contraseña)

```bash
curl -X POST http://localhost:8000/api/students/register \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan",
    "apellido": "Pérez",
    "edad": 20,
    "institucion_id": 1
  }'
```

##### Login de Estudiante (nombre + apellido + institución)

```bash
curl -X POST http://localhost:8000/api/students/login \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan",
    "apellido": "Pérez",
    "institucion_id": 1
  }'
```

##### Buscar estudiantes (para sugerencias en el frontend)

```bash
curl -X GET "http://localhost:8000/api/students/search?nombre=Juan&institucion_id=1"
```

##### Obtener instituciones disponibles

```bash
curl -X GET http://localhost:8000/api/students/instituciones
```

## 🏗️ Estructura del Proyecto

```
Lectorix_API/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── CreateAdminUser.php          # Crear admin inicial
│   │       ├── CreateInstituciones.php      # Crear instituciones
│   │       ├── GenerateModelsFromDatabase.php # Generar modelos
│   │       └── GenerarCodigosEstudiantes.php # Generar códigos únicos
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php           # Autenticación admin/instituciones
│   │   │   └── StudentAuthController.php    # Autenticación de estudiantes
│   │   └── Middleware/
│   │       └── AdminMiddleware.php          # Middleware de admin
│   └── Models/
│       ├── Usuario.php                      # Modelo con códigos únicos y JWT
│       └── Institucion.php                  # Modelo de institución
├── bootstrap/
│   └── app.php                              # Configuración de Lumen
├── config/
│   ├── auth.php                             # Configuración de autenticación
│   └── jwt.php                              # Configuración de JWT
├── routes/
│   └── web.php                              # Definición de rutas separadas
├── AUTENTICACION_SEPARADA.md                # Documentación detallada
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

# Generar códigos únicos para estudiantes
php artisan estudiantes:generar-codigos
php artisan estudiantes:generar-codigos --force
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

1. **Autenticación Separada**: Dos paneles independientes con diferentes métodos de autenticación
2. **Estudiantes Sin Contraseña**: Los estudiantes ingresan solo con nombre, apellido e institución
3. **Códigos Únicos**: Sistema automático para evitar duplicados de nombres entre instituciones
4. **Control de Acceso**: Solo administradores pueden crear usuarios y generar tokens
5. **Base de Datos**: Requiere campo `codigo_estudiante` en tabla `usuarios`
6. **Migración**: Comando disponible para generar códigos a estudiantes existentes
7. **Tokens JWT**: Mantienen compatibilidad para ambos tipos de usuario

## 🔑 Sistema de Códigos Únicos

### Formato

Los códigos de estudiante siguen el formato: `INST{ID}_NOMBRE_APELLIDO[_CONTADOR]`

### Ejemplos

-   `INST001_JUAN_PEREZ` (primer Juan Pérez en institución 1)
-   `INST001_JUAN_PEREZ_2` (segundo Juan Pérez en institución 1)
-   `INST002_JUAN_PEREZ` (Juan Pérez en institución 2)

### Migración SQL

```sql
-- Agregar campo a la tabla usuarios
ALTER TABLE usuarios ADD COLUMN codigo_estudiante VARCHAR(100) NULL;
```

### Comando de migración

```bash
# Generar códigos para estudiantes sin código
php artisan estudiantes:generar-codigos

# Regenerar todos los códigos (usar con precaución)
php artisan estudiantes:generar-codigos --force
```

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📚 Documentación Adicional

-   **[AUTENTICACION_SEPARADA.md](./AUTENTICACION_SEPARADA.md)** - Documentación detallada del nuevo sistema
-   **[AUTH_API.md](./AUTH_API.md)** - Guía de autenticación original
-   **[GUIA_PRUEBAS_API.md](./GUIA_PRUEBAS_API.md)** - Guía de pruebas de la API

## 📞 Soporte

Para soporte técnico o preguntas sobre la API, contacta al equipo de desarrollo.

---

**Desarrollado con ❤️ para Lectorix**
