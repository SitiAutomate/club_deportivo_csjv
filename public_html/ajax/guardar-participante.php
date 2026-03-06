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
$primerNombre = trim($input['primer_nombre'] ?? $input['Primer_Nombre'] ?? $input['nombre'] ?? '');
$segundoNombre = trim($input['segundo_nombre'] ?? $input['Segundo_Nombre'] ?? '');
$primerApellido = trim($input['primer_apellido'] ?? $input['Primer_Apellido'] ?? $input['apellido'] ?? '');
$segundoApellido = trim($input['segundo_apellido'] ?? $input['Segundo_Apellido'] ?? '');
$fechaNacimiento = trim($input['fecha_nacimiento'] ?? $input['Fecha_Nacimiento'] ?? '');
$tipoDocumento = trim($input['tipo_identificacion'] ?? $input['Tipo_documento'] ?? '');

if ($documento === '' || $documentoInicial === '' || $documento !== $documentoInicial) {
    jsonResponse(['success' => false, 'error' => 'El documento debe coincidir con el ingresado inicialmente'], 400);
}
$validacion = validarDocumentoParticipante($documento);
if (!$validacion['valid']) {
    jsonResponse(['success' => false, 'error' => $validacion['error']], 400);
}
$documento = normalizarDocumentoParticipante($documento);
$documentoInicial = normalizarDocumentoParticipante($documentoInicial);
if ($primerNombre === '' || $primerApellido === '') {
    jsonResponse(['success' => false, 'error' => 'Primer nombre y primer apellido son requeridos'], 400);
}

$participante = new Participante($database);

$existe = $participante->getByDocumento($documento);
if ($existe) {
    jsonResponse(['success' => false, 'error' => 'Ya existe un participante con ese documento'], 409);
}

try {
    $participante->create([
        'documento' => $documento,
        'Tipo_documento' => $tipoDocumento ?: null,
        'Primer_Nombre' => $primerNombre,
        'Segundo_Nombre' => $segundoNombre ?: null,
        'Primer_Apellido' => $primerApellido,
        'Segundo_Apellido' => $segundoApellido ?: null,
        'fecha_nacimiento' => $fechaNacimiento ?: null,
        'Fecha_Nacimiento' => $fechaNacimiento ?: null
    ]);

    $nuevo = $participante->getByDocumento($documento);
    $responsableDoc = trim($input['responsable_documento'] ?? $nuevo['IDResponsable'] ?? '');

    $apiExt = new ExternalApiService();
    if ($apiExt->isConfigured() && $responsableDoc !== '') {
        $responsable = new Responsable($database);
        $rowResp = $responsable->getByDocumento($responsableDoc);
        if ($rowResp) {
            $apiExt->crearResponsable([
                'documento' => $rowResp['IDResponsable'] ?? $responsableDoc,
                'nombres' => $rowResp['Nombres'] ?? '',
                'apellidos' => $rowResp['Apellidos'] ?? '',
                'email' => $rowResp['Correo_Responsable'] ?? '',
                'celular' => $rowResp['Celular_Responsable'] ?? '',
                'tipo_persona' => $rowResp['Tipo_Persona'] ?? '',
                'ciudad' => $rowResp['Ciudad'] ?? '',
                'departamento' => '',
                'direccion' => $rowResp['direccion'] ?? '',
                'tipo_identificacion' => $rowResp['tipo_identificacion'] ?? '',
            ]);
        }
        $apiExt->crearParticipante($nuevo, $responsableDoc);
    }

    jsonResponse([
        'success' => true,
        'participante' => [
            'id' => $nuevo['IDParticipante'],
            'documento' => $nuevo['IDParticipante'],
            'nombre' => $nuevo['Nombre_Completo'] ?? trim(($nuevo['Primer_Nombre'] ?? '') . ' ' . ($nuevo['Primer_Apellido'] ?? '')),
            'apellido' => $nuevo['Primer_Apellido'] ?? '',
            'fecha_nacimiento' => $nuevo['Fecha_Nacimiento'] ?? null,
            'responsable_id' => $responsableDoc,
            'responsable_documento' => $responsableDoc
        ]
    ]);
} catch (Exception $e) {
    AppLogger::error('guardar-participante: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    jsonResponse(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()], 500);
}
