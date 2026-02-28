<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/csrf.php';

$inscripcionTipo = new InscripcionTipo($database);
$tipos = $inscripcionTipo->getAll();

$datosAdicionalesPath = __DIR__ . '/../config/datos_adicionales.php';
$camposDatosAdicionales = file_exists($datosAdicionalesPath) ? require $datosAdicionalesPath : [];
$camposDatosAdicionales = array_filter($camposDatosAdicionales, fn($c) => !empty($c['enabled']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de inscripción - Club Deportivo y Maex</title>
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
        a {
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
    <link href="assets/css/app.css?v=<?= @filemtime(__DIR__ . '/assets/css/app.css') ?: '1' ?>" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <header class="header-inscripcion d-flex align-items-center gap-4 mb-5 py-4 px-4 rounded-3 shadow-sm">
            <img src="assets/images/logo.png" alt="Logo" class="header-logo flex-shrink-0" onerror="this.style.display='none'">
            <div class="header-text flex-grow-1">
                <h1 class="mb-1 display-6 fw-bold">Formulario de inscripción</h1>
                <p class="h5 mb-2 text-muted">Club Deportivo y Maex</p>
                <p class="mb-0 header-descripcion" style="font-size: 1rem;">
                    Este es el espacio para inscribirte fácilmente a la oferta de formación. Solo necesitas llenar los datos que te pedimos y asegurarte de que estén correctos. Con esta información podremos confirmar tu cupo, enviarte todos los detalles y realizar el proceso de facturación.
                </p>
            </div>
        </header>

        <form id="formInscripcion" class="needs-validation">
            <!-- Políticas -->
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

            <div id="camposFormulario" style="display:none;">
                <!-- Participante -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">1. Datos del Participante</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Ingrese el documento del participante (estudiante). Debe validar el participante para poder ingresar el documento del responsable.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="docParticipante" class="form-label fw-bold">Documento del participante</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="docParticipante" name="docParticipante"
                                           placeholder="Ingrese documento y presione Validar o salga del campo" required autocomplete="on">
                                    <button type="button" class="btn btn-outline-primary" id="btnValidarParticipante">
                                        <span class="btn-text">Validar</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Ingrese el documento del participante.</div>
                                <div id="participanteInfo" class="mt-2 small text-muted" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Responsable -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">2. Datos del Responsable</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-3">Ingrese el documento del responsable (padre, madre o acudiente). Debe validar el documento del responsable para continuar con la inscripción.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="docResponsable" class="form-label fw-bold">Documento del responsable</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="docResponsable" name="docResponsable"
                                           placeholder="Ingrese documento y presione Validar" disabled required autocomplete="on">
                                    <button type="button" class="btn btn-outline-primary" id="btnValidarResponsable">
                                        <span class="btn-text">Validar</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Ingrese el documento del responsable.</div>
                                <div id="responsableInfo" class="mt-2 small text-muted" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tipo de inscripción (dinámico) - visible solo tras validar responsable -->
                <div class="card mb-4" id="cardTipoInscripcion" style="display:none;">
                    <div class="card-header">
                        <h5 class="mb-0">3. Tipo de Inscripción</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="tipoInscripcion" class="form-label fw-bold">Seleccione el tipo</label>
                            <select class="form-select" id="tipoInscripcion" name="tipoInscripcion" required disabled>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($tipos as $t): ?>
                                <option value="<?= (int)$t['IDTipo'] ?>"><?= htmlspecialchars(($t['Nombre_Tipo'] ?? '') . (isset($t['descripcion']) && $t['descripcion'] ? ': ' . $t['descripcion'] : '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="camposDinamicos"></div>
                    </div>
                </div>

                <!-- Datos adicionales - visible solo para tipos con muestraDatosAdicionales = true -->
                <div class="card mb-4 datos-adicionales-card" id="cardDatosAdicionales" style="display:none;">
                    <div class="card-header">
                        <h5 class="mb-0">4. Datos adicionales</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">Complete la siguiente información para garantizar el bienestar del participante durante la actividad.</p>
                        <?php foreach ($camposDatosAdicionales as $key => $cfg): ?>
                        <div class="datos-adicionales-item campo-dato-adicional" data-key="<?= htmlspecialchars($key) ?>" data-type="<?= htmlspecialchars($cfg['type'] ?? '') ?>">
                            <?php if (($cfg['type'] ?? '') === 'select_si_no'): ?>
                            <label class="form-label fw-bold"><?= htmlspecialchars($cfg['label'] ?? '') ?></label>
                            <select class="form-select" name="<?= htmlspecialchars($key) ?>" id="campo_<?= htmlspecialchars($key) ?>">
                                <option value="">-- Seleccione --</option>
                                <option value="Sí">Sí</option>
                                <option value="No">No</option>
                            </select>
                            <?php elseif (($cfg['type'] ?? '') === 'select_si_no_text'): ?>
                            <div class="row g-3"><div class="col-md-6">
                                <label class="form-label fw-bold"><?= htmlspecialchars($cfg['label'] ?? '') ?></label>
                                <select class="form-select select-si-no-text" name="<?= htmlspecialchars($key) ?>" id="campo_<?= htmlspecialchars($key) ?>" data-textfield="<?= htmlspecialchars($cfg['textFieldName'] ?? '') ?>">
                                    <option value="">-- Seleccione --</option>
                                    <option value="Sí">Sí</option>
                                    <option value="No">No</option>
                                </select>
                                <div class="mt-2 wrap-texto-si-no" style="display:none;">
                                    <label class="form-label small text-muted">Especifique</label>
                                    <textarea class="form-control" name="<?= htmlspecialchars($cfg['textFieldName'] ?? '') ?>" rows="2" placeholder="<?= htmlspecialchars($cfg['textPlaceholder'] ?? '') ?>" disabled></textarea>
                                </div>
                            </div></div>
                            <?php elseif (($cfg['type'] ?? '') === 'textarea'): ?>
                            <label class="form-label fw-bold"><?= htmlspecialchars($cfg['label'] ?? '') ?></label>
                            <textarea class="form-control" name="<?= htmlspecialchars($key) ?>" id="campo_<?= htmlspecialchars($key) ?>" rows="<?= (int)($cfg['rows'] ?? 3) ?>" placeholder="<?= htmlspecialchars($cfg['placeholder'] ?? '') ?>" <?= !empty($cfg['required']) ? 'required' : '' ?>></textarea>
                            <?php elseif (($cfg['type'] ?? '') === 'text'): ?>
                            <label class="form-label fw-bold"><?= htmlspecialchars($cfg['label'] ?? '') ?></label>
                            <input type="text" class="form-control" name="<?= htmlspecialchars($key) ?>" id="campo_<?= htmlspecialchars($key) ?>" placeholder="<?= htmlspecialchars($cfg['placeholder'] ?? '') ?>" <?= !empty($cfg['required']) ? 'required' : '' ?>>
                            <?php elseif (($cfg['type'] ?? '') === 'select'): ?>
                            <label class="form-label fw-bold"><?= htmlspecialchars($cfg['label'] ?? '') ?></label>
                            <select class="form-select" name="<?= htmlspecialchars($key) ?>" id="campo_<?= htmlspecialchars($key) ?>" <?= !empty($cfg['required']) ? 'required' : '' ?>>
                                <option value="">-- Seleccione --</option>
                                <?php foreach (($cfg['options'] ?? []) as $v => $l): ?>
                                <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($l) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end" id="divBotones" style="display:none;">
                    <button type="submit" class="btn btn-primary" id="btnEnviar" disabled>
                        <span class="btn-text">Enviar Inscripción</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal Nuevo Participante -->
    <div class="modal fade" id="modalParticipante" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Participante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formParticipante">
                    <div class="modal-body">
                        <input type="hidden" name="documento_inicial" id="modalParticipanteDocumentoInicial">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipo de identificación</label>
                                <select class="form-select" name="tipo_identificacion" id="modalParticipanteTipoId">
                                    <option value="">-- Seleccione --</option>
                                    <option value="U">Registro civil</option>
                                    <option value="T">Tarjeta identidad</option>
                                    <option value="C">Cédula Ciudadanía</option>
                                    <option value="X">Tarjeta Extranjería</option>
                                    <option value="E">Cédula de Extranjería</option>
                                    <option value="O">Pasaporte</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Número de documento</label>
                                <input type="text" class="form-control" name="documento" id="modalParticipanteDocumento" readonly>
                                <small class="text-muted">Debe coincidir con el documento ingresado inicialmente.</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Primer nombre</label>
                                <input type="text" class="form-control" name="primer_nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Segundo nombre</label>
                                <input type="text" class="form-control" name="segundo_nombre">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Primer apellido</label>
                                <input type="text" class="form-control" name="primer_apellido" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Segundo apellido</label>
                                <input type="text" class="form-control" name="segundo_apellido">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fecha de nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" id="modalParticipanteFechaNac" required>
                            <small class="text-muted">Edad mínima: 4 años.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Responsable -->
    <div class="modal fade" id="modalResponsable" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Responsable</h5>
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
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Éxito Inscripción -->
    <div class="modal fade" id="modalExito" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-exito-header text-white">
                    <h5 class="modal-title">✓ Inscripción registrada correctamente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalExitoContenido">
                    <!-- Contenido dinámico con resumen -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/inscripcion.js?v=<?= @filemtime(__DIR__ . '/assets/js/inscripcion.js') ?: '1' ?>"></script>
</body>
</html>
