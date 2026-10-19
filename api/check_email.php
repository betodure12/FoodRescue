<?php
/**
 * FoodRescue API - Verificación de Disponibilidad de Correo Electrónico
 * Endpoint para validar si un correo ya se encuentra registrado (previene duplicados en tiempo real).
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$rawEmail = $_GET['email'] ?? $_POST['email'] ?? '';
$email = mb_strtolower(trim($rawEmail));

if (empty($email)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Email no proporcionado',
        'exists' => false,
        'valid' => false
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'invalid_format',
        'message' => 'Formato de correo no válido',
        'exists' => false,
        'valid' => false
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, rol, nombre FROM usuarios WHERE LOWER(email) = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode([
            'status' => 'success',
            'exists' => true,
            'valid' => true,
            'message' => 'Este correo ya está registrado en el sistema.',
            'rol' => $user['rol']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'success',
            'exists' => false,
            'valid' => true,
            'message' => 'Correo disponible para registro.'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al consultar la base de datos.',
        'exists' => false,
        'valid' => true
    ], JSON_UNESCAPED_UNICODE);
}
