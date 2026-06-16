<?php
require_once dirname(__DIR__) . '/models/Necesidad.php';
require_once dirname(__DIR__) . '/models/Inventario.php';
require_once dirname(__DIR__) . '/models/ProyectoVida.php';

class NecesidadController {
    private ?PDO $db;
    private Necesidad $necesidadModel;
    private Inventario $inventarioModel;
    private ProyectoVida $proyectoModel;

    public function __construct(?PDO $db) {
        $this->db = $db;
        $this->necesidadModel = new Necesidad($db);
        $this->inventarioModel = new Inventario($db);
        $this->proyectoModel = new ProyectoVida($db);
    }

    private function verificarProyecto(): ?array {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return $this->proyectoModel->buscarPorUsuarioId($_SESSION['user_id']);
    }

    /**
     * Lista todas las necesidades del proyecto actual.
     */
    public function listar(): void {
        header('Content-Type: application/json');

        $proyecto = $this->verificarProyecto();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes un proyecto de vida activo.']);
            return;
        }

        try {
            $items = $this->necesidadModel->obtenerPorProyectoId($proyecto['id']);
            echo json_encode(['status' => 'success', 'data' => $items]);
        } catch (Exception $e) {
            error_log("Error al listar necesidades: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error al cargar la lista de necesidades.']);
        }
    }

    /**
     * Crea una nueva necesidad.
     */
    public function crear(): void {
        header('Content-Type: application/json');

        $proyecto = $this->verificarProyecto();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes un proyecto de vida activo.']);
            return;
        }

        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
        $categoria = isset($_POST['categoria']) && trim($_POST['categoria']) !== '' ? trim($_POST['categoria']) : null;
        $prioridad = isset($_POST['prioridad']) ? trim($_POST['prioridad']) : 'Media';
        $presupuesto = isset($_POST['presupuesto']) && $_POST['presupuesto'] !== '' ? (float)$_POST['presupuesto'] : null;
        $emoji = isset($_POST['emoji']) && trim($_POST['emoji']) !== '' ? trim($_POST['emoji']) : null;

        if (empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre del artículo es obligatorio.']);
            return;
        }

        if ($cantidad < 1) {
            echo json_encode(['status' => 'error', 'message' => 'La cantidad debe ser al menos 1.']);
            return;
        }

        try {
            $data = [
                'proyecto_id' => $proyecto['id'],
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'cantidad' => $cantidad,
                'categoria' => $categoria,
                'prioridad' => $prioridad,
                'adquirido' => 0,
                'presupuesto' => $presupuesto,
                'emoji' => $emoji,
                'creado_por' => $_SESSION['user_id']
            ];

            $itemId = $this->necesidadModel->crear($data);
            if ($itemId > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Artículo agregado a la lista de necesidades.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo agregar el artículo.']);
            }
        } catch (Exception $e) {
            error_log("Error al crear necesidad: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al agregar el artículo.']);
        }
    }

