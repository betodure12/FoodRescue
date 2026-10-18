<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();

// Validar que el usuario esté logueado y sea cliente
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'cliente') {
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

$usuarioId = (int)$_SESSION['usuario_id'];
$reservaId = (int)($_POST['reserva_id'] ?? 0);
$calificacion = (int)($_POST['calificacion'] ?? 5);
$comentario = mb_substr(strip_tags(trim($_POST['comentario'] ?? '')), 0, 500);

// Mitigación de Spam de Calificaciones (Rate Limiting)
$rateKey = 'review_user_' . $usuarioId;
if (!checkRateLimit($rateKey, 15, 60)) {
    $remaining = getRateLimitRemainingSeconds($rateKey);
    $_SESSION['flash_error'] = "Has enviado múltiples calificaciones en poco tiempo. Por favor espera {$remaining} segundos.";
    header("Location: index.php");
    exit;
}

if ($reservaId <= 0 || $calificacion < 1 || $calificacion > 5) {
    $_SESSION['flash_error'] = "Por favor selecciona una calificación válida entre 1 y 5 estrellas.";
    header("Location: index.php");
    exit;
}

try {
    $pdo = getDBConnection();

    // Asegurar tabla de reseñas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `resenas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `reserva_id` INT NOT NULL UNIQUE,
            `usuario_id` INT NOT NULL,
            `comercio_id` INT NOT NULL,
            `producto_id` INT NOT NULL,
            `calificacion` INT NOT NULL,
            `comentario` TEXT NULL,
            `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Verificar que la reserva pertenezca al usuario y esté en estado 'retirado'
    $stmt = $pdo->prepare("
        SELECT r.id, r.producto_id, p.comercio_id, r.estado
        FROM reservas r
        JOIN productos p ON r.producto_id = p.id
        WHERE r.id = :rid AND r.usuario_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':rid' => $reservaId, ':uid' => $usuarioId]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        $_SESSION['flash_error'] = "La reserva indicada no existe o no pertenece a tu cuenta.";
        header("Location: index.php");
        exit;
    }

    if ($reserva['estado'] !== 'retirado') {
        $_SESSION['flash_error'] = "Solo puedes calificar productos que ya hayas retirado en el local.";
        header("Location: index.php");
        exit;
    }

    // Insertar o actualizar la reseña
    $insertStmt = $pdo->prepare("
        INSERT INTO resenas (reserva_id, usuario_id, comercio_id, producto_id, calificacion, comentario)
        VALUES (:reserva_id, :usuario_id, :comercio_id, :producto_id, :calificacion, :comentario)
        ON DUPLICATE KEY UPDATE 
            calificacion = :calificacion_update,
            comentario = :comentario_update,
            fecha_creacion = NOW()
    ");

    $insertStmt->execute([
        ':reserva_id'          => $reservaId,
        ':usuario_id'          => $usuarioId,
        ':comercio_id'         => $reserva['comercio_id'],
        ':producto_id'         => $reserva['producto_id'],
        ':calificacion'        => $calificacion,
        ':comentario'          => $comentario,
        ':calificacion_update' => $calificacion,
        ':comentario_update'   => $comentario
    ]);

    $_SESSION['flash_success'] = "¡Muchas gracias por calificar tu rescate! Tu opinión (⭐ {$calificacion}/5) ayuda a la comunidad y apoya al comercio.";
    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    error_log("Review error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Ocurrió un error inesperado al registrar la calificación.";
    header("Location: index.php");
    exit;
}
