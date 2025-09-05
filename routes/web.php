<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

/*
|--------------------------------------------------------------------------
| Admin Auth Routes - API Panel Administrativo
|--------------------------------------------------------------------------
|
| Rutas para autenticación de administradores e instituciones
|
*/

$router->group(['prefix' => 'api/admin'], function () use ($router) {
    // Rutas públicas (sin autenticación)
    $router->post('login', 'AuthController@login');
    
    // Rutas protegidas (requieren autenticación)
    $router->group(['middleware' => 'auth:api'], function () use ($router) {
        // Rutas para todos los usuarios autenticados (admin/instituciones)
        $router->get('me', 'AuthController@me');
        $router->post('logout', 'AuthController@logout');
        $router->post('refresh', 'AuthController@refresh');
        $router->post('change-password', 'AuthController@changePassword');
        
        // Rutas solo para administradores (rol_id = 1)
        $router->group(['middleware' => 'admin'], function () use ($router) {
            $router->post('create-user', 'AuthController@createUser');
            $router->post('generate-token', 'AuthController@generateToken');
            $router->get('users', 'AuthController@listUsers');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Student Auth Routes - API Panel de Estudiantes
|--------------------------------------------------------------------------
|
| Rutas para autenticación de estudiantes (nombre + apellido)
|
*/

$router->group(['prefix' => 'api/students'], function () use ($router) {
    // Rutas públicas (sin autenticación)
    $router->post('login', 'StudentAuthController@loginStudent');
    $router->post('register', 'StudentAuthController@registerStudent');
    $router->get('search', 'StudentAuthController@searchStudents');
    $router->get('instituciones', 'StudentAuthController@getInstituciones');
    
    // Rutas protegidas (requieren autenticación de estudiante)
    $router->group(['middleware' => 'auth:api'], function () use ($router) {
        $router->get('me', 'StudentAuthController@me');
        $router->post('logout', 'StudentAuthController@logout');
    });
});

/*
|--------------------------------------------------------------------------
| Usuario Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de usuarios - Requieren autenticación
|
*/

$router->group(['prefix' => 'api/usuarios', 'middleware' => 'auth:api'], function () use ($router) {
    // Rutas para todos los usuarios autenticados
    $router->get('/', 'UsuarioController@index');
    $router->get('/{id}', 'UsuarioController@show');
    $router->put('/{id}', 'UsuarioController@update');
    
    // Rutas solo para administradores
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'UsuarioController@store');
        $router->delete('/{id}', 'UsuarioController@disabled');
    });
});

/*
|--------------------------------------------------------------------------
| Juego Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de juegos - Requieren autenticación
|
*/

$router->group(['prefix' => 'api/juegos', 'middleware' => 'auth:api'], function () use ($router) {
    // Rutas para todos los usuarios autenticados
    $router->get('/', 'JuegoController@index');
    $router->get('/{id}', 'JuegoController@show');

    // Rutas solo para administradores
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'JuegoController@store');
    });
});

/*
|--------------------------------------------------------------------------
| Insignias Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de insignias - Requieren autenticación
|
*/

