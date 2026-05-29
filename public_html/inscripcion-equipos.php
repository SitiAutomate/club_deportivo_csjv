<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/csrf.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción de equipos - Club Deportivo y Fundación Maex</title>
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
            --bs-success: #18A6E0;
            --bs-warning: #FF6D00;
            --bs-danger: #dc3545;
            --bs-body-color: #334155;
            --bs-heading-color: #1e293b;
        }
        a { text-decoration: none; }
        a:hover { text-decoration: underline; }

        .page-equipos {
            background: #f8fafc;
            color: #334155;
        }
        .page-equipos .card {
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .page-equipos .card-header {
            background: #f1f5f9;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
        }
        .page-equipos .equipo-card { border-left: 4px solid #64748b; }
        .page-equipos .deportista-row { background: #f8fafc; }
        .page-equipos .text-muted { color: #64748b !important; }
        .page-equipos .form-text { color: #64748b; }

        .page-equipos #barEquiposInscritos {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #334155;
        }
        .page-equipos #badgeEquiposExistentes {
            background: #334155 !important;
            color: #fff !important;
        }
        .page-equipos #btnVerEquiposInscritos {
            color: #334155;
            border: 1px solid #94a3b8;
            background: #fff;
        }
        .page-equipos #btnVerEquiposInscritos:hover {
            background: #e2e8f0;
            border-color: #64748b;
            color: #1e293b;
        }
    </style>
    <link href="assets/css/app.css?v=<?= @filemtime(__DIR__ . '/assets/css/app.css') ?: '1' ?>" rel="stylesheet">
</head>
<body class="page-equipos">
    <div class="container py-4">
        <header class="header-inscripcion d-flex align-items-center gap-4 mb-5 py-4 px-4 rounded-3 shadow-sm">
            <img src="assets/images/logo.png?v=<?= @filemtime(__DIR__ . '/assets/images/logo.png') ?: '1' ?>" alt="Logo" class="header-logo flex-shrink-0" onerror="this.style.display='none'">
            <div class="header-text flex-grow-1">
                <h1 class="mb-1 display-6 fw-bold">Inscripción de equipos</h1>
                <p class="h5 mb-2 text-muted">Club Deportivo y Fundación Maex</p>
                <p class="mb-2 header-descripcion" style="font-size: 1rem;">
                    Una jornada pensada para disfrutar, aprender y vivir la pasión por el deporte en un ambiente de integración, alegría y compañerismo.
                </p>
                <p class="mb-0 header-descripcion" style="font-size: 1rem;">
                    Diligencie los datos del responsable de pago, seleccione el evento y registre los equipos a inscribir.
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
                    <li class="mb-2">Ingrese el documento del responsable de pago y valídelo. Si no está registrado, complete sus datos.</li>
                    <li class="mb-2">Seleccione el evento y la cantidad de equipos a inscribir (entre 1 y 10).</li>
                    <li class="mb-2">Diligencie por cada equipo el nombre, rama, categoría, entrenador, asistente y la planilla de deportistas.</li>
                    <li class="mb-0">Al enviar, se confirmará la inscripción y recibirá un correo con el listado de equipos.</li>
                </ol>
            </div>
        </div>

        <form id="formInscripcionEquipos" class="needs-validation" novalidate>
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
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">1. Responsable de pago</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Ingrese el documento del responsable de pago y valídelo. Si no está registrado en el sistema, podrá completar sus datos a continuación.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="docResponsable" class="form-label fw-bold">Documento del responsable</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="docResponsable" name="docResponsable"
                                           placeholder="Ingrese documento y presione Validar" required autocomplete="on">
                                    <button type="button" class="btn btn-outline-primary" id="btnValidarResponsable">
                                        <span class="btn-text">Validar</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Ingrese el documento del responsable.</div>
                                <div id="responsableInfo" class="mt-2 small text-muted d-none" role="status"></div>
                                <div id="responsableError" class="mt-2 small text-danger d-none" role="alert"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4" id="cardEvento" style="display:none;">
                    <div class="card-header">
                        <h5 class="mb-0">2. Evento y cantidad de equipos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="eventoSelect" class="form-label fw-bold">Seleccione el evento</label>
                                <select class="form-select" id="eventoSelect" required>
                                    <option value="">-- Cargando eventos... --</option>
                                </select>
                                <div class="form-text" id="eventoInfo"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="cantidadEquipos" class="form-label fw-bold">Cantidad de equipos</label>
                                <select class="form-select" id="cantidadEquipos">
                                    <option value="">-- Seleccione --</option>
                                </select>
                                <div class="form-text">Mínimo 1, máximo 10 por envío (hasta 10 equipos en total por responsable y evento).</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="barEquiposInscritos" class="alert d-none flex-wrap align-items-center justify-content-between gap-2 mb-3" role="status">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge" id="badgeEquiposExistentes"></span>
                        <span class="small mb-0" id="textoEquiposExistentes"></span>
                    </div>
                    <button type="button" class="btn btn-sm" id="btnVerEquiposInscritos" style="display:none;">
                        Ver equipos inscritos
                    </button>
                </div>

                <div id="contenedorEquipos"></div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                    <button type="submit" class="btn btn-primary px-4" id="btnEnviar" disabled>
                        <span class="btn-text">Confirmar inscripción de equipos</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </form>

        <div id="resultadoFinal" class="alert d-none" role="status"></div>
    </div>

    <!-- Modal equipos ya inscritos (solo lectura) -->
    <div class="modal fade" id="modalEquiposInscritos" tabindex="-1" aria-labelledby="modalEquiposInscritosLabel">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEquiposInscritosLabel">Equipos ya inscritos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="listaEquiposExistentes"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Éxito -->
    <div class="modal fade" id="modalExito" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-exito-header">
                    <h5 class="modal-title text-white">✓ Inscripción registrada</h5>
                </div>
                <div class="modal-body" id="modalExitoBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="btnCerrarExito">Aceptar</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal Nuevo Responsable -->
    <div class="modal fade" id="modalResponsable" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar responsable de pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formResponsable">
                    <div class="modal-body">
                        <input type="hidden" name="documento_inicial" id="modalResponsableDocumentoInicial">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipo de identificación</label>
                                <select class="form-select" name="tipo_identificacion" id="modalResponsableTipoId">
                                    <option value="">-- Seleccione --</option>
                                    <option value="C">Cédula Ciudadanía</option>
                                    <option value="E">Cédula de Extranjería</option>
                                    <option value="N">NIT</option>
                                    <option value="O">Pasaporte</option>
                                    <option value="Y">Extranjero</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Número de documento</label>
                                <input type="text" class="form-control" name="documento" id="modalResponsableDocumento" readonly>
                                <small class="text-muted">Debe coincidir con el documento ingresado inicialmente.</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nombres</label>
                                <input type="text" class="form-control" name="nombres" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Apellidos</label>
                                <input type="text" class="form-control" name="apellidos" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Celular</label>
                                <input type="tel" class="form-control" name="celular">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Correo electrónico</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipo de persona</label>
                                <select class="form-select" name="tipo_persona" required>
                                    <option value="">-- Seleccione --</option>
                                    <option value="Natural">Natural</option>
                                    <option value="Jurídica">Jurídica</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Departamento</label>
                                <div class="input-group">
                                    <select class="form-select" name="departamento" id="modalResponsableDepto">
                                        <option value="">-- Seleccione --</option>
                                    </select>
                                    <span class="input-group-text spinner-select-depto d-none" style="background:transparent;border-left:none;">
                                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ciudad</label>
                                <div class="input-group">
                                    <select class="form-select" name="ciudad" id="modalResponsableCiudad">
                                        <option value="">-- Seleccione departamento primero --</option>
                                    </select>
                                    <span class="input-group-text spinner-select-ciudad d-none" style="background:transparent;border-left:none;">
                                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Dirección de residencia</label>
                            <input type="text" class="form-control" name="direccion">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-text">Guardar responsable</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/inscripcion-equipos.js?v=<?= @filemtime(__DIR__ . '/assets/js/inscripcion-equipos.js') ?: '1' ?>"></script>
</body>
</html>
