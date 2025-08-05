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