<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/reserva_cupo.php';

$formularioHabilitado = reservaCupoHabilitada();
$textoVentana = reservaCupoTextoVentana();
$notaCambio = reservaCupoNotaCambioActividad();
$anioDestino = reservaCupoAnioDestino();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de cupo <?= (int) $anioDestino ?> - Club Deportivo y Fundación Maex</title>
    <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-icon-180x180.png">
    <link rel="manifest" href="favicon/manifest.json">
    <meta name="theme-color" content="#20254A">
    <?= csrfMetaTag() ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-primary: #20254A;
            --bs-primary-rgb: 32, 37, 74;
            --bs-success: #18A6E0;
            --bs-warning: #FF6D00;
            --bs-body-color: #3C3C3B;
            --bs-heading-color: #20254A;
        }
        .reserva-row { border: 1px solid #e2e8f0; border-radius: .75rem; padding: 1rem; margin-bottom: .75rem; background: #fff; }
        .reserva-row .curso-recomendado { color: #20254A; font-weight: 600; }
        .reserva-acciones .btn-check:checked + .btn-outline-success { background: #198754; color: #fff; }
        .reserva-acciones .btn-check:checked + .btn-outline-danger { background: #dc3545; color: #fff; }
        .wrap-no-continua { display: none; }
    </style>
    <link href="assets/css/app.css?v=<?= @filemtime(__DIR__ . '/assets/css/app.css') ?: '1' ?>" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <header class="header-inscripcion d-flex align-items-center gap-4 mb-5 py-4 px-4 rounded-3 shadow-sm">
            <img src="assets/images/logo.png?v=<?= @filemtime(__DIR__ . '/assets/images/logo.png') ?: '1' ?>" alt="Logo" class="header-logo flex-shrink-0" onerror="this.style.display='none'">
            <div class="header-text flex-grow-1">
                <h1 class="mb-1 display-6 fw-bold">Reserva de cupo <?= (int) $anioDestino ?></h1>
                <p class="h5 mb-2 text-muted">Club Deportivo y Fundación Maex</p>
                <p class="mb-2 header-descripcion" style="font-size: 1rem;">
                    Este formulario confirma la continuidad del participante para iniciar desde <strong>el 25 enero de <?= (int) $anioDestino ?></strong>.
                </p>
                <p class="mb-2 header-descripcion" style="font-size: 1rem;">
                    Selecciona si continuará en la actividad recomendada o indica que no continuará y selecciona el motivo.
                </p>
                <p class="mb-2 header-descripcion" style="font-size: 1rem;">
                    Al seleccionar <strong>Continuó</strong> el cupo quedará reservado para 2027 en la actividad indicada.
                </p>
                <p class="mb-2 header-descripcion" style="font-size: 1rem;">
                    Fecha límite para confirmar: <?= htmlspecialchars($textoVentana, ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p class="mb-0 header-descripcion fw-semibold" style="font-size: 1rem;">
                    <?= htmlspecialchars($notaCambio, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </header>

        <?php if (!$formularioHabilitado): ?>
        <div class="alert alert-warning" role="status">
            El formulario de reserva de cupo estuvo disponible <?= htmlspecialchars($textoVentana, ENT_QUOTES, 'UTF-8') ?> y ya no acepta respuestas.
            <div class="mt-2"><?= htmlspecialchars($notaCambio, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php else: ?>
        <form id="formReservaCupo" class="needs-validation" novalidate>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">1. Participante</h5></div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="docParticipante">Documento del participante</label>
                            <input type="text" class="form-control" id="docParticipante" name="documento" required autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary w-100" id="btnValidarParticipante">Validar</button>
                        </div>
                    </div>
                    <p class="mt-2 mb-0 small" id="participanteInfo"></p>
                </div>
            </div>

            <div class="card mb-4" id="cardCursos" style="display:none;">
                <div class="card-header"><h5 class="mb-0">2. Cursos activos y recomendación <?= (int) $anioDestino ?></h5></div>
                <div class="card-body">
                    <p class="text-muted small">Para cada curso indica si continúas o no. Si no continúas, selecciona el motivo.</p>
                    <div id="listaCursos"></div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary" id="btnEnviar" disabled>
                            <span class="btn-text">Enviar respuestas</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="alert alert-success d-none" id="msgExito" role="status"></div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($formularioHabilitado): ?>
    <script src="assets/js/reserva-cupo.js?v=<?= @filemtime(__DIR__ . '/assets/js/reserva-cupo.js') ?: '1' ?>"></script>
    <?php endif; ?>
</body>
</html>
