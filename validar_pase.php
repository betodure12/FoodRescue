<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();
sendSecurityHeaders();

$pageTitle = "Verificación de Pase Digital";
$pdo = getDBConnection();

$codigo = mb_substr(strtoupper(trim($_GET['codigo'] ?? $_POST['codigo'] ?? '')), 0, 20);
$mensajeAccion = null;
$tipoMensaje = null;

// Manejo de confirmación de entrega desde esta pantalla (por el comercio o cajero)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'confirmar_entrega') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $mensajeAccion = "Sesión o token de seguridad expirado. Por favor, recarga la página.";
        $tipoMensaje = "danger";
    } else {
        $reservaIdPost = (int)($_POST['reserva_id'] ?? 0);
        $codigoPost = mb_substr(strtoupper(trim($_POST['codigo_retiro'] ?? '')), 0, 20);

        try {
            $pdo->beginTransaction();

            $checkStmt = $pdo->prepare("
                SELECT r.*, p.titulo AS producto_titulo, p.precio_descuento, p.comercio_id,
                       u_com.nombre AS comercio_nombre, u_cli.nombre AS cliente_nombre
                FROM reservas r
                JOIN productos p ON r.producto_id = p.id
                JOIN usuarios u_com ON p.comercio_id = u_com.id
                JOIN usuarios u_cli ON r.usuario_id = u_cli.id
                WHERE r.id = :id AND r.codigo_retiro = :codigo
                FOR UPDATE
            ");
            $checkStmt->execute([':id' => $reservaIdPost, ':codigo' => $codigoPost]);
            $resCurrent = $checkStmt->fetch();

            if (!$resCurrent) {
                $pdo->rollBack();
                $mensajeAccion = "La reserva solicitada no existe.";
                $tipoMensaje = "danger";
            } elseif ($resCurrent['estado'] === 'retirado') {
                $pdo->rollBack();
                $mensajeAccion = "Esta reserva ya fue canjeada previamente.";
                $tipoMensaje = "warning";
            } else {
                // Actualizar estado a retirado
                $upd = $pdo->prepare("UPDATE reservas SET estado = 'retirado' WHERE id = :id AND estado = 'reservado'");
                $upd->execute([':id' => $resCurrent['id']]);

                // Asignar puntos de lealtad al cliente (+10 pts)
                $puntosStmt = $pdo->prepare("
                    INSERT INTO puntos_lealtad (usuario_id, total_compras, puntos, nivel)
                    VALUES (:uid, 1, 10, 'Bronce')
                    ON DUPLICATE KEY UPDATE 
                        total_compras = total_compras + 1,
                        puntos = puntos + 10,
                        nivel = CASE 
                            WHEN puntos + 10 >= 200 THEN 'Oro'
                            WHEN puntos + 10 >= 80 THEN 'Plata'
                            ELSE 'Bronce'
                        END,
                        descuento_desbloqueado = CASE 
                            WHEN puntos + 10 >= 80 THEN 1 
                            ELSE descuento_desbloqueado 
                        END
                ");
                $puntosStmt->execute([':uid' => $resCurrent['usuario_id']]);

                $pdo->commit();
                $mensajeAccion = "¡Entrega confirmada y registrada exitosamente en caja!";
                $tipoMensaje = "success";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $mensajeAccion = "Ocurrió un error al procesar el canje: " . $e->getMessage();
            $tipoMensaje = "danger";
        }
    }
}

// Consultar los datos del pase
$reserva = null;
if (!empty($codigo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, 
                   p.titulo AS producto_titulo, p.descripcion AS producto_descripcion,
                   p.precio_original, p.precio_descuento, p.horario_retiro, p.categoria,
                   u_com.id AS comercio_id, u_com.nombre AS comercio_nombre, u_com.direccion AS comercio_direccion, u_com.telefono AS comercio_telefono,
                   u_cli.id AS cliente_id, u_cli.nombre AS cliente_nombre, u_cli.telefono AS cliente_telefono, u_cli.email AS cliente_email
            FROM reservas r
            JOIN productos p ON r.producto_id = p.id
            JOIN usuarios u_com ON p.comercio_id = u_com.id
            JOIN usuarios u_cli ON r.usuario_id = u_cli.id
            WHERE r.codigo_retiro = :codigo
            LIMIT 1
        ");
        $stmt->execute([':codigo' => $codigo]);
        $reserva = $stmt->fetch();
    } catch (PDOException $e) {
        $errorDB = $e->getMessage();
    }
}

$isLoggedIn = isset($_SESSION['usuario_id']);
$userRole = $_SESSION['usuario_rol'] ?? null;
$userId = $_SESSION['usuario_id'] ?? null;
$isComercioDuenio = ($isLoggedIn && $userRole === 'comercio' && $reserva && (int)$reserva['comercio_id'] === (int)$userId);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <!-- Mensaje de acción si hubo confirmación -->
            <?php if ($mensajeAccion): ?>
                <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show rounded-4 shadow-sm mb-4 border-0" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi <?= $tipoMensaje === 'success' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger' ?> fs-3 me-3"></i>
                        <div>
                            <strong><?= $tipoMensaje === 'success' ? '¡Operación Exitosa!' : 'Aviso del Sistema' ?></strong>
                            <div><?= htmlspecialchars($mensajeAccion) ?></div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!$reserva): ?>
                <!-- Caso: Código No Encontrado o Inválido -->
                <div class="card fr-card border-0 shadow-sm p-4 text-center">
                    <div class="stat-icon bg-danger-subtle text-danger mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.8rem;">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Pase No Encontrado</h4>
                    <p class="text-muted small mb-4">
                        El código <code><?= htmlspecialchars($codigo ?: 'VACÍO') ?></code> no corresponde a ninguna reserva registrada en FoodRescue.
                    </p>

                    <form action="validar_pase.php" method="GET" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="codigo" class="form-control font-monospace text-uppercase text-center fw-bold" placeholder="Ingresa código (ej. FR-AC46A5)" required>
                            <button class="btn btn-fr-primary px-3" type="submit">
                                <i class="bi bi-search me-1"></i> Verificar
                            </button>
                        </div>
                    </form>

                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="index.php" class="btn btn-outline-secondary rounded-pill btn-sm px-3">
                            <i class="bi bi-arrow-left me-1"></i> Ir al Menú Principal
                        </a>
                        <?php if (!$isLoggedIn): ?>
                            <a href="login.php" class="btn btn-outline-success rounded-pill btn-sm px-3 fw-medium">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: 
                $isRetirado = ($reserva['estado'] === 'retirado');
                $isCancelado = ($reserva['estado'] === 'cancelado');
                $totalPagar = (float)$reserva['precio_descuento'] * (int)$reserva['cantidad'];
            ?>
                <!-- Caso: Pase Encontrado y Válido -->
                <div class="card fr-card border-0 shadow-lg overflow-hidden">
                    
                    <!-- Encabezado de Estado del Pase -->
                    <div class="p-4 text-center text-white position-relative" style="background: <?= $isRetirado ? 'linear-gradient(135deg, #1e293b, #334155)' : ($isCancelado ? 'linear-gradient(135deg, #991b1b, #dc2626)' : 'linear-gradient(135deg, #064e3b, #047857)') ?>;">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-20 mb-2 p-3" style="width: 64px; height: 64px;">
                            <?php if ($isRetirado): ?>
                                <i class="bi bi-check2-all text-warning fs-1"></i>
                            <?php elseif ($isCancelado): ?>
                                <i class="bi bi-x-lg text-white fs-1"></i>
                            <?php else: ?>
                                <i class="bi bi-shield-check text-white fs-1"></i>
                            <?php endif; ?>
                        </div>

                        <h4 class="fw-bold mb-1 text-white">
                            <?php if ($isRetirado): ?>
                                Pase Canjeado / Entregado
                            <?php elseif ($isCancelado): ?>
                                Pase Cancelado
                            <?php else: ?>
                                Pase Válido & Vigente
                            <?php endif; ?>
                        </h4>
                        
                        <div class="badge <?= $isRetirado ? 'bg-warning text-dark' : ($isCancelado ? 'bg-danger text-white' : 'bg-success-subtle text-success') ?> px-3 py-1 rounded-pill fw-semibold">
                            <?php if ($isRetirado): ?>
                                <i class="bi bi-clock-history me-1"></i> CANJEADO PREVIAMENTE
                            <?php elseif ($isCancelado): ?>
                                <i class="bi bi-slash-circle me-1"></i> RESERVA ANULADA
                            <?php else: ?>
                                <i class="bi bi-check-circle-fill me-1"></i> LISTO PARA RETIRO EN MOSTRADOR
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Cuerpo del Pase Digital -->
                    <div class="p-4">

                        <!-- Código Alfanumérico -->
                        <div class="text-center p-3 rounded-4 mb-4 border" style="background: #f8fafc;">
                            <small class="text-muted text-uppercase fw-semibold d-block" style="letter-spacing: 1px; font-size: 0.75rem;">Código de Canje FoodRescue</small>
                            <div class="display-6 fw-extrabold font-monospace text-dark my-1" style="letter-spacing: 2px;">
                                <?= htmlspecialchars($reserva['codigo_retiro']) ?>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-calendar3 me-1"></i> Reservado el: <?= date('d/m/Y H:i', strtotime($reserva['fecha_reserva'])) ?> hs
                            </small>
                        </div>

                        <!-- Detalle del Pack y Comercio -->
                        <div class="list-group list-group-flush mb-4">
                            <!-- Pack -->
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted d-block">Pack Rescatado</small>
                                    <strong class="text-dark fs-6"><?= htmlspecialchars($reserva['producto_titulo']) ?></strong>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1">x<?= $reserva['cantidad'] ?> un.</span>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                    <?= htmlspecialchars($reserva['categoria'] ?? 'Gastronomía') ?>
                                </span>
                            </div>

                            <!-- Comercio -->
                            <div class="list-group-item px-0 py-2">
                                <small class="text-muted d-block">Comercio Asociado</small>
                                <div class="fw-bold text-dark"><i class="bi bi-shop me-1 text-primary"></i> <?= htmlspecialchars($reserva['comercio_nombre']) ?></div>
                                <div class="text-muted small"><i class="bi bi-geo-alt me-1 text-danger"></i> <?= htmlspecialchars($reserva['comercio_direccion'] ?: 'Itauguá, Central') ?></div>
                            </div>

                            <!-- Cliente -->
                            <div class="list-group-item px-0 py-2">
                                <small class="text-muted d-block">Cliente que Realizó la Reserva</small>
                                <div class="fw-bold text-dark"><i class="bi bi-person-fill me-1 text-success"></i> <?= htmlspecialchars($reserva['cliente_nombre']) ?></div>
                                <?php if (!empty($reserva['cliente_telefono'])): ?>
                                    <div class="text-muted small"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($reserva['cliente_telefono']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Horario de Retiro -->
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Horario Límite de Retiro:</span>
                                <span class="badge bg-light text-dark border px-3 py-1 font-monospace">
                                    <i class="bi bi-clock text-primary me-1"></i> <?= htmlspecialchars($reserva['horario_retiro'] ?: 'Hoy al cierre') ?>
                                </span>
                            </div>

                            <!-- Monto a Pagar -->
                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center bg-success-subtle rounded-3 mt-2 px-3">
                                <div>
                                    <span class="fw-bold text-success-emphasis d-block">Total a Cobrar en Mostrador:</span>
                                    <small class="text-muted">Descuento aplicado contra desperdicio</small>
                                </div>
                                <div class="fs-4 fw-extrabold text-success">
                                    <?= formatGs($totalPagar) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción para Comercio y Cliente -->
                        <div class="d-grid gap-2">
                            <?php if (!$isRetirado && !$isCancelado): ?>
                                <!-- Formulario para Confirmar Entrega en 1 Clic -->
                                <form action="validar_pase.php" method="POST" onsubmit="return confirm('¿Confirmas que el cliente está retirando el pack en este momento y efectuó el pago de <?= formatGs($totalPagar) ?>?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="accion" value="confirmar_entrega">
                                    <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
                                    <input type="hidden" name="codigo_retiro" value="<?= htmlspecialchars($reserva['codigo_retiro']) ?>">

                                    <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill shadow-sm fw-bold py-3">
                                        <i class="bi bi-check2-circle me-1"></i> Confirmar Entrega en Caja
                                    </button>
                                </form>
                            <?php elseif ($isRetirado): ?>
                                <div class="alert alert-secondary text-center rounded-pill py-2 small mb-2 border-0">
                                    <i class="bi bi-check-all text-success me-1"></i> Este pedido ya fue procesado y entregado.
                                </div>
                            <?php endif; ?>

                            <!-- Botón para Compartir o Contactar por WhatsApp -->
                            <?php if (!empty($reserva['comercio_telefono'])): ?>
                                <a href="https://api.whatsapp.com/send?phone=<?= preg_replace('/[^0-9]/', '', $reserva['comercio_telefono']) ?>&text=<?= urlencode("Hola {$reserva['comercio_nombre']}, te consulto sobre mi reserva {$reserva['codigo_retiro']} de FoodRescue.") ?>" target="_blank" class="btn btn-outline-success rounded-pill">
                                    <i class="bi bi-whatsapp me-1"></i> Mensaje al Comercio
                                </a>
                            <?php endif; ?>

                            <div class="d-flex flex-wrap gap-2 justify-content-center mt-2">
                                <a href="index.php" class="btn btn-light rounded-pill text-muted small px-3">
                                    <i class="bi bi-house me-1"></i> Ir al Menú Principal
                                </a>
                                <?php if (!$isLoggedIn): ?>
                                    <a href="login.php" class="btn btn-outline-success rounded-pill small px-3 fw-medium">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
