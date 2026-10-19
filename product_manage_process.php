<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();

// Validar que el usuario esté logueado y sea comercio
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'comercio') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = "Petición no válida o token de seguridad expirado (CSRF). Intenta nuevamente.";
    header("Location: index.php");
    exit;
}

$comercioId = (int)$_SESSION['usuario_id'];
$action = trim($_POST['action'] ?? '');
$productoId = (int)($_POST['producto_id'] ?? 0);

if ($productoId <= 0) {
    $_SESSION['flash_error'] = "Identificador de producto no válido.";
    header("Location: index.php");
    exit;
}

try {
    $pdo = getDBConnection();

    // Verificación Estricta de Propiedad (Mitigación IDOR / OWASP A01:2021 Broken Access Control)
    $checkStmt = $pdo->prepare("SELECT * FROM productos WHERE id = :id AND comercio_id = :cid LIMIT 1");
    $checkStmt->execute([':id' => $productoId, ':cid' => $comercioId]);
    $producto = $checkStmt->fetch();

    if (!$producto) {
        $_SESSION['flash_error'] = "El producto no existe o no tienes permisos de acceso para gestionarlo.";
        header("Location: index.php");
        exit;
    }

    if ($action === 'delete') {
        // Regla de Integridad Referencial: Verificar si hay reservas activas pendientes de entrega
        $resCheck = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE producto_id = :id AND estado = 'reservado'");
        $resCheck->execute([':id' => $productoId]);
        $pendientes = (int)$resCheck->fetchColumn();

        if ($pendientes > 0) {
            $_SESSION['flash_error'] = "No puedes eliminar una publicación que tiene {$pendientes} reserva(s) pendiente(s) de retiro en caja. Puedes pausarla para no recibir nuevas reservas.";
            header("Location: index.php");
            exit;
        }

        // Eliminar producto de forma segura
        $delStmt = $pdo->prepare("DELETE FROM productos WHERE id = :id AND comercio_id = :cid");
        $delStmt->execute([':id' => $productoId, ':cid' => $comercioId]);
        $safeTitle = htmlspecialchars($producto['titulo'], ENT_QUOTES, 'UTF-8');
        $_SESSION['flash_success'] = "La publicación '{$safeTitle}' fue eliminada correctamente.";

    } elseif ($action === 'toggle_status') {
        // Alternar estado entre disponible y agotado
        $nuevoEstado = ($producto['estado'] === 'disponible') ? 'agotado' : 'disponible';
        $toggleStmt = $pdo->prepare("UPDATE productos SET estado = :estado WHERE id = :id AND comercio_id = :cid");
        $toggleStmt->execute([':estado' => $nuevoEstado, ':id' => $productoId, ':cid' => $comercioId]);
        $_SESSION['flash_success'] = "El estado del producto fue cambiado a: " . strtoupper($nuevoEstado);

    } elseif ($action === 'edit') {
        // Desinfección y acotamiento de entradas
        $titulo = mb_substr(strip_tags(trim($_POST['titulo'] ?? '')), 0, 150);
        $descripcion = mb_substr(strip_tags(trim($_POST['descripcion'] ?? '')), 0, 1000);
        $categoria = mb_substr(strip_tags(trim($_POST['categoria'] ?? 'Panadería')), 0, 50);
        $precioOriginal = max(0, min(100000000, (float)($_POST['precio_original'] ?? 0)));
        $precioDescuento = max(0, min(100000000, (float)($_POST['precio_descuento'] ?? 0)));
        $stock = max(0, min(1000, (int)($_POST['stock'] ?? 1)));
        $horarioRetiro = mb_substr(strip_tags(trim($_POST['horario_retiro'] ?? '')), 0, 100);
        $estado = ($stock > 0) ? trim($_POST['estado'] ?? 'disponible') : 'agotado';

        if (empty($titulo) || $precioOriginal <= 0 || $precioDescuento <= 0 || $stock < 0) {
            $_SESSION['flash_error'] = "Por favor completa todos los campos obligatorios con valores numéricos válidos.";
            header("Location: index.php");
            exit;
        }

        if ($precioDescuento >= $precioOriginal) {
            $_SESSION['flash_error'] = "El precio de rescate con descuento debe ser menor al precio original.";
            header("Location: index.php");
            exit;
        }

        $editStmt = $pdo->prepare("
            UPDATE productos 
            SET titulo = :titulo, descripcion = :descripcion, categoria = :categoria,
                precio_original = :precio_original, precio_descuento = :precio_descuento,
                stock = :stock, horario_retiro = :horario_retiro, estado = :estado
            WHERE id = :id AND comercio_id = :cid
        ");

        $editStmt->execute([
            ':titulo'          => $titulo,
            ':descripcion'     => $descripcion,
            ':categoria'       => $categoria,
            ':precio_original' => $precioOriginal,
            ':precio_descuento'=> $precioDescuento,
            ':stock'           => $stock,
            ':horario_retiro'  => $horarioRetiro,
            ':estado'          => $estado,
            ':id'              => $productoId,
            ':cid'             => $comercioId
        ]);

        // Si se enviaron coordenadas en la edición, actualizarlas
        if (!empty($_POST['latitud']) && !empty($_POST['longitud'])) {
            $latRaw = (float)$_POST['latitud'];
            $lngRaw = (float)$_POST['longitud'];
            if ($latRaw >= -90 && $latRaw <= 90 && $lngRaw >= -180 && $lngRaw <= 180) {
                try {
                    $pdo->exec("ALTER TABLE productos ADD COLUMN IF NOT EXISTS `latitud` DECIMAL(10,7) DEFAULT NULL");
                    $pdo->exec("ALTER TABLE productos ADD COLUMN IF NOT EXISTS `longitud` DECIMAL(10,7) DEFAULT NULL");
                    $pdo->prepare("UPDATE productos SET latitud = :lat, longitud = :lng WHERE id = :id AND comercio_id = :cid")
                        ->execute([':lat' => round($latRaw, 7), ':lng' => round($lngRaw, 7), ':id' => $productoId, ':cid' => $comercioId]);
                } catch (Throwable $t) {
                    error_log("Error updating coords in edit: " . $t->getMessage());
                }
            }
        }

        $safeTitle = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
        $_SESSION['flash_success'] = "¡Publicación '{$safeTitle}' actualizada exitosamente!";
    } else {
        $_SESSION['flash_error'] = "Acción no reconocida.";
    }

    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    error_log("Product management error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Ocurrió un error inesperado al procesar la solicitud en la base de datos.";
    header("Location: index.php");
    exit;
}
