<?php

/**
 * Respuesta JSON estándar para AJAX
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Validar formato de documento de participante (menor en Colombia).
 * - Si empieza por letras: pasaporte → 6-9 caracteres alfanuméricos (sin espacios).
 * - Si empieza por números: TI → 8-10 dígitos.
 */
function validarDocumentoParticipante(string $documento): array
{
    $doc = trim($documento);
    if ($doc === '') {
        return ['valid' => false, 'error' => 'Documento requerido'];
    }
    $primer = mb_substr($doc, 0, 1);
    if (ctype_alpha($primer)) {
        if (!ctype_alnum(str_replace(' ', '', $doc))) {
            return ['valid' => false, 'error' => 'El documento no debe incluir el tipo (ej. TI, CC). Solo el número del pasaporte, de 6 a 9 caracteres.'];
        }
        $sinEspacios = preg_replace('/\s+/', '', $doc);
        $len = mb_strlen($sinEspacios);
        if ($len < 6 || $len > 9) {
            return ['valid' => false, 'error' => 'El pasaporte debe tener entre 6 y 9 caracteres.'];
        }
    } elseif (ctype_digit($primer)) {
        $soloNumeros = preg_replace('/\D/', '', $doc);
        $len = strlen($soloNumeros);
        if ($len < 8 || $len > 10) {
            return ['valid' => false, 'error' => 'La Tarjeta de Identidad debe tener entre 8 y 10 dígitos.'];
        }
    } else {
        return ['valid' => false, 'error' => 'El documento debe comenzar por letras (pasaporte) o números (Tarjeta de Identidad).'];
    }
    return ['valid' => true];
}

/**
 * Normalizar documento de participante para almacenar (quitar espacios, solo dígitos si es numérico).
 */
function normalizarDocumentoParticipante(string $documento): string
{
    $doc = trim($documento);
    $primer = mb_substr($doc, 0, 1);
    if (ctype_digit($primer)) {
        return preg_replace('/\D/', '', $doc);
    }
    return preg_replace('/\s+/', '', $doc);
}

/**
 * Obtener entrada POST como array
 */
function getPostData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    return $_POST;
}
