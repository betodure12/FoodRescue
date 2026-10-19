<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = "🛡️ FoodRescue CyberShield: Petición no autorizada (CSRF Token Inválido). ¡Sigue participando! 😉";
    header("Location: login.php");
    exit;
}

$rawEmail = $_POST['email'] ?? '';
$rawPassword = $_POST['password'] ?? '';

if (!is_string($rawEmail) || !is_string($rawPassword)) {
    $_SESSION['flash_error'] = "🛡️ FoodRescue CyberShield: Formato de petición alterado. ¡Sigue participando! 😉";
    header("Location: login.php");
    exit;
}

$email = mb_substr(mb_strtolower(trim($rawEmail)), 0, 150);
$password = mb_substr($rawPassword, 0, 128);

if (empty($email) || empty($password)) {
    $_SESSION['flash_error'] = "Por favor ingresa tu correo y contraseña.";
    header("Location: login.php");
    exit;
}

// Mitigación de Ataques de Fuerza Bruta (Brute-Force Rate Limiting)
$rateKey = 'login_' . $email . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'ip');
if (!checkRateLimit($rateKey, 5, 60)) {
    $remaining = getRateLimitRemainingSeconds($rateKey);
    $_SESSION['flash_error'] = "🛡️ FoodRescue CyberShield: Límite de intentos superado. ¡Sigue participando! 😉 Tu acceso está bloqueado preventivamente por {$remaining} segundos.";
    header("Location: login.php");
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE LOWER(email) = :email LIMIT 1");
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

