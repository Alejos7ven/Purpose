<?php
/**
 * Modelo FeedPost
 * Gestiona publicaciones, comentarios y reacciones en la red social para parejas.
 */
class FeedPost {
    private ?PDO $db;

    public function __construct(?PDO $db) {
        $this->db = $db;
    }

    /**
     * Obtiene todas las publicaciones del feed, incluyendo la información de si la pareja actual reaccionó.
     */
    public function obtenerFeed(int $proyectoIdActual): array {
        if (!$this->db) return [];

        // 1. Obtener todos los posts con información del creador y del post compartido si existe
        $sql = "SELECT p.*, 
                       pv.nombre AS proyecto_nombre, 
                       pv.foto AS proyecto_foto,
                       u1.nombre AS creador_nombre,
                       u2.nombre AS pareja_nombre,
                       sp.contenido AS shared_contenido,
                       sp.foto AS shared_foto,
                       sp.created_at AS shared_created_at,
                       spv.nombre AS shared_proyecto_nombre,
                       spv.foto AS shared_proyecto_foto
                FROM feed_posts p
                JOIN proyectos_vida pv ON p.proyecto_vida_id = pv.id
                JOIN usuarios u1 ON pv.creador_id = u1.id
                LEFT JOIN usuarios u2 ON pv.pareja_id = u2.id
                LEFT JOIN feed_posts sp ON p.shared_post_id = sp.id
                LEFT JOIN proyectos_vida spv ON sp.proyecto_vida_id = spv.id
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Para cada post, adjuntar comentarios, reacciones y la reacción del proyecto actual
        foreach ($posts as &$post) {
            $postId = (int)$post['id'];
            $post['comentarios'] = $this->obtenerComentarios($postId);
            $post['reacciones'] = $this->obtenerResumenReacciones($postId);
            $post['reaccion_actual'] = $this->obtenerReaccionProyecto($postId, $proyectoIdActual);
        }

        return $posts;
    }

    /**
     * Busca un post por su ID.
     */
    public function buscarPorId(int $id): ?array {
        if (!$this->db) return null;

        $sql = "SELECT * FROM feed_posts WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $post = $stmt->fetch();
        return $post ? $post : null;
    }

