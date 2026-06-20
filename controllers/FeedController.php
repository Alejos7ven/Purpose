<?php
require_once dirname(__DIR__) . '/models/FeedPost.php';
require_once dirname(__DIR__) . '/models/ProyectoVida.php';
require_once dirname(__DIR__) . '/helpers/ImageCompressor.php';

class FeedController {
    private ?PDO $db;
    private FeedPost $feedPostModel;
    private ProyectoVida $proyectoModel;

    public function __construct(?PDO $db) {
        $this->db = $db;
        $this->feedPostModel = new FeedPost($db);
        $this->proyectoModel = new ProyectoVida($db);
    }

    /**
     * Helper para verificar sesión y obtener el proyecto de vida del usuario.
     */
    private function obtenerProyectoActual(): ?array {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return $this->proyectoModel->buscarPorUsuarioId($_SESSION['user_id']);
    }

    /**
     * Lista todas las publicaciones del feed.
     */
    public function listar(): void {
        header('Content-Type: application/json');

        $proyecto = $this->obtenerProyectoActual();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado o no tienes un proyecto de vida activo.']);
            return;
        }

        try {
            $posts = $this->feedPostModel->obtenerFeed($proyecto['id']);
            echo json_encode([
                'status' => 'success',
                'data' => $posts,
                'proyecto_actual_id' => $proyecto['id']
            ]);
        } catch (Exception $e) {
            error_log("Error al listar feed: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error al cargar las publicaciones.']);
        }
    }

    /**
     * Crea una nueva publicación en el feed.
     */
    public function crear(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            return;
        }

        $proyecto = $this->obtenerProyectoActual();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        $contenido = isset($_POST['contenido']) ? trim($_POST['contenido']) : '';
        
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

        if (empty($contenido) && $fotoPath === null) {
            echo json_encode(['status' => 'error', 'message' => 'La publicación no puede estar vacía. Escribe un estado o sube una foto.']);
            return;
        }

