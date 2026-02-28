<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/EmailService.php';
require_once __DIR__ . '/../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}
csrfValidate();

header('Content-Type: application/json; charset=utf-8');

$input = getPostData();
$participanteDocumento = trim($input['participante_id'] ?? $input['participante_documento'] ?? '');
$responsableDocumento = trim($input['responsable_id'] ?? $input['responsable_documento'] ?? '');
$tipoId = (int) ($input['tipo_id'] ?? 0);

if ($participanteDocumento === '' || $responsableDocumento === '' || $tipoId <= 0) {
    jsonResponse(['success' => false, 'error' => 'participante_id, responsable_id y tipo_id son requeridos'], 400);
}

$anio = (int) ($input['año'] ?? $input['anio'] ?? date('Y'));
$mes = $input['Mes'] ?? null;
$periodo = $input['Periodo'] ?? null;
if ($mes && !$periodo) {
    $anioCorto = $anio % 100;
    $periodo = $mes . str_pad((string) $anioCorto, 2, '0', STR_PAD_LEFT);
}

$detalle = [
    'Fecha_Inscripción' => $input['fecha_inscripcion'] ?? $input['Fecha_Inscripción'] ?? date('Y-m-d'),
    'año' => $anio,
    'Mes' => $mes,
    'Periodo' => $periodo,
    'Sede' => $input['Sede'] ?? null,
    'Transporte' => $input['Transporte'] ?? null,
    'Politicas' => $input['Politicas'] ?? 'Si',
    'Estado' => $input['Estado'] ?? 'ACTIVO',
];

// Campos de datos adicionales desde config
$datosConfigPath = __DIR__ . '/../../config/datos_adicionales.php';
$camposDatos = file_exists($datosConfigPath) ? require $datosConfigPath : [];
foreach ($camposDatos as $key => $cfg) {
    if (empty($cfg['enabled']) || empty($cfg['column'])) continue;
    $col = $cfg['column'];
    $type = $cfg['type'] ?? '';
    if ($type === 'checkbox') {
        $detalle[$col] = (($input[$key] ?? '') === 'Sí') ? 'Sí' : null;
    } elseif ($type === 'select_si_no') {
        $val = trim($input[$key] ?? '');
        $detalle[$col] = ($val === 'Sí' || $val === 'No') ? $val : null;
    } elseif ($type === 'select_si_no_text') {
        $textKey = $cfg['textFieldName'] ?? '';
        $val = (($input[$key] ?? '') === 'Sí' && !empty(trim($input[$textKey] ?? ''))) ? trim($input[$textKey]) : null;
        $detalle[$col] = $val;
    } else {
        $val = trim($input[$key] ?? '');
        $detalle[$col] = $val !== '' ? $val : null;
    }
}

