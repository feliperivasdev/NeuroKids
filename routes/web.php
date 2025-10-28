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
    return [
        'app' => 'NeuroKids API - Docker Dev Mode',
        'version' => $router->app->version(),
        'message' => '🎉 ¡Sincronización automática funcionando!',
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => 'development'
    ];
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
            $router->get('users/{id}', 'AuthController@showUser');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Estudiantes Auth Routes - API Panel de Estudiantes
|--------------------------------------------------------------------------
|
| Rutas para autenticación de estudiantes (nombre + apellido)
|
*/

$router->group(['prefix' => 'api/estudiantes'], function () use ($router) {
    // Rutas públicas (sin autenticación)
    $router->post('iniciar-sesion', 'EstudianteAuthController@loginStudent');
    $router->post('registro', 'EstudianteAuthController@registerStudent');
    $router->get('buscar', 'EstudianteAuthController@searchStudents');
    $router->get('instituciones', 'EstudianteAuthController@getInstituciones');
    
    // Rutas protegidas (requieren autenticación de estudiante)
    $router->group(['middleware' => 'jwt.auth'], function () use ($router) {
        $router->get('perfil', 'EstudianteAuthController@me');
        $router->post('cerrar-sesion', 'EstudianteAuthController@logout');
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
| Instituciones Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de instituciones - Requieren autenticación
|
*/

$router->group(['prefix' => 'api/instituciones', 'middleware' => 'auth:api'], function () use ($router) {
    // Rutas para todos los usuarios autenticados
    $router->get('/', 'InstitucionController@index');
    $router->get('/{id}', 'InstitucionController@show');
    
    // Rutas solo para administradores
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'InstitucionController@store');
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
| Usuarios Juegos Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de asignaciones y progreso de juegos por usuario
|
*/

$router->group(['prefix' => 'api/usuarios-juegos', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'UsuariosJuegoController@index');
    $router->get('/usuario/{usuario_id}', 'UsuariosJuegoController@getByUser');
    $router->get('/{id}', 'UsuariosJuegoController@show');
    $router->post('/progreso/{id}', 'UsuariosJuegoController@updateProgress');

    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'UsuariosJuegoController@store');
        $router->put('/{id}', 'UsuariosJuegoController@update');
        $router->delete('/{id}', 'UsuariosJuegoController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Usuarios Lecturas Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de asignaciones y progreso de lecturas por usuario
|
*/

$router->group(['prefix' => 'api/usuarios-lecturas', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'UsuariosLecturaController@index');
    $router->get('/usuario/{usuario_id}', 'UsuariosLecturaController@getByUser');
    $router->get('/{id}', 'UsuariosLecturaController@show');
    $router->post('/progreso/{id}', 'UsuariosLecturaController@updateProgress');

    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'UsuariosLecturaController@store');
        $router->put('/{id}', 'UsuariosLecturaController@update');
        $router->delete('/{id}', 'UsuariosLecturaController@destroy');
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
| Evaluaciones Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de evaluaciones
|
*/

$router->group(['prefix' => 'api/evaluaciones', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'EvaluacionController@index');
    $router->get('/{id}', 'EvaluacionController@show');
    
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'EvaluacionController@store');
        $router->put('/{id}', 'EvaluacionController@update');
        $router->delete('/{id}', 'EvaluacionController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Evaluaciones Usuario Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de evaluaciones de usuarios
|
*/

$router->group(['prefix' => 'api/evaluaciones-usuario', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'EvaluacionesUsuarioController@index');
    $router->get('/usuario/{usuario_id}', 'EvaluacionesUsuarioController@getByUser');
    
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'EvaluacionesUsuarioController@store');
        $router->put('/{id}', 'EvaluacionesUsuarioController@update');
        $router->delete('/{id}', 'EvaluacionesUsuarioController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Lecturas Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de lecturas
|
*/

$router->group(['prefix' => 'api/lecturas', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'LecturaController@index');
    $router->get('/nivel/{nivel}', 'LecturaController@getByNivel');
    $router->get('/{id}', 'LecturaController@show');
    
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'LecturaController@store');
        $router->put('/{id}', 'LecturaController@update');
        $router->delete('/{id}', 'LecturaController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Niveles de Dificultad Routes - API Protegida
|--------------------------------------------------------------------------
|
| Rutas para gestión de niveles de dificultad
|
*/

$router->group(['prefix' => 'api/niveles-dificultad', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'NivelesDificultadController@index');
    $router->get('/{id}', 'NivelesDificultadController@show');
    
    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'NivelesDificultadController@store');
        $router->put('/{id}', 'NivelesDificultadController@update');
        $router->delete('/{id}', 'NivelesDificultadController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Preguntas Evaluacion Routes - API Protegida
|--------------------------------------------------------------------------
*/

$router->group(['prefix' => 'api/preguntas-evaluacion', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'PreguntasEvaluacionController@index');
    $router->get('/{id}', 'PreguntasEvaluacionController@show');
    $router->get('/evaluacion/{evaluacion_id}', 'PreguntasEvaluacionController@getByEvaluacion');

    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'PreguntasEvaluacionController@store');
        $router->put('/{id}', 'PreguntasEvaluacionController@update');
        $router->delete('/{id}', 'PreguntasEvaluacionController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Rangos Edad Routes - API Protegida
|--------------------------------------------------------------------------
*/

$router->group(['prefix' => 'api/rangos-edad', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'RangosEdadController@index');
    $router->get('/{id}', 'RangosEdadController@show');

    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'RangosEdadController@store');
        $router->put('/{id}', 'RangosEdadController@update');
        $router->delete('/{id}', 'RangosEdadController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Respuestas Evaluacion Routes - API Protegida
|--------------------------------------------------------------------------
*/

$router->group(['prefix' => 'api/respuestas-evaluacion', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'RespuestasEvaluacionController@index');
    $router->get('/{id}', 'RespuestasEvaluacionController@show');
    $router->get('/pregunta/{pregunta_id}', 'RespuestasEvaluacionController@getByPregunta');
    $router->post('/verificar/{id}', 'RespuestasEvaluacionController@verificarRespuesta');

    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'RespuestasEvaluacionController@store');
        $router->put('/{id}', 'RespuestasEvaluacionController@update');
        $router->delete('/{id}', 'RespuestasEvaluacionController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Respuestas Lectura Routes - API Protegida
|--------------------------------------------------------------------------
*/

$router->group(['prefix' => 'api/respuestas-lectura', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'RespuestasLecturaController@index');
    $router->get('/{id}', 'RespuestasLecturaController@show');
    $router->get('/pregunta/{pregunta_id}', 'RespuestasLecturaController@getByPregunta');
    $router->post('/verificar/{id}', 'RespuestasLecturaController@verificarRespuesta');

    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'RespuestasLecturaController@store');
        $router->put('/{id}', 'RespuestasLecturaController@update');
        $router->delete('/{id}', 'RespuestasLecturaController@destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Resultados Pregunta Routes - API Protegida
|--------------------------------------------------------------------------
*/

$router->group(['prefix' => 'api/resultados-pregunta', 'middleware' => 'auth:api'], function () use ($router) {
    $router->get('/', 'ResultadosPreguntaController@index');
    $router->get('/{id}', 'ResultadosPreguntaController@show');
    $router->get('/usuario/{usuario_id}', 'ResultadosPreguntaController@getByUsuario');
    $router->get('/estadisticas/{usuario_id}', 'ResultadosPreguntaController@getEstadisticasUsuario');

    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->post('/', 'ResultadosPreguntaController@store');
        $router->put('/{id}', 'ResultadosPreguntaController@update');
        $router->delete('/{id}', 'ResultadosPreguntaController@destroy');
    });
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