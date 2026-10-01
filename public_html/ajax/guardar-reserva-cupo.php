<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/reserva_cupo.php';
require_once __DIR__ . '/../../includes/EmailService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Método no permitido'], 405);
}
csrfValidate();

header('Content-Type: application/json; charset=utf-8');
$traceId = bin2hex(random_bytes(8));
header('X-Trace-Id: ' . $traceId);

if (!reservaCupoHabilitada()) {
    jsonResponse([
        'success' => false,
        'error' => 'El formulario de reserva de cupo ya no está disponible.',
        'traceId' => $traceId,
    ], 403);
}

$input = getPostData();
$participanteDoc = trim((string) ($input['participante_id'] ?? $input['participante_documento'] ?? ''));
$decisiones = $input['decisiones'] ?? [];

if ($participanteDoc === '') {
    jsonResponse(['success' => false, 'error' => 'Documento del participante requerido', 'traceId' => $traceId], 400);
}
if (!is_array($decisiones) || count($decisiones) < 1) {
    jsonResponse(['success' => false, 'error' => 'Debe indicar al menos una decisión de continuidad.', 'traceId' => $traceId], 400);
}

$participante = new Participante($database);
$rowPart = $participante->getByDocumento($participanteDoc);
if (!$rowPart) {
    jsonResponse(['success' => false, 'error' => 'Participante no encontrado.', 'traceId' => $traceId], 404);
}

$categorias = reservaCupoCategoriasNoContinua();
$anioDestino = reservaCupoAnioDestino();
$inscripcion = new Inscripcion($database);
$creadas = [];
$errores = [];
$responsableDocUsado = '';

foreach ($decisiones as $idx => $d) {
    if (!is_array($d)) {
        continue;
    }
    $accion = trim((string) ($d['accion'] ?? ''));
    $cursoRecomendadoId = trim((string) ($d['curso_recomendado_id'] ?? $d['IDCurso'] ?? ''));
    $cursoActualId = trim((string) ($d['curso_actual_id'] ?? ''));
    $nombreCurso = trim((string) ($d['curso_recomendado_nombre'] ?? $d['nombreCurso'] ?? $cursoRecomendadoId));
    $sede = trim((string) ($d['sede'] ?? 'MEDELLÍN'));
    $transporte = $d['transporte'] ?? null;
    $responsableDoc = trim((string) ($d['responsable_documento'] ?? $rowPart['IDResponsable'] ?? ''));

    if (!in_array($accion, ['continuar', 'no_continuar'], true)) {
        $errores[] = 'Decisión inválida en el ítem ' . ($idx + 1) . '.';
        continue;
    }
    if ($cursoRecomendadoId === '') {
        $errores[] = 'Falta el curso recomendado en el ítem ' . ($idx + 1) . '.';
        continue;
    }
    if ($responsableDoc === '') {
        $errores[] = 'No se encontró responsable para el curso ' . $cursoRecomendadoId . '.';
        continue;
    }

    $yaActivo = (int) $database->count('inscripciones_1', [
        'validador_participante' => $participanteDoc,
        'IDCurso' => $cursoRecomendadoId,
        'Mes' => '01',
        'año' => $anioDestino,
        'Tipo' => 1,
        'Estado' => ['ACTIVO', 'Activo'],
    ]);
    $yaRetirado = (int) $database->count('inscripciones_1', [
        'validador_participante' => $participanteDoc,
        'IDCurso' => $cursoRecomendadoId,
        'Mes' => '01',
        'año' => $anioDestino,
        'Tipo' => 1,
        'Estado' => ['RETIRADO', 'Retirado', 'retirado'],
    ]);
    if ($yaActivo > 0) {
        $errores[] = 'Ya existe una reserva de cupo de enero ' . $anioDestino . ' para: ' . $nombreCurso;
        continue;
    }
    if ($yaRetirado > 0) {
        $errores[] = 'Ya se registró la no continuidad de enero ' . $anioDestino . ' para: ' . $nombreCurso;
        continue;
    }

    $causalRetiro = null;
    if ($accion === 'continuar') {
        $estado = 'ACTIVO';
        $obs = reservaCupoObservacionContinua();
    } else {
        $catKey = trim((string) ($d['categoria'] ?? ''));
        if ($catKey === '' || !isset($categorias[$catKey])) {
            $errores[] = 'Seleccione una categoría de no continuidad para: ' . $nombreCurso;
            continue;
        }
        $estado = 'RETIRADO';
        $causalRetiro = $categorias[$catKey];
        $obs = reservaCupoObservacionNoContinua($causalRetiro);
    }

    $detalle = [
        'IDCurso' => $cursoRecomendadoId,
        'nombreCurso' => $nombreCurso !== '' ? $nombreCurso : $cursoRecomendadoId,
        'Sede' => $sede !== '' ? $sede : 'MEDELLÍN',
        'Transporte' => $transporte,
        'Estado' => $estado,
        'Mes' => '01',
        'Periodo' => '01' . str_pad((string) ($anioDestino % 100), 2, '0', STR_PAD_LEFT),
        'año' => $anioDestino,
        'Fecha_Inscripción' => date('Y-m-d'),
        'Politicas' => 'Si',
        'OBSERVACION' => $obs,
        'CAUSAL DE RETIRO' => $causalRetiro,
    ];

    $id = $inscripcion->create($participanteDoc, $responsableDoc, 1, $detalle);
    if ($id <= 0) {
        $errores[] = 'No fue posible guardar la respuesta para: ' . $nombreCurso;
        continue;
    }
    $responsableDocUsado = $responsableDoc;
    $creadas[] = [
        'id' => $id,
        'curso_id' => $cursoRecomendadoId,
        'nombre' => $nombreCurso,
        'accion' => $accion,
        'estado' => $estado,
        'categoria' => $causalRetiro,
        'curso_actual_id' => $cursoActualId,
    ];
}

