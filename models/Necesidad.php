<?php
/**
 * Modelo Necesidad
 */
class Necesidad {
    private ?PDO $db;

    public function __construct(?PDO $db) {
        $this->db = $db;
    }

    /**
     * Obtiene todas las necesidades (cosas que necesitan) de un proyecto de vida.
     */
    public function obtenerPorProyectoId(int $proyectoId): array {
        if (!$this->db) return [];

        $sql = "SELECT n.*, u.nombre AS creador_nombre 
                FROM necesidades n
                JOIN usuarios u ON n.creado_por = u.id
                WHERE n.proyecto_id = :proyecto_id 
                ORDER BY n.adquirido ASC, n.prioridad DESC, n.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['proyecto_id' => $proyectoId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca una necesidad por su ID.
     */
    public function buscarPorId(int $id): ?array {
        if (!$this->db) return null;

        $sql = "SELECT * FROM necesidades WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        return $item ? $item : null;
    }

    /**
     * Registra una nueva necesidad.
     */
    public function crear(array $data): int {
        if (!$this->db) return 0;

        $sql = "INSERT INTO necesidades 
                (proyecto_id, nombre, descripcion, cantidad, categoria, prioridad, adquirido, presupuesto, emoji, creado_por)
                VALUES (:proyecto_id, :nombre, :descripcion, :cantidad, :categoria, :prioridad, :adquirido, :presupuesto, :emoji, :creado_por)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'proyecto_id' => $data['proyecto_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'cantidad' => $data['cantidad'] ?? 1,
            'categoria' => $data['categoria'] ?? null,
            'prioridad' => $data['prioridad'] ?? 'Media',
            'adquirido' => $data['adquirido'] ?? 0,
            'presupuesto' => $data['presupuesto'] ?? null,
            'emoji' => $data['emoji'] ?? null,
            'creado_por' => $data['creado_por']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza una necesidad.
     */
    public function actualizar(int $id, array $data): bool {
        if (!$this->db) return false;

        $sql = "UPDATE necesidades SET 
                nombre = :nombre,
                descripcion = :descripcion,
                cantidad = :cantidad,
                categoria = :categoria,
                prioridad = :prioridad,
                adquirido = :adquirido,
                presupuesto = :presupuesto,
                emoji = :emoji
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'cantidad' => $data['cantidad'] ?? 1,
            'categoria' => $data['categoria'] ?? null,
            'prioridad' => $data['prioridad'] ?? 'Media',
            'adquirido' => $data['adquirido'] ?? 0,
            'presupuesto' => $data['presupuesto'] ?? null,
            'emoji' => $data['emoji'] ?? null
        ]);
    }

    /**
     * Marca una necesidad como adquirida (o pendiente) y opcionalmente retorna si funcionó.
     */
    public function marcarAdquirido(int $id, int $adquirido): bool {
        if (!$this->db) return false;

        $sql = "UPDATE necesidades SET adquirido = :adquirido WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'adquirido' => $adquirido
        ]);
    }

    /**
     * Elimina una necesidad.
     */
    public function eliminar(int $id): bool {
        if (!$this->db) return false;

        $sql = "DELETE FROM necesidades WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
