<?php
require_once dirname(__DIR__) . '/models/Inventario.php';
require_once dirname(__DIR__) . '/models/ProyectoVida.php';
require_once dirname(__DIR__) . '/models/Necesidad.php';

class InventarioController {
    private ?PDO $db;
    private Inventario $inventarioModel;
    private ProyectoVida $proyectoModel;
    private Necesidad $necesidadModel;

    public function __construct(?PDO $db) {
        $this->db = $db;
        $this->inventarioModel = new Inventario($db);
        $this->proyectoModel = new ProyectoVida($db);
        $this->necesidadModel = new Necesidad($db);
    }

    /**
     * Obtiene el proyecto del usuario actual, retornando error si no tiene uno.
     */
    private function verificarProyecto(): ?array {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return $this->proyectoModel->buscarPorUsuarioId($_SESSION['user_id']);
    }

    /**
     * Lista todos los artículos del inventario del proyecto actual.
     */
    public function listar(): void {
        header('Content-Type: application/json');

        $proyecto = $this->verificarProyecto();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes un proyecto de vida activo.']);
            return;
        }

        try {
            $items = $this->inventarioModel->obtenerPorProyectoId($proyecto['id']);
            echo json_encode(['status' => 'success', 'data' => $items]);
        } catch (Exception $e) {
            error_log("Error al listar inventario: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error al cargar el inventario.']);
        }
    }

    /**
     * Crea un artículo en el inventario.
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
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
        $valor = isset($_POST['valor']) && $_POST['valor'] !== '' ? (float)$_POST['valor'] : null;
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
                'estado' => $estado,
                'valor' => $valor,
                'emoji' => $emoji,
                'creado_por' => $_SESSION['user_id']
            ];

            $itemId = $this->inventarioModel->crear($data);
            if ($itemId > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Artículo agregado al inventario.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo agregar el artículo.']);
            }
        } catch (Exception $e) {
            error_log("Error al crear artículo de inventario: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al agregar el artículo.']);
        }
    }

    /**
     * Actualiza un artículo del inventario.
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
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
        $valor = isset($_POST['valor']) && $_POST['valor'] !== '' ? (float)$_POST['valor'] : null;
        $emoji = isset($_POST['emoji']) && trim($_POST['emoji']) !== '' ? trim($_POST['emoji']) : null;

        if ($id <= 0 || empty($nombre)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos insuficientes para actualizar.']);
            return;
        }

        if ($cantidad < 1) {
            echo json_encode(['status' => 'error', 'message' => 'La cantidad debe ser al menos 1.']);
            return;
        }

        try {
            // Verificar pertenencia del artículo al proyecto
            $item = $this->inventarioModel->buscarPorId($id);
            if (!$item || $item['proyecto_id'] !== $proyecto['id']) {
                echo json_encode(['status' => 'error', 'message' => 'Artículo no encontrado o no pertenece a tu proyecto.']);
                return;
            }

            $data = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'cantidad' => $cantidad,
                'categoria' => $categoria,
                'estado' => $estado,
                'valor' => $valor,
                'emoji' => $emoji
            ];

            $success = $this->inventarioModel->actualizar($id, $data);
            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Artículo actualizado con éxito.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se realizaron cambios o error al actualizar.']);
            }
        } catch (Exception $e) {
            error_log("Error al actualizar artículo de inventario: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al actualizar.']);
        }
    }

    /**
     * Elimina un artículo del inventario.
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
            echo json_encode(['status' => 'error', 'message' => 'ID de artículo inválido.']);
            return;
        }

        try {
            $item = $this->inventarioModel->buscarPorId($id);
            if (!$item || $item['proyecto_id'] !== $proyecto['id']) {
                echo json_encode(['status' => 'error', 'message' => 'Artículo no encontrado o no pertenece a tu proyecto.']);
                return;
            }

            $success = $this->inventarioModel->eliminar($id);
            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Artículo eliminado del inventario.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el artículo.']);
            }
        } catch (Exception $e) {
            error_log("Error al eliminar artículo de inventario: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al eliminar.']);
        }
    }

    /**
     * Traslada un artículo del inventario de vuelta a la lista de necesidades.
     */
    public function moverANecesidades(): void {
        header('Content-Type: application/json');

        $proyecto = $this->verificarProyecto();
        if (!$proyecto) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes un proyecto de vida activo.']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de artículo inválido.']);
            return;
        }

        try {
            $item = $this->inventarioModel->buscarPorId($id);
            if (!$item || $item['proyecto_id'] !== $proyecto['id']) {
                echo json_encode(['status' => 'error', 'message' => 'Artículo no encontrado o no pertenece a tu proyecto.']);
                return;
            }

            // Iniciar transacción
            $this->db->beginTransaction();

            // 1. Crear necesidad marcada como no adquirida
            $dataNecesidad = [
                'proyecto_id' => $proyecto['id'],
                'nombre' => $item['nombre'],
                'descripcion' => $item['descripcion'] . "\n(Devuelto del inventario)",
                'cantidad' => $item['cantidad'],
                'prioridad' => 'Media',
                'adquirido' => 0,
                'presupuesto' => $item['valor'],
                'creado_por' => $_SESSION['user_id']
            ];

            $this->necesidadModel->crear($dataNecesidad);

            // 2. Eliminar del inventario
            $this->inventarioModel->eliminar($id);

            $this->db->commit();

            echo json_encode([
                'status' => 'success',
                'message' => '¡El artículo ha sido trasladado de vuelta a tu lista de necesidades!'
            ]);

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error al mover artículo a necesidades: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error interno al procesar el traslado.']);
        }
    }
}
