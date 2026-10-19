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

// Coordenadas geográficas exactas de la promoción / local
$latitud = null;
$longitud = null;
if (!empty($_POST['latitud']) && !empty($_POST['longitud'])) {
    $latRaw = (float)$_POST['latitud'];
    $lngRaw = (float)$_POST['longitud'];
    if ($latRaw >= -90 && $latRaw <= 90 && $lngRaw >= -180 && $lngRaw <= 180) {
        $latitud = round($latRaw, 7);
        $longitud = round($lngRaw, 7);
    }
}

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

    // Asegurar columnas en tablas usuarios y productos
    try {
        $uCols = $pdo->query("DESCRIBE usuarios")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('latitud', $uCols)) $pdo->exec("ALTER TABLE usuarios ADD COLUMN `latitud` DECIMAL(10,7) DEFAULT NULL");
        if (!in_array('longitud', $uCols)) $pdo->exec("ALTER TABLE usuarios ADD COLUMN `longitud` DECIMAL(10,7) DEFAULT NULL");

        $pCols = $pdo->query("DESCRIBE productos")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('latitud', $pCols)) $pdo->exec("ALTER TABLE productos ADD COLUMN `latitud` DECIMAL(10,7) DEFAULT NULL");
        if (!in_array('longitud', $pCols)) $pdo->exec("ALTER TABLE productos ADD COLUMN `longitud` DECIMAL(10,7) DEFAULT NULL");
    } catch (Throwable $colErr) {
        error_log("Column migration note in publish: " . $colErr->getMessage());
    }

    // Si se enviaron coordenadas exactas, actualizar también el perfil del comercio
    if ($latitud !== null && $longitud !== null) {
        $updUser = $pdo->prepare("UPDATE usuarios SET latitud = :lat, longitud = :lng WHERE id = :cid");
        $updUser->execute([':lat' => $latitud, ':lng' => $longitud, ':cid' => $comercioId]);
    } else {
        // Si no se enviaron, obtener las coordenadas guardadas del comercio
        $getUserLoc = $pdo->prepare("SELECT latitud, longitud FROM usuarios WHERE id = :cid LIMIT 1");
        $getUserLoc->execute([':cid' => $comercioId]);
        $userLoc = $getUserLoc->fetch();
        if ($userLoc && !empty($userLoc['latitud']) && !empty($userLoc['longitud'])) {
            $latitud = (float)$userLoc['latitud'];
            $longitud = (float)$userLoc['longitud'];
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO productos (comercio_id, titulo, descripcion, categoria, precio_original, precio_descuento, stock, horario_retiro, latitud, longitud, estado, fecha_creacion)
        VALUES (:comercio_id, :titulo, :descripcion, :categoria, :precio_original, :precio_descuento, :stock, :horario_retiro, :latitud, :longitud, 'disponible', :fecha_creacion)
    ");

    $stmt->execute([
        ':comercio_id'     => $comercioId,
        ':titulo'          => $titulo,
        ':descripcion'     => $descripcion,
        ':categoria'       => $categoria,
        ':precio_original' => $precioOriginal,
        ':precio_descuento'=> $precioDescuento,
        ':stock'           => $stock,
        ':horario_retiro'  => $horarioRetiro,
        ':latitud'         => $latitud,
        ':longitud'        => $longitud,
        ':fecha_creacion'  => date('Y-m-d H:i:s')
    ]);

    $_SESSION['flash_success'] = "¡Excedente publicado exitosamente! Ya está visible para los clientes cercanos en el mapa interactivo.";
    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    error_log("Publish error: " . $e->getMessage());
    $_SESSION['flash_error'] = "Ocurrió un error inesperado al guardar la publicación en la base de datos.";
    header("Location: index.php");
    exit;
}
