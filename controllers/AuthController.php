<?php
require_once dirname(__DIR__) . '/models/Usuario.php';
require_once dirname(__DIR__) . '/helpers/ImageCompressor.php';

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
                // Verificar si la cuenta está aprobada
                $estado = $user['estado'] ?? 'pendiente';
                if ($estado !== 'aprobado') {
                    if ($estado === 'pendiente') {
                        echo json_encode(['status' => 'error', 'message' => 'Tu solicitud de registro está pendiente de aprobación por el administrador.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Tu solicitud de registro ha sido rechazada.']);
                    }
                    return;
                }
                
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

    /**
     * Registra una nueva solicitud de cuenta.
     */
    public function registrarse(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            return;
        }

        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
        $tlf = isset($_POST['tlf']) ? trim($_POST['tlf']) : '';
        $mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';

        if (empty($email) || empty($password) || empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'Los campos correo, contraseña y nombre son obligatorios.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'El formato del correo electrónico no es válido.']);
            return;
        }

        try {
            $existing = $this->usuarioModel->buscarPorEmail($email);
            if ($existing) {
                echo json_encode(['status' => 'error', 'message' => 'El correo electrónico ya se encuentra registrado.']);
                return;
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $data = [
                'email' => $email,
                'password' => $hashedPassword,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'tlf' => $tlf,
                'mensaje_registro' => $mensaje,
                'estado' => 'pendiente'
            ];

            $userId = $this->usuarioModel->crear($data);
            if ($userId > 0) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Tu solicitud de registro ha sido enviada con éxito. Estará pendiente de aprobación por el administrador.'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo procesar tu registro.']);
            }
        } catch (Exception $e) {
            error_log("Error en Registro: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error en el servidor.']);
        }
    }

    /**
     * Obtiene los datos del perfil del usuario logueado.
     */
    public function obtenerPerfil(): void {
        header('Content-Type: application/json');

        if (!self::estaAutenticado()) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        try {
            $user = $this->usuarioModel->buscarPorId($_SESSION['user_id']);
            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado.']);
                return;
            }

            unset($user['password']);

            echo json_encode(['status' => 'success', 'data' => $user]);
        } catch (Exception $e) {
            error_log("Error al obtener perfil: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al cargar perfil.']);
        }
    }

    /**
     * Actualiza el perfil del usuario logueado.
     */
    public function actualizarPerfil(): void {
        header('Content-Type: application/json');

        if (!self::estaAutenticado()) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
        $tlf = isset($_POST['tlf']) ? trim($_POST['tlf']) : '';

        if (empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre es obligatorio.']);
            return;
        }

        try {
            $user = $this->usuarioModel->buscarPorId($_SESSION['user_id']);
            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado.']);
                return;
            }

            $fotoPath = $user['foto'];
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $error = ImageCompressor::validar($_FILES['foto'], 10);
                if ($error !== null) {
                    echo json_encode(['status' => 'error', 'message' => $error]);
                    return;
                }
                $uploadedPath = $this->guardarFoto($_FILES['foto']);
                if ($uploadedPath === null) {
                    echo json_encode(['status' => 'error', 'message' => 'No se pudo procesar la imagen. Intenta con otro archivo.']);
                    return;
                }
                $fotoPath = $uploadedPath;

                if (!empty($user['foto']) && file_exists(dirname(__DIR__) . '/view/' . $user['foto'])) {
                    @unlink(dirname(__DIR__) . '/view/' . $user['foto']);
                }
            }

            $data = [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'tlf' => $tlf,
                'foto' => $fotoPath
            ];

            $success = $this->usuarioModel->actualizarPerfil($_SESSION['user_id'], $data);
            if ($success) {
                $_SESSION['user_nombre'] = $nombre;

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Perfil actualizado con éxito.',
                    'data' => [
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'tlf' => $tlf,
                        'foto' => $fotoPath
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se realizaron cambios o error al actualizar.']);
            }
        } catch (Exception $e) {
            error_log("Error al actualizar perfil: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al actualizar perfil.']);
        }
    }

    /**
     * Permite cambiar la contraseña del usuario logueado.
     */
    public function cambiarPassword(): void {
        header('Content-Type: application/json');

        if (!self::estaAutenticado()) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['status' => 'error', 'message' => 'La nueva contraseña y la confirmación no coinciden.']);
            return;
        }

        if (strlen($newPassword) < 6) {
            echo json_encode(['status' => 'error', 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
            return;
        }

        try {
            $user = $this->usuarioModel->buscarPorId($_SESSION['user_id']);
            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado.']);
                return;
            }

            if (!password_verify($currentPassword, $user['password'])) {
                echo json_encode(['status' => 'error', 'message' => 'La contraseña actual es incorrecta.']);
                return;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $success = $this->usuarioModel->actualizarPassword($_SESSION['user_id'], $hashedPassword);

            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Contraseña cambiada con éxito.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo cambiar la contraseña.']);
            }
        } catch (Exception $e) {
            error_log("Error al cambiar contraseña: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor.']);
        }
    }

    private function verificarAdmin(): bool {
        return self::estaAutenticado() && $_SESSION['user_email'] === 'demarzomonterojose@gmail.com';
    }

    /**
     * Lista las solicitudes pendientes (sólo admin).
     */
    public function listarSolicitudes(): void {
        header('Content-Type: application/json');

        if (!$this->verificarAdmin()) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        try {
            $solicitudes = $this->usuarioModel->obtenerPendientes();
            echo json_encode(['status' => 'success', 'data' => $solicitudes]);
        } catch (Exception $e) {
            error_log("Error al listar solicitudes: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error al cargar las solicitudes.']);
        }
    }

    /**
     * Aprueba o rechaza una solicitud de registro (sólo admin).
     */
    public function procesarSolicitud(): void {
        header('Content-Type: application/json');

        if (!$this->verificarAdmin()) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

        if ($id <= 0 || !in_array($accion, ['aprobar', 'rechazar'])) {
            echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
            return;
        }

        try {
            $estado = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';
            $success = $this->usuarioModel->actualizarEstado($id, $estado);

            if ($success) {
                $mensaje = ($accion === 'aprobar') ? 'Solicitud aprobada con éxito.' : 'Solicitud rechazada con éxito.';
                echo json_encode(['status' => 'success', 'message' => $mensaje]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el estado de la solicitud.']);
            }
        } catch (Exception $e) {
            error_log("Error al procesar solicitud: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al procesar solicitud.']);
        }
    }

    /**
     * Guarda, comprime y convierte la foto de perfil a JPEG.
     * Intenta comprimir con GD; si falla, guarda el original.
     */
    private function guardarFoto(array $file): ?string {
        $uploadDir = dirname(__DIR__) . '/view/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileName = ImageCompressor::comprimir(
            $file['tmp_name'], $uploadDir, 'perfil_', 512, 85
        );
        if ($fileName !== null) return 'uploads/' . $fileName;

        // Fallback
        error_log('ImageCompressor[perfil] falló - guardando original.');
        $info = @getimagesize($file['tmp_name']);
        if (!$info) return null;
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$info['mime']] ?? 'jpg';
        $name = uniqid('perfil_') . '.' . $ext;
        return move_uploaded_file($file['tmp_name'], $uploadDir . $name) ? 'uploads/' . $name : null;
    }
}
