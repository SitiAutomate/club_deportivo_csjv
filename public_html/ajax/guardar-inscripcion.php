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
$traceId = bin2hex(random_bytes(8));
header('X-Trace-Id: ' . $traceId);

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

$mesNombrePorNumero = [
    '01' => 'ENERO',
    '02' => 'FEBRERO',
    '03' => 'MARZO',
    '04' => 'ABRIL',
    '05' => 'MAYO',
    '06' => 'JUNIO',
    '07' => 'JULIO',
    '08' => 'AGOSTO',
    '09' => 'SEPTIEMBRE',
    '10' => 'OCTUBRE',
    '11' => 'NOVIEMBRE',
    '12' => 'DICIEMBRE',
];

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
} elseif ($tipoId === 4) {
    require_once __DIR__ . '/../../includes/levelup.php';
    $levelupSede = strtoupper(trim((string) ($input['levelup_sede'] ?? $input['Sede'] ?? '')));
    $levelupNivel = (int) ($input['levelup_nivel'] ?? 0);
    $cursoLevel = levelupCursoPorSedeYNivel($levelupSede, $levelupNivel);
    if (!$cursoLevel) {
        jsonResponse(['success' => false, 'error' => 'Seleccione sede y nivel válidos para Level Up.', 'traceId' => $traceId], 400);
    }
    $detalle['IDCurso'] = (string) ($cursoLevel['id'] ?? '');
    $detalle['nombreCurso'] = $cursoLevel['nombre'] ?? 'Level Up';
    $detalle['Sede'] = $levelupSede;
    $mesActual = str_pad((string) date('n'), 2, '0', STR_PAD_LEFT);
    $detalle['Mes'] = $mesActual;
    $detalle['Periodo'] = $mesActual . str_pad((string) ($anio % 100), 2, '0', STR_PAD_LEFT);

    if ($levelupNivel === 1) {
        $modalidad = trim((string) ($input['levelup_modalidad'] ?? $input['Sesión'] ?? ''));
        if (!in_array($modalidad, ['Individual', 'Grupal'], true)) {
            jsonResponse(['success' => false, 'error' => 'Seleccione la modalidad Individual o Grupal.', 'traceId' => $traceId], 400);
        }
        $detalle['Sesión'] = $modalidad;
    } else {
        $detalle['Sesión'] = 'Individual';
    }
} elseif ($tipoId === 18) {
    require_once __DIR__ . '/../../includes/eventos_tipo18.php';
    $idCursoOk = eventosTipo18OpenKewmgangId();
    $idCursoReq = trim((string) ($input['curso_id'] ?? $input['IDCurso'] ?? $idCursoOk));
    if ($idCursoReq !== $idCursoOk || eventosTipo18EsFestivegas($idCursoReq)) {
        jsonResponse(['success' => false, 'error' => 'Curso de inscripción no válido para este formulario.', 'traceId' => $traceId], 400);
    }

    $modalidades = $input['openk_modalidades'] ?? [];
    if (!is_array($modalidades)) {
        $modalidades = $modalidades ? [trim((string) $modalidades)] : [];
    }
    $modalidades = array_values(array_filter(array_map('trim', $modalidades)));
    $permitidas = ['Festival infantil', 'Combate individual'];
    foreach ($modalidades as $m) {
        if (!in_array($m, $permitidas, true)) {
            jsonResponse(['success' => false, 'error' => 'Modalidad no válida.', 'traceId' => $traceId], 400);
        }
    }
    if (count($modalidades) < 1) {
        jsonResponse(['success' => false, 'error' => 'Seleccione al menos una modalidad.', 'traceId' => $traceId], 400);
    }

    $tieneCombate = in_array('Combate individual', $modalidades, true);
    $detalle['IDCurso'] = $idCursoOk;
    $detalle['nombreCurso'] = trim((string) ($input['nombreCurso'] ?? 'Open Kewmgang'));
    $detalle['Modalidad'] = implode(', ', $modalidades);
    $mesActual = str_pad((string) date('n'), 2, '0', STR_PAD_LEFT);
    $detalle['Mes'] = $mesActual;
    $detalle['Periodo'] = $mesActual . str_pad((string) ($anio % 100), 2, '0', STR_PAD_LEFT);
    $detalle['Sede'] = $detalle['Sede'] ?? 'MEDELLÍN';

    $precioTotal = 0;
    if (in_array('Festival infantil', $modalidades, true)) {
        $precioTotal += 60000;
    }
    if (in_array('Combate individual', $modalidades, true)) {
        $precioTotal += 75000;
    }

    if ($tieneCombate) {
        $rama = trim((string) ($input['openk_rama'] ?? ''));
        $division = trim((string) ($input['openk_division'] ?? ''));
        $grado = trim((string) ($input['openk_grado'] ?? ''));
        $estatura = trim((string) ($input['openk_estatura'] ?? ''));
        $peso = trim((string) ($input['openk_peso'] ?? ''));

        if (!in_array($rama, ['Femenino', 'Masculino'], true)) {
            jsonResponse(['success' => false, 'error' => 'Seleccione la rama (Femenino o Masculino).', 'traceId' => $traceId], 400);
        }
        $divisionesValidas = ['Benjamin', 'Pre cadetes', 'Cadetes', 'Junior'];
        if (!in_array($division, $divisionesValidas, true)) {
            jsonResponse(['success' => false, 'error' => 'Seleccione una división válida.', 'traceId' => $traceId], 400);
        }
        if (!in_array($grado, ['Blancos', 'Amarillo', 'Verde'], true)) {
            jsonResponse(['success' => false, 'error' => 'Seleccione el grado (Blancos, Amarillo o Verde).', 'traceId' => $traceId], 400);
        }

        if ($division === 'Junior') {
            if ($peso === '' || !is_numeric($peso) || (float) $peso <= 0) {
                jsonResponse(['success' => false, 'error' => 'Ingrese el peso en kg para la división Junior.', 'traceId' => $traceId], 400);
            }
            $estatura = null;
        } else {
            if ($estatura === '' || !is_numeric($estatura) || (float) $estatura <= 0) {
                jsonResponse(['success' => false, 'error' => 'Ingrese la estatura en cm.', 'traceId' => $traceId], 400);
            }
            $peso = null;
        }

        $detalle['IDAsign'] = $rama;
        $detalle['Sesión'] = $division;
        $detalle['categoria'] = $grado;
        $detalle['OBSERVACION'] = json_encode([
            'modalidades' => $modalidades,
            'valor_total' => $precioTotal,
            'estatura_cm' => $estatura !== null ? (float) $estatura : null,
            'peso_kg' => $peso !== null ? (float) $peso : null,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $detalle['OBSERVACION'] = json_encode([
            'modalidades' => $modalidades,
            'valor_total' => $precioTotal,
        ], JSON_UNESCAPED_UNICODE);
    }
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
} elseif ($tipoId === 4) {
    // Duplicados por asignatura se validan al guardar cada fila.
} elseif ($tipoId === 18) {
    require_once __DIR__ . '/../../includes/eventos_tipo18.php';
    $idCurso = eventosTipo18OpenKewmgangId();
    if ($inscripcion->existeDuplicada($participanteDocumento, $idCurso, $anio, $tipoId)) {
        jsonResponse(['success' => false, 'error' => 'Este participante ya está inscrito en Open Kewmgang.', 'traceId' => $traceId], 400);
    }
} elseif ($tipoId !== 3) {
    $idCurso = $input['IDCurso'] ?? $input['curso_id'] ?? $input['campamento_id'] ?? $input['salida_id'] ?? null;
    if ($idCurso && $inscripcion->existeDuplicada($participanteDocumento, (string) $idCurso, $anio, $tipoId)) {
        $msg = $tipoId === 2 ? 'Ya está inscrito en este campamento' : 'Ya está inscrito en esta actividad';
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
        if ($usaApiInscripcion && $apiExt->isConfigured() && $rowPart) {
            if ($rowResp) {
                $apiExt->crearResponsable([
                    'documento' => $rowResp['IDResponsable'] ?? $responsableDocumento,
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
            $apiExt->crearParticipante($rowPart, $responsableDocumento);
        }
        foreach ($cursoIds as $i => $cid) {
            $detalle['IDCurso'] = $cid;
            $detalle['nombreCurso'] = $nombresCurso[$i] ?? $cid;
            $newId = $inscripcion->create($participanteDocumento, $responsableDocumento, $tipoId, $detalle);
            $ids[] = $newId;
            if ($newId <= 0 && class_exists('AppLogger')) {
                AppLogger::error('guardar-inscripcion: insert id <= 0 (tipo 1)', [
                    'traceId' => $traceId,
                    'tipoId' => $tipoId,
                    'anio' => $anio,
                    'idCurso' => $cid,
                    'participante' => $participanteDocumento,
                    'responsable' => $responsableDocumento
                ]);
            }
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
        $idsInvalidos = array_filter($ids, fn($v) => (int) $v <= 0);
        if (!empty($idsInvalidos)) {
            if (class_exists('AppLogger')) {
                AppLogger::error('guardar-inscripcion: una o más inscripciones no se guardaron (tipo 1)', [
                    'traceId' => $traceId,
                    'tipoId' => $tipoId,
                    'anio' => $anio,
                    'participante' => $participanteDocumento,
                    'responsable' => $responsableDocumento,
                    'ids' => $ids
                ]);
            }
            jsonResponse(['success' => false, 'error' => 'No fue posible registrar la inscripción. Intente de nuevo.', 'trace_id' => $traceId], 500);
        }
        if (class_exists('AppLogger')) {
            AppLogger::info('guardar-inscripcion: inscripciones creadas (tipo 1)', [
                'traceId' => $traceId,
                'tipoId' => $tipoId,
                'anio' => $anio,
                'participante' => $participanteDocumento,
                'responsable' => $responsableDocumento,
                'ids' => $ids
            ]);
        }
        $tipoTexto = 'Curso(s)';
        $detalleTexto = implode(', ', $nombresCurso);
        $transporte = $detalle['Transporte'] ?? null;
        $mesInscripcion = null;
        $mesNumero = str_pad((string) ($detalle['Mes'] ?? ''), 2, '0', STR_PAD_LEFT);
        if ($mesNumero !== '' && isset($mesNombrePorNumero[$mesNumero])) {
            $mesInscripcion = $mesNombrePorNumero[$mesNumero];
        } elseif (!empty($detalle['Mes'])) {
            $mesInscripcion = (string) $detalle['Mes'];
        }
        if ($responsableEmail) {
            $emailService = new EmailService();
            $emailOk = $emailService->enviarConfirmacionInscripcion(
                $responsableEmail,
                $participanteNombre,
                $responsableNombre,
                $tipoTexto,
                $detalleTexto,
                $transporte,
                $mesInscripcion
            );
            if (!$emailOk && class_exists('AppLogger')) {
                AppLogger::error('guardar-inscripcion: email no enviado (tipo 1)', [
                    'traceId' => $traceId,
                    'tipoId' => $tipoId,
                    'anio' => $anio,
                    'participante' => $participanteDocumento,
                    'responsableEmail' => $responsableEmail,
                    'emailError' => $emailService->getLastError(),
                ]);
            } elseif (class_exists('AppLogger')) {
                AppLogger::info('guardar-inscripcion: email enviado (tipo 1)', [
                    'traceId' => $traceId,
                    'tipoId' => $tipoId,
                    'responsableEmail' => $responsableEmail,
                ]);
            }
        }
        jsonResponse(['success' => true, 'inscripcion_ids' => $ids, 'inscripcion_id' => $ids[0] ?? null, 'trace_id' => $traceId]);
    } elseif ($tipoId === 4) {
        $asignaturaModel = new Asignatura($database);
        $asignaturaIds = $input['asignatura_ids'] ?? [];
        if (!is_array($asignaturaIds)) {
            $asignaturaIds = $asignaturaIds !== '' && $asignaturaIds !== null ? [(string) $asignaturaIds] : [];
        }
        $asignaturasNuevas = $input['asignaturas_nuevas'] ?? [];
        if (!is_array($asignaturasNuevas)) {
            $asignaturasNuevas = $asignaturasNuevas ? [trim((string) $asignaturasNuevas)] : [];
        }

        $idsAsignaturas = [];
        foreach ($asignaturaIds as $idAsig) {
            $idAsig = (int) $idAsig;
            if ($idAsig > 0) {
                $idsAsignaturas[] = $idAsig;
            }
        }
        foreach ($asignaturasNuevas as $nombreNuevo) {
            $nombreNuevo = trim((string) $nombreNuevo);
            if ($nombreNuevo === '') {
                continue;
            }
            $idNuevo = $asignaturaModel->create($nombreNuevo);
            if ($idNuevo > 0) {
                $idsAsignaturas[] = $idNuevo;
            }
        }
        $idsAsignaturas = array_values(array_unique($idsAsignaturas));

        if (count($idsAsignaturas) < 1) {
            jsonResponse(['success' => false, 'error' => 'Seleccione o registre al menos una asignatura.', 'traceId' => $traceId], 400);
        }

        $idCurso = (string) ($detalle['IDCurso'] ?? '');
        $ids = [];
        $nombresAsignaturas = [];
        foreach ($idsAsignaturas as $idAsig) {
            if ($inscripcion->existeDuplicadaConAsignatura($participanteDocumento, $idCurso, $idAsig, $anio, $tipoId)) {
                $rowAsig = $asignaturaModel->getById($idAsig);
                $nom = $rowAsig['Asignatura'] ?? $idAsig;
                jsonResponse([
                    'success' => false,
                    'error' => "El participante ya está inscrito en la asignatura: {$nom}.",
                    'traceId' => $traceId
                ], 400);
            }
            $detalleAsig = $detalle;
            $detalleAsig['Asignatura'] = $idAsig;
            $newId = $inscripcion->create($participanteDocumento, $responsableDocumento, $tipoId, $detalleAsig);
            $ids[] = $newId;
            $rowAsig = $asignaturaModel->getById($idAsig);
            $nombresAsignaturas[] = $rowAsig['Asignatura'] ?? (string) $idAsig;
        }

        $idsInvalidos = array_filter($ids, fn($v) => (int) $v <= 0);
        if (!empty($idsInvalidos)) {
            jsonResponse(['success' => false, 'error' => 'No fue posible registrar la inscripción. Intente de nuevo.', 'trace_id' => $traceId], 500);
        }

        $tipoTexto = 'Level Up';
        $detalleTexto = ($detalle['nombreCurso'] ?? 'Level Up')
            . ' — Asignaturas: ' . implode(', ', $nombresAsignaturas);
        if (!empty($detalle['Sesión'])) {
            $detalleTexto .= ' (Modalidad: ' . $detalle['Sesión'] . ')';
        }

        if ($responsableEmail) {
            $emailService = new EmailService();
            $emailService->enviarConfirmacionInscripcion(
                $responsableEmail,
                $participanteNombre,
                $responsableNombre,
                $tipoTexto,
                $detalleTexto
            );
        }

        jsonResponse([
            'success' => true,
            'inscripcion_ids' => $ids,
            'inscripcion_id' => $ids[0] ?? null,
            'nombres_asignaturas' => $nombresAsignaturas,
            'trace_id' => $traceId,
        ]);
    } elseif ($tipoId === 18) {
        $id = $inscripcion->create($participanteDocumento, $responsableDocumento, $tipoId, $detalle);
        if ((int) $id <= 0) {
            jsonResponse(['success' => false, 'error' => 'No fue posible registrar la inscripción. Intente de nuevo.', 'trace_id' => $traceId], 500);
        }

        $obs = [];
        if (!empty($detalle['OBSERVACION'])) {
            $obs = json_decode((string) $detalle['OBSERVACION'], true) ?: [];
        }
        $valorTotal = (int) ($obs['valor_total'] ?? 0);
        $modalidadesTxt = $detalle['Modalidad'] ?? '';
        $detalleTexto = ($detalle['nombreCurso'] ?? 'Open Kewmgang') . ' — ' . $modalidadesTxt;
        if ($valorTotal > 0) {
            $detalleTexto .= ' (Valor total: $' . number_format($valorTotal, 0, ',', '.') . ')';
        }
        if (!empty($detalle['IDAsign'])) {
            $detalleTexto .= '. Rama: ' . $detalle['IDAsign'];
        }
        if (!empty($detalle['Sesión'])) {
            $detalleTexto .= ', División: ' . $detalle['Sesión'];
        }
        if (!empty($detalle['categoria'])) {
            $detalleTexto .= ', Grado: ' . $detalle['categoria'];
        }

        if ($responsableEmail) {
            $emailService = new EmailService();
            $emailService->enviarConfirmacionInscripcion(
                $responsableEmail,
                $participanteNombre,
                $responsableNombre,
                'Open Kewmgang',
                $detalleTexto
            );
        }

        jsonResponse([
            'success' => true,
            'inscripcion_id' => $id,
            'trace_id' => $traceId,
            'valor_total' => $valorTotal,
        ]);
    } else {
        $detalle['IDCurso'] = $input['IDCurso'] ?? $input['curso_id'] ?? $input['campamento_id'] ?? $input['salida_id'] ?? null;
        $detalle['nombreCurso'] = $input['nombreCurso'] ?? null;
        $id = $inscripcion->create($participanteDocumento, $responsableDocumento, $tipoId, $detalle);
        if ((int) $id <= 0) {
            if (class_exists('AppLogger')) {
                AppLogger::error('guardar-inscripcion: insert id <= 0 (tipo != 1)', [
                    'traceId' => $traceId,
                    'tipoId' => $tipoId,
                    'anio' => $anio,
                    'idCurso' => $detalle['IDCurso'] ?? null,
                    'participante' => $participanteDocumento,
                    'responsable' => $responsableDocumento
                ]);
            }
            jsonResponse(['success' => false, 'error' => 'No fue posible registrar la inscripción. Intente de nuevo.', 'trace_id' => $traceId], 500);
        }
        if (class_exists('AppLogger')) {
            AppLogger::info('guardar-inscripcion: inscripción creada (tipo != 1)', [
                'traceId' => $traceId,
                'tipoId' => $tipoId,
                'anio' => $anio,
                'id' => $id,
                'idCurso' => $detalle['IDCurso'] ?? null,
                'participante' => $participanteDocumento,
                'responsable' => $responsableDocumento
            ]);
        }
        $apiExt = new ExternalApiService();
        if ($apiExt->isConfigured()) {
            if ($rowResp) {
                $apiExt->crearResponsable([
                    'documento' => $rowResp['IDResponsable'] ?? $responsableDocumento,
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
            if ($rowPart) {
                $apiExt->crearParticipante($rowPart, $responsableDocumento);
            }
        }
        $idCurso = (int) ($detalle['IDCurso'] ?? 0);
        $participantesAdicionales = $input['participantes_adicionales'] ?? [];
        $participantesAdicionalesEmail = [];
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
            $participantesAdicionalesEmail = array_map(function ($p) {
                return [
                    'nombre' => trim((string) ($p['nombre'] ?? '')),
                    'documento' => trim((string) ($p['documento'] ?? '')),
                    'fechanacimiento' => trim((string) ($p['fechanacimiento'] ?? '')),
                ];
            }, $participantesAdicionales);
            if (!empty($participantesAdicionales) && $cfgPa) {
                $paModel = new ParticipanteAdicional($database);
                $paModel->guardarParaInscripcion($id, $idCurso, $participantesAdicionales);
            }
        }
        $tipoTexto = $tipoId === 2 ? 'Campamento' : ($tipoId === 5 || $tipoId === 3 ? 'Salida' : 'Inscripción');
        $detalleTexto = $detalle['nombreCurso'] ?? '-';
        $metodoPago = null;
        // English Camp: incluir método de pago seleccionado en el correo.
        $idCursoDetalle = (string) ($detalle['IDCurso'] ?? '');
        $nombreCursoDetalle = mb_strtolower(trim((string) ($detalle['nombreCurso'] ?? '')));
        $esEnglishCamp = $tipoId === 2 && (
            $idCursoDetalle === '2262' ||
            strpos($nombreCursoDetalle, 'english camp') !== false
        );
        if ($esEnglishCamp) {
            $metodoPagoInput = trim((string) ($input['modalidad_pago_english_camp'] ?? ''));
            $metodoPagoSesion = trim((string) ($detalle['Sesion'] ?? ''));
            $metodoPago = $metodoPagoInput !== '' ? $metodoPagoInput : ($metodoPagoSesion !== '' ? $metodoPagoSesion : null);
        }
        if ($responsableEmail) {
            $emailService = new EmailService();
            $emailOk = $emailService->enviarConfirmacionInscripcion(
                $responsableEmail,
                $participanteNombre,
                $responsableNombre,
                $tipoTexto,
                $detalleTexto,
                null,
                null,
                $metodoPago,
                ($tipoId === 16 ? $participantesAdicionalesEmail : [])
            );
            if (!$emailOk && class_exists('AppLogger')) {
                AppLogger::error('guardar-inscripcion: email no enviado (tipo != 1)', [
                    'traceId' => $traceId,
                    'tipoId' => $tipoId,
                    'anio' => $anio,
                    'idCurso' => $detalle['IDCurso'] ?? null,
                    'participante' => $participanteDocumento,
                    'responsableEmail' => $responsableEmail,
                    'emailError' => $emailService->getLastError(),
                ]);
            } elseif (class_exists('AppLogger')) {
                AppLogger::info('guardar-inscripcion: email enviado (tipo != 1)', [
                    'traceId' => $traceId,
                    'tipoId' => $tipoId,
                    'id' => $id,
                    'idCurso' => $detalle['IDCurso'] ?? null,
                    'responsableEmail' => $responsableEmail,
                ]);
            }
        }
        jsonResponse(['success' => true, 'inscripcion_id' => $id, 'trace_id' => $traceId]);
    }
} catch (Exception $e) {
    AppLogger::error('guardar-inscripcion: ' . $e->getMessage(), ['traceId' => $traceId, 'trace' => $e->getTraceAsString()]);
    jsonResponse(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage(), 'trace_id' => $traceId], 500);
}
