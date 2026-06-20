<?php
require_once dirname(__DIR__) . '/models/ProyectoVida.php';
require_once dirname(__DIR__) . '/models/Usuario.php';
require_once dirname(__DIR__) . '/helpers/ImageCompressor.php';

class ProyectoController {
    private ?PDO $db;
    private ProyectoVida $proyectoModel;
    private Usuario $usuarioModel;

    public function __construct(?PDO $db) {
        $this->db = $db;
        $this->proyectoModel = new ProyectoVida($db);
        $this->usuarioModel = new Usuario($db);
    }

    /**
     * Obtiene el proyecto del usuario actual.
     */
    public function obtenerProyecto(): void {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada.']);
            return;
        }

        try {
            $proyecto = $this->proyectoModel->buscarPorUsuarioId($_SESSION['user_id']);
            if ($proyecto) {
                echo json_encode(['status' => 'success', 'data' => $proyecto]);
            } else {
                echo json_encode(['status' => 'success', 'data' => null, 'message' => 'Aún no tienes un proyecto de vida creado.']);
            }
        } catch (Exception $e) {
            error_log("Error al obtener proyecto: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error al cargar el proyecto de vida.']);
        }
    }

    /**
     * Obtiene las estadísticas del proyecto del usuario actual.
     */
    public function obtenerEstadisticas(): void {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada.']);
            return;
        }