    /**
     * Actualiza una necesidad.
     */
    public function actualizar(): void {
        header('Content-Type: application/json');

        $proyecto = $this->verificarProyecto();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes un proyecto de vida activo.']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
        $categoria = isset($_POST['categoria']) && trim($_POST['categoria']) !== '' ? trim($_POST['categoria']) : null;
        $prioridad = isset($_POST['prioridad']) ? trim($_POST['prioridad']) : 'Media';
        $adquirido = isset($_POST['adquirido']) ? (int)$_POST['adquirido'] : 0;
        $presupuesto = isset($_POST['presupuesto']) && $_POST['presupuesto'] !== '' ? (float)$_POST['presupuesto'] : null;
        $emoji = isset($_POST['emoji']) && trim($_POST['emoji']) !== '' ? trim($_POST['emoji']) : null;

        if ($id <= 0 || empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos insuficientes para actualizar.']);
            return;
        }

        try {
            $item = $this->necesidadModel->buscarPorId($id);
            if (!$item || $item['proyecto_id'] !== $proyecto['id']) {
                echo json_encode(['status' => 'error', 'message' => 'Artículo no encontrado o no pertenece a tu proyecto.']);
                return;
            }

            $data = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'cantidad' => $cantidad,
                'categoria' => $categoria,
                'prioridad' => $prioridad,
                'adquirido' => $adquirido,
                'presupuesto' => $presupuesto,
                'emoji' => $emoji
            ];

            $success = $this->necesidadModel->actualizar($id, $data);
            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Necesidad actualizada con éxito.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se realizaron cambios o error al actualizar.']);
            }
        } catch (Exception $e) {
            error_log("Error al actualizar necesidad: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al actualizar.']);
        }
    }

    /**
     * Marca una necesidad como adquirida.
     */
    public function marcarAdquirido(): void {
        header('Content-Type: application/json');

        $proyecto = $this->verificarProyecto();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes un proyecto de vida activo.']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $adquirido = isset($_POST['adquirido']) ? (int)$_POST['adquirido'] : 0;

        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            return;
        }

        try {
            $item = $this->necesidadModel->buscarPorId($id);
            if (!$item || $item['proyecto_id'] !== $proyecto['id']) {
                echo json_encode(['status' => 'error', 'message' => 'Artículo no encontrado o no pertenece a tu proyecto.']);
                return;
            }

            $success = $this->necesidadModel->marcarAdquirido($id, $adquirido);
            if ($success) {
                $statusText = $adquirido ? 'marcado como adquirido' : 'marcado como pendiente';
                echo json_encode(['status' => 'success', 'message' => "Artículo {$statusText}."]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar el estado del artículo.']);
            }
        } catch (Exception $e) {
            error_log("Error al cambiar estado de necesidad: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor.']);
        }
    }

    /**
     * Convierte una necesidad adquirida en un artículo de inventario.
     */
    public function moverAInventario(): void {
        header('Content-Type: application/json');

        $proyecto = $this->verificarProyecto();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes un proyecto de vida activo.']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            return;
        }

        try {
            // Obtener necesidad
            $item = $this->necesidadModel->buscarPorId($id);
            if (!$item || $item['proyecto_id'] !== $proyecto['id']) {
                echo json_encode(['status' => 'error', 'message' => 'Artículo no encontrado o no pertenece a tu proyecto.']);
                return;
            }

            // Iniciar transacción para asegurar atomicidad
            $this->db->beginTransaction();

            // 1. Marcar como adquirido en necesidades
            $this->necesidadModel->marcarAdquirido($id, 1);

            $dataInventario = [
                'proyecto_id' => $proyecto['id'],
                'nombre' => $item['nombre'],
                'descripcion' => $item['descripcion'] . "\n(Mover de lista de necesidades)",
                'cantidad' => $item['cantidad'],
                'categoria' => !empty($item['categoria']) ? $item['categoria'] : 'Adquirido',
                'estado' => 'Nuevo',
                'valor' => $item['presupuesto'],
                'emoji' => $item['emoji'] ?? null,
                'creado_por' => $_SESSION['user_id']
            ];

            $this->inventarioModel->crear($dataInventario);

            // 3. Eliminar la necesidad original
            $this->necesidadModel->eliminar($id);

            $this->db->commit();

            echo json_encode([
                'status' => 'success',
                'message' => '¡El artículo ha sido adquirido y trasladado a tu inventario exitosamente!'
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error al mover artículo al inventario: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error al procesar el traslado al inventario.']);
        }
    }

    /**
     * Elimina una necesidad.
     */
    public function eliminar(): void {
        header('Content-Type: application/json');

        $proyecto = $this->verificarProyecto();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes un proyecto de vida activo.']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
            return;
        }

        try {
            $item = $this->necesidadModel->buscarPorId($id);
            if (!$item || $item['proyecto_id'] !== $proyecto['id']) {
                echo json_encode(['status' => 'error', 'message' => 'Artículo no encontrado o no pertenece a tu proyecto.']);
                return;
            }

            $success = $this->necesidadModel->eliminar($id);
            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Artículo eliminado de la lista de necesidades.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el artículo.']);
            }
        } catch (Exception $e) {
            error_log("Error al eliminar necesidad: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al eliminar.']);
        }
    }
}
