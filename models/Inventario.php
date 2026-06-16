<?php
/**
 * Modelo Inventario
 */
class Inventario {
    private ?PDO $db;

    public function __construct(?PDO $db) {
        $this->db = $db;
    }

    /**
     * Obtiene todos los artículos de inventario de un proyecto de vida.
     */
    public function obtenerPorProyectoId(int $proyectoId): array {
        if (!$this->db) return [];

        $sql = "SELECT i.*, u.nombre AS creador_nombre 
                FROM inventario i
                JOIN usuarios u ON i.creado_por = u.id
                WHERE i.proyecto_id = :proyecto_id 
                ORDER BY i.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['proyecto_id' => $proyectoId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca un artículo por su ID.
     */
    public function buscarPorId(int $id): ?array {
        if (!$this->db) return null;

        $sql = "SELECT * FROM inventario WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();
        return $item ? $item : null;
    }

    /**
     * Registra un nuevo artículo en el inventario.
     */
    public function crear(array $data): int {
        if (!$this->db) return 0;

        $sql = "INSERT INTO inventario 
                (proyecto_id, nombre, descripcion, cantidad, categoria, estado, valor, emoji, creado_por)
                VALUES (:proyecto_id, :nombre, :descripcion, :cantidad, :categoria, :estado, :valor, :emoji, :creado_por)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'proyecto_id' => $data['proyecto_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'cantidad' => $data['cantidad'] ?? 1,
            'categoria' => $data['categoria'] ?? null,
            'estado' => $data['estado'] ?? null,
            'valor' => $data['valor'] ?? null,
            'emoji' => $data['emoji'] ?? null,
            'creado_por' => $data['creado_por']
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza un artículo del inventario.
     */
    public function actualizar(int $id, array $data): bool {
        if (!$this->db) return false;

        $sql = "UPDATE inventario SET 
                nombre = :nombre,
                descripcion = :descripcion,
                cantidad = :cantidad,
                categoria = :categoria,
                estado = :estado,
                valor = :valor,
                emoji = :emoji
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'cantidad' => $data['cantidad'] ?? 1,
            'categoria' => $data['categoria'] ?? null,
            'estado' => $data['estado'] ?? null,
            'valor' => $data['valor'] ?? null,
            'emoji' => $data['emoji'] ?? null
        ]);
    }

    /**
     * Elimina un artículo del inventario.
     */
    public function eliminar(int $id): bool {
        if (!$this->db) return false;

        $sql = "DELETE FROM inventario WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
