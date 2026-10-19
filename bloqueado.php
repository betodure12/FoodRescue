<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();
sendSecurityHeaders();

$pageTitle = "Acceso Protegido - FoodRescue CyberShield";
$motivo = safeOutput($_GET['motivo'] ?? 'actividad_sospechosa');
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$horaPy = date('d/m/Y - H:i:s');
$incidentCode = 'SEC-' . strtoupper(substr(md5($ip . time()), 0, 8));

$motivosExplicacion = [
    'rate_limit' => 'Se ha detectado una ráfaga excesiva de peticiones o intento de fuerza bruta automatizada.',
    'csrf' => 'Se intentó ejecutar una petición sin token CSRF válido o con firma alterada.',
    'sqli' => 'Se detectaron patrones de inyección SQL (SQLi) o caracteres no autorizados en los parámetros.',
    'xss' => 'Se detectó intento de inyección de scripts o contenido HTML ejecutable (XSS).',
    'bot' => 'Se detectó tráfico proveniente de escáneres de vulnerabilidades o herramientas automáticas de hacking.',
    'actividad_sospechosa' => 'El cortafuegos defensivo de la plataforma bloqueó la solicitud por infringir las políticas de seguridad.'
];

$detalleMotivo = $motivosExplicacion[$motivo] ?? $motivosExplicacion['actividad_sospechosa'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #0b1120;
            color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, rgba(11, 17, 32, 0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            pointer-events: none;
        }
        .shield-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 30px rgba(16, 185, 129, 0.15);
            position: relative;
            z-index: 1;
            max-width: 640px;
            width: 100%;
        }
        .shield-icon-container {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, rgba(15, 23, 42, 0.8) 100%);
            border: 2px solid #10b981;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.4);
            animation: pulse-shield 2.5s infinite;
        }
        @keyframes pulse-shield {
            0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.3); transform: scale(1); }
            50% { box-shadow: 0 0 35px rgba(16, 185, 129, 0.6); transform: scale(1.04); }
        }
        .badge-siga {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #111827;
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            padding: 0.5rem 1.25rem;
            border-radius: 50rem;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.35);
            display: inline-block;
        }
        .terminal-box {
            background: #030712;
            border: 1px solid #1e293b;
            border-radius: 0.75rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.82rem;
            color: #94a3b8;
            padding: 1rem;
            text-align: left;
        }
        .terminal-highlight {
            color: #10b981;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="shield-card p-4 p-md-5 text-center">
    
    <!-- Escudo Animado -->
    <div class="mb-3">
        <div class="shield-icon-container">
            <i class="bi bi-shield-shaded text-success fs-1"></i>
        </div>
    </div>

    <!-- Título Principal -->
    <h2 class="fw-extrabold text-white mb-2">FoodRescue CyberShield</h2>
    <div class="text-secondary small text-uppercase mb-3" style="letter-spacing: 1.5px;">Sistema Defensivo Activo · OWASP Top 10</div>

    <!-- Mensaje Solicitado: ¡Siga Participando! -->
    <div class="mb-3">
        <span class="badge-siga">
            🎯 ¡Siga participando! 😉
        </span>
    </div>

    <p class="text-light fs-6 mb-3">
        Intento de ataque neutralizado con éxito. La plataforma se encuentra <strong>100% blindada</strong> contra inyecciones SQL, ataques de fuerza bruta, secuestro de sesiones y scripts maliciosos.
    </p>

    <!-- Caja de Diagnóstico / Reporte Técnico -->
    <div class="terminal-box mb-4">
        <div class="d-flex justify-content-between border-bottom border-secondary border-opacity-25 pb-1 mb-2">
            <span><strong>INCIDENT_STATUS:</strong> <span class="terminal-highlight">INTERCEPTED & MITIGATED</span></span>
            <span class="text-warning"><?= $incidentCode ?></span>
        </div>
        <div><i class="bi bi-geo-alt-fill text-danger me-1"></i> <strong>IP del Solicitante:</strong> <?= htmlspecialchars($ip) ?></div>
        <div><i class="bi bi-clock-fill text-info me-1"></i> <strong>Hora Oficial (Paraguay):</strong> <?= $horaPy ?> hs</div>
        <div><i class="bi bi-shield-x text-warning me-1"></i> <strong>Causa de Bloqueo:</strong> <?= htmlspecialchars($detalleMotivo) ?></div>
        <div><i class="bi bi-cpu-fill text-success me-1"></i> <strong>Mecanismo Defensivo:</strong> PDO Prepared Statements + CSRF Token + Dual IP Rate Limiter</div>
    </div>

    <!-- Botones de Acción -->
    <div class="d-flex flex-wrap gap-2 justify-content-center">
        <a href="index.php" class="btn btn-success rounded-pill px-4 py-2 fw-semibold shadow-sm">
            <i class="bi bi-arrow-left-circle me-1"></i> Volver a FoodRescue en Paz
        </a>
        <a href="login.php" class="btn btn-outline-light rounded-pill px-3 py-2 small fw-medium">
            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión Legítima
        </a>
    </div>

    <div class="mt-4 text-muted small" style="font-size: 0.72rem;">
        FoodRescue Startup · Seguridad Defensiva Multi-Capa Integrada · Itauguá, Central
    </div>

</div>

</body>
</html>