$router->group(['prefix' => 'api/insignias', 'middleware' => 'auth:api'], function () use ($router) {
    // Rutas para todos los usuarios autenticados
    $router->get('/', 'InsigniaController@index');
    $router->get('/{id}', 'InsigniaController@show');

    // Rutas solo para administradores
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'InsigniaController@store');
        $router->put('/{id}', 'InsigniaController@update');
        $router->delete('/{id}', 'InsigniaController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Usuarios Insignias Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de asignación de insignias a usuarios
|
*/

$router->group(['prefix' => 'api/usuarios-insignias', 'middleware' => 'auth:api'], function () use ($router) {
    // Rutas para todos los usuarios autenticados
    $router->get('/', 'UsuariosInsigniaController@index');
    $router->get('/usuario/{usuario_id}', 'UsuariosInsigniaController@getByUser');
    $router->get('/estadisticas/{usuario_id}', 'UsuariosInsigniaController@estadisticas');

    // Rutas solo para administradores
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'UsuariosInsigniaController@store');
        $router->delete('/{id}', 'UsuariosInsigniaController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Pruebas Lectura Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de pruebas de lectura - Requieren autenticación
|
*/

$router->group(['prefix' => 'api/pruebas-lectura', 'middleware' => 'auth:api'], function () use ($router) {
    // Rutas para todos los usuarios autenticados
    $router->get('/', 'PruebasLecturaController@index');
    $router->get('/{id}', 'PruebasLecturaController@show');
    $router->get('/nivel/{nivel}', 'PruebasLecturaController@getByNivel');
    $router->get('/diagnosticas', 'PruebasLecturaController@getDiagnosticas');

    // Rutas solo para administradores
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'PruebasLecturaController@store');
        $router->put('/{id}', 'PruebasLecturaController@update');
        $router->delete('/{id}', 'PruebasLecturaController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Asignaciones Juego Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de asignaciones de juegos a usuarios
|
*/

$router->group(['prefix' => 'api/asignaciones-juegos', 'middleware' => 'auth:api'], function () use ($router) {
    // Rutas para todos los usuarios autenticados
    $router->get('/', 'AsignacionesJuegoController@index');
    $router->get('/usuario/{usuario_id}', 'AsignacionesJuegoController@getByUser');
    $router->get('/estadisticas/{usuario_id}', 'AsignacionesJuegoController@estadisticasUsuario');
    $router->put('/completar/{id}', 'AsignacionesJuegoController@completar');

    // Rutas solo para administradores
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'AsignacionesJuegoController@store');
        $router->put('/{id}', 'AsignacionesJuegoController@update');
        $router->delete('/{id}', 'AsignacionesJuegoController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Roles Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de roles - Solo administradores
|
*/

$router->group(['prefix' => 'api/roles', 'middleware' => ['auth:api', 'admin']], function () use ($router) {
    $router->get('/', 'RolesController@index');
    $router->get('/{id}', 'RolesController@show');
    $router->post('/', 'RolesController@store');
    $router->put('/{id}', 'RolesController@update');
    $router->delete('/{id}', 'RolesController@destroy');
});

/*
|--------------------------------------------------------------------------
| Progresión Automática Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para el sistema de progresión automática estilo Duolingo
|
*/

$router->group(['prefix' => 'api/progresion', 'middleware' => 'auth:api'], function () use ($router) {
    // Rutas para todos los usuarios autenticados
    $router->post('/completar-test', 'ProgresionController@completarTest');
    $router->get('/tests-disponibles', 'ProgresionController@testsDisponibles');
    $router->get('/progreso-general', 'ProgresionController@progresoGeneral');
    $router->get('/juegos-disponibles', 'ProgresionController@juegosDisponibles');
    $router->get('/lecturas-disponibles', 'ProgresionController@lecturasDisponibles');
    $router->post('/auto-asignar-juego', 'ProgresionController@autoAsignarJuego');
});

/*
|--------------------------------------------------------------------------
| Condiciones Insignia Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de condiciones automáticas de insignias
|
*/

$router->group(['prefix' => 'api/condiciones-insignia', 'middleware' => 'auth:api'], function () use ($router) {
    // Rutas para todos los usuarios autenticados
    $router->get('/', 'CondicionesInsigniaController@index');
    $router->get('/insignia/{insignia_id}', 'CondicionesInsigniaController@getByInsignia');
    $router->get('/tipos-condiciones', 'CondicionesInsigniaController@tiposCondiciones');

    // Rutas solo para administradores
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'CondicionesInsigniaController@store');
        $router->put('/{id}', 'CondicionesInsigniaController@update');
        $router->delete('/{id}', 'CondicionesInsigniaController@destroy');
        $router->post('/predeterminadas', 'CondicionesInsigniaController@crearCondicionesPredeterminadas');
    });
});