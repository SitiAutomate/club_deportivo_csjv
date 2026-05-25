<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/participacion_junio.php';

$formularioJunioHabilitado = participacionJunioHabilitada();
$textoVentanaJunio = participacionJunioTextoVentana();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participación en vacaciones - Club Deportivo y Fundación Maex</title>
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
            --bs-secondary: #C6C6C6;
            --bs-secondary-rgb: 198, 198, 198;
            --bs-success: #18A6E0;
            --bs-success-rgb: 24, 166, 224;
            --bs-info: #18A6E0;
            --bs-info-rgb: 24, 166, 224;
            --bs-warning: #FF6D00;
            --bs-warning-rgb: 255, 109, 0;
            --bs-danger: #dc3545;
            --bs-danger-rgb: 220, 53, 69;
            --bs-dark: #3C3C3B;
            --bs-dark-rgb: 60, 60, 59;
            --bs-body-color: #3C3C3B;
            --bs-heading-color: #20254A;
            --bs-link-color: #3C3C3B;
            --bs-link-hover-color: #20254A;
        }
        a { text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
    <link href="assets/css/app.css?v=<?= @filemtime(__DIR__ . '/assets/css/app.css') ?: '1' ?>" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <header class="header-inscripcion d-flex align-items-center gap-4 mb-5 py-4 px-4 rounded-3 shadow-sm">
            <img src="assets/images/logo.png?v=<?= @filemtime(__DIR__ . '/assets/images/logo.png') ?: '1' ?>" alt="Logo" class="header-logo flex-shrink-0" onerror="this.style.display='none'">
            <div class="header-text flex-grow-1">
                <h1 class="mb-1 display-6 fw-bold">Participación en vacaciones (15 al 30 de junio)</h1>
                <p class="h5 mb-2 text-muted">Club Deportivo y Fundación Maex</p>
                <p class="mb-3 header-descripcion" style="font-size: 1rem;">
                    ¡Queremos seguir creando experiencias significativas para nuestros deportistas!
                </p>
                <p class="mb-3 header-descripcion" style="font-size: 1rem;">
                    Por eso, hemos preparado este formulario para conocer la participación y las expectativas frente a las actividades de vacaciones del Club Deportivo. Su respuesta será muy importante para proyectar espacios enriquecedores, dinámicos y acordes con las necesidades de nuestras familias.
                </p>
                <p class="mb-3 header-descripcion" style="font-size: 1rem;">
                    Recuerda que el grupo se abrirá durante este periodo solo si se cuenta con la participación necesaria para realizar los entrenamientos.
                </p>
                <p class="mb-0 header-descripcion" style="font-size: 1rem;">
                    Este formulario estará disponible <?= htmlspecialchars($textoVentanaJunio, ENT_QUOTES, 'UTF-8') ?>.
                </p>
            </div>
        </header>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">¿Cómo funciona?</h5>
            </div>
            <div class="card-body">
                <ol class="mb-0 ps-3">
                    <li class="mb-2">Acepte la autorización para el tratamiento de datos personales.</li>
                    <li class="mb-2">Ingrese el documento del participante y valídelo.</li>
                    <li class="mb-2">Indique si participará en los entrenamientos del club deportivo durante el periodo del 15 al 30 de junio.</li>
                    <li class="mb-2">Si respondió que sí, seleccione los cursos en los que confirmará su participación en vacaciones.</li>
                    <li class="mb-2">Al enviar, confirmará su participación en el periodo de vacaciones en los cursos seleccionados y recibirá un correo con la confirmación.</li>
                    <li class="mb-2">Tener en cuenta que, una vez confirmada la participación durante este periodo, la factura será generada por el mes completo.</li>
                    <li class="mb-0">No se contará con servicio de transporte entre el 15 y el 30 de junio.</li>
                </ol>
            </div>
        </div>

        <?php if (!$formularioJunioHabilitado): ?>
        <div class="alert alert-warning" role="status">
            El formulario estuvo disponible <?= htmlspecialchars($textoVentanaJunio, ENT_QUOTES, 'UTF-8') ?> y ya no acepta respuestas.
        </div>
        <?php else: ?>
        <form id="formParticipacionJunio" class="needs-validation" novalidate>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Políticas de tratamiento de datos</h5>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="checkPoliticas" required>
                        <label class="form-check-label" for="checkPoliticas">
                            Acepto la <a href="https://clubdeportivosjv.com//wp-content/uploads/2024/08/Autorizacion-para-el-tratamiento-de-datos-personales-Micro-Sitios-Digitales-WEB-UE.pdf" target="_blank" rel="noopener">autorización para el tratamiento de datos personales</a>.
                        </label>
                    </div>
                </div>
            </div>

            <div id="contenidoFormulario" style="display:none;">
                <div class="card mb-4" id="cardParticipara">
                    <div class="card-header">
                        <h5 class="mb-0">1. Participación en vacaciones</h5>
                    </div>
                    <div class="card-body">
                        <p class="fw-bold mb-3" id="labelParticipara">Participará en los entrenamientos del club deportivo durante el periodo del 15 al 30 de junio</p>
                        <div class="d-flex flex-wrap gap-4 mb-2" role="radiogroup" aria-labelledby="labelParticipara">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="participaraVacaciones" id="participaraSi" value="Si" required>
                                <label class="form-check-label fw-semibold" for="participaraSi">Sí</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="participaraVacaciones" id="participaraNo" value="No" required>
                                <label class="form-check-label fw-semibold" for="participaraNo">No</label>
                            </div>
                        </div>
                        <p class="form-text mb-0" id="textoParticiparaNo">Si responde <strong>No</strong>, se registrará un <strong>retiro temporal por vacaciones</strong> en todos los cursos activos del mes actual para el periodo del 15 al 30 de junio. No implica salida definitiva del club deportivo.</p>
                        <p class="form-text text-primary mb-0 mt-2 d-none" id="hintValidarDocumento" role="status"></p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">2. Documento del participante</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Ingrese el documento del participante y valídelo para consultar los cursos con participación activa en el mes actual.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="documentoParticipante" class="form-label fw-bold">Documento del participante</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="documentoParticipante" name="documentoParticipante"
                                           placeholder="Ingrese documento y presione Validar" required autocomplete="on">
                                    <button type="button" class="btn btn-outline-primary" id="btnValidarParticipante">
                                        <span class="btn-text">Validar</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Ingrese el documento del participante.</div>
                                <div class="form-text">Use el mismo documento con el que el participante está registrado.</div>
                                <div id="participanteResumen" class="mt-2 small text-muted d-none" role="status"></div>
                                <div id="participanteError" class="mt-2 small text-danger d-none" role="alert"></div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="card mb-4" id="cardCursos" style="display:none;">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">3. Cursos del mes actual</h5>
                        <span class="badge text-bg-secondary" id="badgeMesConsulta"></span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3" id="textoSeleccionCursos">Seleccione los cursos en los que confirmará su participación en vacaciones.</p>
                        <div id="listaCursos" class="vstack gap-2"></div>
                        <div id="sinCursos" class="alert alert-warning d-none mb-0" role="status">
                            No encontramos cursos activos del mes actual para este participante.
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                    <button type="submit" class="btn btn-primary px-4" id="btnEnviar" disabled>Confirmar participación en vacaciones</button>
                </div>
            </div>
        </form>

        <div id="resultadoFinal" class="alert d-none" role="status"></div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/participacion-junio.js?v=<?= @filemtime(__DIR__ . '/assets/js/participacion-junio.js') ?: '1' ?>"></script>
        <?php endif; ?>

        <p class="text-center text-muted small mb-0">
            <a href="index.php">Volver al formulario principal</a>
        </p>
    </div>
</body>
</html>
