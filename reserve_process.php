<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();

// Validar que el usuario sea cliente
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
$productoId = (int)($_POST['producto_id'] ?? 0);
// Límites estrictos de cantidad (mínimo 1, máximo 10 por pedido)
$cantidad = max(1, min(10, (int)($_POST['cantidad'] ?? 1)));

// Mitigación de Acaparamiento y Bots (Rate Limiting de Reservas)
$rateKey = 'reserve_user_' . $usuarioId;
if (!checkRateLimit($rateKey, 10, 60)) {
    $remaining = getRateLimitRemainingSeconds($rateKey);
    $_SESSION['flash_error'] = "Has alcanzado el límite temporal de reservas. Por favor espera {$remaining} segundos.";
    header("Location: index.php");
    exit;
}

if ($productoId <= 0) {
    $_SESSION['flash_error'] = "Producto no válido.";
    header("Location: index.php");
    exit;
}

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    // Consultar stock y estado del producto con bloqueo pesimista FOR UPDATE para evitar sobre-reservas (Race Conditions)
    $prodStmt = $pdo->prepare("
        SELECT id, titulo, stock, estado, precio_descuento, comercio_id 
        FROM productos 
        WHERE id = :id 
        FOR UPDATE
    ");
    $prodStmt->execute([':id' => $productoId]);
    $producto = $prodStmt->fetch();

    if (!$producto || $producto['estado'] !== 'disponible' || $producto['stock'] < $cantidad) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Lo sentimos, el producto no está disponible o el stock seleccionado ya fue reservado.";
        header("Location: index.php");
        exit;
    }

    // Regla de Negocio: Un comercio no puede auto-reservar sus propios productos
    if ((int)$producto['comercio_id'] === $usuarioId) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = "No puedes reservar tus propias publicaciones comerciales.";
        header("Location: index.php");
        exit;
    }

    // Generar código de retiro criptográficamente seguro (16.7 millones de combinaciones)
    $codigoRetiro = 'FR-' . strtoupper(bin2hex(random_bytes(3)));

    // Crear la reserva
    $reservaStmt = $pdo->prepare("
        INSERT INTO reservas (usuario_id, producto_id, cantidad, codigo_retiro, estado)
        VALUES (:usuario_id, :producto_id, :cantidad, :codigo_retiro, 'reservado')
    ");
    $reservaStmt->execute([
        ':usuario_id'    => $usuarioId,
        ':producto_id'   => $productoId,
        ':cantidad'      => $cantidad,
        ':codigo_retiro' => $codigoRetiro
    ]);

    // Actualizar stock del producto
    $nuevoStock = $producto['stock'] - $cantidad;
    $nuevoEstado = ($nuevoStock <= 0) ? 'agotado' : 'disponible';

    $updateProdStmt = $pdo->prepare("
        UPDATE productos 
        SET stock = :stock, estado = :estado 
        WHERE id = :id
    ");
    $updateProdStmt->execute([
        ':stock'  => $nuevoStock,
        ':estado' => $nuevoEstado,
        ':id'     => $productoId
    ]);

    // Actualizar Puntos de Lealtad del Cliente (10 puntos por cada reserva realizada)
    $puntosGanados = 10 * $cantidad;
    $puntosStmt = $pdo->prepare("
        INSERT INTO puntos_lealtad (usuario_id, total_compras, puntos, descuento_desbloqueado, nivel)
        VALUES (:usuario_id, 1, :puntos, 0, 'Bronce')
        ON DUPLICATE KEY UPDATE 
            total_compras = total_compras + 1,
            puntos = puntos + :puntos_sum,
            nivel = CASE 
                WHEN (puntos + :puntos_calc) >= 300 THEN 'Oro'
                WHEN (puntos + :puntos_calc2) >= 100 THEN 'Plata'
                ELSE 'Bronce'
            END,
            descuento_desbloqueado = CASE 
                WHEN (puntos + :puntos_calc3) >= 100 THEN 1 
                ELSE 0 
            END
    ");
    $puntosStmt->execute([
        ':usuario_id'    => $usuarioId,
        ':puntos'        => $puntosGanados,
        ':puntos_sum'    => $puntosGanados,
        ':puntos_calc'   => $puntosGanados,
        ':puntos_calc2'  => $puntosGanados,
        ':puntos_calc3'  => $puntosGanados
    ]);

    $pdo->commit();

    $safeCod = htmlspecialchars($codigoRetiro, ENT_QUOTES, 'UTF-8');
    $_SESSION['flash_success'] = "¡Reserva confirmada con éxito! Tu código de retiro es <strong>{$safeCod}</strong>. ¡Gracias por salvar comida!";
    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Reservation error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Ocurrió un error inesperado al procesar la reserva. Inténtalo de nuevo.";
    header("Location: index.php");
    exit;
}
