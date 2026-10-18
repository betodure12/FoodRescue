<?php
/**
 * FoodRescue REST API - Endpoint de Validación de Pase de Retiro
 * Método: POST
 * Salida: JSON estándar UTF-8 con CORS
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';
sendSecurityHeaders();

if (!in_array($method, ['GET', 'POST', 'HEAD'])) {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Método no permitido. Utilice GET o POST.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Mitigación de Fuerza Bruta en API (Rate Limiting de 30 consultas por minuto por IP)
$rateKey = 'api_validar_' . ($_SERVER['REMOTE_ADDR'] ?? 'ip');
if (!checkRateLimit($rateKey, 30, 60)) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'code' => 429,
        'message' => 'Límite de consultas por minuto superado (HTTP 429 Too Many Requests). Intenta nuevamente en 60 segundos.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Leer cuerpo en JSON, POST tradicional o GET query param
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

$codigo = mb_substr(strtoupper(trim(
    $jsonData['codigo_retiro'] 
    ?? $jsonData['codigo'] 
    ?? $_POST['codigo_retiro'] 
    ?? $_POST['codigo'] 
    ?? $_GET['codigo_retiro'] 
    ?? $_GET['codigo'] 
    ?? ''
)), 0, 20);

if (empty($codigo)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'code' => 400,
        'message' => 'Parámetro obligatorio ausente: codigo_retiro o codigo. Ej: ?codigo=FR-1001'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("
        SELECT r.*, p.titulo AS producto_titulo, p.precio_descuento, p.horario_retiro,
               u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
               c.nombre AS comercio_nombre, c.direccion AS comercio_direccion
        FROM reservas r
        JOIN productos p ON r.producto_id = p.id
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN usuarios c ON p.comercio_id = c.id
        WHERE r.codigo_retiro = :codigo
        LIMIT 1
    ");
    $stmt->execute([':codigo' => $codigo]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'code' => 404,
            'message' => "Código de rescate '{$codigo}' no encontrado en el sistema."
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    $totalPagar = (float)$reserva['precio_descuento'] * (int)$reserva['cantidad'];

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'code' => 200,
        'data' => [
            'codigo_retiro' => $reserva['codigo_retiro'],
            'estado_actual' => $reserva['estado'],
            'fecha_reserva' => $reserva['fecha_reserva'],
            'producto' => [
                'titulo' => $reserva['producto_titulo'],
                'cantidad' => (int)$reserva['cantidad'],
                'precio_unitario' => (float)$reserva['precio_descuento'],
                'total_a_pagar' => $totalPagar,
                'total_formateado' => formatGs($totalPagar),
                'horario_retiro' => $reserva['horario_retiro'] ?: 'Hoy'
            ],
            'cliente' => [
                'nombre' => $reserva['cliente_nombre'],
                'telefono' => $reserva['cliente_telefono'] ?: 'No indicado'
            ],
            'comercio' => [
                'nombre' => $reserva['comercio_nombre'],
                'direccion' => $reserva['comercio_direccion'] ?: 'Itauguá'
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("API Validar error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Error interno al consultar la base de datos.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
