<?php
/**
 * Script de prueba para la API de Lectorix
 * 
 * Uso: php test_api.php
 */

class ApiTester
{
    private $baseUrl = 'http://localhost:8000/api';
    private $adminToken = null;
    private $userToken = null;

    public function run()
    {
        echo "🚀 Iniciando pruebas de la API de Lectorix\n";
        echo "==========================================\n\n";

        // 1. Probar login de administrador
        $this->testAdminLogin();

        // 2. Probar creación de usuario
        $this->testCreateUser();

        // 3. Probar generación de token para usuario
        $this->testGenerateToken();

        // 4. Probar login de usuario normal
        $this->testUserLogin();

        // 5. Probar acceso a rutas protegidas
        $this->testProtectedRoutes();

        // 6. Probar control de roles
        $this->testRoleControl();

        echo "\n✅ Todas las pruebas completadas\n";
    }

    private function testAdminLogin()
    {
        echo "1. 🔐 Probando login de administrador...\n";
        
        $data = [
            'correo' => 'admin@lectorix.com',
            'contrasena' => 'admin123'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $data);
        
        if ($response && isset($response['success']) && $response['success']) {
            $this->adminToken = $response['data']['token'];
            echo "   ✅ Login de administrador exitoso\n";
            echo "   📧 Usuario: {$response['data']['usuario']['nombre']}\n";
            echo "   🔑 Rol: {$response['data']['usuario']['rol_id']}\n\n";
        } else {
            echo "   ❌ Error en login de administrador\n";
            echo "   💡 Asegúrate de tener un usuario administrador en la base de datos\n\n";
        }
    }

    private function testCreateUser()
    {
        if (!$this->adminToken) {
            echo "2. 👤 Saltando creación de usuario (no hay token de admin)\n\n";
            return;
        }

        echo "2. 👤 Probando creación de usuario...\n";
        
        $data = [
            'nombre' => 'Usuario Prueba',
            'correo' => 'usuario@prueba.com',
            'contrasena' => '123456',
            'rol_id' => 2, // Rol de usuario normal
            'institucion_id' => 1
        ];

        $response = $this->makeRequest('POST', '/auth/create-user', $data, $this->adminToken);
        
        if ($response && isset($response['success']) && $response['success']) {
            echo "   ✅ Usuario creado exitosamente\n";
            echo "   📧 Usuario: {$response['data']['usuario']['nombre']}\n";
            echo "   🔑 Rol: {$response['data']['usuario']['rol_id']}\n\n";
        } else {
            echo "   ❌ Error al crear usuario\n";
            if (isset($response['message'])) {
                echo "   📝 Error: {$response['message']}\n";
            }
            echo "\n";
        }
    }

    private function testGenerateToken()
    {
        if (!$this->adminToken) {
            echo "3. 🎫 Saltando generación de token (no hay token de admin)\n\n";
            return;
        }

        echo "3. 🎫 Probando generación de token para usuario...\n";
        
        // Primero necesitamos obtener la lista de usuarios
        $response = $this->makeRequest('GET', '/auth/users', null, $this->adminToken);
        
        if ($response && isset($response['success']) && $response['success'] && !empty($response['data']['usuarios'])) {
            $userId = $response['data']['usuarios'][0]['id'];
            
            $data = ['usuario_id' => $userId];
            $tokenResponse = $this->makeRequest('POST', '/auth/generate-token', $data, $this->adminToken);
            
            if ($tokenResponse && isset($tokenResponse['success']) && $tokenResponse['success']) {
                $this->userToken = $tokenResponse['data']['token'];
                echo "   ✅ Token generado exitosamente\n";
                echo "   👤 Para usuario: {$tokenResponse['data']['usuario']['nombre']}\n\n";
            } else {
                echo "   ❌ Error al generar token\n";
            }
        } else {
            echo "   ❌ No se pudieron obtener usuarios\n";
        }
        echo "\n";
    }

    private function testUserLogin()
    {
        echo "4. 🔐 Probando login de usuario normal...\n";
        
        $data = [
            'correo' => 'usuario@prueba.com',
            'contrasena' => '123456'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $data);
        
        if ($response && isset($response['success']) && $response['success']) {
            $this->userToken = $response['data']['token'];
            echo "   ✅ Login de usuario exitoso\n";
            echo "   📧 Usuario: {$response['data']['usuario']['nombre']}\n";
            echo "   🔑 Rol: {$response['data']['usuario']['rol_id']}\n\n";
        } else {
            echo "   ❌ Error en login de usuario\n";
            echo "   💡 Asegúrate de que el usuario existe en la base de datos\n\n";
        }
    }

    private function testProtectedRoutes()
    {
        echo "5. 🛡️ Probando rutas protegidas...\n";
        
        if ($this->userToken) {
            $response = $this->makeRequest('GET', '/auth/me', null, $this->userToken);
            if ($response && isset($response['success']) && $response['success']) {
                echo "   ✅ Ruta /me accesible con token\n";
            } else {
                echo "   ❌ Error al acceder a /me\n";
            }
        }

        if ($this->adminToken) {
            $response = $this->makeRequest('GET', '/auth/me', null, $this->adminToken);
            if ($response && isset($response['success']) && $response['success']) {
                echo "   ✅ Ruta /me accesible con token de admin\n";
            } else {
                echo "   ❌ Error al acceder a /me con admin\n";
            }
        }

        echo "\n";
    }

    private function testRoleControl()
    {
        echo "6. 🔒 Probando control de roles...\n";
        
        if ($this->userToken) {
            // Intentar acceder a rutas de admin con token de usuario normal
            $response = $this->makeRequest('GET', '/auth/users', null, $this->userToken);
            if ($response && isset($response['success']) && !$response['success'] && $response['message'] === 'No tienes permisos para ver usuarios') {
                echo "   ✅ Control de roles funcionando (usuario no puede ver lista de usuarios)\n";
            } else {
                echo "   ❌ Error en control de roles\n";
            }
        }

        if ($this->adminToken) {
            // Verificar que admin puede acceder a rutas de admin
            $response = $this->makeRequest('GET', '/auth/users', null, $this->adminToken);
            if ($response && isset($response['success']) && $response['success']) {
                echo "   ✅ Admin puede acceder a rutas de administración\n";
            } else {
                echo "   ❌ Error: Admin no puede acceder a rutas de administración\n";
            }
        }

        echo "\n";
    }

    private function makeRequest($method, $endpoint, $data = null, $token = null)
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $headers = ['Content-Type: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            return null;
        }
        
        return json_decode($response, true);
    }
}

// Ejecutar las pruebas
$tester = new ApiTester();
$tester->run(); 