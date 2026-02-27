<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate();
}

$input = getPostData();
$documento = trim($input['documento'] ?? '');
$participanteDocumento = trim($input['participante_id'] ?? $input['participante_documento'] ?? '');

if ($documento === '' || $participanteDocumento === '') {
    jsonResponse(['success' => false, 'error' => 'Datos requeridos'], 400);
}

$participante = new Participante($database);
$responsable = new Responsable($database);

$isAssigned = $participante->isResponsableAsignado($participanteDocumento, $documento);
$row = $responsable->getByDocumento($documento);

$result = [
    'success' => true,
    'isAssigned' => $isAssigned,
    'exists' => (bool) $row,
    'responsable' => null
];

if ($row) {
    $result['responsable'] = [
        'id' => $row['IDResponsable'],
        'documento' => $row['IDResponsable'],
        'nombre' => $row['Nombre_Completo'] ?? trim(($row['Nombres'] ?? '') . ' ' . ($row['Apellidos'] ?? '')),
        'apellido' => $row['Apellidos'] ?? '',
        'Nombres' => $row['Nombres'] ?? '',
        'Apellidos' => $row['Apellidos'] ?? ''
    ];
}

jsonResponse($result);
