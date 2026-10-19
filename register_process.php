<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash_error'] = "Petición no válida o token de seguridad expirado (CSRF). Intenta nuevamente.";
    header("Location: register.php");
    exit;
}

// Mitigación de Spam y Ataques de Creación Masiva de Cuentas (Rate Limiting)
$rateKey = 'register_' . ($_SERVER['REMOTE_ADDR'] ?? 'ip');
if (!checkRateLimit($rateKey, 4, 120)) {
    $remaining = getRateLimitRemainingSeconds($rateKey);
    $_SESSION['flash_error'] = "Límite de solicitudes de registro superado. Por favor espera {$remaining} segundos antes de intentar crear otra cuenta.";
    header("Location: register.php");
    exit;
}

// Obtener, desinfectar y truncar datos para prevenir desbordamientos o inyecciones
$nombre = mb_substr(strip_tags(trim($_POST['nombre'] ?? '')), 0, 100);
$email = mb_strtolower(trim($_POST['email'] ?? ''));
$telefono = mb_substr(strip_tags(trim($_POST['telefono'] ?? '')), 0, 30);
$direccion = mb_substr(strip_tags(trim($_POST['direccion'] ?? '')), 0, 200);
$rol = trim($_POST['rol'] ?? 'cliente');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Validaciones
if (empty($nombre) || empty($email) || empty($password)) {
    $_SESSION['flash_error'] = "Por favor completa todos los campos obligatorios.";
    header("Location: register.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash_error'] = "El correo electrónico no tiene un formato válido.";
    header("Location: register.php");
    exit;
}

if (!in_array($rol, ['cliente', 'comercio'], true)) {
    $rol = 'cliente';
}

if (strlen($password) < 6 || strlen($password) > 128) {
    $_SESSION['flash_error'] = "La contraseña debe tener entre 6 y 128 caracteres.";
    header("Location: register.php");
    exit;
}

if ($password !== $password_confirm) {
    $_SESSION['flash_error'] = "Las contraseñas no coinciden.";
    header("Location: register.php");
    exit;
}

try {
    $pdo = getDBConnection();

    // Verificar si el email ya se encuentra registrado (insensible a mayúsculas)
    $checkStmt = $pdo->prepare("SELECT id, rol FROM usuarios WHERE LOWER(email) = :email LIMIT 1");
    $checkStmt->execute([':email' => $email]);

    if ($checkStmt->fetch()) {
        $_SESSION['flash_error'] = "El correo '{$email}' ya se encuentra registrado en FoodRescue. Si es tu cuenta, por favor inicia sesión.";
        header("Location: register.php?email=" . urlencode($email));
        exit;
    }

    // Hashear la contraseña con el algoritmo seguro de PHP (BCRYPT / Argon2 por defecto)
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Iniciar transacción para asegurar consistencia
    $pdo->beginTransaction();

    // Insertar nuevo usuario
    $insertStmt = $pdo->prepare("
        INSERT INTO usuarios (nombre, email, password, rol, direccion, telefono) 
        VALUES (:nombre, :email, :password, :rol, :direccion, :telefono)
    ");
    
    $insertStmt->execute([
        ':nombre' => $nombre,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':rol' => $rol,
        ':direccion' => $direccion,
        ':telefono' => $telefono
    ]);

    $nuevoUsuarioId = (int)$pdo->lastInsertId();

    // Si es un cliente, inicializar su registro en la tabla de puntos de lealtad
    if ($rol === 'cliente') {
        $lealtadStmt = $pdo->prepare("
            INSERT INTO puntos_lealtad (usuario_id, total_compras, puntos, descuento_desbloqueado, nivel) 
            VALUES (:usuario_id, 0, 0, 0, 'Bronce')
        ");
        $lealtadStmt->execute([':usuario_id' => $nuevoUsuarioId]);
    }

    $pdo->commit();

    // Autologin del usuario registrado con sesión regenerada
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $nuevoUsuarioId;
    $_SESSION['usuario_nombre'] = $nombre;
    $_SESSION['usuario_email'] = $email;
    $_SESSION['usuario_rol'] = $rol;
    $_SESSION['ultimo_acceso'] = time();
    $_SESSION['flash_success'] = "¡Bienvenido a FoodRescue, {$nombre}! Tu cuenta ha sido creada exitosamente.";

    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Registration error: " . $e->getMessage());
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $_SESSION['flash_error'] = "El correo '{$email}' ya se encuentra registrado. Por favor inicia sesión.";
        header("Location: login.php?email=" . urlencode($email));
    } else {
        $_SESSION['flash_error'] = "Ocurrió un error inesperado al procesar el registro. Por favor intenta de nuevo.";
        header("Location: register.php");
    }
    exit;
}
