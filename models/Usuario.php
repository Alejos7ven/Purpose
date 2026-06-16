<?php
/**
 * Modelo Usuario
 */
class Usuario {
    private ?PDO $db;

    public ?int $id = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $nombre = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(?PDO $db) {
        $this->db = $db;
    }

    /**
     * Busca un usuario por su email.
     */
    public function buscarPorEmail(string $email): ?array {
        if (!$this->db) return null;
        
        $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        
        $user = $stmt->fetch();
        return $user ? $user : null;
    }

    /**
     * Busca un usuario por su ID.
     */
    public function buscarPorId(int $id): ?array {
        if (!$this->db) return null;

        $sql = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch();
        return $user ? $user : null;
    }

    /**
     * Obtiene todos los usuarios excepto uno específico (para el selector de pareja).
     */
    public function obtenerTodosMenosId(int $id): array {
        if (!$this->db) return [];

        $sql = "SELECT id, email, nombre FROM usuarios WHERE id <> :id ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }
}
