<?php
require_once dirname(__DIR__) . '/models/Usuario.php';

class AuthController {
    private ?PDO $db;
    private Usuario $usuarioModel;

    public function __construct(?PDO $db) {
        $this->db = $db;
        $this->usuarioModel = new Usuario($db);
    }

    /**
     * Procesa la solicitud de login.
     */
    public function login(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            return;
        }

        // Validar variables
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($email) || empty($password)) {
            echo json_encode(['status' => 'error', 'message' => 'Por favor, ingrese email y contraseña.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato del correo electrónico no es válido.']);
            return;
        }

        try {
            $user = $this->usuarioModel->buscarPorEmail($email);

            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado o contraseña incorrecta.']);
                return;
            }

            // Verificar contraseña
            if (password_verify($password, $user['password'])) {
                // Login exitoso, guardar sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_nombre'] = $user['nombre'];

                echo json_encode([
                    'status' => 'success',
                    'message' => '¡Bienvenido(a), ' . htmlspecialchars($user['nombre']) . '!',
                    'data' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'nombre' => $user['nombre']
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado o contraseña incorrecta.']);
            }
        } catch (Exception $e) {
            error_log("Error en Login: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error en el servidor.']);
        }
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(): void {
        // Limpiar variables de sesión
        $_SESSION = [];

        // Destruir cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destruir sesión
        session_destroy();

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * Verifica si el usuario está autenticado.
     */
    public static function estaAutenticado(): bool {
        return isset($_SESSION['user_id']);
    }
}