        try {
            $data = [
                'proyecto_vida_id' => $proyecto['id'],
                'autor_usuario_id' => $_SESSION['user_id'],
                'contenido' => $contenido,
                'foto' => $fotoPath
            ];

            $postId = $this->feedPostModel->crear($data);

            if ($postId > 0) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Publicación compartida con éxito.',
                    'data' => ['id' => $postId]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo crear la publicación.']);
            }
        } catch (Exception $e) {
            error_log("Error al crear publicación: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al publicar.']);
        }
    }

    /**
     * Comparte una publicación existente.
     */
    public function compartir(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            return;
        }

        $proyecto = $this->obtenerProyectoActual();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        $sharedPostId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $contenido = isset($_POST['contenido']) ? trim($_POST['contenido']) : '';

        if ($sharedPostId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Publicación inválida para compartir.']);
            return;
        }

        // Verificar que el post original exista
        $originalPost = $this->feedPostModel->buscarPorId($sharedPostId);
        if (!$originalPost) {
            echo json_encode(['status' => 'error', 'message' => 'La publicación original ya no existe.']);
            return;
        }

        try {
            $data = [
                'proyecto_vida_id' => $proyecto['id'],
                'autor_usuario_id' => $_SESSION['user_id'],
                'contenido' => $contenido,
                'foto' => null, // Compartir no sube una nueva foto directa
                'shared_post_id' => $sharedPostId
            ];

            $postId = $this->feedPostModel->crear($data);

            if ($postId > 0) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Publicación compartida en tu feed.',
                    'data' => ['id' => $postId]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo compartir la publicación.']);
            }
        } catch (Exception $e) {
            error_log("Error al compartir publicación: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al compartir.']);
        }
    }

    /**
     * Elimina una publicación.
     */
    public function eliminar(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            return;
        }

        $proyecto = $this->obtenerProyectoActual();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        $postId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($postId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Publicación inválida.']);
            return;
        }

        try {
            // Verificar si el post tiene foto para eliminarla físicamente
            $post = $this->feedPostModel->buscarPorId($postId);
            
            $success = $this->feedPostModel->eliminar($postId, $proyecto['id']);

            if ($success) {
                if ($post && !empty($post['foto']) && file_exists(dirname(__DIR__) . '/view/' . $post['foto'])) {
                    @unlink(dirname(__DIR__) . '/view/' . $post['foto']);
                }
                echo json_encode(['status' => 'success', 'message' => 'Publicación eliminada con éxito.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar la publicación o no pertenece a tu perfil de pareja.']);
            }
        } catch (Exception $e) {
            error_log("Error al eliminar publicación: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al eliminar la publicación.']);
        }
    }

    /**
     * Agrega un comentario a una publicación.
     */
    public function comentar(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            return;
        }

        $proyecto = $this->obtenerProyectoActual();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $contenido = isset($_POST['contenido']) ? trim($_POST['contenido']) : '';

        if ($postId <= 0 || empty($contenido)) {
            echo json_encode(['status' => 'error', 'message' => 'El comentario no puede estar vacío.']);
            return;
        }

        try {
            $data = [
                'post_id' => $postId,
                'proyecto_vida_id' => $proyecto['id'],
                'autor_usuario_id' => $_SESSION['user_id'],
                'contenido' => $contenido
            ];

            $comentarioId = $this->feedPostModel->agregarComentario($data);

            if ($comentarioId > 0) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Comentario agregado con éxito.',
                    'data' => [
                        'id' => $comentarioId,
                        'proyecto_nombre' => $proyecto['nombre'],
                        'proyecto_foto' => $proyecto['foto'],
                        'autor_nombre' => $_SESSION['user_nombre']
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar el comentario.']);
            }
        } catch (Exception $e) {
            error_log("Error al comentar: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al comentar.']);
        }
    }

    /**
     * Elimina un comentario.
     */
    public function eliminarComentario(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            return;
        }

        $proyecto = $this->obtenerProyectoActual();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        $comentarioId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($comentarioId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Comentario inválido.']);
            return;
        }

        try {
            $success = $this->feedPostModel->eliminarComentario($comentarioId, $proyecto['id']);
            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Comentario eliminado con éxito.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el comentario o no tienes permisos para hacerlo.']);
            }
        } catch (Exception $e) {
            error_log("Error al eliminar comentario: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al eliminar comentario.']);
        }
    }

    /**
     * Reacciona a un post.
     */
    public function reaccionar(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
            return;
        }

        $proyecto = $this->obtenerProyectoActual();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
            return;
        }

        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
        $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'me_gusta';

        // Validar tipo de reacción
        $tiposPermitidos = ['me_gusta', 'me_encanta', 'me_divierte', 'me_asombra', 'me_entristece', 'me_enoja'];
        if (!in_array($tipo, $tiposPermitidos)) {
            $tipo = 'me_gusta';
        }

        if ($postId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Publicación inválida.']);
            return;
        }

        try {
            $resultado = $this->feedPostModel->reaccionar($postId, $proyecto['id'], $_SESSION['user_id'], $tipo);
            
            // Obtener el resumen actualizado de reacciones del post
            $resumen = $this->feedPostModel->obtenerResumenReacciones($postId);
            
            echo json_encode([
                'status' => 'success',
                'resultado' => $resultado,
                'reacciones' => $resumen,
                'reaccion_actual' => $this->feedPostModel->obtenerReaccionProyecto($postId, $proyecto['id'])
            ]);
        } catch (Exception $e) {
            error_log("Error al reaccionar: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al registrar la reacción.']);
        }
    }

    /**
     * Guarda la foto de la publicación.
     * Intenta comprimir con GD; si falla, guarda el original directamente.
     * Acepta hasta 10 MB de entrada.
     */
    private function guardarFoto(array $file): ?string {
        $uploadDir = dirname(__DIR__) . '/view/uploads/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Intentar comprimir con GD
        $fileName = ImageCompressor::comprimir(
            $file['tmp_name'],
            $uploadDir,
            'feed_post_',
            1280,
            80
        );

        if ($fileName !== null) {
            return 'uploads/' . $fileName;
        }

        // Fallback: guardar original si la compresión falla
        error_log('ImageCompressor falló - guardando original. MIME: ' . $file['type'] . ' Size: ' . $file['size']);

        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        $info = @getimagesize($file['tmp_name']);
        $realMime = $info ? $info['mime'] : $file['type'];

        if (!in_array($realMime, $allowedMimes)) {
            error_log('Tipo no permitido en fallback: ' . $realMime);
            return null;
        }

        $ext = match (true) {
            str_contains($realMime, 'jpeg'), str_contains($realMime, 'jpg') => 'jpg',
            str_contains($realMime, 'png')  => 'png',
            str_contains($realMime, 'gif')  => 'gif',
            str_contains($realMime, 'webp') => 'webp',
            default => 'jpg',
        };

        $fallbackName = uniqid('feed_post_') . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fallbackName)) {
            return 'uploads/' . $fallbackName;
        }

        return null;
    }
}