// Tipo 1 = Cursos: puede ser múltiple (curso_ids[]), con filtros mes/sede
if ($tipoId === 1) {
    $cursoIds = $input['curso_ids'] ?? [];
    if (!is_array($cursoIds)) {
        $cursoIds = $cursoIds ? [strval($cursoIds)] : [];
    }
    $cursoId = $input['curso_id'] ?? $input['IDCurso'] ?? null;
    if (empty($cursoIds) && $cursoId) {
        $cursoIds = [$cursoId];
    }
    if (empty($cursoIds)) {
        jsonResponse(['success' => false, 'error' => 'Seleccione al menos un curso'], 400);
    }
    $nombresCurso = $input['nombres_curso'] ?? [];
    if (!is_array($nombresCurso)) {
        $nombresCurso = $nombresCurso ? [$nombresCurso] : [];
    }
} elseif ($tipoId === 2) {
    $detalle['IDCurso'] = $input['campamento_id'] ?? $input['IDCurso'] ?? null;
    $detalle['nombreCurso'] = $input['nombreCurso'] ?? null;
    $detalle['Sede'] = $detalle['Sede'] ?? 'MEDELLÍN';
    $mesActual = str_pad((string) date('n'), 2, '0', STR_PAD_LEFT);
    $detalle['Mes'] = $detalle['Mes'] ?? $mesActual;
    $detalle['Periodo'] = $detalle['Periodo'] ?? ($mesActual . str_pad((string) ($anio % 100), 2, '0', STR_PAD_LEFT));
} elseif ($tipoId === 5 || $tipoId === 3) {
    $detalle['IDCurso'] = $input['salida_id'] ?? $input['IDCurso'] ?? null;
    $detalle['nombreCurso'] = $input['nombreCurso'] ?? null;
    $detalle['Sede'] = $detalle['Sede'] ?? 'MEDELLÍN';
    $mesActual = str_pad((string) date('n'), 2, '0', STR_PAD_LEFT);
    $detalle['Mes'] = $detalle['Mes'] ?? $mesActual;
    $detalle['Periodo'] = $detalle['Periodo'] ?? ($mesActual . str_pad((string) ($anio % 100), 2, '0', STR_PAD_LEFT));
} else {
    $configPath = __DIR__ . '/../../config/tipos_inscripcion.php';
    $config = file_exists($configPath) ? require $configPath : [];
    $cfg = $config[$tipoId] ?? null;
    if (!$cfg) {
        jsonResponse(['success' => false, 'error' => 'Tipo de inscripción no configurado'], 400);
    }
    $selectorName = $cfg['selectorName'] ?? 'curso_id';
    $detalle['IDCurso'] = $input[$selectorName] ?? $input['IDCurso'] ?? null;
    $detalle['nombreCurso'] = $input['nombreCurso'] ?? null;
    $detalle['Sede'] = $detalle['Sede'] ?? $cfg['defaultSede'] ?? 'MEDELLÍN';
    if (empty($detalle['Mes'])) {
        $mesActual = str_pad((string) date('n'), 2, '0', STR_PAD_LEFT);
        $detalle['Mes'] = $mesActual;
        $detalle['Periodo'] = $mesActual . str_pad((string) ($anio % 100), 2, '0', STR_PAD_LEFT);
    }
}

$inscripcion = new Inscripcion($database);

// Evitar inscripciones duplicadas
if ($tipoId === 1 && !empty($cursoIds)) {
    foreach ($cursoIds as $cid) {
        if ($inscripcion->existeDuplicada($participanteDocumento, (string) $cid, $anio, $tipoId)) {
            jsonResponse(['success' => false, 'error' => 'Ya está inscrito en uno o más de los cursos seleccionados'], 400);
        }
    }
} elseif (in_array($tipoId, [2, 3, 5, 14])) {
    $idCurso = $input['IDCurso'] ?? $input['curso_id'] ?? $input['campamento_id'] ?? $input['salida_id'] ?? null;
    if ($idCurso && $inscripcion->existeDuplicada($participanteDocumento, (string) $idCurso, $anio, $tipoId)) {
        $msg = $tipoId === 2 ? 'Ya está inscrito en este campamento' : 'Ya está inscrito en esta salida';
        jsonResponse(['success' => false, 'error' => $msg], 400);
    }
}

$participanteNombre = '';
$responsableNombre = '';
$responsableEmail = '';

