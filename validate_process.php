<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();

// Validar que el usuario esté logueado y tenga rol de comercio
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
$codigoRetiro = mb_substr(strtoupper(trim($_POST['codigo_retiro'] ?? '')), 0, 20);
$reservaId = isset($_POST['reserva_id']) ? (int)$_POST['reserva_id'] : null;

// Protección contra ataques de fuerza bruta / adivinación de códigos de canje
$rateKey = 'validate_cashier_' . $comercioId;
if (!checkRateLimit($rateKey, 20, 60)) {
    $remaining = getRateLimitRemainingSeconds($rateKey);
    $_SESSION['flash_error'] = "Demasiados intentos de validación en caja. Bloqueado preventivamente por {$remaining} segundos.";
    header("Location: index.php");
    exit;
}

if (empty($codigoRetiro) && empty($reservaId)) {
    $_SESSION['flash_error'] = "Debes ingresar un código de retiro válido o seleccionar una reserva.";
    header("Location: index.php");
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    // Buscar la reserva con bloqueo FOR UPDATE para garantizar consistencia transaccional
    if ($reservaId) {
        $stmt = $pdo->prepare("
            SELECT r.*, p.titulo AS producto_titulo, p.precio_descuento, p.comercio_id, u.nombre AS cliente_nombre, u.telefono AS cliente_telefono
            FROM reservas r
            JOIN productos p ON r.producto_id = p.id
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.id = :rid AND p.comercio_id = :cid
            FOR UPDATE
        ");
        $stmt->execute([':rid' => $reservaId, ':cid' => $comercioId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT r.*, p.titulo AS producto_titulo, p.precio_descuento, p.comercio_id, u.nombre AS cliente_nombre, u.telefono AS cliente_telefono
            FROM reservas r
            JOIN productos p ON r.producto_id = p.id
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.codigo_retiro = :codigo AND p.comercio_id = :cid
            FOR UPDATE
        ");
        $stmt->execute([':codigo' => $codigoRetiro, ':cid' => $comercioId]);
    }

    $reserva = $stmt->fetch();

    if (!$reserva) {
        $pdo->rollBack();
        $safeCodigo = htmlspecialchars($codigoRetiro, ENT_QUOTES, 'UTF-8');
        $_SESSION['flash_error'] = "El código de retiro '{$safeCodigo}' no existe o no corresponde a publicaciones de tu comercio.";
        header("Location: index.php");
        exit;
    }

    if ($reserva['estado'] === 'retirado') {
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Aviso: Esta reserva ({$reserva['codigo_retiro']}) ya fue marcada como RETIRADA previamente.";
        header("Location: index.php");
        exit;
    }

    if ($reserva['estado'] === 'cancelado') {
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Esta reserva figura como CANCELADA y no puede ser entregada.";
        header("Location: index.php");
        exit;
    }

    // Actualización atómica de estado a 'retirado'
    $updateStmt = $pdo->prepare("
        UPDATE reservas 
        SET estado = 'retirado' 
        WHERE id = :id AND estado = 'reservado'
    ");
    $updateStmt->execute([':id' => $reserva['id']]);

    if ($updateStmt->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Alerta de concurrencia: La reserva ya fue procesada simultáneamente por otra terminal.";
        header("Location: index.php");
        exit;
    }

    $pdo->commit();

    $totalCobrado = $reserva['precio_descuento'] * $reserva['cantidad'];
    $totalFormateado = "Gs. " . number_format($totalCobrado, 0, ',', '.');
    $safeCli = htmlspecialchars($reserva['cliente_nombre'], ENT_QUOTES, 'UTF-8');
    $safeCod = htmlspecialchars($reserva['codigo_retiro'], ENT_QUOTES, 'UTF-8');

    $_SESSION['flash_success'] = "¡Entrega confirmada con éxito! Código: <strong>{$safeCod}</strong> | Cliente: <strong>{$safeCli}</strong> | Total a cobrar: <strong>{$totalFormateado}</strong>.";
    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Validate error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Ocurrió un error inesperado al validar la entrega en caja.";
    header("Location: index.php");
    exit;
}
