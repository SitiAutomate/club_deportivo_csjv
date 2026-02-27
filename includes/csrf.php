<?php

/**
 * Protección de rutas: Bearer token + validación de Origen.
 * Impide que Postman u otras herramientas inserten datos sin cargar el formulario.
 */

function csrfStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function csrfToken(): string
{
    csrfStart();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfMetaTag(): string
{
    return '<meta name="csrf-token" content="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Orígenes permitidos (desde APP_URL en .env).
 * Acepta el dominio base y variantes con/sin www, http/https.
 */
function getAllowedOrigins(): array
{
    $url = rtrim(env('APP_URL', ''), '/');
    if ($url === '') {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $url = $proto . '://' . $_SERVER['HTTP_HOST'];
        } else {
            return [];
        }
    }
    $parsed = parse_url($url);
    $scheme = $parsed['scheme'] ?? 'https';
    $host = $parsed['host'] ?? '';
    if ($host === '') return [];
    $base = $scheme . '://' . $host;
    return [
        $base,
        $scheme . '://www.' . $host,
        preg_replace('#^https?://www\.#', $scheme . '://', $base),
        'http://localhost',
        'https://localhost',
        'http://127.0.0.1',
        'https://127.0.0.1',
    ];
}

/**
 * Valida que la petición venga de un origen permitido.
 * Requiere Origin o Referer; si ambos faltan, se rechaza (bloquea Postman/curl).
 */
function validateOrigin(): bool
{
    $origin = trim($_SERVER['HTTP_ORIGIN'] ?? '');
    $referer = trim($_SERVER['HTTP_REFERER'] ?? '');
    if ($origin === '' && $referer === '') {
        return false;
    }
    $allowed = getAllowedOrigins();
    foreach ($allowed as $a) {
        $a = rtrim($a, '/');
        if ($origin !== '' && (strpos($origin . '/', $a . '/') === 0 || $origin === $a)) return true;
        if ($referer !== '' && strpos($referer, $a) === 0) return true;
    }
    return false;
}

/**
 * Obtiene el token Bearer o X-CSRF-Token de la petición.
 */
function getRequestToken(): ?string
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^\s*Bearer\s+(.+)$/i', $auth, $m)) {
        return trim($m[1]);
    }
    return $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? null;
}

/**
 * Valida token Bearer/CSRF y origen. Debe llamarse al inicio de cada endpoint POST.
 * Acepta: Authorization: Bearer &lt;token&gt; o header X-CSRF-Token.
 * Devuelve true si es válido; si no, envía JSON de error y termina.
 */
function csrfValidate(): bool
{
    csrfStart();

    $token = getRequestToken();
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        jsonError(403, 'Solicitud no autorizada. Recargue el formulario.');
    }

    if (!validateOrigin()) {
        jsonError(403, 'Origen no permitido.');
    }

    return true;
}

function jsonError(int $code, string $msg): void
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