    /**
     * Crea un nuevo post.
     */
    public function crear(array $data): int {
        if (!$this->db) return 0;

        $sql = "INSERT INTO feed_posts (proyecto_vida_id, autor_usuario_id, contenido, foto, shared_post_id) 
                VALUES (:proyecto_vida_id, :autor_usuario_id, :contenido, :foto, :shared_post_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'proyecto_vida_id' => $data['proyecto_vida_id'],
            'autor_usuario_id' => $data['autor_usuario_id'],
            'contenido' => $data['contenido'],
            'foto' => $data['foto'] ?? null,
            'shared_post_id' => $data['shared_post_id'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Elimina un post.
     */
    public function eliminar(int $id, int $proyectoVidaId): bool {
        if (!$this->db) return false;

        $sql = "DELETE FROM feed_posts WHERE id = :id AND proyecto_vida_id = :proyecto_vida_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'proyecto_vida_id' => $proyectoVidaId
        ]);
    }

    // ==========================================
    // COMENTARIOS
    // ==========================================

    /**
     * Obtiene los comentarios de una publicación.
     */
    public function obtenerComentarios(int $postId): array {
        if (!$this->db) return [];

        $sql = "SELECT c.*, 
                       pv.nombre AS proyecto_nombre, 
                       pv.foto AS proyecto_foto,
                       u.nombre AS autor_nombre
                FROM feed_comentarios c
                JOIN proyectos_vida pv ON c.proyecto_vida_id = pv.id
                JOIN usuarios u ON c.autor_usuario_id = u.id
                WHERE c.post_id = :post_id
                ORDER BY c.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Agrega un comentario a un post.
     */
    public function agregarComentario(array $data): int {
        if (!$this->db) return 0;

        $sql = "INSERT INTO feed_comentarios (post_id, proyecto_vida_id, autor_usuario_id, contenido) 
                VALUES (:post_id, :proyecto_vida_id, :autor_usuario_id, :contenido)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'post_id' => $data['post_id'],
            'proyecto_vida_id' => $data['proyecto_vida_id'],
            'autor_usuario_id' => $data['autor_usuario_id'],
            'contenido' => $data['contenido']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Elimina un comentario.
     */
    public function eliminarComentario(int $id, int $proyectoVidaId): bool {
        if (!$this->db) return false;

        $sql = "DELETE FROM feed_comentarios WHERE id = :id AND proyecto_vida_id = :proyecto_vida_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'proyecto_vida_id' => $proyectoVidaId
        ]);
    }

    // ==========================================
    // REACCIONES
    // ==========================================

    /**
     * Obtiene el resumen de reacciones de un post agrupadas por tipo.
     */
    public function obtenerResumenReacciones(int $postId): array {
        if (!$this->db) return [];

        $sql = "SELECT tipo, COUNT(*) as total 
                FROM feed_reacciones 
                WHERE post_id = :post_id 
                GROUP BY tipo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
        $resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totales = [];
        $totalGeneral = 0;
        foreach ($resumen as $row) {
            $totales[$row['tipo']] = (int)$row['total'];
            $totalGeneral += (int)$row['total'];
        }

        return [
            'totales' => $totales,
            'total_general' => $totalGeneral
        ];
    }

    /**
     * Obtiene la reacción del proyecto actual para un post específico.
     */
    public function obtenerReaccionProyecto(int $postId, int $proyectoVidaId): ?string {
        if (!$this->db) return null;

        $sql = "SELECT tipo FROM feed_reacciones 
                WHERE post_id = :post_id AND proyecto_vida_id = :proyecto_vida_id 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'post_id' => $postId,
            'proyecto_vida_id' => $proyectoVidaId
        ]);
        
        $tipo = $stmt->fetchColumn();
        return $tipo ? $tipo : null;
    }

    /**
     * Registra, cambia o remueve una reacción de una pareja para un post.
     */
    public function reaccionar(int $postId, int $proyectoVidaId, int $usuarioId, string $tipo): string {
        if (!$this->db) return 'error';

        // 1. Verificar si ya existe reacción para este post y proyecto
        $reaccionActual = $this->obtenerReaccionProyecto($postId, $proyectoVidaId);

        if ($reaccionActual !== null) {
            if ($reaccionActual === $tipo) {
                // Si es la misma reacción, se elimina (toggle off)
                $sql = "DELETE FROM feed_reacciones WHERE post_id = :post_id AND proyecto_vida_id = :proyecto_vida_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'post_id' => $postId,
                    'proyecto_vida_id' => $proyectoVidaId
                ]);
                return 'eliminada';
            } else {
                // Si es un tipo diferente, se actualiza
                $sql = "UPDATE feed_reacciones SET tipo = :tipo, autor_usuario_id = :autor_usuario_id 
                        WHERE post_id = :post_id AND proyecto_vida_id = :proyecto_vida_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    'tipo' => $tipo,
                    'autor_usuario_id' => $usuarioId,
                    'post_id' => $postId,
                    'proyecto_vida_id' => $proyectoVidaId
                ]);
                return 'actualizada';
            }
        } else {
            // Si no existe, se inserta una nueva
            $sql = "INSERT INTO feed_reacciones (post_id, proyecto_vida_id, autor_usuario_id, tipo) 
                    VALUES (:post_id, :proyecto_vida_id, :autor_usuario_id, :tipo)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'post_id' => $postId,
                'proyecto_vida_id' => $proyectoVidaId,
                'autor_usuario_id' => $usuarioId,
                'tipo' => $tipo
            ]);
            return 'creada';
        }
    }
}
