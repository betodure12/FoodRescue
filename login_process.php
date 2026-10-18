<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = "Petición no válida o token de seguridad expirado (CSRF). Intenta nuevamente.";
    header("Location: login.php");
    exit;
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['flash_error'] = "Por favor ingresa tu correo y contraseña.";
    header("Location: login.php");
    exit;
}

// Mitigación de Ataques de Fuerza Bruta (Brute-Force Rate Limiting)
$rateKey = 'login_' . strtolower($email) . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'ip');
if (!checkRateLimit($rateKey, 5, 60)) {
    $remaining = getRateLimitRemainingSeconds($rateKey);
    $_SESSION['flash_error'] = "Demasiados intentos fallidos. Tu acceso está protegido temporalmente. Por favor espera {$remaining} segundos antes de reintentar.";
    header("Location: login.php");
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['password'])) {
        // Restablecer contador de rate limiting
        resetRateLimit($rateKey);

        // Regenerar ID de sesión para prevenir Session Fixation attacks (OWASP A07:2021)
        session_regenerate_id(true);

        // Iniciar sesión
        $_SESSION['usuario_id'] = (int)$usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_rol'] = $usuario['rol'];
        $_SESSION['ultimo_acceso'] = time();

        header("Location: index.php");
        exit;
    } else {
        $_SESSION['flash_error'] = "Credenciales incorrectas. Verifica tu correo y contraseña.";
        header("Location: login.php");
        exit;
    }

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Ocurrió un error inesperado al procesar el inicio de sesión. Inténtalo de nuevo.";
    header("Location: login.php");
    exit;
}