        try {
            $proyecto = $this->proyectoModel->buscarPorUsuarioId($_SESSION['user_id']);
            if (!$proyecto) {
                echo json_encode(['status' => 'error', 'message' => 'No tienes un proyecto de vida activo.']);
                return;
            }

            $stats = $this->proyectoModel->obtenerEstadisticas($proyecto['id']);
            echo json_encode(['status' => 'success', 'data' => $stats]);
        } catch (Exception $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error al cargar las estadísticas del proyecto.']);
        }
    }

    /**
     * Obtiene la lista de usuarios disponibles para ser agregados como pareja.
     */
    public function obtenerUsuariosDisponibles(): void {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada.']);
            return;
        }

        try {
            $usuarios = $this->usuarioModel->obtenerTodosMenosId($_SESSION['user_id']);
            echo json_encode(['status' => 'success', 'data' => $usuarios]);
        } catch (Exception $e) {
            error_log("Error al obtener usuarios: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error al cargar la lista de usuarios.']);
        }
    }

    /**
     * Crea un nuevo proyecto de vida.
     */
    public function crearProyecto(): void {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada.']);
            return;
        }

        // Verificar si ya tiene un proyecto
        $proyectoExistente = $this->proyectoModel->buscarPorUsuarioId($_SESSION['user_id']);
        if ($proyectoExistente) {
            echo json_encode(['status' => 'error', 'message' => 'Ya participas en un proyecto de vida.']);
            return;
        }

        // Validar datos recibidos
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $fecha_noviazgo = isset($_POST['fecha_noviazgo']) ? trim($_POST['fecha_noviazgo']) : '';
        $fecha_compromiso = isset($_POST['fecha_compromiso']) ? trim($_POST['fecha_compromiso']) : '';
        $fecha_matrimonio = isset($_POST['fecha_matrimonio']) ? trim($_POST['fecha_matrimonio']) : '';
        $pareja_id = isset($_POST['pareja_id']) && $_POST['pareja_id'] !== '' ? (int)$_POST['pareja_id'] : null;

        if (empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del proyecto es obligatorio.']);
            return;
        }

        if ($pareja_id !== null && $pareja_id === $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'No puedes ser tu propia pareja en el proyecto.']);
            return;
        }

        // Manejar subida de foto
        $fotoPath = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $error = ImageCompressor::validar($_FILES['foto'], 10);
            if ($error !== null) {
                echo json_encode(['status' => 'error', 'message' => $error]);
                return;
            }
            $fotoPath = $this->guardarFoto($_FILES['foto']);
            if ($fotoPath === null) {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo procesar la imagen. Intenta con otro archivo.']);
                return;
            }
        }

        try {
            $data = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'fecha_noviazgo' => $fecha_noviazgo,
                'fecha_compromiso' => $fecha_compromiso,
                'fecha_matrimonio' => $fecha_matrimonio,
                'creador_id' => $_SESSION['user_id'],
                'pareja_id' => $pareja_id,
                'foto' => $fotoPath
            ];

            $proyectoId = $this->proyectoModel->crear($data);

            if ($proyectoId > 0) {
                echo json_encode([
                    'status' => 'success',
                    'message' => '¡Proyecto de vida creado con éxito!',
                    'data' => ['id' => $proyectoId]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo crear el proyecto de vida.']);
            }
        } catch (Exception $e) {
            error_log("Error al crear proyecto: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor al crear el proyecto.']);
        }
    }

    /**
     * Actualiza el proyecto de vida.
     */
    public function actualizarProyecto(): void {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada.']);
            return;
        }

        $proyecto = $this->proyectoModel->buscarPorUsuarioId($_SESSION['user_id']);
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'Proyecto de vida no encontrado.']);
            return;
        }

        // Validar datos
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $fecha_noviazgo = isset($_POST['fecha_noviazgo']) ? trim($_POST['fecha_noviazgo']) : '';
        $fecha_compromiso = isset($_POST['fecha_compromiso']) ? trim($_POST['fecha_compromiso']) : '';
        $fecha_matrimonio = isset($_POST['fecha_matrimonio']) ? trim($_POST['fecha_matrimonio']) : '';
        $pareja_id = isset($_POST['pareja_id']) && $_POST['pareja_id'] !== '' ? (int)$_POST['pareja_id'] : null;

        if (empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del proyecto es obligatorio.']);
            return;
        }

        if ($pareja_id !== null && $pareja_id === $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'No puedes ser tu propia pareja en el proyecto.']);
            return;
        }

        $data = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'fecha_noviazgo' => $fecha_noviazgo,
            'fecha_compromiso' => $fecha_compromiso,
            'fecha_matrimonio' => $fecha_matrimonio,
            'pareja_id' => $pareja_id
        ];

        // Manejar subida de foto si se sube una nueva
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $error = ImageCompressor::validar($_FILES['foto'], 10);
            if ($error !== null) {
                echo json_encode(['status' => 'error', 'message' => $error]);
                return;
            }
            $fotoPath = $this->guardarFoto($_FILES['foto']);
            if ($fotoPath === null) {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo procesar la imagen. Intenta con otro archivo.']);
                return;
            }
            $data['foto'] = $fotoPath;

            // Opcional: eliminar foto anterior de disco si no es la por defecto
            if (!empty($proyecto['foto']) && file_exists(dirname(__DIR__) . '/view/' . $proyecto['foto'])) {
                @unlink(dirname(__DIR__) . '/view/' . $proyecto['foto']);
            }
        }

        try {
            $success = $this->proyectoModel->actualizar($proyecto['id'], $data);
            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Proyecto de vida actualizado con éxito.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se realizaron cambios o error al actualizar.']);
            }
        } catch (Exception $e) {
            error_log("Error al actualizar proyecto: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al actualizar el proyecto.']);
        }
    }

    /**
     * Guarda, comprime y convierte la foto del proyecto a JPEG.
     * Intenta comprimir con GD; si falla, guarda el original.
     */
    private function guardarFoto(array $file): ?string {
        $uploadDir = dirname(__DIR__) . '/view/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $fileName = ImageCompressor::comprimir(
            $file['tmp_name'], $uploadDir, 'proyecto_', 900, 82
        );
        if ($fileName !== null) return 'uploads/' . $fileName;

        // Fallback
        error_log('ImageCompressor[proyecto] falló - guardando original.');
        $info = @getimagesize($file['tmp_name']);
        if (!$info) return null;
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$info['mime']] ?? 'jpg';
        $name = uniqid('proyecto_') . '.' . $ext;
        return move_uploaded_file($file['tmp_name'], $uploadDir . $name) ? 'uploads/' . $name : null;
    }
}
