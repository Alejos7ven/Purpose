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
     * Obtiene todos los usuarios aprobados excepto uno específico (para el selector de pareja).
     */
    public function obtenerTodosMenosId(int $id): array {
        if (!$this->db) return [];

        $sql = "SELECT id, email, nombre FROM usuarios WHERE id <> :id AND estado = 'aprobado' ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    /**
     * Registra un nuevo usuario con estado pendiente.
     */
    public function crear(array $data): int {
        if (!$this->db) return 0;

        $sql = "INSERT INTO usuarios (email, password, estado, nombre, apellido, tlf, foto, mensaje_registro) 
                VALUES (:email, :password, :estado, :nombre, :apellido, :tlf, :foto, :mensaje_registro)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'email' => $data['email'],
            'password' => $data['password'],
            'estado' => $data['estado'] ?? 'pendiente',
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'] ?? null,
            'tlf' => $data['tlf'] ?? null,
            'foto' => $data['foto'] ?? null,
            'mensaje_registro' => $data['mensaje_registro'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza el perfil del usuario.
     */
    public function actualizarPerfil(int $id, array $data): bool {
        if (!$this->db) return false;

        $sql = "UPDATE usuarios SET 
                nombre = :nombre,
                apellido = :apellido,
                tlf = :tlf,
                foto = :foto
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'] ?? null,
            'tlf' => $data['tlf'] ?? null,
            'foto' => $data['foto'] ?? null
        ]);
    }

    /**
     * Actualiza la contraseña del usuario.
     */
    public function actualizarPassword(int $id, string $passwordHash): bool {
        if (!$this->db) return false;

        $sql = "UPDATE usuarios SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'password' => $passwordHash
        ]);
    }

    /**
     * Obtiene los usuarios con estado pendiente para aprobación.
     */
    public function obtenerPendientes(): array {
        if (!$this->db) return [];

        $sql = "SELECT id, email, nombre, apellido, tlf, mensaje_registro, created_at 
                FROM usuarios 
                WHERE estado = 'pendiente' 
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Cambia el estado de un usuario (aprobar/rechazar).
     */
    public function actualizarEstado(int $id, string $estado): bool {
        if (!$this->db) return false;

        $sql = "UPDATE usuarios SET estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'estado' => $estado
        ]);
    }
}
