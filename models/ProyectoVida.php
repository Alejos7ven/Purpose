<?php
/**
 * Modelo ProyectoVida
 */
class ProyectoVida {
    private ?PDO $db;

    public function __construct(?PDO $db) {
        $this->db = $db;
    }

    /**
     * Busca el proyecto de vida asociado a un usuario (como creador o como pareja).
     */
    public function buscarPorUsuarioId(int $userId): ?array {
        if (!$this->db) return null;

        $sql = "SELECT p.*, 
                       u1.nombre AS creador_nombre, u1.email AS creador_email,
                       u2.nombre AS pareja_nombre, u2.email AS pareja_email
                FROM proyectos_vida p
                JOIN usuarios u1 ON p.creador_id = u1.id
                LEFT JOIN usuarios u2 ON p.pareja_id = u2.id
                WHERE p.creador_id = :user_id1 OR p.pareja_id = :user_id2
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id1' => $userId,
            'user_id2' => $userId
        ]);

        $project = $stmt->fetch();
        return $project ? $project : null;
    }

    /**
     * Crea un nuevo proyecto de vida.
     */
    public function crear(array $data): int {
        if (!$this->db) return 0;

        $sql = "INSERT INTO proyectos_vida 
                (nombre, descripcion, foto, fecha_noviazgo, fecha_compromiso, fecha_matrimonio, creador_id, pareja_id)
                VALUES (:nombre, :descripcion, :foto, :fecha_noviazgo, :fecha_compromiso, :fecha_matrimonio, :creador_id, :pareja_id)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'foto' => $data['foto'] ?? null,
            'fecha_noviazgo' => !empty($data['fecha_noviazgo']) ? $data['fecha_noviazgo'] : null,
            'fecha_compromiso' => !empty($data['fecha_compromiso']) ? $data['fecha_compromiso'] : null,
            'fecha_matrimonio' => !empty($data['fecha_matrimonio']) ? $data['fecha_matrimonio'] : null,
            'creador_id' => $data['creador_id'],
            'pareja_id' => !empty($data['pareja_id']) ? $data['pareja_id'] : null
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza un proyecto de vida existente.
     */
    public function actualizar(int $id, array $data): bool {
        if (!$this->db) return false;

        $sql = "UPDATE proyectos_vida SET 
                nombre = :nombre,
                descripcion = :descripcion,
                fecha_noviazgo = :fecha_noviazgo,
                fecha_compromiso = :fecha_compromiso,
                fecha_matrimonio = :fecha_matrimonio,
                pareja_id = :pareja_id";
        
        $params = [
            'id' => $id,
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha_noviazgo' => !empty($data['fecha_noviazgo']) ? $data['fecha_noviazgo'] : null,
            'fecha_compromiso' => !empty($data['fecha_compromiso']) ? $data['fecha_compromiso'] : null,
            'fecha_matrimonio' => !empty($data['fecha_matrimonio']) ? $data['fecha_matrimonio'] : null,
            'pareja_id' => !empty($data['pareja_id']) ? $data['pareja_id'] : null
        ];

        // Solo actualizar foto si se proporciona una nueva
        if (isset($data['foto'])) {
            $sql .= ", foto = :foto";
            $params['foto'] = $data['foto'];
        }

        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Elimina un proyecto de vida.
     */
    public function eliminar(int $id): bool {
        if (!$this->db) return false;

        $sql = "DELETE FROM proyectos_vida WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Obtiene las estadísticas generales del proyecto de vida.
     */
    public function obtenerEstadisticas(int $proyectoId): array {
        if (!$this->db) return [];

        // 1. Inversión Total
        $sqlInversion = "SELECT SUM(cantidad * IFNULL(valor, 0)) AS total FROM inventario WHERE proyecto_id = :proyecto_id";
        $stmt = $this->db->prepare($sqlInversion);
        $stmt->execute(['proyecto_id' => $proyectoId]);
        $totalInversion = (float)($stmt->fetchColumn() ?: 0);

        // 2. Presupuesto Pendiente
        $sqlPresupuesto = "SELECT SUM(cantidad * IFNULL(presupuesto, 0)) AS total FROM necesidades WHERE proyecto_id = :proyecto_id AND adquirido = 0";
        $stmt = $this->db->prepare($sqlPresupuesto);
        $stmt->execute(['proyecto_id' => $proyectoId]);
        $totalPresupuesto = (float)($stmt->fetchColumn() ?: 0);

        // 3. Compras por mes (cantidad de compras e inversión agrupadas por mes)
        $sqlMes = "SELECT 
                       DATE_FORMAT(created_at, '%Y-%m') AS mes,
                       COUNT(id) AS cantidad_articulos,
                       SUM(cantidad) AS cantidad_total,
                       SUM(cantidad * IFNULL(valor, 0)) AS total_inversion
                   FROM inventario
                   WHERE proyecto_id = :proyecto_id
                   GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                   ORDER BY mes ASC";
        $stmt = $this->db->prepare($sqlMes);
        $stmt->execute(['proyecto_id' => $proyectoId]);
        $comprasMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Necesidades por Prioridad
        $sqlPrioridad = "SELECT 
                             prioridad,
                             COUNT(id) AS cantidad,
                             SUM(cantidad * IFNULL(presupuesto, 0)) AS presupuesto_total
                         FROM necesidades
                         WHERE proyecto_id = :proyecto_id AND adquirido = 0
                         GROUP BY prioridad";
        $stmt = $this->db->prepare($sqlPrioridad);
        $stmt->execute(['proyecto_id' => $proyectoId]);
        $necesidadesPrioridad = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Inventario por Categoría
        $sqlCategoria = "SELECT 
                             IFNULL(NULLIF(categoria, ''), 'Sin Categoría') AS categoria,
                             COUNT(id) AS cantidad,
                             SUM(cantidad * IFNULL(valor, 0)) AS inversion_total
                         FROM inventario
                         WHERE proyecto_id = :proyecto_id
                         GROUP BY categoria";
        $stmt = $this->db->prepare($sqlCategoria);
        $stmt->execute(['proyecto_id' => $proyectoId]);
        $inventarioCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5b. Necesidades por Categoría (Nuevo)
        $sqlNecesidadesCategoria = "SELECT 
                                        IFNULL(NULLIF(categoria, ''), 'Sin Categoría') AS categoria,
                                        COUNT(id) AS cantidad,
                                        SUM(cantidad * IFNULL(presupuesto, 0)) AS presupuesto_total
                                    FROM necesidades
                                    WHERE proyecto_id = :proyecto_id AND adquirido = 0
                                    GROUP BY categoria";
        $stmt = $this->db->prepare($sqlNecesidadesCategoria);
        $stmt->execute(['proyecto_id' => $proyectoId]);
        $necesidadesCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. Resumen de cantidades para progreso
        $sqlResumen = "SELECT 
                           (SELECT SUM(cantidad) FROM inventario WHERE proyecto_id = :proyecto_id1) AS total_adquirido,
                           (SELECT SUM(cantidad) FROM necesidades WHERE proyecto_id = :proyecto_id2 AND adquirido = 0) AS total_pendiente";
        $stmt = $this->db->prepare($sqlResumen);
        $stmt->execute([
            'proyecto_id1' => $proyectoId,
            'proyecto_id2' => $proyectoId
        ]);
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalAdquirido = (int)($resumen['total_adquirido'] ?? 0);
        $totalPendiente = (int)($resumen['total_pendiente'] ?? 0);
        $totalItems = $totalAdquirido + $totalPendiente;
        $porcentajeProgreso = $totalItems > 0 ? round(($totalAdquirido / $totalItems) * 100, 1) : 0;

        return [
            'total_inversion' => $totalInversion,
            'total_presupuesto' => $totalPresupuesto,
            'compras_mes' => $comprasMes,
            'necesidades_prioridad' => $necesidadesPrioridad,
            'inventario_categoria' => $inventarioCategoria,
            'necesidades_categoria' => $necesidadesCategoria,
            'progreso' => [
                'adquirido' => $totalAdquirido,
                'pendiente' => $totalPendiente,
                'total' => $totalItems,
                'porcentaje' => $porcentajeProgreso
            ]
        ];
    }
}

