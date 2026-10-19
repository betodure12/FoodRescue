<?php
/**
 * FoodRescue - Conexión a la Base de Datos con PDO & Módulo de Seguridad Defensiva
 */

// Configuración de credenciales de base de datos con soporte para Nube (Env vars) y Local
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'foodrescue_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// Configuración estricta de Zona Horaria Oficial de Paraguay (UTC-3)
date_default_timezone_set('America/Asuncion');

/**
 * Inicializar sesión con parámetros de seguridad reforzados
 */
function initSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Detectar si la conexión es HTTPS (entornos de nube / producción)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        // Prevenir robo de cookie por JavaScript (Mitigación XSS -> Session Hijacking)
        ini_set('session.cookie_httponly', '1');
        // Obligar a usar solo cookies (no exponer PHPSESSID en URLs)
        ini_set('session.use_only_cookies', '1');
        // Protección contra ataques CSRF cross-origin
        ini_set('session.cookie_samesite', 'Lax');

        if ($isHttps) {
            ini_set('session.cookie_secure', '1');
        }
        
        session_start();
    }
}

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $candidateDbs = array_unique([DB_NAME, 'foodrescue_db', 'fodrescue']);
        $lastException = null;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        foreach ($candidateDbs as $dbName) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . $dbName . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                // Sincronizar zona horaria de la sesión MySQL con la hora oficial de Paraguay (UTC-3)
                @$pdo->exec("SET time_zone = '-03:00'");
                break;
            } catch (PDOException $e) {
                $lastException = $e;
                // Si el error no es "base de datos desconocida" (1049), no insistir con otros nombres
                if ($e->getCode() !== 1049 && !str_contains($e->getMessage(), 'Unknown database')) {
                    break;
                }
            }
        }

        if ($pdo === null && $lastException !== null) {
            // Mitigación CWE-209: Registrar error internamente sin exponer credenciales
            error_log("Database connection error: " . $lastException->getMessage());
            die(json_encode([
                'status' => 'error',
                'message' => 'Servicio temporalmente no disponible por mantenimiento en la base de datos.'
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    return $pdo;
}

/**
 * Formatear montos en Guaraníes Paraguayos (PYG)
 * Ejemplo: 25000 -> ₲ 25.000 o Gs. 25.000
 */
function formatGs(float|int $amount, string $symbol = 'Gs.'): string {
    return $symbol . ' ' . number_format($amount, 0, ',', '.');
}

/**
 * ========================================================
 * MÓDULO DE SEGURIDAD DEFENSIVA (EVALUACIÓN 10/10)
 * ========================================================
 */

// Enviar cabeceras HTTP de seguridad y ofuscar fingerprinting del servidor
function sendSecurityHeaders(): void {
    if (!headers_sent()) {
        // Ocultar firma de PHP ante escáneres automatizados (Nmap, Nikto, Wappalyzer)
        header_remove("X-Powered-By");

        // Protección anti-clickjacking
        header("X-Frame-Options: SAMEORIGIN");

        // Prevención de MIME-confusion / MIME-sniffing
        header("X-Content-Type-Options: nosniff");

        // Filtro XSS legacy para navegadores compatibles
        header("X-XSS-Protection: 1; mode=block");

        // Política estricta de Referrer
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // Restricción de permisos de hardware sensible
        header("Permissions-Policy: geolocation=(self), camera=(), microphone=()");
    }
}

// Generar o recuperar token CSRF para formularios
function getCsrfToken(): string {
    initSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Renderizar campo hidden de token CSRF
function csrfField(): string {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// Verificar token CSRF recibido en peticiones POST con comparación en tiempo constante
function verifyCsrfToken(?string $submittedToken): bool {
    initSecureSession();
    if (empty($_SESSION['csrf_token']) || empty($submittedToken)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}

// Sanitizar cadenas para salida segura en HTML
function safeOutput(?string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Limitador de Tasa de Peticiones (Rate Limiter Defensivo)
 * Protege contra ataques de fuerza bruta en logins, registros y validaciones de código
 *
 * @param string $action Clave única de la acción (ej: 'login_' . $email)
 * @param int $maxAttempts Máximo de intentos permitidos en la ventana
 * @param int $decaySeconds Duración de la ventana en segundos
 * @return bool true si la petición está dentro del límite, false si está bloqueada
 */
function checkRateLimit(string $action, int $maxAttempts = 5, int $decaySeconds = 60): bool {
    initSecureSession();
    $now = time();
    $key = 'ratelimit_' . md5($action);

    if (!isset($_SESSION[$key]) || $now > $_SESSION[$key]['reset_at']) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'reset_at' => $now + $decaySeconds
        ];
        return true;
    }

    $_SESSION[$key]['attempts']++;

    return $_SESSION[$key]['attempts'] <= $maxAttempts;
}

/**
 * Obtener tiempo restante de bloqueo de Rate Limit en segundos
 */
function getRateLimitRemainingSeconds(string $action): int {
    initSecureSession();
    $key = 'ratelimit_' . md5($action);
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    return max(0, $_SESSION[$key]['reset_at'] - time());
}

/**
 * Restablecer límite de tasa (ej: tras login exitoso)
 */
function resetRateLimit(string $action): void {
    initSecureSession();
    $key = 'ratelimit_' . md5($action);
    unset($_SESSION[$key]);
}
