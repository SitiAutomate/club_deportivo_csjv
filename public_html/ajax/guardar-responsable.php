<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}
csrfValidate();

header('Content-Type: application/json; charset=utf-8');

$input = getPostData();

$documento = trim($input['documento'] ?? '');
$documentoInicial = trim($input['documento_inicial'] ?? $documento);
$nombres = trim($input['nombres'] ?? $input['Nombres'] ?? $input['nombre'] ?? '');
$apellidos = trim($input['apellidos'] ?? $input['Apellidos'] ?? $input['apellido'] ?? '');
$telefono = trim($input['celular'] ?? $input['telefono'] ?? $input['Celular_Responsable'] ?? '');
$email = trim($input['email'] ?? $input['Correo_Responsable'] ?? '');
$tipoPersona = trim($input['tipo_persona'] ?? $input['Tipo_Persona'] ?? '');
$ciudad = trim($input['ciudad'] ?? $input['Ciudad'] ?? '');
$direccion = trim($input['direccion'] ?? '');
$tipoIdentificacion = trim($input['tipo_identificacion'] ?? '');

if ($documento === '' || $documentoInicial === '' || $documento !== $documentoInicial) {
    jsonResponse(['success' => false, 'error' => 'El documento debe coincidir con el ingresado inicialmente'], 400);
}
if ($nombres === '' || $apellidos === '') {
    jsonResponse(['success' => false, 'error' => 'Nombres y apellidos son requeridos'], 400);
}

$responsable = new Responsable($database);

$existe = $responsable->getByDocumento($documento);
if ($existe) {
    jsonResponse(['success' => false, 'error' => 'Ya existe un responsable con ese documento'], 409);
}

try {
    $payload = [
        'documento' => $documento,
        'IDResponsable' => $documento,
        'tipo_identificacion' => $tipoIdentificacion ?: null,
        'Nombres' => $nombres,
        'Apellidos' => $apellidos,
        'Celular_Responsable' => $telefono ?: null,
        'Correo_Responsable' => $email ?: null,
        'Tipo_Persona' => $tipoPersona ?: null,
        'Ciudad' => $ciudad ?: null,
        'direccion' => $direccion ?: null
    ];
    $payloadApi = [
        'documento' => $documento,
        'tipo_identificacion' => $tipoIdentificacion ?: null,
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'celular' => $telefono ?: null,
        'email' => $email ?: null,
        'tipo_persona' => $tipoPersona ?: null,
        'ciudad' => $ciudad ?: null,
        'departamento' => trim($input['departamento'] ?? '') ?: '',
        'direccion' => $direccion ?: null,
    ];
    $responsable->create($payload);

    $nuevo = $responsable->getByDocumento($documento);

    $apiExt = new ExternalApiService();
    if ($apiExt->isConfigured()) {
        $apiExt->crearResponsable($payloadApi);
    }
    jsonResponse([
        'success' => true,
        'responsable' => [
            'id' => $nuevo['IDResponsable'],
            'documento' => $nuevo['IDResponsable'],
            'nombre' => $nuevo['Nombre_Completo'] ?? trim(($nuevo['Nombres'] ?? '') . ' ' . ($nuevo['Apellidos'] ?? '')),
            'apellido' => $nuevo['Apellidos'] ?? ''
        ]
    ]);
} catch (Exception $e) {
    AppLogger::error('guardar-responsable: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    jsonResponse(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()], 500);
}
