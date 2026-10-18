<?php
require_once __DIR__ . '/config/db.php';
initSecureSession();

// Validar que el usuario esté logueado y sea comercio
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'comercio') {
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

$comercioId = (int)$_SESSION['usuario_id'];

// Protección contra spam de publicaciones masivas
$rateKey = 'publish_' . $comercioId;
if (!checkRateLimit($rateKey, 25, 60)) {
    $remaining = getRateLimitRemainingSeconds($rateKey);
    $_SESSION['flash_error'] = "Has alcanzado el límite de publicaciones por minuto. Espera {$remaining} segundos.";
    header("Location: index.php");
    exit;
}

// Desinfección y acotamiento de datos
$titulo = mb_substr(strip_tags(trim($_POST['titulo'] ?? '')), 0, 150);
$descripcion = mb_substr(strip_tags(trim($_POST['descripcion'] ?? '')), 0, 1000);
$categoria = mb_substr(strip_tags(trim($_POST['categoria'] ?? 'Panadería')), 0, 50);
$precioOriginal = max(1, min(100000000, (float)($_POST['precio_original'] ?? 0)));
$precioDescuento = max(1, min(100000000, (float)($_POST['precio_descuento'] ?? 0)));
$stock = max(1, min(1000, (int)($_POST['stock'] ?? 1)));
$horarioRetiro = mb_substr(strip_tags(trim($_POST['horario_retiro'] ?? '')), 0, 100);

// Validaciones
if (empty($titulo) || $precioOriginal <= 0 || $precioDescuento <= 0 || $stock < 1) {
    $_SESSION['flash_error'] = "Por favor completa los campos obligatorios con valores numéricos válidos.";
    header("Location: index.php");
    exit;
}

if ($precioDescuento >= $precioOriginal) {
    $_SESSION['flash_error'] = "El precio con descuento debe ser menor al precio original.";
    header("Location: index.php");
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        INSERT INTO productos (comercio_id, titulo, descripcion, categoria, precio_original, precio_descuento, stock, horario_retiro, estado)
        VALUES (:comercio_id, :titulo, :descripcion, :categoria, :precio_original, :precio_descuento, :stock, :horario_retiro, 'disponible')
    ");

    $stmt->execute([
        ':comercio_id'     => $comercioId,
        ':titulo'          => $titulo,
        ':descripcion'     => $descripcion,
        ':categoria'       => $categoria,
        ':precio_original' => $precioOriginal,
        ':precio_descuento'=> $precioDescuento,
        ':stock'           => $stock,
        ':horario_retiro'  => $horarioRetiro
    ]);

    $_SESSION['flash_success'] = "¡Excedente publicado exitosamente! Ya está visible para los clientes cercanos.";
    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    error_log("Publish error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Ocurrió un error inesperado al guardar la publicación en la base de datos.";
    header("Location: index.php");
    exit;
}