try {
    $responsable = new Responsable($database);
    $rowResp = $responsable->getByDocumento($responsableDocumento);
    if ($rowResp) {
        $responsableNombre = trim($rowResp['Nombre_Completo'] ?? trim(($rowResp['Nombres'] ?? '') . ' ' . ($rowResp['Apellidos'] ?? '')));
        $responsableEmail = trim($rowResp['Correo_Responsable'] ?? '') ?: null;
    }
    $participante = new Participante($database);
    $rowPart = $participante->getByDocumento($participanteDocumento);
    if ($rowPart) {
        $participanteNombre = trim($rowPart['Nombre_Completo'] ?? trim(($rowPart['Primer_Nombre'] ?? '') . ' ' . ($rowPart['Primer_Apellido'] ?? '')));
    }

    if ($tipoId === 1 && !empty($cursoIds)) {
        $ids = [];
        $configPath = __DIR__ . '/../../config/tipos_inscripcion.php';
        $tiposConfig = file_exists($configPath) ? require $configPath : [];
        $usaApiInscripcion = !empty($tiposConfig[$tipoId]['usaApiInscripcion'] ?? false);
        $periodo = trim((string) ($detalle['Periodo'] ?? ''));
        if ($periodo === '' && $detalle['Mes']) {
            $periodo = $detalle['Mes'] . str_pad((string) ($anio % 100), 2, '0', STR_PAD_LEFT);
        }
        if ($periodo === '') {
            $mesActual = str_pad((string) date('n'), 2, '0', STR_PAD_LEFT);
            $periodo = $mesActual . str_pad((string) (date('Y') % 100), 2, '0', STR_PAD_LEFT);
        }
        $cursoModel = new Curso($database);
        $apiExt = new ExternalApiService();
        foreach ($cursoIds as $i => $cid) {
            $detalle['IDCurso'] = $cid;
            $detalle['nombreCurso'] = $nombresCurso[$i] ?? $cid;
            $ids[] = $inscripcion->create($participanteDocumento, $responsableDocumento, $tipoId, $detalle);
            if ($usaApiInscripcion && $apiExt->isConfigured()) {
                $info = $cursoModel->getFacturacionPorId((string) $cid);
                if ($info && !empty(trim($info['Codigo_Facturacion'] ?? ''))) {
                    $valor = (float) preg_replace('/[^0-9.]/', '', (string) ($info['Tarifa_Curso'] ?? '0'));
                    $apiExt->crearInscripcionApi(
                        trim($info['Codigo_Facturacion']),
                        $participanteDocumento,
                        $periodo,
                        $valor
                    );
                }
            }
        }
        $tipoTexto = 'Curso(s)';
        $detalleTexto = implode(', ', $nombresCurso);
        $transporte = $detalle['Transporte'] ?? null;
        if ($responsableEmail) {
            $emailService = new EmailService();
            $emailService->enviarConfirmacionInscripcion(
                $responsableEmail,
                $participanteNombre,
                $responsableNombre,
                $tipoTexto,
                $detalleTexto,
                $transporte
            );
        }
        jsonResponse(['success' => true, 'inscripcion_ids' => $ids, 'inscripcion_id' => $ids[0] ?? null]);
    } else {
        $detalle['IDCurso'] = $input['IDCurso'] ?? $input['curso_id'] ?? $input['campamento_id'] ?? $input['salida_id'] ?? null;
        $detalle['nombreCurso'] = $input['nombreCurso'] ?? null;
        $id = $inscripcion->create($participanteDocumento, $responsableDocumento, $tipoId, $detalle);
        $apiExt = new ExternalApiService();
        if ($apiExt->isConfigured() && $rowPart) {
            $apiExt->crearParticipante($rowPart, $responsableDocumento);
        }
        $idCurso = (int) ($detalle['IDCurso'] ?? 0);
        $participantesAdicionales = $input['participantes_adicionales'] ?? [];
        if (is_array($participantesAdicionales) && $idCurso > 0) {
            $configPath = __DIR__ . '/../../config/participantes_adicionales.php';
            $configs = file_exists($configPath) ? require $configPath : [];
            $key = $tipoId . '_' . $idCurso;
            $cfgPa = $configs[$key] ?? null;
            if ($cfgPa && !empty($cfgPa['fields'])) {
                $camposVisibles = $cfgPa['fields'];
                $primer = $participantesAdicionales[0] ?? [];
                foreach ($camposVisibles as $c) {
                    if (empty(trim($primer[$c] ?? ''))) {
                        jsonResponse(['success' => false, 'error' => 'El primer participante adicional debe completar todos los campos visibles'], 400);
                    }
                }
            }
            $participantesAdicionales = array_values(array_filter($participantesAdicionales, function ($p) {
                return !empty(trim($p['documento'] ?? '')) || !empty(trim($p['nombre'] ?? ''));
            }));
            if (!empty($participantesAdicionales) && $cfgPa) {
                $paModel = new ParticipanteAdicional($database);
                $paModel->guardarParaInscripcion($id, $idCurso, $participantesAdicionales);
            }
        }
        $tipoTexto = $tipoId === 2 ? 'Campamento' : ($tipoId === 5 || $tipoId === 3 ? 'Salida' : 'Inscripción');
        $detalleTexto = $detalle['nombreCurso'] ?? '-';
        if ($responsableEmail) {
            $emailService = new EmailService();
            $emailService->enviarConfirmacionInscripcion(
                $responsableEmail,
                $participanteNombre,
                $responsableNombre,
                $tipoTexto,
                $detalleTexto,
                null
            );
        }
        jsonResponse(['success' => true, 'inscripcion_id' => $id]);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()], 500);
}
