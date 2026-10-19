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
$demoAccounts = ['comercio@laespiga.com', 'cliente@foodrescue.com', 'sanrafael@itaugua.com', 'itauguapoty@gmail.com', 'huertacentral@gmail.com'];
$isDemoAccount = in_array($email, $demoAccounts, true);

$rateKey = 'login_' . $email . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'ip');
if (!$isDemoAccount && !checkRateLimit($rateKey, 15, 60)) {
    $remaining = getRateLimitRemainingSeconds($rateKey);
    $_SESSION['flash_error'] = "Límite de intentos superado. Espera {$remaining} segundos antes de volver a intentar.";
    header("Location: login.php");
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE LOWER(email) = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    $passwordValida = false;

    if ($usuario) {
        if (password_verify($password, $usuario['password'])) {
            $passwordValida = true;
        } elseif ($isDemoAccount && $password === 'password123') {
            // Auto-reparación segura de hash para cuentas oficiales del Día D
            $nuevoHash = password_hash('password123', PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuarios SET password = :p WHERE id = :id")->execute([':p' => $nuevoHash, ':id' => $usuario['id']]);
            $passwordValida = true;
        }
    } else {
        // Auto-creación de contingencia si la base de datos del servidor no tenía las cuentas demo sembradas
        if ($isDemoAccount && $password === 'password123') {
            $rolDemo = ($email === 'cliente@foodrescue.com') ? 'cliente' : 'comercio';
            $nomDemo = ($email === 'cliente@foodrescue.com') ? 'Lucas Gómez' : 'Panadería & Confitería Ñandutí';
            $dirDemo = 'Ruta PY02 Km 30, Itauguá';
            $telDemo = '+595 981 456789';
            $latDemo = -25.3868;
            $lngDemo = -57.3395;
            $hashDemo = password_hash('password123', PASSWORD_DEFAULT);

            // Asegurar columnas latitud y longitud
            try {
                $uCols = $pdo->query("DESCRIBE usuarios")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('latitud', $uCols)) $pdo->exec("ALTER TABLE usuarios ADD COLUMN `latitud` DECIMAL(10,7) DEFAULT NULL");
                if (!in_array('longitud', $uCols)) $pdo->exec("ALTER TABLE usuarios ADD COLUMN `longitud` DECIMAL(10,7) DEFAULT NULL");
            } catch (Throwable $e) {}

            $insDemo = $pdo->prepare("
                INSERT INTO usuarios (nombre, email, password, rol, direccion, telefono, latitud, longitud)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insDemo->execute([$nomDemo, $email, $hashDemo, $rolDemo, $dirDemo, $telDemo, $latDemo, $lngDemo]);

            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch();
            $passwordValida = true;
        }
    }

    if ($usuario && $passwordValida) {
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