if (empty($creadas) && !empty($errores)) {
    jsonResponse([
        'success' => false,
        'error' => implode(' ', $errores),
        'errores' => $errores,
        'traceId' => $traceId,
    ], 400);
}

$nReservas = 0;
$nNoContinua = 0;
foreach ($creadas as $c) {
    if (($c['accion'] ?? '') === 'continuar') {
        $nReservas++;
    } else {
        $nNoContinua++;
    }
}
$partesMsg = [];
if ($nReservas > 0) {
    $partesMsg[] = $nReservas . ' reserva(s) de cupo registrada(s) para enero ' . $anioDestino;
}
if ($nNoContinua > 0) {
    $partesMsg[] = $nNoContinua . ' registro(s) de no continuidad';
}
$mensaje = !empty($partesMsg)
    ? implode('. ', $partesMsg) . '.'
    : 'Respuesta registrada.';

$emailEnviado = false;
$emailError = null;
if (!empty($creadas)) {
    $participanteNombre = $rowPart['Nombre_Completo']
        ?? trim(($rowPart['Primer_Nombre'] ?? '') . ' ' . ($rowPart['Primer_Apellido'] ?? ''));
    if ($responsableDocUsado === '') {
        $responsableDocUsado = trim((string) ($rowPart['IDResponsable'] ?? ''));
    }
    $responsableModel = new Responsable($database);
    $responsable = $responsableDocUsado !== '' ? $responsableModel->getByDocumento($responsableDocUsado) : null;
    $correo = trim((string) ($responsable['Correo_Responsable'] ?? ''));
    $responsableNombre = trim((string) ($responsable['Nombre_Completo']
        ?? trim(($responsable['Nombres'] ?? '') . ' ' . ($responsable['Apellidos'] ?? ''))));

    if ($correo !== '') {
        $emailService = new EmailService();
        $emailEnviado = $emailService->enviarConfirmacionReservaCupo(
            $correo,
            $participanteNombre !== '' ? $participanteNombre : $participanteDoc,
            $responsableNombre !== '' ? $responsableNombre : $responsableDocUsado,
            $anioDestino,
            $creadas
        );
        if (!$emailEnviado) {
            $emailError = $emailService->getLastError();
            if (class_exists('AppLogger')) {
                AppLogger::warning('reserva_cupo email no enviado', [
                    'traceId' => $traceId,
                    'correo' => $correo,
                    'emailError' => $emailError,
                ]);
            }
        } elseif (class_exists('AppLogger')) {
            AppLogger::info('reserva_cupo email enviado', ['traceId' => $traceId, 'correo' => $correo]);
        }
    } elseif (class_exists('AppLogger')) {
        AppLogger::warning('reserva_cupo sin correo responsable', [
            'traceId' => $traceId,
            'responsable' => $responsableDocUsado,
        ]);
        $emailError = 'El responsable no tiene correo registrado.';
    }
}

jsonResponse([
    'success' => true,
    'creadas' => $creadas,
    'reservas' => $nReservas,
    'no_continua' => $nNoContinua,
    'errores' => $errores,
    'mensaje' => $mensaje,
    'emailEnviado' => $emailEnviado,
    'emailError' => $emailError,
    'traceId' => $traceId,
]);
