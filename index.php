<?php
// Iniciar sesión con configuraciones de seguridad
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ProyectoController.php';
require_once __DIR__ . '/controllers/InventarioController.php';
require_once __DIR__ . '/controllers/NecesidadController.php';

// Inicializar base de datos
$dbClass = new Database();
$db = $dbClass->getConnection();

// Analizar la URL solicitada
$requestUri = $_SERVER['REQUEST_URI'];
// Remover subcarpeta si está en subdirectorio como /proposito/
$basePath = '/proposito';
if (str_starts_with($requestUri, $basePath)) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Remover parámetros query (?param=value)
$routePath = parse_url($requestUri, PHP_URL_PATH);
$routePath = '/' . trim($routePath, '/');

// Enrutador de API y Vistas
try {
    if (!AuthController::estaAutenticado()) {
        // Rutas públicas permitidas
        if ($routePath === '/api/login') {
            $controller = new AuthController($db);
            $controller->login();
        } else {
            // Si no está autenticado y pide API, retornar error JSON
            if (str_starts_with($routePath, '/api/')) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'No autorizado. Debe iniciar sesión.']);
            } else {
                // Para cualquier otra vista, forzar el login
                require_once __DIR__ . '/view/login.php';
            }
        }
        exit;
    }

    // Rutas protegidas (Usuario autenticado)
    switch ($routePath) {
        // Vistas
        case '/':
        case '/dashboard':
            require_once __DIR__ . '/view/dashboard.php';
            break;

        // API Autenticación
        case '/api/logout':
            $controller = new AuthController($db);
            $controller->logout();
            break;

        // API Proyecto de Vida
        case '/api/proyecto':
            $controller = new ProyectoController($db);
            $controller->obtenerProyecto();
            break;

        case '/api/proyecto/estadisticas':
            $controller = new ProyectoController($db);
            $controller->obtenerEstadisticas();
            break;

        case '/api/proyecto/crear':
            $controller = new ProyectoController($db);
            $controller->crearProyecto();
            break;

        case '/api/proyecto/actualizar':
            $controller = new ProyectoController($db);
            $controller->actualizarProyecto();
            break;

        case '/api/proyecto/parejas':
            $controller = new ProyectoController($db);
            $controller->obtenerUsuariosDisponibles();
            break;

        // API Inventario
        case '/api/inventario':
            $controller = new InventarioController($db);
            $controller->listar();
            break;

        case '/api/inventario/crear':
            $controller = new InventarioController($db);
            $controller->crear();
            break;

        case '/api/inventario/actualizar':
            $controller = new InventarioController($db);
            $controller->actualizar();
            break;

        case '/api/inventario/eliminar':
            $controller = new InventarioController($db);
            $controller->eliminar();
            break;

        case '/api/inventario/mover_necesidades':
            $controller = new InventarioController($db);
            $controller->moverANecesidades();
            break;

        // API Necesidades
        case '/api/necesidades':
            $controller = new NecesidadController($db);
            $controller->listar();
            break;

        case '/api/necesidades/crear':
            $controller = new NecesidadController($db);
            $controller->crear();
            break;

        case '/api/necesidades/actualizar':
            $controller = new NecesidadController($db);
            $controller->actualizar();
            break;

        case '/api/necesidades/marcar_adquirido':
            $controller = new NecesidadController($db);
            $controller->marcarAdquirido();
            break;

        case '/api/necesidades/mover_inventario':
            $controller = new NecesidadController($db);
            $controller->moverAInventario();
            break;

        case '/api/necesidades/eliminar':
            $controller = new NecesidadController($db);
            $controller->eliminar();
            break;

        default:
            // Si la ruta no se encuentra, redirigir al dashboard
            header('Location: ' . $basePath . '/');
            break;
    }
} catch (Exception $e) {
    error_log("Error de enrutamiento: " . $e->getMessage());
    if (str_starts_with($routePath, '/api/')) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error en el servidor.']);
    } else {
        echo "<h1>Error 500</h1><p>Ocurrió un error inesperado. Por favor intente más tarde.</p>";
    }
}
