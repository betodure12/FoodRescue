<?php
require_once __DIR__ . '/../config/db.php';
initSecureSession();
sendSecurityHeaders();

$isLoggedIn = isset($_SESSION['usuario_id']);
$userRole = $_SESSION['usuario_rol'] ?? null;
$userName = $_SESSION['usuario_nombre'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . " - " : "" ?>FoodRescue | Startup de Rescate Alimentario</title>
    
    <!-- Meta Tags para Experiencia Móvil / PWA (Rubro: Startup -> Móvil) -->
    <meta name="theme-color" content="#064e3b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FoodRescue">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="manifest.json">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Leaflet CSS para Mapas -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- QRCode Local Engine -->
    <script src="assets/js/qrcode.min.js"></script>
</head>
<body>

<!-- Barra Superior Informativa de la Startup (Desktop & Notebooks) -->
<div class="startup-top-bar d-none d-lg-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.72rem;"><i class="bi bi-rocket-takeoff-fill me-1"></i> STARTUP TECH</span>
            <span class="text-truncate">Economía Circular & Rescate de Alimentos · Itauguá, Central</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span><i class="bi bi-recycle text-warning me-1"></i> Triple Impacto ESG & ODS 12/13</span>
            <span class="text-white-50">|</span>
            <button type="button" class="btn btn-link text-white text-decoration-none p-0 small fw-semibold" data-bs-toggle="modal" data-bs-target="#modalPitchDeck">
                <i class="bi bi-file-earmark-slides text-warning me-1"></i> Pitch Deck & Business Model
            </button>
        </div>
    </div>
</div>

<!-- Navegación Superior Responsiva -->
<nav class="navbar navbar-expand-lg custom-nav sticky-top">
    <div class="container">
        <a class="navbar-brand navbar-brand-foodrescue" href="index.php">
            <span class="brand-icon"><i class="bi bi-basket2-fill"></i></span>
            <span>Food<span class="text-warning">Rescue</span></span>
            <span class="d-none d-sm-inline badge bg-success-subtle text-success border border-success-subtle fw-semibold rounded-pill ms-1" style="font-size: 0.7rem;">Startup</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none p-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarFoodRescue" aria-controls="navbarFoodRescue" aria-expanded="false" aria-label="Abrir menú de navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarFoodRescue">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link fw-medium" href="index.php"><i class="bi bi-house-door me-1"></i> Inicio</a>
                </li>
                <?php if ($isLoggedIn && $userRole === 'comercio'): ?>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="index.php#mis-productos"><i class="bi bi-tags me-1"></i> Publicaciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="index.php#validador-caja"><i class="bi bi-qr-code-scan me-1"></i> Validador</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="index.php#mis-reservas"><i class="bi bi-receipt me-1"></i> Reservas</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="index.php#impacto-esg"><i class="bi bi-graph-up-arrow me-1"></i> Impacto ESG</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="index.php#como-funciona"><i class="bi bi-arrow-repeat me-1"></i> Cómo Funciona</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="index.php#calculadora-impacto"><i class="bi bi-calculator me-1"></i> Calculadora</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="index.php#catalogo"><i class="bi bi-basket me-1"></i> Packs & Mapa</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link fw-medium" href="index.php#ecosistema-api"><i class="bi bi-braces me-1"></i> APIs REST</a>
                </li>
            </ul>

            <div class="d-flex flex-wrap align-items-center gap-2 mt-3 mt-lg-0 pt-2 pt-lg-0 border-top border-lg-0">
                <!-- Botón Pitch Deck accesible siempre -->
                <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-semibold d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalPitchDeck">
                    <i class="bi bi-file-earmark-slides-fill text-warning me-1"></i> Pitch Deck
                </button>

                <?php if ($isLoggedIn): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5 text-success"></i>
                            <span class="fw-semibold text-truncate" style="max-width: 140px;"><?= htmlspecialchars($userName) ?></span>
                            <span class="badge bg-<?= $userRole === 'comercio' ? 'warning text-dark' : 'success' ?> badge-role">
                                <?= ucfirst($userRole) ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
                            <li><h6 class="dropdown-header">Mi Cuenta</h6></li>
                            <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($_SESSION['usuario_email'] ?? '') ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-success fw-medium px-3 rounded-pill btn-sm">Iniciar Sesión</a>
                    <a href="register.php" class="btn btn-fr-primary rounded-pill px-3 btn-sm">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="py-4">
    <div class="container">
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-shield-exclamation text-danger fs-4 me-3"></i>
                <div class="flex-grow-1">
                    <strong>Aviso del Sistema:</strong> <?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle-fill text-success fs-4 me-3"></i>
                <div class="flex-grow-1">
                    <?= $_SESSION['flash_success'] ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
    </div>
