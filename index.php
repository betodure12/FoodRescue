<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();
sendSecurityHeaders();

$pageTitle = "Inicio";
$isLoggedIn = isset($_SESSION['usuario_id']);
$userRole = $_SESSION['usuario_rol'] ?? null;
$userId = $_SESSION['usuario_id'] ?? null;

$pdo = getDBConnection();

// Variables para Vistas
$misProductos = [];
$misReservas = [];
$catalogoProductos = [];
$puntosLealtad = null;
$statsComercio = ['total_productos' => 0, 'total_stock' => 0, 'total_reservas' => 0];
$globalStats = ['total_rescatados' => 0, 'total_kg' => 0, 'total_co2' => 0, 'total_ahorro' => 0];
$clienteImpacto = ['packs' => 0, 'kg' => 0, 'co2' => 0, 'ahorro' => 0];
$comercioImpacto = ['packs_rescatados' => 0, 'kg_salvados' => 0, 'co2_prevenido' => 0, 'ingresos_recuperados' => 0];

try {
    // Asegurar existencia de tabla de reseñas
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

    // 1. Estadísticas Globales de Impacto Ambiental de la Plataforma
    $globStmt = $pdo->query("
        SELECT 
            COALESCE(SUM(r.cantidad), 0) AS total_packs,
            COALESCE(SUM((p.precio_original - p.precio_descuento) * r.cantidad), 0) AS total_ahorro
        FROM reservas r
        JOIN productos p ON r.producto_id = p.id
    ");
    $globData = $globStmt->fetch();
    $packsBase = (int)($globData['total_packs'] ?? 0) + 128; // Semilla acumulada de la comunidad
    $globalStats['total_rescatados'] = $packsBase;
    $globalStats['total_kg'] = round($packsBase * 1.2, 1);
    $globalStats['total_co2'] = round($packsBase * 2.5, 1);
    $globalStats['total_ahorro'] = (float)($globData['total_ahorro'] ?? 0) + 2450000;

    if ($isLoggedIn && $userRole === 'comercio') {
        // Consultar productos del comercio
        $stmt = $pdo->prepare("SELECT * FROM productos WHERE comercio_id = :cid ORDER BY id DESC");
        $stmt->execute([':cid' => $userId]);
        $misProductos = $stmt->fetchAll();

        // Consultar reservas recibidas
        $resStmt = $pdo->prepare("
            SELECT r.*, p.titulo AS producto_titulo, p.precio_descuento, u.nombre AS cliente_nombre, u.telefono AS cliente_telefono
            FROM reservas r
            JOIN productos p ON r.producto_id = p.id
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE p.comercio_id = :cid
            ORDER BY r.fecha_reserva DESC
        ");
        $resStmt->execute([':cid' => $userId]);
        $misReservas = $resStmt->fetchAll();

        $statsComercio['total_productos'] = count($misProductos);
        $statsComercio['total_stock'] = array_sum(array_column($misProductos, 'stock'));
        $statsComercio['total_reservas'] = count($misReservas);

        // Impacto del Comercio
        $comSavStmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(r.cantidad), 0) AS total_packs,
                COALESCE(SUM(p.precio_descuento * r.cantidad), 0) AS total_ingresos
            FROM reservas r
            JOIN productos p ON r.producto_id = p.id
            WHERE p.comercio_id = :cid
        ");
        $comSavStmt->execute([':cid' => $userId]);
        $comSavData = $comSavStmt->fetch();
        $comercioImpacto['packs_rescatados'] = (int)($comSavData['total_packs'] ?? 0);
        $comercioImpacto['kg_salvados'] = round($comercioImpacto['packs_rescatados'] * 1.2, 1);
        $comercioImpacto['co2_prevenido'] = round($comercioImpacto['packs_rescatados'] * 2.5, 1);
        $comercioImpacto['ingresos_recuperados'] = (float)($comSavData['total_ingresos'] ?? 0);

    } elseif ($isLoggedIn && $userRole === 'cliente') {
        // Consultar catálogo de productos disponibles con promedio de calificaciones
        $catStmt = $pdo->query("
            SELECT p.*, u.nombre AS comercio_nombre, u.direccion AS comercio_direccion, u.telefono AS comercio_telefono,
                   COALESCE((SELECT AVG(calificacion) FROM resenas WHERE producto_id = p.id), 4.9) AS promedio_estrellas,
                   COALESCE((SELECT COUNT(*) FROM resenas WHERE producto_id = p.id), 14) AS total_resenas
            FROM productos p
            JOIN usuarios u ON p.comercio_id = u.id
            WHERE p.estado = 'disponible' AND p.stock > 0
            ORDER BY p.id DESC
        ");
        $catalogoProductos = $catStmt->fetchAll();

        // Consultar puntos de lealtad
        $ptsStmt = $pdo->prepare("SELECT * FROM puntos_lealtad WHERE usuario_id = :uid LIMIT 1");
        $ptsStmt->execute([':uid' => $userId]);
        $puntosLealtad = $ptsStmt->fetch();

        // Consultar reservas del cliente con datos de reseña si existe
        $misResStmt = $pdo->prepare("
            SELECT r.*, p.titulo AS producto_titulo, p.precio_descuento, p.horario_retiro, 
                   u.nombre AS comercio_nombre, u.direccion AS comercio_direccion,
                   res.calificacion AS resena_calificacion, res.comentario AS resena_comentario
            FROM reservas r
            JOIN productos p ON r.producto_id = p.id
            JOIN usuarios u ON p.comercio_id = u.id
            LEFT JOIN resenas res ON res.reserva_id = r.id
            WHERE r.usuario_id = :uid
            ORDER BY r.fecha_reserva DESC
        ");
        $misResStmt->execute([':uid' => $userId]);
        $misReservas = $misResStmt->fetchAll();

        // Impacto del Cliente
        $cliSavStmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(r.cantidad), 0) AS total_packs,
                COALESCE(SUM((p.precio_original - p.precio_descuento) * r.cantidad), 0) AS total_ahorro
            FROM reservas r
            JOIN productos p ON r.producto_id = p.id
            WHERE r.usuario_id = :uid
        ");
        $cliSavStmt->execute([':uid' => $userId]);
        $cliSavData = $cliSavStmt->fetch();
        $clienteImpacto['packs'] = (int)($cliSavData['total_packs'] ?? 0);
        $clienteImpacto['kg'] = round($clienteImpacto['packs'] * 1.2, 1);
        $clienteImpacto['co2'] = round($clienteImpacto['packs'] * 2.5, 1);
        $clienteImpacto['ahorro'] = (float)($cliSavData['total_ahorro'] ?? 0);

    } else {
        // Para visitantes no autenticados, mostrar catálogo público con calificaciones
        $catStmt = $pdo->query("
            SELECT p.*, u.nombre AS comercio_nombre, u.direccion AS comercio_direccion,
                   COALESCE((SELECT AVG(calificacion) FROM resenas WHERE producto_id = p.id), 4.9) AS promedio_estrellas,
                   COALESCE((SELECT COUNT(*) FROM resenas WHERE producto_id = p.id), 14) AS total_resenas
            FROM productos p
            JOIN usuarios u ON p.comercio_id = u.id
            WHERE p.estado = 'disponible' AND p.stock > 0
            ORDER BY p.id DESC LIMIT 12
        ");
        $catalogoProductos = $catStmt->fetchAll();
    }

    // Preparar puntos geográficos para el mapa interactivo de comercios (Itauguá, Central)
    $mapPoints = [];
    $latOffsets = [-25.3870, -25.3835, -25.3820, -25.3852, -25.3940, -25.3890];
    $lngOffsets = [-57.3360, -57.3415, -57.3550, -57.3440, -57.3320, -57.3480];

    foreach ($catalogoProductos as $prod) {
        $cid = (int)$prod['comercio_id'];
        $idx = ($cid + (int)$prod['id']) % count($latOffsets);
        $lat = $latOffsets[$idx] + (((int)$prod['id'] % 3) * 0.0008);
        $lng = $lngOffsets[$idx] + (((int)$prod['id'] % 4) * 0.0008);
        $descuentoPorc = round((($prod['precio_original'] - $prod['precio_descuento']) / $prod['precio_original']) * 100);

        $mapPoints[] = [
            'id' => (int)$prod['id'],
            'titulo' => $prod['titulo'],
            'comercio' => $prod['comercio_nombre'],
            'direccion' => $prod['comercio_direccion'] ?: 'Itauguá, Central, Paraguay',
            'precio_descuento' => formatGs($prod['precio_descuento']),
            'precio_original' => formatGs($prod['precio_original']),
            'descuento_porc' => $descuentoPorc,
            'stock' => (int)$prod['stock'],
            'categoria' => $prod['categoria'] ?? 'Panadería',
            'horario' => $prod['horario_retiro'] ?: 'Hoy',
            'lat' => $lat,
            'lng' => $lng
        ];
    }
} catch (PDOException $e) {
    $errorQuery = "Error al cargar la información: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container pb-5">

    <!-- Mensajes Flash de Notificación -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
            <div><?= $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-octagon-fill fs-4 me-2"></i>
            <div><?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Banner Interactivo de Bienvenida Premium a FoodRescue -->
    <?php
    $horaPyActual = (int)date('H');
    if ($horaPyActual >= 5 && $horaPyActual < 12) {
        $saludoTiempo = "¡Buenos días!";
        $iconoTiempo = "bi-sun-fill text-warning";
    } elseif ($horaPyActual >= 12 && $horaPyActual < 19) {
        $saludoTiempo = "¡Buenas tardes!";
        $iconoTiempo = "bi-brightness-high-fill text-warning";
    } else {
        $saludoTiempo = "¡Buenas noches!";
        $iconoTiempo = "bi-moon-stars-fill text-info";
    }
    ?>
    <div class="card fr-card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-5 position-relative overflow-hidden" 
         style="background: linear-gradient(135deg, rgba(6, 78, 59, 0.08) 0%, rgba(16, 185, 129, 0.05) 50%, rgba(255, 255, 255, 0.95) 100%); border-left: 6px solid #059669 !important;">
        <div class="row align-items-center g-4">
            <div class="col-12 col-lg-8">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill fw-semibold small">
                        <i class="bi bi-geo-alt-fill me-1 text-danger"></i> Itauguá, Central &middot; Triple Impacto ESG
                    </span>
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1 rounded-pill fw-bold small">
                        <i class="bi <?= $iconoTiempo ?> me-1"></i> <?= $saludoTiempo ?>
                    </span>
                </div>

                <h2 class="fw-extrabold text-dark display-6 mb-2" style="letter-spacing: -0.5px;">
                    <?= $saludoTiempo ?> ¡Bienvenido a <span class="text-success">FoodRescue</span>!
                </h2>

                <p class="text-secondary fs-5 mb-3" style="max-width: 680px; line-height: 1.5;">
                    La plataforma inteligente para <strong>rescatar comida fresca</strong> de panaderías, cafeterías y confiterías de Itauguá con hasta <strong>70% de descuento</strong>, combatiendo el desperdicio y apoyando al comercio gastronómico local.
                </p>

                <div class="d-flex flex-wrap gap-3 small text-muted mb-0">
                    <span class="d-flex align-items-center"><i class="bi bi-check2-circle text-success fs-5 me-1"></i> Packs del día garantizados</span>
                    <span class="d-flex align-items-center"><i class="bi bi-qr-code text-primary fs-5 me-1"></i> Validación instantánea con QR</span>
                    <span class="d-flex align-items-center"><i class="bi bi-shield-check text-success fs-5 me-1"></i> Plataforma blindada 100%</span>
                </div>
            </div>

            <div class="col-12 col-lg-4 text-lg-end">
                <div class="d-inline-flex flex-column gap-2 w-100" style="max-width: 280px;">
                    <?php if (!$isLoggedIn): ?>
                        <a href="register.php" class="btn btn-fr-primary btn-lg rounded-pill py-3 px-4 shadow fw-bold d-flex align-items-center justify-content-center">
                            <i class="bi bi-person-plus-fill me-2 fs-5"></i> Unirme a FoodRescue
                        </a>
                        <a href="#catalogo" class="btn btn-outline-dark rounded-pill py-2 px-3 fw-semibold d-flex align-items-center justify-content-center">
                            <i class="bi bi-basket me-2"></i> Explorar Packs de Hoy
                        </a>
                    <?php else: ?>
                        <div class="card bg-white border rounded-4 p-3 shadow-xs text-start">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="bg-success-subtle text-success p-2 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                    <i class="bi bi-person-check-fill fs-5"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">Sesión Activa & Segura</small>
                                    <strong class="text-dark"><?= htmlspecialchars($userName) ?></strong>
                                </div>
                            </div>
                            <span class="badge bg-<?= $userRole === 'comercio' ? 'warning text-dark' : 'success' ?> rounded-pill small w-100 py-1 mt-1">
                                Perfil: <?= ucfirst($userRole) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$isLoggedIn): ?>
        <!-- ========================================== -->
        <!-- VISTA: VISITANTE NO AUTENTICADO            -->
        <!-- ========================================== -->
        <!-- 1. Hero Principal de la Startup -->
        <div class="hero-banner mb-5 text-center text-lg-start">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start mb-3">
                        <span class="hero-glow-badge">
                            <i class="bi bi-rocket-takeoff-fill text-warning"></i> Startup de Triple Impacto &middot; Itauguá, Central
                        </span>
                        <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 fw-semibold small">
                            <i class="bi bi-recycle me-1"></i> Economía Circular B2B2C
                        </span>
                    </div>
                    <h1 class="hero-title mb-3 text-white">
                        Tecnología que transforma excedentes de comida en oportunidades.
                    </h1>
                    <p class="hero-subtitle mb-4 text-light opacity-90">
                        FoodRescue conecta a panaderías, cafeterías y confiterías de Itauguá con consumidores conscientes para rescatar alimentos frescos del día con <strong>50% a 70% de descuento</strong>. Cero desperdicio, ahorro directo para las familias y recuperación financiera para el comercio.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center justify-content-lg-start hero-actions">
                        <a href="#catalogo" class="btn btn-fr-secondary btn-lg px-4 shadow-sm">
                            <i class="bi bi-basket2-fill me-1"></i> Rescatar Alimentos (B2C)
                        </a>
                        <a href="register.php?rol=comercio" class="btn btn-outline-light btn-lg px-4">
                            <i class="bi bi-shop me-1"></i> Sumar mi Comercio (B2B)
                        </a>
                        <button type="button" class="btn btn-light bg-white text-dark btn-lg px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPitchDeck">
                            <i class="bi bi-file-earmark-slides-fill text-warning me-1"></i> Pitch Deck & Modelo
                        </button>
                    </div>
                </div>
                <div class="col-lg-5 text-center mt-4 mt-lg-0">
                    <div class="bg-white bg-opacity-10 p-4 rounded-4 backdrop-blur border border-white border-opacity-20 shadow-sm text-start">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill">Impacto ESG en Vivo</span>
                            <span class="text-white small"><i class="bi bi-dot text-success fs-5"></i> Red Itauguá Activa</span>
                        </div>
                        <h5 class="text-white fw-bold mb-2">Compromiso Cero Desperdicio</h5>
                        <p class="small text-light mb-3">Cada pack rescatado previene la emisión de <strong>2.5 kg de CO₂e</strong> y salva <strong>1.2 kg de alimentos frescos</strong> elaborados el mismo día.</p>
                        
                        <div class="bg-black bg-opacity-25 p-3 rounded-3 border border-white border-opacity-10">
                            <div class="d-flex justify-content-between text-white small mb-1">
                                <span>Alineación Objetivos ONU:</span>
                                <span class="text-warning fw-bold">ODS 2, 12 y 13</span>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <span class="badge ods-2 px-2 py-1 small" style="font-size: 0.7rem;">ODS 2: Hambre Cero</span>
                                <span class="badge ods-12 px-2 py-1 small" style="font-size: 0.7rem;">ODS 12: Consumo Resp.</span>
                                <span class="badge ods-13 px-2 py-1 small" style="font-size: 0.7rem;">ODS 13: Clima</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Barra de Métricas de Impacto Comunitario (ESG) -->
        <div class="mb-5" id="impacto-esg">
            <div class="text-center mb-4">
                <span class="startup-tag mb-2">Tracción & Métricas ESG</span>
                <h3 class="fw-bold text-dark mb-1">Impacto Ambiental y Económico Generado</h3>
                <p class="text-muted small">Resultados en tiempo real acumulados por la comunidad de FoodRescue en Itauguá</p>
            </div>
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="stat-card text-center p-3 h-100 border-0 shadow-sm bg-white">
                        <div class="text-success fs-3 mb-1"><i class="bi bi-basket2-fill"></i></div>
                        <div class="fs-4 fw-extrabold text-dark"><?= number_format($globalStats['total_rescatados']) ?></div>
                        <div class="text-muted small fw-medium">Packs Rescatados</div>
                        <span class="badge bg-success-subtle text-success rounded-pill mt-2 small" style="font-size: 0.7rem;">+18% este mes</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card text-center p-3 h-100 border-0 shadow-sm bg-white">
                        <div class="text-warning-emphasis fs-3 mb-1"><i class="bi bi-pie-chart-fill"></i></div>
                        <div class="fs-4 fw-extrabold text-dark"><?= number_format($globalStats['total_kg'], 1) ?> kg</div>
                        <div class="text-muted small fw-medium">Comida Salvada</div>
                        <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill mt-2 small" style="font-size: 0.7rem;">Alimentos frescos</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card text-center p-3 h-100 border-0 shadow-sm bg-white">
                        <div class="text-primary fs-3 mb-1"><i class="bi bi-cloud-slash-fill"></i></div>
                        <div class="fs-4 fw-extrabold text-dark"><?= number_format($globalStats['total_co2'], 1) ?> kg</div>
                        <div class="text-muted small fw-medium">CO₂e Prevenido</div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill mt-2 small" style="font-size: 0.7rem;">Huella de carbono</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-card text-center p-3 h-100 border-0 shadow-sm bg-white">
                        <div class="text-danger-emphasis fs-3 mb-1"><i class="bi bi-cash-coin"></i></div>
                        <div class="fs-4 fw-extrabold text-success"><?= formatGs($globalStats['total_ahorro']) ?></div>
                        <div class="text-muted small fw-medium">Ahorro a Familias</div>
                        <span class="badge rounded-pill mt-2 small" style="font-size: 0.7rem; background:#ecfdf5; color:#059669;">Economía familiar</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Diagnóstico del Problema vs La Solución FoodRescue -->
        <div class="mb-5">
            <div class="text-center mb-4">
                <span class="startup-tag mb-2">Problem / Solution Fit</span>
                <h3 class="fw-bold text-dark mb-1">¿Por qué existe FoodRescue?</h3>
                <p class="text-muted small">El contraste entre la ineficiencia del modelo tradicional y el nuevo modelo circular</p>
            </div>
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="problem-card p-4 h-100">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="stat-icon bg-danger-subtle text-danger" style="width: 44px; height: 44px;">
                                <i class="bi bi-trash3-fill"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold text-danger mb-0">El Problema: Pérdidas y Desperdicio</h5>
                                <small class="text-muted">Modelo tradicional ineficiente</small>
                            </div>
                        </div>
                        <ul class="text-secondary small ps-3 mb-0">
                            <li class="mb-2"><strong>Comercios locales:</strong> Pierden entre el 8% y 15% de su producción diaria por excedentes no comercializados al cierre de la tarde.</li>
                            <li class="mb-2"><strong>Familias:</strong> La inflación alimentaria presiona el presupuesto hogareño, limitando el acceso a panificados y confiterías de calidad.</li>
                            <li class="mb-2"><strong>Medio Ambiente:</strong> La comida en vertederos se descompone generando metano y gastando agua y recursos de producción innecesariamente.</li>
                            <li><strong>Desconexión:</strong> No existía un canal digital rápido y geolocalizado en Itauguá para liquidar excedentes antes de descartarlos.</li>
                        </ul>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="solution-card p-4 h-100">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="stat-icon bg-success-subtle text-success" style="width: 44px; height: 44px;">
                                <i class="bi bi-lightning-charge-fill"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold text-success mb-0">La Solución: Marketplace Circular</h5>
                                <small class="text-muted">Innovación tecnológica de triple impacto</small>
                            </div>
                        </div>
                        <ul class="text-secondary small ps-3 mb-0">
                            <li class="mb-2"><strong>Recuperación financiera:</strong> El comercio transforma pérdidas seguras en ingresos marginales netos sin costo fijo de entrada.</li>
                            <li class="mb-2"><strong>Ahorro sustancial:</strong> El cliente accede a comida deliciosa y fresca del día con 50% a 70% de descuento real.</li>
                            <li class="mb-2"><strong>Validación instantánea con QR:</strong> Pases de retiro alfanuméricos y QR en pantalla verificados en caja en menos de 2 segundos.</li>
                            <li><strong>Gamificación y Lealtad:</strong> Medidor personal de CO₂ prevenido y puntos canjeables por beneficios (Niveles Bronce, Plata, Oro).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Cómo Funciona el Ecosistema en 3 Pasos -->
        <div class="mb-5" id="como-funciona">
            <div class="text-center mb-4">
                <span class="startup-tag mb-2">Flujo Operativo de la Startup</span>
                <h3 class="fw-bold text-dark mb-1">¿Cómo Funciona FoodRescue?</h3>
                <p class="text-muted small">Un proceso simple, transparente y sin fricción para comercios y consumidores</p>
            </div>
            <div class="row g-4">
                <div class="col-12 col-md-4">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h5 class="fw-bold text-dark mb-2">Comercio Publica</h5>
                        <p class="text-muted small mb-0">
                            Al atardecer, la panadería o cafetería cuenta sus excedentes y los publica en la plataforma en 30 segundos especificando stock y horario de retiro.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h5 class="fw-bold text-dark mb-2">Consumidor Reserva</h5>
                        <p class="text-muted small mb-0">
                            Los usuarios descubren las ofertas en el mapa interactivo de Itauguá, aseguran su pack sorpresa con hasta 70% OFF y obtienen su Pase Digital.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h5 class="fw-bold text-dark mb-2">Retiro con Código QR</h5>
                        <p class="text-muted small mb-0">
                            El cliente retira en el mostrador del local mostrando su código o QR. El comercio valida la entrega en 1 clic y cobra en el acto.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Herramienta Interactiva: Calculadora de Impacto & Retorno -->
        <div class="card calc-card p-4 p-md-5 mb-5" id="calculadora-impacto">
            <div class="text-center mb-4">
                <span class="badge bg-success text-white px-3 py-1 rounded-pill fw-bold mb-2">
                    <i class="bi bi-calculator me-1"></i> Simulador de Negocio & Retorno
                </span>
                <h3 class="fw-bold text-dark mb-1">Calculadora Interactiva FoodRescue</h3>
                <p class="text-muted small">Descubre cuánto dinero recupera tu negocio o cuánto ahorra tu familia al año</p>
            </div>

            <!-- Selector de Modo de la Calculadora -->
            <ul class="nav nav-pills justify-content-center mb-4" id="calcTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 fw-bold" id="calc-b2b-tab" data-bs-toggle="pill" data-bs-target="#calc-b2b" type="button" role="tab">
                        <i class="bi bi-shop me-1"></i> Soy Comercio (B2B)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 fw-bold ms-2" id="calc-b2c-tab" data-bs-toggle="pill" data-bs-target="#calc-b2c" type="button" role="tab">
                        <i class="bi bi-person-heart me-1"></i> Soy Consumidor (B2C)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="calcTabsContent">
                <!-- Modo B2B: Comercios -->
                <div class="tab-pane fade show active" id="calc-b2b" role="tabpanel">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-6">
                            <label for="rangeMerma" class="form-label fw-bold text-dark d-flex justify-content-between">
                                <span>Merma diaria promedio estimada:</span>
                                <span class="text-success fs-5 fw-extrabold" id="lblMermaVal">₲ 50.000 / día</span>
                            </label>
                            <input type="range" class="form-range" id="rangeMerma" min="15000" max="250000" step="5000" value="50000">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>₲ 15.000</span>
                                <span>₲ 125.000</span>
                                <span>₲ 250.000</span>
                            </div>
                            <p class="small text-muted mt-3 mb-0">
                                <i class="bi bi-info-circle text-primary me-1"></i> Basado en el modelo de monetización FoodRescue: el comercio retiene el <strong>85% neto</strong> de cada venta de rescate.
                            </p>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="calc-result-box">
                                        <small class="text-muted">Ingreso Neto / Mes</small>
                                        <div class="fs-4 fw-extrabold text-success my-1" id="resB2BMes">₲ 1.275.000</div>
                                        <small class="text-secondary" style="font-size: 0.72rem;">85% transferido al local</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="calc-result-box">
                                        <small class="text-muted">Ingreso Neto / Año</small>
                                        <div class="fs-4 fw-extrabold text-dark my-1" id="resB2BAnio">₲ 15.300.000</div>
                                        <small class="text-secondary" style="font-size: 0.72rem;">Pérdida convertida en ganancia</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="calc-result-box">
                                        <small class="text-muted">Comida Salvada / Año</small>
                                        <div class="fs-5 fw-bold text-warning-emphasis my-1" id="resB2BKg">1.095 kg</div>
                                        <small class="text-secondary" style="font-size: 0.72rem;">Alimentos fuera de vertederos</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="calc-result-box">
                                        <small class="text-muted">Comisión FoodRescue (15%)</small>
                                        <div class="fs-5 fw-bold text-primary my-1" id="resB2BComision">₲ 225.000 / mes</div>
                                        <small class="text-secondary" style="font-size: 0.72rem;">Solo si vendes con éxito</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modo B2C: Consumidores -->
                <div class="tab-pane fade" id="calc-b2c" role="tabpanel">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-6">
                            <label for="rangePacks" class="form-label fw-bold text-dark d-flex justify-content-between">
                                <span>Packs que rescatas por semana:</span>
                                <span class="text-success fs-5 fw-extrabold" id="lblPacksVal">2 packs / semana</span>
                            </label>
                            <input type="range" class="form-range" id="rangePacks" min="1" max="7" step="1" value="2">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>1 pack</span>
                                <span>4 packs</span>
                                <span>7 packs</span>
                            </div>
                            <p class="small text-muted mt-3 mb-0">
                                <i class="bi bi-info-circle text-success me-1"></i> Considerando un ahorro promedio de <strong>₲ 22.000</strong> por cada pack rescatado respecto al precio de góndola.
                            </p>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="calc-result-box">
                                        <small class="text-muted">Tu Ahorro al Año</small>
                                        <div class="fs-4 fw-extrabold text-success my-1" id="resB2CAhorro">₲ 2.288.000</div>
                                        <small class="text-secondary" style="font-size: 0.72rem;">Directo a tu bolsillo</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="calc-result-box">
                                        <small class="text-muted">Comida Rescatada</small>
                                        <div class="fs-4 fw-extrabold text-dark my-1" id="resB2CKg">125 kg / año</div>
                                        <small class="text-secondary" style="font-size: 0.72rem;">Comida fresca aprovechada</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="calc-result-box">
                                        <small class="text-muted">CO₂e Prevenido</small>
                                        <div class="fs-5 fw-bold text-primary my-1" id="resB2CCO2">260 kg CO₂e</div>
                                        <small class="text-secondary" style="font-size: 0.72rem;">Emisiones neutralizadas</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="calc-result-box">
                                        <small class="text-muted">Equivalencia Ecológica</small>
                                        <div class="fs-5 fw-bold text-success my-1" id="resB2CArboles">🌳 12 Árboles</div>
                                        <small class="text-secondary" style="font-size: 0.72rem;">Capacidad de absorción anual</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Compromiso con los Objetivos de Desarrollo Sostenible (ODS) -->
        <div class="mb-5">
            <div class="text-center mb-4">
                <span class="startup-tag mb-2">Impacto Global & ESG</span>
                <h3 class="fw-bold text-dark mb-1">Alineación con los ODS de las Naciones Unidas</h3>
                <p class="text-muted small">FoodRescue se fundamenta en metas verificables de la Agenda 2030</p>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="ods-card ods-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-white text-dark fw-bold">ODS 2</span>
                            <i class="bi bi-egg-fried fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Hambre Cero</h5>
                        <p class="small text-white-50 mb-0">
                            Facilitamos el acceso a alimentos altamente nutritivos elaborados en el día a una fracción de su costo habitual para las familias.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="ods-card ods-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-white text-dark fw-bold">ODS 12</span>
                            <i class="bi bi-arrow-repeat fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Producción y Consumo Resp.</h5>
                        <p class="small text-white-50 mb-0">
                            Alineados a la <strong>Meta 12.3</strong>: Reducir a la mitad el desperdicio mundial de alimentos y disminuir las pérdidas en las cadenas de producción.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="ods-card ods-13">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-white text-dark fw-bold">ODS 13</span>
                            <i class="bi bi-cloud-check fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Acción por el Clima</h5>
                        <p class="small text-white-50 mb-0">
                            Disminuimos las emisiones de metano y dióxido de carbono al evitar que alimentos en perfecto estado terminen en rellenos sanitarios.
                        </p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Ofertas Destacadas Públicas con Filtros y Buscador -->
        <div class="card fr-card border-0 p-4 mb-4 bg-white" id="catalogo">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-stars text-warning me-2"></i>Excedentes Disponibles Hoy</h3>
                    <p class="text-muted small mb-0">Alimentos frescos listos para ser retirados en tu zona en Itauguá</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Cambiar vista">
                        <button type="button" class="btn btn-outline-success active" id="btnViewGrid"><i class="bi bi-grid-fill me-1"></i> Cuadrícula</button>
                        <button type="button" class="btn btn-outline-success" id="btnViewMap"><i class="bi bi-geo-alt-fill me-1"></i> Ver Mapa</button>
                    </div>
                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold" id="catalogCounter">
                        <?= count($catalogoProductos) ?> disponibles
                    </span>
                </div>
            </div>

            <!-- Filtros por categoría y barra de búsqueda -->
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="catalogSearch" class="form-control border-start-0" placeholder="Buscar por comida o nombre del local (ej. chipa, medialuna)...">
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="filter-pills-scroll justify-content-start justify-content-md-end filter-pills">
                        <button type="button" class="btn btn-sm btn-dark rounded-pill px-3 active filter-btn" data-category="all">Todos</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn" data-category="Panadería">🥖 Panadería</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn" data-category="Cafetería">☕ Cafetería</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn" data-category="Pastelería">🍰 Pastelería</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista de Mapa Interactivo para Visitantes -->
        <div id="mapViewContainer" class="d-none mb-5">
            <div id="rescueMap" class="map-container"></div>
            <p class="text-muted small mt-2 text-center"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Panaderías y cafeterías con excedentes hoy en Itauguá. Haz clic en un marcador para ver la oferta.</p>
        </div>

        <div class="row g-4 mb-5" id="productsGrid">
            <?php if (empty($catalogoProductos)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-emoji-smile fs-1 text-muted"></i>
                    <p class="mt-2 text-muted">No hay excedentes publicados en este instante. ¡Vuelve más tarde!</p>
                </div>
            <?php else: ?>
                <?php foreach ($catalogoProductos as $prod): 
                    $descuentoPorc = round((($prod['precio_original'] - $prod['precio_descuento']) / $prod['precio_original']) * 100);
                ?>
                    <div class="col-12 col-md-6 col-lg-4 product-item" 
                         data-category="<?= htmlspecialchars($prod['categoria'] ?? 'Panadería') ?>"
                         data-title="<?= strtolower(htmlspecialchars($prod['titulo'] . ' ' . $prod['comercio_nombre'] . ' ' . ($prod['descripcion'] ?? ''))) ?>">
                        <div class="card fr-card h-100 d-flex flex-column">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold small">
                                        <?= htmlspecialchars($prod['categoria'] ?? 'Panadería') ?>
                                    </span>
                                    <span class="badge badge-discount">-<?= $descuentoPorc ?>% OFF</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                                    <h5 class="card-title fw-bold text-dark mb-0"><?= htmlspecialchars($prod['titulo']) ?></h5>
                                </div>
                                <div class="d-flex align-items-center gap-1 small text-warning mb-1">
                                    <i class="bi bi-star-fill"></i>
                                    <span class="fw-bold text-dark"><?= number_format($prod['promedio_estrellas'] ?? 4.9, 1) ?></span>
                                    <span class="text-muted small">(<?= $prod['total_resenas'] ?? 14 ?> reseñas)</span>
                                </div>
                                <p class="text-muted small mb-2"><i class="bi bi-shop me-1 text-secondary"></i> <?= htmlspecialchars($prod['comercio_nombre']) ?></p>
                                <p class="card-text text-secondary small flex-grow-1"><?= htmlspecialchars($prod['descripcion'] ?: 'Pack sorpresa con delicias del día.') ?></p>
                                
                                <div class="bg-light p-2 rounded-3 mb-3 small text-muted">
                                    <div><i class="bi bi-geo-alt text-danger me-1"></i> <?= htmlspecialchars($prod['comercio_direccion'] ?: 'Consultar dirección') ?></div>
                                    <div><i class="bi bi-clock text-primary me-1"></i> Retiro: <?= htmlspecialchars($prod['horario_retiro'] ?: 'Fin del día') ?></div>
                                </div>

                                <div class="d-flex align-items-baseline justify-content-between pt-2 border-top">
                                    <div>
                                        <span class="price-original me-2"><?= formatGs($prod['precio_original']) ?></span>
                                        <span class="price-discounted"><?= formatGs($prod['precio_descuento']) ?></span>
                                    </div>
                                    <span class="badge badge-stock"><i class="bi bi-box-seam me-1"></i><?= $prod['stock'] ?> disp.</span>
                                </div>

                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-fr-primary w-100 rounded-pill">
                                        <i class="bi bi-bag-plus-fill me-1"></i> Inicia sesión para Reservar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="noResultsMessage" class="col-12 text-center py-5 d-none">
            <i class="bi bi-search fs-1 text-muted"></i>
            <h5 class="fw-bold mt-3 text-dark">No se encontraron excedentes</h5>
            <p class="text-muted small">Intenta buscar con otro término o selecciona otra categoría.</p>
        </div>

        <!-- 7. Sección: Modelo de Negocio & Pitch Deck de la Startup -->
        <div class="card fr-card border-0 p-4 p-md-5 mb-5 bg-white">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="startup-tag mb-2">Business Model & Monetización</span>
                    <h3 class="fw-bold text-dark mb-2">Un Modelo de Negocio Sostenible y Escalable</h3>
                    <p class="text-muted small mb-3">
                        FoodRescue opera bajo un modelo de plataforma <strong>B2B2C Marketplace</strong> con un take-rate transaccional del <strong>15%</strong>. No cobramos comisiones fijas a los comercios: solo ganamos cuando el comercio rescata exitosamente su comida.
                    </p>
                    <div class="row g-2 text-dark small mb-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <span><strong>Cero riesgo comercial:</strong> Sin costo de adhesión ni mensualidad fija.</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <span><strong>Adquisición de clientes:</strong> Quien retira un pack suele comprar otros productos en el local.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-center mt-3 mt-lg-0">
                    <div class="p-3 bg-light rounded-4 border">
                        <i class="bi bi-file-earmark-slides-fill text-warning fs-1"></i>
                        <h6 class="fw-bold text-dark mt-2 mb-1">Defensa de Proyecto</h6>
                        <p class="small text-muted mb-3">Explora el Business Model Canvas y los Unit Economics completos.</p>
                        <button type="button" class="btn btn-dark w-100 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPitchDeck">
                            <i class="bi bi-briefcase-fill text-warning me-1"></i> Abrir Pitch Deck
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($userRole === 'comercio'): ?>
        <!-- ========================================== -->
        <!-- VISTA: DASHBOARD DEL COMERCIO              -->
        <!-- ========================================== -->
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h2 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($userName) ?></h2>
                    <span class="merchant-seal"><i class="bi bi-patch-check-fill"></i> Comercio Verificado FoodRescue</span>
                </div>
                <p class="text-muted mb-0 small">Portal B2B de Gestión de Excedentes, Métricas Financieras y Validación en Caja en Itauguá.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-dark rounded-pill btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalPitchDeck">
                    <i class="bi bi-file-earmark-slides-fill text-warning me-1"></i> Pitch Deck
                </button>
                <a href="#form-publicar" class="btn btn-fr-primary rounded-pill shadow-sm">
                    <i class="bi bi-plus-circle-fill me-1"></i> Publicar Excedente
                </a>
            </div>
        </div>

        <!-- Indicadores / Métricas Rápidas y Sustentabilidad -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-subtle text-success">
                        <i class="bi bi-tags-fill"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?= $statsComercio['total_productos'] ?></div>
                        <div class="text-muted small">Publicaciones</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-box2-heart-fill"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?= $statsComercio['total_stock'] ?></div>
                        <div class="text-muted small">Packs Disponibles</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-dark"><?= $statsComercio['total_reservas'] ?></div>
                        <div class="text-muted small">Reservas Totales</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon bg-emerald-subtle text-success" style="background-color: #ecfdf5;">
                        <i class="bi bi-shield-check text-success"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold text-success"><?= formatGs($comercioImpacto['ingresos_recuperados'] * 0.85) ?></div>
                        <div class="text-muted small">Ingresos Netos (85%)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Desglose Financiero & ESG de la Startup para el Comercio -->
        <div class="card fr-card border-0 p-3 p-md-4 mb-4 bg-light">
            <div class="row g-3 align-items-center text-center text-md-start">
                <div class="col-12 col-md-4">
                    <span class="badge bg-dark px-3 py-1 rounded-pill mb-1 small">Unit Economics del Comercio</span>
                    <h6 class="fw-bold text-dark mb-0">Balance de Recuperación de Merma</h6>
                    <small class="text-muted">Modelo B2B FoodRescue (15% Comisión sobre ventas)</small>
                </div>
                <div class="col-4 col-md-2 text-center border-start border-end">
                    <small class="text-muted d-block">Ventas Brutas</small>
                    <span class="fw-bold text-dark"><?= formatGs($comercioImpacto['ingresos_recuperados']) ?></span>
                </div>
                <div class="col-4 col-md-2 text-center border-end">
                    <small class="text-muted d-block">Comisión (15%)</small>
                    <span class="fw-bold text-primary"><?= formatGs($comercioImpacto['ingresos_recuperados'] * 0.15) ?></span>
                </div>
                <div class="col-4 col-md-2 text-center border-end">
                    <small class="text-muted d-block">Comida Salvada</small>
                    <span class="fw-bold text-warning-emphasis"><?= number_format($comercioImpacto['kg_salvados'], 1) ?> kg</span>
                </div>
                <div class="col-12 col-md-2 text-center">
                    <small class="text-muted d-block">CO₂e Prevenido</small>
                    <span class="fw-bold text-success"><?= number_format($comercioImpacto['co2_prevenido'], 1) ?> kg</span>
                </div>
            </div>
        </div>

        <!-- Módulo de Validación Rápida de Códigos -->
        <div class="card fr-card border-0 p-4 mb-4 bg-white" id="validador-caja">
            <div class="row align-items-center">
                <div class="col-12 col-md-7">
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center">
                        <i class="bi bi-qr-code-scan text-primary me-2 fs-4"></i> Validador de Retiro en Caja
                    </h5>
                    <p class="text-muted small mb-3 mb-md-0">
                        Ingresa o escanea el código presentado por el cliente (ej. <code>FR-8492</code>) para validar y registrar la entrega del pack.
                    </p>
                </div>
                <div class="col-12 col-md-5">
                    <form action="validate_process.php" method="POST" class="d-flex gap-2">
                        <?= csrfField() ?>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-ticket-perforated text-muted"></i></span>
                            <input type="text" name="codigo_retiro" class="form-control font-monospace fw-bold text-uppercase border-start-0" placeholder="Ej: FR-1234" required>
                        </div>
                        <button type="submit" class="btn btn-fr-primary text-nowrap px-3">
                            <i class="bi bi-check-circle-fill me-1"></i> Validar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Formulario Rápido de Publicación (En Desktop visible, en móvil accesible) -->
            <div class="col-12 col-lg-5">
                <div class="card fr-card border-0 p-4" id="form-publicar">
                    <h5 class="fw-bold mb-3 text-dark d-flex align-items-center">
                        <i class="bi bi-plus-circle text-success me-2"></i> Publicar Excedente del Día
                    </h5>
                    <form action="publish_process.php" method="POST">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Título del Pack o Producto *</label>
                            <input type="text" name="titulo" class="form-control" placeholder="Ej: Docena de Chipas o Facturas" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Categoría</label>
                                <select name="categoria" class="form-select">
                                    <option value="Panadería">Panadería</option>
                                    <option value="Cafetería">Cafetería</option>
                                    <option value="Pastelería">Pastelería</option>
                                    <option value="Comida Preparada">Comida Preparada</option>
                                    <option value="Verdulería / Frutas">Frutas / Verduras</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Stock Disponible *</label>
                                <input type="number" name="stock" class="form-control" value="1" min="1" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Precio Original (Gs.) *</label>
                                <input type="number" step="500" name="precio_original" id="precio_orig" class="form-control" placeholder="50000" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold small">Precio Rescate (Gs.) *</label>
                                <input type="number" step="500" name="precio_descuento" id="precio_desc" class="form-control" placeholder="25000" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Horario de Retiro</label>
                            <input type="text" name="horario_retiro" class="form-control" placeholder="Ej: 19:30 a 20:30 hs">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Descripción breve</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalle qué incluye el pack..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-fr-primary w-100 py-2">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Publicar Excedente
                        </button>
                    </form>
                </div>
            </div>

            <!-- Listado de Mis Publicaciones -->
            <div class="col-12 col-lg-7">
                <div class="card fr-card border-0 p-4" id="mis-productos">
                    <h5 class="fw-bold mb-3 text-dark d-flex align-items-center">
                        <i class="bi bi-card-checklist text-primary me-2"></i> Mis Publicaciones Activas
                    </h5>

                    <?php if (empty($misProductos)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mt-2 mb-0">Aún no has publicado ningún excedente. ¡Comienza ahora!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Precio Original</th>
                                        <th>Precio Rescate</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($misProductos as $prod): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($prod['titulo']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($prod['horario_retiro'] ?: 'Sin horario') ?> | <?= htmlspecialchars($prod['categoria'] ?? 'Panadería') ?></small>
                                            </td>
                                            <td class="text-muted text-decoration-line-through small"><?= formatGs($prod['precio_original']) ?></td>
                                            <td class="fw-bold text-success"><?= formatGs($prod['precio_descuento']) ?></td>
                                            <td><span class="badge bg-secondary"><?= $prod['stock'] ?> un.</span></td>
                                            <td>
                                                <?php if ($prod['estado'] === 'disponible' && $prod['stock'] > 0): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Disponible</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Pausado/Agotado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <!-- Botón Editar -->
                                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditProd<?= $prod['id'] ?>" title="Editar publicación">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <!-- Botón Pausar/Activar -->
                                                    <form action="product_manage_process.php" method="POST" class="d-inline m-0">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="producto_id" value="<?= $prod['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-warning" title="<?= $prod['estado'] === 'disponible' ? 'Pausar publicación' : 'Activar publicación' ?>">
                                                            <i class="bi bi-<?= $prod['estado'] === 'disponible' ? 'pause-fill' : 'play-fill' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <!-- Botón Eliminar -->
                                                    <form action="product_manage_process.php" method="POST" class="d-inline m-0" onsubmit="return confirm('¿Seguro que deseas eliminar la publicación <?= htmlspecialchars($prod['titulo']) ?>?');">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="producto_id" value="<?= $prod['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                            <i class="bi bi-trash-fill"></i></button></form></div></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div> <!-- Fin de card #mis-productos -->

                <!-- Modales de Edición de Publicación fuera de la card -->
                <?php foreach ($misProductos as $prod): ?>
                            <div class="modal fade" id="modalEditProd<?= $prod['id'] ?>" tabindex="-1" aria-labelledby="modalEditLabel<?= $prod['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 rounded-4">
                                        <div class="modal-header bg-light border-0">
                                            <h5 class="modal-title fw-bold" id="modalEditLabel<?= $prod['id'] ?>">Editar Publicación</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="product_manage_process.php" method="POST">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="producto_id" value="<?= $prod['id'] ?>">

                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">Título del Pack *</label>
                                                    <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($prod['titulo']) ?>" required>
                                                </div>

                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">Categoría</label>
                                                        <select name="categoria" class="form-select">
                                                            <option value="Panadería" <?= ($prod['categoria'] === 'Panadería') ? 'selected' : '' ?>>Panadería</option>
                                                            <option value="Cafetería" <?= ($prod['categoria'] === 'Cafetería') ? 'selected' : '' ?>>Cafetería</option>
                                                            <option value="Pastelería" <?= ($prod['categoria'] === 'Pastelería') ? 'selected' : '' ?>>Pastelería</option>
                                                            <option value="Comida Preparada" <?= ($prod['categoria'] === 'Comida Preparada') ? 'selected' : '' ?>>Comida Preparada</option>
                                                            <option value="Verdulería / Frutas" <?= ($prod['categoria'] === 'Verdulería / Frutas') ? 'selected' : '' ?>>Frutas / Verduras</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">Stock Disponible *</label>
                                                        <input type="number" name="stock" class="form-control" value="<?= $prod['stock'] ?>" min="0" required>
                                                    </div>
                                                </div>

                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">Precio Original (Gs.) *</label>
                                                        <input type="number" step="500" name="precio_original" class="form-control" value="<?= (int)$prod['precio_original'] ?>" required>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">Precio Rescate (Gs.) *</label>
                                                        <input type="number" step="500" name="precio_descuento" class="form-control" value="<?= (int)$prod['precio_descuento'] ?>" required>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">Horario de Retiro</label>
                                                    <input type="text" name="horario_retiro" class="form-control" value="<?= htmlspecialchars($prod['horario_retiro'] ?? '') ?>">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">Descripción breve</label>
                                                    <textarea name="descripcion" class="form-control" rows="2"><?= htmlspecialchars($prod['descripcion'] ?? '') ?></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">Estado</label>
                                                    <select name="estado" class="form-select">
                                                        <option value="disponible" <?= ($prod['estado'] === 'disponible') ? 'selected' : '' ?>>Disponible para Rescate</option>
                                                        <option value="agotado" <?= ($prod['estado'] === 'agotado') ? 'selected' : '' ?>>Agotado / Pausado</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-fr-primary rounded-pill px-4">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
            </div>
        </div>

                <!-- Reservas Recibidas -->
                <div class="card fr-card border-0 p-4 mt-4" id="mis-reservas">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center">
                            <i class="bi bi-bag-check-fill text-success me-2"></i> Reservas de Clientes
                        </h5>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= count($misReservas) ?> registradas</span>
                    </div>

                    <?php if (empty($misReservas)): ?>
                        <p class="text-muted small mb-0">No tienes reservas registradas todavía.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($misReservas as $res): ?>
                                <div class="list-group-item px-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($res['producto_titulo']) ?></span>
                                            <span class="badge bg-light text-dark border">x<?= $res['cantidad'] ?> un.</span>
                                            <?php if ($res['estado'] === 'retirado'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">Entregado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill small">Pendiente de Retiro</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <i class="bi bi-person me-1"></i> Cliente: <strong><?= htmlspecialchars($res['cliente_nombre']) ?></strong> 
                                            | <i class="bi bi-telephone me-1"></i> Tel: <?= htmlspecialchars($res['cliente_telefono'] ?: 'No registrado') ?>
                                            | <i class="bi bi-cash me-1"></i> A cobrar: <strong class="text-success"><?= formatGs($res['precio_descuento'] * $res['cantidad']) ?></strong>
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y H:i', strtotime($res['fecha_reserva'])) ?>
                                        </small>
                                    </div>
                                    <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                                        <span class="badge bg-warning text-dark fs-6 font-monospace px-3 py-2 text-center">
                                            <?= htmlspecialchars($res['codigo_retiro']) ?>
                                        </span>
                                        <?php if ($res['estado'] === 'reservado'): ?>
                                            <form action="validate_process.php" method="POST" class="m-0" onsubmit="return confirm('¿Confirmas la entrega del pedido <?= $res['codigo_retiro'] ?> al cliente?');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="reserva_id" value="<?= $res['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 text-nowrap">
                                                    <i class="bi bi-check2-circle me-1"></i> Entregar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" disabled>
                                                <i class="bi bi-check-all me-1"></i> Completado
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php elseif ($userRole === 'cliente'): ?>
        <!-- ========================================== -->
        <!-- VISTA: DASHBOARD DEL CLIENTE               -->
        <!-- ========================================== -->

        <!-- Widget de Lealtad e Impacto Ecológico Personal -->
        <div class="row g-3 mb-4" id="mi-huella">
            <div class="col-12 col-lg-6">
                <div class="card fr-card border-0 p-4 h-100 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning-subtle text-warning fs-3" style="width: 58px; height: 58px;">
                            <i class="bi bi-award-fill"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="fw-bold text-dark mb-0">¡Hola, <?= htmlspecialchars($userName) ?>!</h5>
                                <span class="badge bg-warning text-dark fw-bold">Nivel <?= htmlspecialchars($puntosLealtad['nivel'] ?? 'Bronce') ?></span>
                            </div>
                            <p class="text-muted small mb-0 mt-1">
                                Tienes <strong><?= (int)($puntosLealtad['puntos'] ?? 0) ?></strong> puntos acumulados en <strong><?= (int)($puntosLealtad['total_compras'] ?? 0) ?></strong> compras.
                                <?php if (!empty($puntosLealtad['descuento_desbloqueado'])): ?>
                                    <span class="text-success fw-bold d-block mt-1"><i class="bi bi-gift-fill me-1"></i>¡Cupón de beneficio 15% OFF desbloqueado!</span>
                                <?php endif; ?>
                            </p>
                            
                            <!-- Barra de Progreso Gamificada -->
                            <div class="mt-2 pt-1 border-top">
                                <?php 
                                $puntosActuales = (int)($puntosLealtad['puntos'] ?? 0);
                                $nivelActual = $puntosLealtad['nivel'] ?? 'Bronce';
                                if ($nivelActual === 'Bronce') {
                                    $metaPuntos = 100;
                                    $porcentaje = min(100, round(($puntosActuales / 100) * 100));
                                    $falta = max(0, 100 - $puntosActuales);
                                    $siguienteNivel = "Plata";
                                } elseif ($nivelActual === 'Plata') {
                                    $metaPuntos = 300;
                                    $porcentaje = min(100, round((($puntosActuales - 100) / 200) * 100));
                                    $falta = max(0, 300 - $puntosActuales);
                                    $siguienteNivel = "Oro";
                                } else {
                                    $metaPuntos = 300;
                                    $porcentaje = 100;
                                    $falta = 0;
                                    $siguienteNivel = "Máximo (Oro)";
                                }
                                ?>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>Progreso a Nivel <?= $siguienteNivel ?>:</span>
                                    <span class="fw-bold text-dark"><?= $puntosActuales ?> / <?= $metaPuntos ?> pts</span>
                                </div>
                                <div class="loyalty-progress-container">
                                    <div class="loyalty-progress-bar" style="width: <?= $porcentaje ?>%;"></div>
                                </div>
                                <?php if ($falta > 0): ?>
                                    <small class="text-secondary" style="font-size: 0.75rem;"><i class="bi bi-info-circle me-1 text-primary"></i>¡Te faltan solo <strong><?= $falta ?> puntos</strong> (<?= ceil($falta / 10) ?> packs más) para subir a Nivel <?= $siguienteNivel ?>!</small>
                                <?php else: ?>
                                    <small class="text-success fw-bold" style="font-size: 0.75rem;"><i class="bi bi-star-fill text-warning me-1"></i>¡Felicidades! Eres miembro embajador de nivel máximo.</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card fr-card border-0 p-4 h-100 bg-white">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                        <i class="bi bi-tree-fill text-success me-2"></i> Mi Huella Verde de Rescate
                    </h6>
                    <div class="row text-center g-2">
                        <div class="col-4">
                            <div class="bg-light p-2 rounded-3">
                                <div class="fs-5 fw-bold text-dark"><?= number_format($clienteImpacto['kg'], 1) ?> kg</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">Comida Salvada</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light p-2 rounded-3">
                                <div class="fs-5 fw-bold text-primary"><?= number_format($clienteImpacto['co2'], 1) ?> kg</div>
                                <div class="text-muted small" style="font-size: 0.75rem;">CO₂e Evitado</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light p-2 rounded-3">
                                <div class="fs-5 fw-bold text-success"><?= formatGs($clienteImpacto['ahorro']) ?></div>
                                <div class="text-muted small" style="font-size: 0.75rem;">Dinero Ahorrado</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catálogo de Productos Disponibles con Filtros -->
        <div class="card fr-card border-0 p-4 mb-4 bg-white" id="catalogo">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-tag-fill text-success me-2"></i>Excedentes Disponibles para Rescate</h3>
                    <p class="text-muted small mb-0">Reserva online y retira en el comercio antes del horario de cierre</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Cambiar vista">
                        <button type="button" class="btn btn-outline-success active" id="btnViewGridCli"><i class="bi bi-grid-fill me-1"></i> Cuadrícula</button>
                        <button type="button" class="btn btn-outline-success" id="btnViewMapCli"><i class="bi bi-geo-alt-fill me-1"></i> Ver Mapa</button>
                    </div>
                    <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold" id="catalogCounterCli">
                        <?= count($catalogoProductos) ?> disponibles
                    </span>
                </div>
            </div>

            <!-- Filtros por categoría y barra de búsqueda -->
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="catalogSearchCli" class="form-control border-start-0" placeholder="Buscar por producto o local (ej. baguettes, chipas, café)...">
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="filter-pills-scroll justify-content-start justify-content-md-end filter-pills">
                        <button type="button" class="btn btn-sm btn-dark rounded-pill px-3 active filter-btn-cli" data-category="all">Todos</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn-cli" data-category="Panadería">🥖 Panadería</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn-cli" data-category="Cafetería">☕ Cafetería</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 filter-btn-cli" data-category="Pastelería">🍰 Pastelería</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista de Mapa Interactivo para Clientes -->
        <div id="mapViewContainerCli" class="d-none mb-5">
            <div id="rescueMapCli" class="map-container"></div>
            <p class="text-muted small mt-2 text-center"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Comercios con excedentes disponibles en Itauguá y alrededores. Haz clic en un marcador para reservar directamente.</p>
        </div>

        <div class="row g-4 mb-5" id="productsGridCli">
            <?php if (empty($catalogoProductos)): ?>
                <div class="col-12 text-center py-5">
                    <div class="stat-icon bg-light text-muted mx-auto mb-2" style="width: 64px; height: 64px;">
                        <i class="bi bi-cup-hot fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No hay excedentes disponibles en este momento</h5>
                    <p class="text-muted small">Los comercios suelen publicar entre las 18:00 y las 21:00 hs. ¡Vuelve pronto!</p>
                </div>
            <?php else: ?>
                <?php foreach ($catalogoProductos as $prod): 
                    $descuentoPorc = round((($prod['precio_original'] - $prod['precio_descuento']) / $prod['precio_original']) * 100);
                ?>
                    <div class="col-12 col-md-6 col-lg-4 product-item-cli"
                         data-category="<?= htmlspecialchars($prod['categoria'] ?? 'Panadería') ?>"
                         data-title="<?= strtolower(htmlspecialchars($prod['titulo'] . ' ' . $prod['comercio_nombre'] . ' ' . ($prod['descripcion'] ?? ''))) ?>">
                        <div class="card fr-card h-100 d-flex flex-column border-0">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold small">
                                        <?= htmlspecialchars($prod['categoria'] ?? 'Panadería') ?>
                                    </span>
                                    <span class="badge badge-discount">-<?= $descuentoPorc ?>% OFF</span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-2 mb-1">
                                    <h5 class="card-title fw-bold text-dark mb-0"><?= htmlspecialchars($prod['titulo']) ?></h5>
                                </div>
                                <div class="d-flex align-items-center gap-1 small text-warning mb-1">
                                    <i class="bi bi-star-fill"></i>
                                    <span class="fw-bold text-dark"><?= number_format($prod['promedio_estrellas'] ?? 4.9, 1) ?></span>
                                    <span class="text-muted small">(<?= $prod['total_resenas'] ?? 14 ?> reseñas)</span>
                                </div>
                                <p class="text-muted small mb-2"><i class="bi bi-shop me-1 text-secondary"></i> <?= htmlspecialchars($prod['comercio_nombre']) ?></p>
                                <p class="card-text text-secondary small flex-grow-1"><?= htmlspecialchars($prod['descripcion'] ?: 'Pack sorpresa con delicias del día preparadas por el comercio.') ?></p>
                                
                                <div class="bg-light p-3 rounded-3 mb-3 small text-muted">
                                    <div class="mb-1"><i class="bi bi-geo-alt text-danger me-1"></i> <?= htmlspecialchars($prod['comercio_direccion'] ?: 'Consultar al comercio') ?></div>
                                    <div><i class="bi bi-clock text-primary me-1"></i> Retiro: <strong><?= htmlspecialchars($prod['horario_retiro'] ?: 'Hoy') ?></strong></div>
                                </div>

                                <div class="d-flex align-items-baseline justify-content-between pt-2 border-top">
                                    <div>
                                        <span class="price-original me-2"><?= formatGs($prod['precio_original']) ?></span>
                                        <span class="price-discounted"><?= formatGs($prod['precio_descuento']) ?></span>
                                    </div>
                                    <span class="badge badge-stock"><i class="bi bi-box-seam me-1"></i><?= $prod['stock'] ?> disp.</span>
                                </div>

                                <div class="mt-3">
                                    <button type="button" class="btn btn-fr-primary w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalReserva<?= $prod['id'] ?>">
                                        <i class="bi bi-bag-check-fill me-1"></i> Reservar Pack
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de Reserva para cada producto -->
                    <div class="modal fade" id="modalReserva<?= $prod['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $prod['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 rounded-4">
                                <div class="modal-header bg-light border-0">
                                    <h5 class="modal-title fw-bold" id="modalLabel<?= $prod['id'] ?>">Confirmar Reserva</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="reserve_process.php" method="POST">
                                    <?= csrfField() ?>
                                    <div class="modal-body p-4">
                                        <input type="hidden" name="producto_id" value="<?= $prod['id'] ?>">
                                        
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($prod['titulo']) ?></h6>
                                        <p class="text-muted small mb-3"><?= htmlspecialchars($prod['comercio_nombre']) ?> - <?= htmlspecialchars($prod['comercio_direccion']) ?></p>

                                        <div class="alert alert-warning-subtle text-dark border-0 small mb-3">
                                            <i class="bi bi-clock-history me-1 text-warning"></i> 
                                            Recuerda que debes retirar tu pedido en el horario: <strong><?= htmlspecialchars($prod['horario_retiro'] ?: 'Horario de cierre') ?></strong>.
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">Cantidad a rescatar (Máx <?= $prod['stock'] ?>):</label>
                                            <input type="number" name="cantidad" class="form-control" value="1" min="1" max="<?= $prod['stock'] ?>" required>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center py-2 border-top">
                                            <span class="text-muted">Total a abonar en el local:</span>
                                            <span class="fs-4 fw-bold text-success"><?= formatGs($prod['precio_descuento']) ?></span>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-fr-primary rounded-pill px-4">Confirmar y Obtener Código</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="noResultsMessageCli" class="col-12 text-center py-5 d-none mb-4">
            <i class="bi bi-search fs-1 text-muted"></i>
            <h5 class="fw-bold mt-3 text-dark">No se encontraron excedentes para rescatar</h5>
            <p class="text-muted small">Prueba ajustando el texto de búsqueda o cambiando la categoría seleccionada.</p>
        </div>

        <!-- Mis Reservas Activas -->
        <?php if (!empty($misReservas)): ?>
            <div class="card fr-card border-0 p-4" id="mis-reservas">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0 text-dark d-flex align-items-center">
                        <i class="bi bi-receipt text-primary me-2"></i> Mis Reservas Activas
                    </h4>
                    <span class="badge bg-primary-subtle text-primary rounded-pill"><?= count($misReservas) ?> órdenes</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Código de Retiro</th>
                                <th>Producto / Comercio</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                                <th>Horario Retiro</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones / Pase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($misReservas as $res): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-warning text-dark font-monospace fs-6 px-2 py-1">
                                            <?= htmlspecialchars($res['codigo_retiro']) ?>
                                        </span>
                                        <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y H:i', strtotime($res['fecha_reserva'])) ?> hs
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($res['producto_titulo']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($res['comercio_nombre']) ?> - <?= htmlspecialchars($res['comercio_direccion']) ?></small>
                                    </td>
                                    <td><?= $res['cantidad'] ?> un.</td>
                                    <td class="fw-bold text-success"><?= formatGs($res['precio_descuento'] * $res['cantidad']) ?></td>
                                    <td class="small"><?= htmlspecialchars($res['horario_retiro'] ?: 'Hoy') ?></td>
                                    <td>
                                        <?php if ($res['estado'] === 'reservado'): ?>
                                            <span class="badge bg-primary rounded-pill">Listo para Retirar</span>
                                        <?php else: ?>
                                            <span class="badge bg-success rounded-pill">Retirado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalQR<?= $res['id'] ?>">
                                                <i class="bi bi-qr-code me-1"></i> QR
                                            </button>
                                            <?php if ($res['estado'] === 'retirado'): ?>
                                                <?php if (!empty($res['resena_calificacion'])): ?>
                                                    <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1 rounded-pill d-flex align-items-center">
                                                        <i class="bi bi-star-fill text-warning me-1"></i><?= $res['resena_calificacion'] ?>/5
                                                    </span>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-warning text-dark rounded-pill px-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalReview<?= $res['id'] ?>" title="Calificar producto">
                                                        <i class="bi bi-star-fill me-1"></i> Calificar
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- Fin de card #mis-reservas -->

            <!-- Modales de Reservas (Calificación y Pase QR) fuera de la card -->
            <?php foreach ($misReservas as $res): ?>
                    <!-- Modal Calificar Reserva Retirada -->
                    <?php if ($res['estado'] === 'retirado' && empty($res['resena_calificacion'])): ?>
                    <div class="modal fade" id="modalReview<?= $res['id'] ?>" tabindex="-1" aria-labelledby="modalRevLabel<?= $res['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 rounded-4">
                                <div class="modal-header bg-warning text-dark border-0">
                                    <h5 class="modal-title fw-bold" id="modalRevLabel<?= $res['id'] ?>">⭐ Calificar Pack Rescatado</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="review_process.php" method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="reserva_id" value="<?= $res['id'] ?>">
                                    <div class="modal-body p-4 text-center">
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($res['producto_titulo']) ?></h6>
                                        <p class="text-muted small mb-3"><?= htmlspecialchars($res['comercio_nombre']) ?></p>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">¿Qué te pareció la calidad y frescura de la comida?</label>
                                            <select name="calificacion" class="form-select text-center fs-5 fw-bold text-warning-emphasis">
                                                <option value="5" selected>⭐⭐⭐⭐⭐ Excelente (5/5)</option>
                                                <option value="4">⭐⭐⭐⭐ Muy Bueno (4/5)</option>
                                                <option value="3">⭐⭐⭐ Bueno (3/5)</option>
                                                <option value="2">⭐⭐ Regular (2/5)</option>
                                                <option value="1">⭐ Malo (1/5)</option>
                                            </select>
                                        </div>

                                        <div class="mb-3 text-start">
                                            <label class="form-label fw-semibold small">Deja un comentario breve (opcional):</label>
                                            <textarea name="comentario" class="form-control" rows="2" placeholder="Ej: ¡Deliciosas facturas, súper frescas y gran atención en el local!"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0 justify-content-center">
                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-fr-primary rounded-pill px-4">Publicar Opinión</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Modal Pase de Retiro y QR para el cliente -->
                    <div class="modal fade" id="modalQR<?= $res['id'] ?>" tabindex="-1" aria-labelledby="modalQRLabel<?= $res['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                                <div class="modal-header bg-success text-white border-0 py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-basket2-fill fs-4"></i>
                                        <h5 class="modal-title fw-bold" id="modalQRLabel<?= $res['id'] ?>">Pase de Rescate Digital</h5>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4 text-center">
                                    <?php
                                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? 'https' : 'http';
                                    $hostActual = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                    $baseDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
                                    $urlValidacionQR = "{$scheme}://{$hostActual}{$baseDir}/validar_pase.php?codigo=" . urlencode($res['codigo_retiro']);
                                    ?>
                                    <div class="p-3 bg-white rounded-4 mb-3 border d-inline-flex align-items-center justify-content-center shadow-sm">
                                        <div id="qrContainer_<?= $res['id'] ?>" class="qr-canvas-holder" data-qr-code="<?= htmlspecialchars($urlValidacionQR) ?>" style="width: 170px; height: 170px;"></div>
                                    </div>

                                    <div class="mb-3">
                                        <span class="text-muted small text-uppercase tracking-wider d-block">Código Alfanumérico de Canje</span>
                                        <div class="fs-2 fw-extrabold font-monospace text-dark tracking-wide bg-warning-subtle py-1 px-3 rounded-3 d-inline-block border border-warning my-1">
                                            <?= htmlspecialchars($res['codigo_retiro']) ?>
                                        </div>
                                        <div class="small text-muted my-1">
                                            <i class="bi bi-calendar3 me-1"></i> Reservado: <strong><?= date('d/m/Y H:i', strtotime($res['fecha_reserva'])) ?> hs</strong>
                                            <span class="mx-1">•</span>
                                            <i class="bi bi-clock me-1"></i> Retiro: <strong><?= htmlspecialchars($res['horario_retiro'] ?: 'Hoy') ?></strong>
                                        </div>
                                        <div class="mt-2">
                                            <a href="validar_pase.php?codigo=<?= urlencode($res['codigo_retiro']) ?>" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1 fw-semibold shadow-sm">
                                                <i class="bi bi-box-arrow-up-right me-1"></i> Abrir Verificación en Vivo / Simular Escaneo
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Botones de Acción Rápida: Descargar, Copiar y Compartir -->
                                    <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">
                                        <button type="button" class="btn btn-success rounded-pill px-3 py-2 btn-sm fw-semibold shadow-sm" 
                                                onclick="downloadRescuePass(<?= $res['id'] ?>, '<?= htmlspecialchars($res['codigo_retiro']) ?>', '<?= htmlspecialchars(addslashes($res['producto_titulo'])) ?>', '<?= htmlspecialchars(addslashes($res['comercio_nombre'])) ?>', '<?= htmlspecialchars(addslashes($res['horario_retiro'] ?: 'Hoy')) ?>', '<?= formatGs($res['precio_descuento'] * $res['cantidad']) ?>')">
                                            <i class="bi bi-download me-1"></i> Guardar Imagen / Pase
                                        </button>
                                        <button type="button" class="btn btn-outline-dark rounded-pill px-3 py-2 btn-sm fw-medium" 
                                                onclick="copyRescueCode('<?= htmlspecialchars($res['codigo_retiro']) ?>', this)">
                                            <i class="bi bi-clipboard me-1"></i> Copiar Código
                                        </button>
                                        <a href="https://api.whatsapp.com/send?text=<?= urlencode("🎟️ *Mi Pase de Rescate FoodRescue*\n\n📌 *Código de Retiro:* " . $res['codigo_retiro'] . "\n🛍️ *Pack:* " . $res['producto_titulo'] . " (x" . $res['cantidad'] . ")\n🏪 *Comercio:* " . $res['comercio_nombre'] . "\n📍 *Dirección:* " . ($res['comercio_direccion'] ?: 'Itauguá') . "\n⏰ *Horario de retiro:* " . ($res['horario_retiro'] ?: 'Hoy') . "\n💵 *Total a pagar:* " . formatGs($res['precio_descuento'] * $res['cantidad'])) ?>" 
                                           target="_blank" class="btn btn-outline-success rounded-pill px-3 py-2 btn-sm fw-medium">
                                            <i class="bi bi-whatsapp me-1"></i> Compartir
                                        </a>
                                    </div>

                                    <div class="text-start bg-light p-3 rounded-3 mb-3 small">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Pack Rescatado:</span>
                                            <strong class="text-dark"><?= htmlspecialchars($res['producto_titulo']) ?> (x<?= $res['cantidad'] ?>)</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Comercio:</span>
                                            <strong class="text-dark"><?= htmlspecialchars($res['comercio_nombre']) ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Dirección:</span>
                                            <span class="text-dark text-truncate ms-2"><?= htmlspecialchars($res['comercio_direccion'] ?: 'Consultar al local') ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Horario de retiro:</span>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($res['horario_retiro'] ?: 'Hoy') ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between pt-2 border-top">
                                            <span class="fw-bold">Total a pagar en mostrador:</span>
                                            <span class="fw-bold text-success fs-6"><?= formatGs($res['precio_descuento'] * $res['cantidad']) ?></span>
                                        </div>
                                    </div>

                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-info-circle me-1 text-primary"></i> Muestra este código o el código QR en la caja del local al momento de retirar tu pedido.
                                    </p>
                                </div>
                                <div class="modal-footer border-0 bg-light py-2 justify-content-center">
                                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>

</div>

<!-- JavaScript para Búsqueda, Filtrado y Mapa Interactivo -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Filtrado en tiempo real por texto y categoría
    function setupCatalogFilter(searchInputId, filterBtnClass, itemClass, counterId, noResultsId) {
        const searchInput = document.getElementById(searchInputId);
        const filterBtns = document.querySelectorAll('.' + filterBtnClass);
        const items = document.querySelectorAll('.' + itemClass);
        const counter = document.getElementById(counterId);
        const noResults = document.getElementById(noResultsId);

        if (!items.length) return;

        let activeCategory = 'all';

        function applyFilter() {
            const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
            let visibleCount = 0;

            items.forEach(item => {
                const category = item.getAttribute('data-category') || '';
                const titleText = item.getAttribute('data-title') || '';

                const matchesCategory = (activeCategory === 'all' || category.toLowerCase() === activeCategory.toLowerCase());
                const matchesSearch = (!query || titleText.includes(query));

                if (matchesCategory && matchesSearch) {
                    item.classList.remove('d-none');
                    visibleCount++;
                } else {
                    item.classList.add('d-none');
                }
            });

            if (counter) {
                counter.textContent = `${visibleCount} disponible${visibleCount === 1 ? '' : 's'}`;
            }

            if (noResults) {
                if (visibleCount === 0) {
                    noResults.classList.remove('d-none');
                } else {
                    noResults.classList.add('d-none');
                }
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilter);
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                filterBtns.forEach(b => {
                    b.classList.remove('btn-dark', 'active');
                    b.classList.add('btn-outline-secondary');
                });
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-dark', 'active');

                activeCategory = btn.getAttribute('data-category') || 'all';
                applyFilter();
            });
        });
    }

    setupCatalogFilter('catalogSearch', 'filter-btn', 'product-item', 'catalogCounter', 'noResultsMessage');
    setupCatalogFilter('catalogSearchCli', 'filter-btn-cli', 'product-item-cli', 'catalogCounterCli', 'noResultsMessageCli');

    // 2. Integración de Leaflet Maps con OpenStreetMap
    const mapPointsData = <?= json_encode($mapPoints ?? [], JSON_UNESCAPED_UNICODE) ?>;

    function initRescueMap(containerId, isClient = false) {
        const container = document.getElementById(containerId);
        if (!container || typeof L === 'undefined') return null;

        // Centrado en Itauguá, Departamento Central, Paraguay
        const map = L.map(containerId).setView([-25.3850, -57.3400], 14);

        // Capa de mapas OpenStreetMap (100% abierta y sin API key requerida)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Icono de pin personalizado de FoodRescue
        const rescueIcon = L.divIcon({
            className: 'custom-map-pin',
            html: `<div style="background:#059669; color:white; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(5,150,105,0.45); border:2.5px solid white; font-size:16px;"><i class="bi bi-basket2-fill"></i></div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -18]
        });

        const bounds = [];

        mapPointsData.forEach(p => {
            if (!p.lat || !p.lng) return;

            bounds.push([p.lat, p.lng]);

            const actionBtn = isClient
                ? `<button type="button" class="btn btn-fr-primary btn-sm w-100 rounded-pill mt-2" data-bs-toggle="modal" data-bs-target="#modalReserva${p.id}"><i class="bi bi-bag-check-fill me-1"></i> Reservar (${p.precio_descuento})</button>`
                : `<a href="login.php" class="btn btn-fr-primary btn-sm w-100 rounded-pill mt-2"><i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión para Reservar</a>`;

            const popupContent = `
                <div class="map-popup-card" style="min-width: 220px;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small" style="font-size: 0.7rem;">${p.categoria}</span>
                        <span class="badge badge-discount" style="font-size: 0.7rem;">-${p.descuento_porc}% OFF</span>
                    </div>
                    <h6 class="fw-bold text-dark mb-0 mt-1">${p.titulo}</h6>
                    <div class="shop-name"><i class="bi bi-shop me-1"></i>${p.comercio}</div>
                    <div class="text-muted small mb-1"><i class="bi bi-geo-alt text-danger me-1"></i>${p.direccion}</div>
                    <div class="text-muted small"><i class="bi bi-clock text-primary me-1"></i>Retiro: <strong>${p.horario}</strong></div>
                    <div class="price-row">
                        <div>
                            <span class="price-original small me-1">${p.precio_original}</span>
                            <span class="price-discounted fs-6">${p.precio_descuento}</span>
                        </div>
                        <span class="badge badge-stock small">${p.stock} disp.</span>
                    </div>
                    ${actionBtn}
                </div>
            `;

            L.marker([p.lat, p.lng], { icon: rescueIcon })
                .addTo(map)
                .bindPopup(popupContent);
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [40, 40] });
        }

        return map;
    }

    // 3. Conmutador de Vistas: Cuadrícula vs Mapa
    function setupViewSwitcher(btnGridId, btnMapId, gridId, mapContainerId, mapInstanceGetter) {
        const btnGrid = document.getElementById(btnGridId);
        const btnMap = document.getElementById(btnMapId);
        const grid = document.getElementById(gridId);
        const mapContainer = document.getElementById(mapContainerId);

        if (!btnGrid || !btnMap || !grid || !mapContainer) return;

        let mapInstance = null;

        btnMap.addEventListener('click', () => {
            btnMap.classList.add('active');
            btnGrid.classList.remove('active');
            grid.classList.add('d-none');
            mapContainer.classList.remove('d-none');

            if (!mapInstance) {
                mapInstance = mapInstanceGetter();
            } else {
                setTimeout(() => mapInstance.invalidateSize(), 200);
            }
        });

        btnGrid.addEventListener('click', () => {
            btnGrid.classList.add('active');
            btnMap.classList.remove('active');
            mapContainer.classList.add('d-none');
            grid.classList.remove('d-none');
        });

        // Auto-ajuste dinámico de pantalla al rotar o redimensionar dispositivo
        window.addEventListener('resize', () => {
            if (mapInstance && !mapContainer.classList.contains('d-none')) {
                mapInstance.invalidateSize();
            }
        });
        window.addEventListener('orientationchange', () => {
            if (mapInstance && !mapContainer.classList.contains('d-none')) {
                setTimeout(() => mapInstance.invalidateSize(), 300);
            }
        });
    }

    // Inicializar para visitantes
    setupViewSwitcher('btnViewGrid', 'btnViewMap', 'productsGrid', 'mapViewContainer', () => initRescueMap('rescueMap', false));

    // Inicializar para clientes
    setupViewSwitcher('btnViewGridCli', 'btnViewMapCli', 'productsGridCli', 'mapViewContainerCli', () => initRescueMap('rescueMapCli', true));

    // 4. Generación instantánea local de códigos QR (0ms latencia, sin parpadeos de red)
    if (typeof QRCode !== 'undefined') {
        document.querySelectorAll('.qr-canvas-holder').forEach(container => {
            const qrCodeValue = container.getAttribute('data-qr-code');
            if (qrCodeValue && !container.hasChildNodes()) {
                new QRCode(container, {
                    text: qrCodeValue,
                    width: 170,
                    height: 170,
                    colorDark: '#064e3b',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            }
        });
    }

    // 5. Calculadora Interactiva de Impacto FoodRescue (Startup Calculator)
    const rangeMerma = document.getElementById('rangeMerma');
    const lblMermaVal = document.getElementById('lblMermaVal');
    const resB2BMes = document.getElementById('resB2BMes');
    const resB2BAnio = document.getElementById('resB2BAnio');
    const resB2BKg = document.getElementById('resB2BKg');
    const resB2BComision = document.getElementById('resB2BComision');

    function formatGsJs(val) {
        return '₲ ' + Math.round(val).toLocaleString('es-PY');
    }

    if (rangeMerma && lblMermaVal) {
        rangeMerma.addEventListener('input', () => {
            const val = parseFloat(rangeMerma.value);
            lblMermaVal.textContent = formatGsJs(val) + ' / día';
            
            const mesBruto = val * 30;
            const mesNeto = mesBruto * 0.85;
            const anioNeto = val * 365 * 0.85;
            const comision = mesBruto * 0.15;
            const kgAnio = Math.round((val / 20000) * 1.2 * 365);

            if (resB2BMes) resB2BMes.textContent = formatGsJs(mesNeto);
            if (resB2BAnio) resB2BAnio.textContent = formatGsJs(anioNeto);
            if (resB2BKg) resB2BKg.textContent = kgAnio.toLocaleString('es-PY') + ' kg';
            if (resB2BComision) resB2BComision.textContent = formatGsJs(comision) + ' / mes';
        });
    }

    const rangePacks = document.getElementById('rangePacks');
    const lblPacksVal = document.getElementById('lblPacksVal');
    const resB2CAhorro = document.getElementById('resB2CAhorro');
    const resB2CKg = document.getElementById('resB2CKg');
    const resB2CCO2 = document.getElementById('resB2CCO2');
    const resB2CArboles = document.getElementById('resB2CArboles');

    if (rangePacks && lblPacksVal) {
        rangePacks.addEventListener('input', () => {
            const packs = parseInt(rangePacks.value);
            lblPacksVal.textContent = packs + (packs === 1 ? ' pack / semana' : ' packs / semana');

            const ahorroAnio = packs * 52 * 22000;
            const kgAnio = Math.round(packs * 52 * 1.2);
            const co2Anio = Math.round(packs * 52 * 2.5);
            const arboles = Math.max(1, Math.round(co2Anio / 21.7)); // 1 árbol absorbe aprox 21.7 kg CO2/año

            if (resB2CAhorro) resB2CAhorro.textContent = formatGsJs(ahorroAnio);
            if (resB2CKg) resB2CKg.textContent = kgAnio.toLocaleString('es-PY') + ' kg / año';
            if (resB2CCO2) resB2CCO2.textContent = co2Anio.toLocaleString('es-PY') + ' kg CO₂e';
            if (resB2CArboles) resB2CArboles.textContent = '🌳 ' + arboles + ' Árboles';
        });
    }
});

// Función para Copiar el Código de Retiro al Portapapeles
function copyRescueCode(code, btn) {
    if (!navigator.clipboard) {
        const temp = document.createElement('input');
        temp.value = code;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
    } else {
        navigator.clipboard.writeText(code);
    }
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> ¡Copiado!';
    btn.classList.remove('btn-outline-dark');
    btn.classList.add('btn-success');
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-dark');
    }, 2000);
}

// Función para Descargar el Pase Digital como Imagen PNG
function downloadRescuePass(resId, codigo, producto, comercio, horario, total) {
    const qrContainer = document.getElementById('qrContainer_' + resId);
    if (!qrContainer) return;

    // Obtener elemento de imagen o canvas del QR
    const qrCanvas = qrContainer.querySelector('canvas');
    const qrImg = qrContainer.querySelector('img');

    const canvas = document.createElement('canvas');
    canvas.width = 640;
    canvas.height = 820;
    const ctx = canvas.getContext('2d');

    // Fondo General
    ctx.fillStyle = '#f8fafc';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Tarjeta Blanca
    ctx.fillStyle = '#ffffff';
    if (ctx.roundRect) {
        ctx.roundRect(24, 24, 592, 772, 24);
    } else {
        ctx.rect(24, 24, 592, 772);
    }
    ctx.fill();
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 2;
    ctx.stroke();

    // Encabezado Verde Degradado
    const grad = ctx.createLinearGradient(24, 24, 616, 120);
    grad.addColorStop(0, '#064e3b');
    grad.addColorStop(1, '#059669');
    ctx.fillStyle = grad;
    ctx.beginPath();
    if (ctx.roundRect) {
        ctx.roundRect(24, 24, 592, 96, [24, 24, 0, 0]);
    } else {
        ctx.rect(24, 24, 592, 96);
    }
    ctx.fill();

    // Textos Encabezado
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 26px Inter, Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('FoodRescue', 320, 64);
    ctx.font = 'bold 13px Inter, Arial, sans-serif';
    ctx.fillStyle = '#a7f3d0';
    ctx.fillText('PASE DE RESCATE DIGITAL', 320, 92);

    // Contenedor QR
    ctx.fillStyle = '#f8fafc';
    ctx.beginPath();
    if (ctx.roundRect) {
        ctx.roundRect(220, 140, 200, 200, 16);
    } else {
        ctx.rect(220, 140, 200, 200);
    }
    ctx.fill();
    ctx.strokeStyle = '#e2e8f0';
    ctx.stroke();

    // Dibujar QR
    const qrSource = qrCanvas || (qrImg && qrImg.complete ? qrImg : null);
    if (qrSource) {
        ctx.drawImage(qrSource, 235, 155, 170, 170);
    }

    // Etiqueta y Caja del Código
    ctx.fillStyle = '#64748b';
    ctx.font = '600 12px Inter, Arial, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('CÓDIGO ALFANUMÉRICO DE CANJE', 320, 368);

    ctx.fillStyle = '#fef3c7';
    ctx.beginPath();
    if (ctx.roundRect) {
        ctx.roundRect(170, 382, 300, 52, 12);
    } else {
        ctx.rect(170, 382, 300, 52);
    }
    ctx.fill();
    ctx.strokeStyle = '#f59e0b';
    ctx.lineWidth = 1.5;
    ctx.stroke();

    ctx.fillStyle = '#1e293b';
    ctx.font = 'bold 28px monospace';
    ctx.fillText(codigo, 320, 418);

    // Línea divisoria punteada
    ctx.strokeStyle = '#cbd5e1';
    ctx.lineWidth = 2;
    ctx.setLineDash([8, 6]);
    ctx.beginPath();
    ctx.moveTo(48, 456);
    ctx.lineTo(592, 456);
    ctx.stroke();
    ctx.setLineDash([]);

    // Caja de Detalles
    ctx.fillStyle = '#f1f5f9';
    ctx.beginPath();
    if (ctx.roundRect) {
        ctx.roundRect(48, 475, 544, 220, 16);
    } else {
        ctx.rect(48, 475, 544, 220);
    }
    ctx.fill();

    ctx.textAlign = 'left';
    ctx.fillStyle = '#64748b';
    ctx.font = '13px Inter, Arial, sans-serif';
    ctx.fillText('Pack Rescatado:', 70, 508);

    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 15px Inter, Arial, sans-serif';
    const prodTitle = producto.length > 40 ? producto.substring(0, 37) + '...' : producto;
    ctx.fillText(prodTitle, 70, 532);

    ctx.fillStyle = '#64748b';
    ctx.font = '13px Inter, Arial, sans-serif';
    ctx.fillText('Comercio:', 70, 570);

    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 15px Inter, Arial, sans-serif';
    ctx.fillText(comercio, 70, 594);

    ctx.fillStyle = '#64748b';
    ctx.font = '13px Inter, Arial, sans-serif';
    ctx.fillText('Horario de retiro: ' + horario, 70, 630);

    // Línea interna total
    ctx.strokeStyle = '#e2e8f0';
    ctx.beginPath();
    ctx.moveTo(70, 646);
    ctx.lineTo(570, 646);
    ctx.stroke();

    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 15px Inter, Arial, sans-serif';
    ctx.fillText('Total a abonar en local:', 70, 674);

    ctx.fillStyle = '#059669';
    ctx.font = 'bold 20px Inter, Arial, sans-serif';
    ctx.textAlign = 'right';
    ctx.fillText(total, 570, 674);

    // Mensaje pie
    ctx.textAlign = 'center';
    ctx.fillStyle = '#94a3b8';
    ctx.font = '12px Inter, Arial, sans-serif';
    ctx.fillText('Presenta este pase en caja al retirar · ¡Gracias por salvar comida!', 320, 755);

    // Descargar Archivo PNG
    const link = document.createElement('a');
    link.download = 'Pase_FoodRescue_' + codigo + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

