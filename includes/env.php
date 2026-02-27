<?php

/**
 * Carga variables desde archivo .env en la raíz del proyecto
 * Formato: CLAVE=valor (líneas vacías y # comentarios ignorados)
 */
function loadEnv(string $path): void
{
    $file = $path . '/.env';
    if (!is_readable($file)) {
        return;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($key !== '') {
            $_ENV[$key] = $value;
            if (!array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $value;
            }
        }
    }
}

function env(string $key, $default = null)
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}
