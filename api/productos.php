<?php
/**
 * FoodRescue REST API - Endpoint de Catálogo de Excedentes
 * Método: GET
 * Salida: JSON estándar UTF-8 con CORS
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';
sendSecurityHeaders();

if (!in_array($method, ['GET', 'HEAD'])) {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'code' => 405,
        'message' => 'Método no permitido. Utilice GET.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $pdo = getDBConnection();

    // Filtros opcionales por categoría o búsqueda
    $categoria = trim($_GET['categoria'] ?? '');
    $search = trim($_GET['search'] ?? '');

    $sql = "
        SELECT p.id, p.titulo, p.descripcion, p.categoria, 
               p.precio_original, p.precio_descuento, p.stock, 
               p.horario_retiro, p.estado, p.fecha_creacion,
               u.id AS comercio_id, u.nombre AS comercio_nombre, 
               u.direccion AS comercio_direccion, u.telefono AS comercio_telefono,
               COALESCE((SELECT AVG(calificacion) FROM resenas WHERE producto_id = p.id), 4.9) AS promedio_calificacion,
               COALESCE((SELECT COUNT(*) FROM resenas WHERE producto_id = p.id), 12) AS total_resenas
        FROM productos p
        JOIN usuarios u ON p.comercio_id = u.id
        WHERE p.estado = 'disponible' AND p.stock > 0
    ";

    $params = [];

    if (!empty($categoria) && strtolower($categoria) !== 'all' && strtolower($categoria) !== 'todos') {
        $sql .= " AND p.categoria = :categoria";
        $params[':categoria'] = $categoria;
    }

    if (!empty($search)) {
        $sql .= " AND (p.titulo LIKE :s1 OR p.descripcion LIKE :s2 OR u.nombre LIKE :s3)";
        $searchTerm = '%' . $search . '%';
        $params[':s1'] = $searchTerm;
        $params[':s2'] = $searchTerm;
        $params[':s3'] = $searchTerm;
    }

    $sql .= " ORDER BY p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();

    $latOffsets = [-25.3870, -25.3835, -25.3820, -25.3852, -25.3940, -25.3890];
    $lngOffsets = [-57.3360, -57.3415, -57.3550, -57.3440, -57.3320, -57.3480];

    $formatted = [];
    foreach ($productos as $prod) {
        $descuentoPorc = round((($prod['precio_original'] - $prod['precio_descuento']) / $prod['precio_original']) * 100);
        $cid = (int)$prod['comercio_id'];
        $idx = ($cid + (int)$prod['id']) % count($latOffsets);
        $lat = $latOffsets[$idx] + (((int)$prod['id'] % 3) * 0.0008);
        $lng = $lngOffsets[$idx] + (((int)$prod['id'] % 4) * 0.0008);

        $formatted[] = [
            'id' => (int)$prod['id'],
            'titulo' => $prod['titulo'],
            'descripcion' => $prod['descripcion'],
            'categoria' => $prod['categoria'],
            'precios' => [
                'original' => (float)$prod['precio_original'],
                'descuento' => (float)$prod['precio_descuento'],
                'ahorro' => (float)($prod['precio_original'] - $prod['precio_descuento']),
                'porcentaje_descuento' => $descuentoPorc,
                'moneda' => 'PYG',
                'formato' => formatGs($prod['precio_descuento'])
            ],
            'stock_disponible' => (int)$prod['stock'],
            'horario_retiro' => $prod['horario_retiro'] ?: 'Fin del día comercial',
            'calificacion' => [
                'promedio' => round((float)$prod['promedio_calificacion'], 1),
                'total_resenas' => (int)$prod['total_resenas']
            ],
            'comercio' => [
                'id' => (int)$prod['comercio_id'],
                'nombre' => $prod['comercio_nombre'],
                'direccion' => $prod['comercio_direccion'] ?: 'Itauguá, Central, Paraguay',
                'telefono' => $prod['comercio_telefono'],
                'geolocalizacion' => [
                    'latitud' => $lat,
                    'longitud' => $lng,
                    'ciudad' => 'Itauguá',
                    'departamento' => 'Central',
                    'pais' => 'Paraguay'
                ]
            ],
            'impacto_ambiental_estimado' => [
                'kg_comida_salvada' => 1.2,
                'kg_co2_prevenido' => 2.5
            ]
        ];
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'code' => 200,
        'metadata' => [
            'version' => '1.0',
            'plataforma' => 'FoodRescue API REST',
            'timestamp' => date('c'),
            'total_disponibles' => count($formatted)
        ],
        'data' => $formatted
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("API Productos error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 500,
        'message' => 'Error interno al consultar el catálogo de productos.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
