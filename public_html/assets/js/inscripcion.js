(function () {
    'use strict';

    const basePath = (() => {
        const p = window.location.pathname;
        if (p.endsWith('/')) return p;
        const idx = p.lastIndexOf('/');
        return idx >= 0 ? p.slice(0, idx + 1) : '/';
    })();
    const getAuthHeaders = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) return {};
        return {
            'Authorization': 'Bearer ' + token,
            'X-CSRF-Token': token
        };
    };
    const ajax = (path, method, data) => {
        const opts = { method: method || 'GET', headers: { ...getAuthHeaders() } };
        if (data && (method === 'POST' || method === 'GET')) {
            if (method === 'POST') {
                opts.headers['Content-Type'] = 'application/json';
                opts.body = JSON.stringify(data);
            } else {
                const params = new URLSearchParams();
                Object.keys(data).forEach(k => {
                    if (data[k] != null && data[k] !== '') params.set(k, data[k]);
                });
                path += (path.includes('?') ? '&' : '?') + params.toString();
            }
        }
        return fetch(basePath + 'ajax/' + path.replace(/^\//, ''), opts).then(r => r.json());
    };

    function debounce(fn, ms) {
        let t;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), ms);
        };
    }

    let participanteActual = null;
    let responsableActual = null;
    let validandoParticipante = false;
    let validandoResponsable = false;

    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const camposFormulario = $('#camposFormulario');
    const checkPoliticas = $('#checkPoliticas');
    const docParticipante = $('#docParticipante');
    const docResponsable = $('#docResponsable');
    const participanteInfo = $('#participanteInfo');
    const responsableInfo = $('#responsableInfo');
    const tipoInscripcion = $('#tipoInscripcion');
    const camposDinamicos = $('#camposDinamicos');
    const formInscripcion = $('#formInscripcion');
    const btnEnviar = $('#btnEnviar');
    const modalParticipante = $('#modalParticipante');
    const modalResponsable = $('#modalResponsable');
    const formParticipante = $('#formParticipante');
    const formResponsable = $('#formResponsable');
    const cardTipoInscripcion = $('#cardTipoInscripcion');
    const divBotones = $('#divBotones');
    const btnValidarParticipante = $('#btnValidarParticipante');
    const btnValidarResponsable = $('#btnValidarResponsable');

    checkPoliticas.addEventListener('change', function () {
        camposFormulario.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
            participanteActual = null;
            responsableActual = null;
            docParticipante.value = '';
            docResponsable.value = '';
            participanteInfo.style.display = 'none';
            responsableInfo.style.display = 'none';
            if (cardTipoInscripcion) cardTipoInscripcion.style.display = 'none';
            if (divBotones) divBotones.style.display = 'none';
            tipoInscripcion.disabled = true;
        }
    });

    function setValidarSpinner(btn, show) {
        if (!btn) return;
        const txt = btn.querySelector('.btn-text');
        const sp = btn.querySelector('.spinner-border');
        if (txt) txt.classList.toggle('d-none', show);
        if (sp) sp.classList.toggle('d-none', !show);
        btn.disabled = show;
    }

    // --- Participante (auto-validar al salir del campo) ---
    function validarParticipante() {
        const doc = (docParticipante.value || '').trim();
        if (!doc) {
            participanteInfo.textContent = 'Ingrese el documento.';
            participanteInfo.className = 'mt-2 small invalid';
            participanteInfo.style.display = 'block';
            return;
        }
        if (validandoParticipante) return;
        participanteInfo.style.display = 'none';
        setValidarSpinner(btnValidarParticipante, true);
        validandoParticipante = true;

        ajax('validar-participante.php', 'POST', { documento: doc })
            .then(res => {
                if (!res.success) {
                    participanteInfo.textContent = res.error || 'Error al validar';
                    participanteInfo.className = 'mt-2 small invalid';
                    participanteInfo.style.display = 'block';
                    return;
                }
                if (res.exists) {
                    participanteActual = res.participante;
                    participanteInfo.textContent = `${res.participante.nombre} - Encontrado`;
                    participanteInfo.className = 'mt-2 small valid';
                    participanteInfo.style.display = 'block';
                    docParticipante.classList.remove('is-invalid');
                    docParticipante.classList.add('is-valid');
                    docParticipante.setCustomValidity('');
                    docResponsable.disabled = false;
                    if (cardTipoInscripcion) cardTipoInscripcion.style.display = 'none';
                    if (divBotones) divBotones.style.display = 'none';
                    if (res.participante.responsable_documento) {
                    docResponsable.value = res.participante.responsable_documento;
                    }
                } else {
                    participanteActual = null;
                    participanteInfo.textContent = 'No existe. Complete el formulario para registrarlo.';
                    participanteInfo.className = 'mt-2 small invalid';
                    participanteInfo.style.display = 'block';
                    const docInput = document.getElementById('modalParticipanteDocumento');
                    const docInicial = document.getElementById('modalParticipanteDocumentoInicial');
                    if (docInput) docInput.value = doc;
                    if (docInicial) docInicial.value = doc;
                    const fechaMax = new Date();
                    fechaMax.setFullYear(fechaMax.getFullYear() - 4);
                    const fechaNac = document.getElementById('modalParticipanteFechaNac');
                    if (fechaNac) fechaNac.max = fechaMax.toISOString().slice(0, 10);
                    const modal = new bootstrap.Modal(modalParticipante);
                    modal.show();
                }
            })
            .catch(() => {
                participanteInfo.textContent = 'Error de conexión. Intente de nuevo.';
                participanteInfo.className = 'mt-2 small invalid';
                participanteInfo.style.display = 'block';
            })
            .finally(() => {
                validandoParticipante = false;
                setValidarSpinner(btnValidarParticipante, false);
            });
    }

    docParticipante.addEventListener('input', function () {
        if (participanteActual) {
            participanteActual = null;
            docParticipante.setCustomValidity('Debe validar el documento del participante');
        }
    });
    docParticipante.addEventListener('blur', debounce(validarParticipante, 400));
    if (btnValidarParticipante) btnValidarParticipante.addEventListener('click', validarParticipante);

    docResponsable.addEventListener('input', function () {
        if (responsableActual) {
            responsableActual = null;
            docResponsable.setCustomValidity('Debe validar el documento del responsable');
        }
    });

    formParticipante.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        const doc = document.getElementById('modalParticipanteDocumento')?.value || '';
        const docInicial = document.getElementById('modalParticipanteDocumentoInicial')?.value || '';
        if (doc !== docInicial) {
            alert('El documento debe coincidir con el ingresado inicialmente.');
            return;
        }
        const data = {
            documento: doc,
            documento_inicial: docInicial,
            tipo_identificacion: fd.get('tipo_identificacion') || null,
            primer_nombre: fd.get('primer_nombre'),
            segundo_nombre: fd.get('segundo_nombre') || null,
            primer_apellido: fd.get('primer_apellido'),
            segundo_apellido: fd.get('segundo_apellido') || null,
            fecha_nacimiento: fd.get('fecha_nacimiento') || null
        };
        fetch(basePath + 'ajax/guardar-participante.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', ...getAuthHeaders() },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    participanteActual = res.participante;
                    docParticipante.value = res.participante.documento;
                    docParticipante.classList.remove('is-invalid');
                    docParticipante.classList.add('is-valid');
                    docParticipante.setCustomValidity('');
                    participanteInfo.textContent = `${(res.participante.nombre || res.participante.Primer_Nombre || '')} - Registrado`;
                    participanteInfo.className = 'mt-2 small valid';
                    participanteInfo.style.display = 'block';
                    docResponsable.disabled = false;
                    bootstrap.Modal.getInstance(modalParticipante).hide();
                    formParticipante.reset();
                    document.getElementById('modalParticipanteDocumento').value = '';
                    document.getElementById('modalParticipanteDocumentoInicial').value = '';
                } else {
                    alert(res.error || 'Error al guardar');
                }
            })
            .catch(() => alert('Error de conexión'));
    });

    // --- Responsable (validar al presionar Validar) ---
    function validarResponsable() {
        const doc = (docResponsable.value || '').trim();
        if (!doc || !participanteActual) {
            responsableInfo.textContent = 'Primero ingrese y valide el documento del participante.';
            responsableInfo.className = 'mt-2 small invalid';
            responsableInfo.style.display = 'block';
            return;
        }
        if (validandoResponsable) return;
        responsableInfo.style.display = 'none';
        setValidarSpinner(btnValidarResponsable, true);
        validandoResponsable = true;

        ajax('validar-responsable.php', 'POST', {
            documento: doc,
            participante_id: participanteActual.id
        })
            .then(res => {
                if (!res.success) {
                    responsableInfo.textContent = res.error || 'Error al validar';
                    responsableInfo.className = 'mt-2 small invalid';
                    responsableInfo.style.display = 'block';
                    return;
                }
                if (res.isAssigned) {
                    responsableActual = res.responsable || { documento: doc };
                    responsableInfo.textContent = 'Responsable asignado confirmado.';
                    responsableInfo.className = 'mt-2 small valid';
                    responsableInfo.style.display = 'block';
                    docResponsable.classList.remove('is-invalid');
                    docResponsable.classList.add('is-valid');
                    docResponsable.setCustomValidity('');
                    if (cardTipoInscripcion) cardTipoInscripcion.style.display = 'block';
                    if (divBotones) divBotones.style.display = 'flex';
                    habilitarTipoInscripcion();
                    return;
                }
                if (res.exists) {
                    if (confirm('El documento ingresado no corresponde al responsable asignado al participante. ¿Desea cambiar de responsable?')) {
                        responsableActual = res.responsable;
                        fetch(basePath + 'ajax/actualizar-responsable.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', ...getAuthHeaders() },
                            body: JSON.stringify({
                                participante_id: participanteActual.id,
                                responsable_id: res.responsable.id
                            })
                        })
                            .then(r => r.json())
                            .then(up => {
                                if (up.success) {
                                    participanteActual.responsable_id = res.responsable.id;
                                    participanteActual.responsable_documento = res.responsable.documento;
                                    responsableInfo.textContent = 'Responsable actualizado.';
                                    responsableInfo.className = 'mt-2 small valid';
                                    responsableInfo.style.display = 'block';
                                    docResponsable.classList.remove('is-invalid');
                                    docResponsable.classList.add('is-valid');
                                    docResponsable.setCustomValidity('');
                                    if (cardTipoInscripcion) cardTipoInscripcion.style.display = 'block';
                                    if (divBotones) divBotones.style.display = 'flex';
                                    habilitarTipoInscripcion();
                                } else {
                                    alert(up.error || 'Error al actualizar');
                                }
                            })
                            .catch(() => alert('Error de conexión'));
                    } else {
                        docResponsable.value = participanteActual.responsable_documento || '';
                        docResponsable.focus();
                    }
                    return;
                }
                const docInput = document.getElementById('modalResponsableDocumento');
                const docInicial = document.getElementById('modalResponsableDocumentoInicial');
                if (docInput) docInput.value = doc;
                if (docInicial) docInicial.value = doc;
                cargarDepartamentosModal();
                const modal = new bootstrap.Modal(modalResponsable);
                modal.show();
            })
            .catch(() => {
                responsableInfo.textContent = 'Error de conexión. Intente de nuevo.';
                responsableInfo.className = 'mt-2 small invalid';
                responsableInfo.style.display = 'block';
            })
            .finally(() => {
                validandoResponsable = false;
                setValidarSpinner(btnValidarResponsable, false);
            });
    }

    function cargarDepartamentosModal() {
        const sel = document.getElementById('modalResponsableDepto');
        const ciudadSel = document.getElementById('modalResponsableCiudad');
        const spDepto = document.querySelector('.spinner-select-depto');
        if (ciudadSel) {
            ciudadSel.innerHTML = '<option value="">-- Seleccione departamento primero --</option>';
        }
        if (!sel) return;
        sel.innerHTML = '<option value="">-- Cargando... --</option>';
        sel.disabled = true;
        if (spDepto) spDepto.classList.remove('d-none');
        ajax('get-departamentos.php').then(res => {
            sel.innerHTML = '<option value="">-- Seleccione --</option>';
            (res.departamentos || []).forEach(d => {
                sel.innerHTML += `<option value="${escapeHtml(d.Depto || '')}">${escapeHtml(d.Nombre_Dpto || '')}</option>`;
            });
        }).catch(() => {
            sel.innerHTML = '<option value="">-- Error al cargar --</option>';
        }).finally(() => {
            sel.disabled = false;
            if (spDepto) spDepto.classList.add('d-none');
        });
    }

    document.getElementById('modalResponsableDepto')?.addEventListener('change', function () {
        const depto = this.value;
        const ciudadSel = document.getElementById('modalResponsableCiudad');
        const spCiudad = document.querySelector('.spinner-select-ciudad');
        if (!ciudadSel) return;
        if (!depto) {
            ciudadSel.innerHTML = '<option value="">-- Seleccione departamento primero --</option>';
            return;
        }
        ciudadSel.innerHTML = '<option value="">-- Cargando... --</option>';
        ciudadSel.disabled = true;
        if (spCiudad) spCiudad.classList.remove('d-none');
        ajax('get-ciudades.php', 'GET', { depto }).then(res => {
            ciudadSel.innerHTML = '<option value="">-- Seleccione --</option>';
            (res.ciudades || []).forEach(c => {
                ciudadSel.innerHTML += `<option value="${escapeHtml(c.Ciudad || '')}">${escapeHtml(c.Nombre_Ciudad || '')}</option>`;
            });
        }).catch(() => {
            ciudadSel.innerHTML = '<option value="">-- Error al cargar --</option>';
        }).finally(() => {
            ciudadSel.disabled = false;
            if (spCiudad) spCiudad.classList.add('d-none');
        });
    });

    if (btnValidarResponsable) btnValidarResponsable.addEventListener('click', validarResponsable);

    formResponsable.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        const doc = document.getElementById('modalResponsableDocumento')?.value || '';
        const docInicial = document.getElementById('modalResponsableDocumentoInicial')?.value || '';
        if (doc !== docInicial) {
            alert('El documento debe coincidir con el ingresado inicialmente.');
            return;
        }
        const data = {
            documento: doc,
            documento_inicial: docInicial,
            tipo_identificacion: fd.get('tipo_identificacion') || null,
            nombres: fd.get('nombres'),
            apellidos: fd.get('apellidos'),
            celular: fd.get('celular') || null,
            email: fd.get('email') || null,
            tipo_persona: fd.get('tipo_persona') || null,
            ciudad: fd.get('ciudad') || null,
            direccion: fd.get('direccion') || null
        };
        fetch(basePath + 'ajax/guardar-responsable.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', ...getAuthHeaders() },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    responsableActual = res.responsable;
                    docResponsable.value = res.responsable.documento;
                    docResponsable.classList.remove('is-invalid');
                    docResponsable.classList.add('is-valid');
                    docResponsable.setCustomValidity('');
                    responsableInfo.textContent = `${res.responsable.nombre} - Registrado`;
                    responsableInfo.className = 'mt-2 small valid';
                    responsableInfo.style.display = 'block';
                    if (cardTipoInscripcion) cardTipoInscripcion.style.display = 'block';
                    if (divBotones) divBotones.style.display = 'flex';
                    fetch(basePath + 'ajax/actualizar-responsable.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', ...getAuthHeaders() },
                        body: JSON.stringify({
                            participante_id: participanteActual.id,
                            responsable_id: res.responsable.id
                        })
                    })
                        .then(r => r.json())
                        .then(up => {
                            if (up.success) participanteActual.responsable_id = res.responsable.id;
                        });
                    bootstrap.Modal.getInstance(modalResponsable).hide();
                    formResponsable.reset();
                    document.getElementById('modalResponsableDocumento').value = '';
                    document.getElementById('modalResponsableDocumentoInicial').value = '';
                    habilitarTipoInscripcion();
                } else {
                    alert(res.error || 'Error al guardar');
                }
            })
            .catch(() => alert('Error de conexión'));
    });

    function habilitarTipoInscripcion() {
        tipoInscripcion.disabled = false;
    }

    // --- Tipo inscripción dinámico ---
    let tiposConfig = {};
    ajax('get-tipos-config.php').then(res => {
        if (res.success) tiposConfig = res.tipos_config || {};
    }).catch(() => {});

    tipoInscripcion.addEventListener('change', function () {
        const tipo = parseInt(this.value, 10);
        camposDinamicos.innerHTML = '';
        btnEnviar.disabled = true;
        const cardDatosAdicionales = $('#cardDatosAdicionales');
        const cfg = tiposConfig[tipo] || {};
        // Datos adicionales solo visibles cuando se selecciona un tipo que los usa
        if (cardDatosAdicionales) {
            const mostrar = !!cfg.muestraDatosAdicionales;
            cardDatosAdicionales.style.display = mostrar ? 'block' : 'none';
            // Selectores obligatorios solo cuando la sección es visible
            const selectoresRequeridos = cardDatosAdicionales.querySelectorAll('select[name="autorizo_imagen"], select.select-si-no-text');
            selectoresRequeridos.forEach(el => {
                if (mostrar) el.setAttribute('required', '');
                else el.removeAttribute('required');
            });
        }
        // Reset campos datos adicionales (selectores con Seleccione por defecto)
        document.querySelectorAll('#cardDatosAdicionales .select-si-no-text').forEach(s => {
            s.value = '';
        });
        document.querySelectorAll('#cardDatosAdicionales .form-select[name="autorizo_imagen"]').forEach(s => {
            s.value = '';
        });
        document.querySelectorAll('#cardDatosAdicionales .wrap-texto-si-no').forEach(w => {
            w.style.display = 'none';
        });
        document.querySelectorAll('#cardDatosAdicionales .wrap-texto-si-no textarea').forEach(t => {
            t.value = '';
            t.disabled = true;
            t.required = false;
        });

        const necesitaSpinner = tipo > 0 && (tipo === 1 || tipo === 2 || tipo === 5 || cfg.hasSelector !== false);
        if (necesitaSpinner) {
            camposDinamicos.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted small">Cargando...</p></div>';
        }
        if (tipo === 1) cargarCamposTipo1();
        else if (tipo === 2) cargarCampamentos();
        else if (tipo === 5) cargarSalidas();
        else if (cfg.hasSelector === false) cargarTipoDirecto(tipo);
        else cargarCamposPorTipo(tipo);
    });

    document.getElementById('cardDatosAdicionales')?.addEventListener('change', function (e) {
        const sel = e.target.closest('.select-si-no-text');
        if (!sel) return;
        const tfName = sel.getAttribute('data-textfield');
        const wrap = sel.closest('.row')?.querySelector('.wrap-texto-si-no');
        const txt = wrap?.querySelector(`textarea[name="${tfName}"]`);
        if (wrap) wrap.style.display = sel.value === 'Sí' ? 'block' : 'none';
        if (txt) {
            if (sel.value === 'Sí') {
                txt.disabled = false;
                txt.required = true;
            } else {
                txt.disabled = true;
                txt.required = false;
                txt.value = '';
            }
        }
    });

    function cargarTipoDirecto(tipo) {
        const cfg = tiposConfig[tipo] || {};
        ajax('get-cursos-por-tipo.php', 'GET', { tipo_id: tipo })
            .then(res => {
                if (!res.success || !res.items || !res.items.length) {
                    camposDinamicos.innerHTML = '<p class="text-muted">No hay opciones disponibles para este tipo.</p>';
                    return;
                }
                const item = res.items[0];
                const selectorName = cfg.selectorName || 'curso_id';
                let html = '<input type="hidden" name="' + escapeHtml(selectorName) + '" value="' + escapeHtml(item.id) + '">';
                html += '<input type="hidden" name="nombreCurso" value="' + escapeHtml(item.nombre_curso || item.nombre || item.nombre_display || '') + '">';
                if (cfg.tieneTemplate) html += '<div class="detalle-template-contenedor"></div>';
                camposDinamicos.innerHTML = html;
                if (cfg.tieneTemplate) {
                    const cont = camposDinamicos.querySelector('.detalle-template-contenedor');
                    if (cont) cargarDetalleTemplate(tipo, item.id, cont);
                }
                btnEnviar.disabled = false;
            })
            .catch(() => {
                camposDinamicos.innerHTML = '<p class="text-danger">Error al cargar la actividad.</p>';
            });
    }

    function cargarCamposPorTipo(tipo) {
        const cfg = tiposConfig[tipo] || {};
        ajax('get-cursos-por-tipo.php', 'GET', { tipo_id: tipo })
            .then(res => {
                if (!res.success || !res.items || !res.items.length) {
                    camposDinamicos.innerHTML = '<p class="text-muted">No hay opciones disponibles para este tipo.</p>';
                    return;
                }
                const resCfg = res.config || {};
                const selectorName = resCfg.selectorName || cfg.selectorName || 'curso_id';
                const label = resCfg.labelSelector || cfg.labelSelector || 'Seleccione';
                let html = `<div class="mb-3"><label class="form-label fw-bold">${escapeHtml(label)}</label>`;
                html += '<select class="form-select select-con-detalle" name="' + escapeHtml(selectorName) + '" data-tipo="' + tipo + '" required><option value="">-- Seleccione --</option>';
                res.items.forEach(it => {
                    const fd = it.fecha_display || '';
                    const fechas = fd ? ` (${fd})` : '';
                    html += `<option value="${escapeHtml(it.id)}" data-nombre="${escapeHtml(it.nombre_curso || it.nombre || '')}">${escapeHtml(it.nombre_display || it.nombre)}${fechas}</option>`;
                });
                html += '</select></div>';
                if (cfg.tieneTemplate) html += '<div class="detalle-template-contenedor"></div>';
                camposDinamicos.innerHTML = html;
                if (cfg.tieneTemplate) {
                    const sel = camposDinamicos.querySelector('.select-con-detalle');
                    const cont = camposDinamicos.querySelector('.detalle-template-contenedor');
                    if (sel && cont) sel.addEventListener('change', () => cargarDetalleTemplate(tipo, sel.value, cont));
                }
                btnEnviar.disabled = false;
            })
            .catch(() => {
                camposDinamicos.innerHTML = '<p class="text-danger">Error al cargar opciones.</p>';
            });
    }

    function cargarCamposTipo1() {
        const anio = new Date().getFullYear();
        ajax('get-filtros-tipo1.php', 'GET', { tipo_id: 1 }).then(res => {
            const meses = res.meses || [];
            const lineas = res.lineas || [];
            const actividades = res.actividades || [];

            let html = '';
            html += '<div class="row g-3 mb-3">';
            html += '<div class="col-md-6 col-lg-3"><label class="form-label fw-bold">Mes</label><select class="form-select filtro-curso" name="mes" id="filtroMes"><option value="">-- Seleccione --</option>';
            meses.forEach(m => {
                html += `<option value="${m.NumMes}">${escapeHtml(m.Mes)}</option>`;
            });
            html += '</select></div>';
            html += '<div class="col-md-6 col-lg-3"><label class="form-label fw-bold">Sede</label><select class="form-select filtro-curso" name="sede" id="filtroSede"><option value="">-- Seleccione --</option><option value="MEDELLÍN">MEDELLÍN</option><option value="RETIRO">RETIRO</option></select></div>';
            html += '<div class="col-md-6 col-lg-3"><label class="form-label fw-bold">Línea</label><select class="form-select filtro-curso" name="linea" id="filtroLinea"><option value="">-- Seleccione --</option>';
            lineas.forEach(l => {
                html += `<option value="${l.IDLinea}">${escapeHtml(l.Nombre_Linea || '')}</option>`;
            });
            html += '</select></div>';
            html += '<div class="col-md-6 col-lg-3"><label class="form-label fw-bold">Actividad</label><select class="form-select filtro-curso" name="actividad" id="filtroActividad"><option value="">-- Seleccione --</option>';
            actividades.forEach(a => {
                html += `<option value="${a.IDActividad}">${escapeHtml(a.Nombre_Actividad || '')}</option>`;
            });
            html += '</select></div></div>';

            html += '<div class="mb-3"><label class="form-label fw-bold">¿Desea hacer uso del servicio de transporte extracurricular?</label>';
            html += '<p class="small text-muted">Este servicio está disponible únicamente para estudiantes del Colegio San José de Las Vegas que ya cuentan con el servicio de transporte escolar.</p>';
            html += '<select class="form-select" name="transporte" id="transporte" required><option value="">-- Seleccione --</option><option value="No">No</option><option value="Sí">Sí</option></select></div>';

            html += '<div class="mb-3" id="cursosActivosParticipante" style="display:none;"><label class="form-label fw-bold">Cursos activos (mes actual y siguiente)</label>';
            html += '<div id="listaCursosActivos" class="cursos-activos-box"></div></div>';

            html += '<input type="hidden" id="cursoCheckValidador" required>';
            html += '<div class="mb-3"><label class="form-label fw-bold">Seleccione el curso o cursos</label>';
            html += '<p class="small text-muted">Puede seleccionar más de un curso.</p>';
            html += '<p class="small text-secondary">⚠️ Si el curso que busca no aparece en la lista, le recomendamos escribir a <a href="mailto:clubdeportivo@sanjosevegas.edu.co">clubdeportivo@sanjosevegas.edu.co</a> para validar disponibilidad.</p>';
            html += '<div id="listaCursos" class="border rounded p-3"><p class="text-muted">Seleccione mes, sede, línea y actividad para cargar los cursos.</p></div></div>';

            camposDinamicos.innerHTML = html;

            camposDinamicos.querySelectorAll('.filtro-curso').forEach(el => {
                el.addEventListener('change', cargarCursosTipo1);
            });
            cargarCursosActivosParticipante();
            cargarCursosTipo1();
        }).catch(() => {
            camposDinamicos.innerHTML = '<p class="text-danger">Error al cargar los filtros.</p>';
        });
    }

    function cargarCursosActivosParticipante() {
        const cont = $('#cursosActivosParticipante');
        const lista = $('#listaCursosActivos');
        if (!participanteActual || !cont || !lista) return;
        ajax('get-cursos-activos-participante.php', 'GET', {
            participante_id: participanteActual.documento || participanteActual.id,
            anio: new Date().getFullYear()
        }).then(res => {
            if (res.success && res.cursos && res.cursos.length) {
                cont.style.display = 'block';
                lista.innerHTML = res.cursos.map(c => `<div class="cursos-activos-item"><span class="fw-semibold">${escapeHtml(c.nombre)}</span> <span class="cursos-activos-meta">${escapeHtml(c.mes_nombre || c.mes || '')}${c.sede ? ' · ' + escapeHtml(c.sede) : ''}</span></div>`).join('');
            } else {
                cont.style.display = 'none';
            }
        }).catch(() => { cont.style.display = 'none'; });
    }

    function cargarCursosTipo1() {
        const mes = $('#filtroMes')?.value;
        const sede = $('#filtroSede')?.value;
        const linea = $('#filtroLinea')?.value;
        const actividad = $('#filtroActividad')?.value;
        const lista = $('#listaCursos');

        if (!lista) return;
        if (!mes || !sede || !linea || !actividad) {
            lista.innerHTML = '<p class="text-muted">Seleccione mes, sede, línea y actividad para cargar los cursos.</p>';
            btnEnviar.disabled = true;
            return;
        }

        lista.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><p class="mt-2 mb-0 text-muted small">Cargando cursos...</p></div>';
        const params = { tipo_id: 1, mes, sede, linea, actividad, anio: new Date().getFullYear() };
        if (participanteActual) params.participante_id = participanteActual.documento || participanteActual.id;
        ajax('get-cursos.php', 'GET', params)
            .then(res => {
                if (!res.success || !res.cursos.length) {
                    lista.innerHTML = '<p class="text-muted">No hay cursos disponibles con los filtros seleccionados.</p>';
                    btnEnviar.disabled = true;
                    return;
                }
                let html = '';
                res.cursos.forEach((c, i) => {
                    const nomCurso = c.nombre_solo ?? c.nombre_curso ?? c.nombre ?? c.id;
                    const idSafe = escapeHtml(String(c.id).replace(/[^a-zA-Z0-9]/g, '_'));
                    const borderClass = i < res.cursos.length - 1 ? ' border-bottom pb-2 mb-2' : '';
                    html += `<div class="form-check${borderClass}"><input class="form-check-input curso-check" type="checkbox" name="curso_ids[]" value="${escapeHtml(c.id)}" data-nombre="${escapeHtml(nomCurso)}" id="curso${idSafe}"><label class="form-check-label" for="curso${idSafe}">${c.nombre}</label></div>`;
                });
                lista.innerHTML = html;
                const validadorCurso = document.getElementById('cursoCheckValidador');
                function actualizarValidadorCurso() {
                    const chk = camposDinamicos.querySelectorAll('input[name="curso_ids[]"]:checked');
                    if (validadorCurso) {
                        validadorCurso.value = chk.length ? '1' : '';
                        validadorCurso.setCustomValidity(chk.length ? '' : 'Seleccione al menos un curso');
                    }
                }
                camposDinamicos.querySelectorAll('.curso-check').forEach(cb => cb.addEventListener('change', actualizarValidadorCurso));
                actualizarValidadorCurso();
                btnEnviar.disabled = false;
            })
            .catch(() => {
                lista.innerHTML = '<p class="text-danger">Error al cargar cursos.</p>';
                btnEnviar.disabled = true;
            });
    }

    function cargarSalidas() {
        const tipoId = 5;
        ajax('get-salidas.php')
            .then(res => {
                if (!res.success || !res.salidas.length) {
                    camposDinamicos.innerHTML = '<p class="text-muted">No hay salidas disponibles.</p>';
                    return;
                }
                const cfg = tiposConfig[tipoId] || {};
                let html = '<div class="mb-3"><label class="form-label fw-bold">Seleccione la salida</label><select class="form-select select-con-detalle" name="salida_id" data-tipo="' + tipoId + '" required><option value="">-- Seleccione --</option>';
                res.salidas.forEach(s => {
                    const fd = s.fecha_display || (s.fecha ? ` (${s.fecha})` : '');
                    const fechas = fd ? ` (${fd})` : '';
                    const nombreLimpio = s.nombre_solo ?? s.nombre_curso ?? s.nombre ?? '';
                    html += `<option value="${s.id}" data-nombre="${escapeHtml(nombreLimpio)}">${escapeHtml(s.nombre)}${fechas}</option>`;
                });
                html += '</select></div>';
                if (cfg.tieneTemplate) html += '<div class="detalle-template-contenedor"></div>';
                camposDinamicos.innerHTML = html;
                if (cfg.tieneTemplate) {
                    const sel = camposDinamicos.querySelector('.select-con-detalle');
                    const cont = camposDinamicos.querySelector('.detalle-template-contenedor');
                    if (sel && cont) sel.addEventListener('change', () => cargarDetalleTemplate(tipoId, sel.value, cont));
                }
                btnEnviar.disabled = false;
            })
            .catch(() => {
                camposDinamicos.innerHTML = '<p class="text-danger">Error al cargar salidas.</p>';
            });
    }

    function cargarDetalleTemplate(tipoId, itemId, contenedor) {
        if (!contenedor) return;
        contenedor.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><p class="mt-1 mb-0 text-muted small">Cargando...</p></div>';
        if (!itemId) {
            contenedor.innerHTML = '';
            return;
        }
        ajax('get-detalle-template.php', 'GET', { tipo_id: tipoId, item_id: itemId })
            .then(res => {
                if (res.success && res.html) contenedor.innerHTML = res.html;
            })
            .catch(() => {});
    }

    function cargarCampamentos() {
        const tipoId = 2;
        const cfg = tiposConfig[tipoId] || {};
        ajax('get-campamentos.php', 'GET', { tipo_id: tipoId })
            .then(res => {
                if (!res.success || !res.campamentos.length) {
                    camposDinamicos.innerHTML = '<p class="text-muted">No hay campamentos disponibles.</p>';
                    return;
                }
                let html = '<div class="mb-3"><label class="form-label fw-bold">Seleccione campamento</label><select class="form-select select-con-detalle" name="campamento_id" data-tipo="' + tipoId + '" required><option value="">-- Seleccione --</option>';
                res.campamentos.forEach(c => {
                    const fd = c.fecha_display || '';
                    const fechas = fd ? ` (${fd})` : '';
                    const nombreLimpio = c.nombre_solo ?? c.nombre_curso ?? c.nombre ?? '';
                    html += `<option value="${c.id}" data-nombre="${escapeHtml(nombreLimpio)}">${escapeHtml(c.nombre)}${fechas}</option>`;
                });
                html += '</select></div>';
                if (cfg.tieneTemplate) html += '<div class="detalle-template-contenedor"></div>';
                html += '<div id="participantesAdicionalesContenedor"></div>';
                camposDinamicos.innerHTML = html;
                if (cfg.tieneTemplate) {
                    const sel = camposDinamicos.querySelector('.select-con-detalle');
                    const cont = camposDinamicos.querySelector('.detalle-template-contenedor');
                    if (sel && cont) {
                        sel.addEventListener('change', () => {
                            cargarDetalleTemplate(tipoId, sel.value, cont);
                            cargarParticipantesAdicionales(tipoId, sel.value, camposDinamicos);
                        });
                        cargarParticipantesAdicionales(tipoId, sel.value, camposDinamicos);
                    }
                }
                btnEnviar.disabled = false;
            })
            .catch(() => {
                camposDinamicos.innerHTML = '<p class="text-danger">Error al cargar campamentos.</p>';
            });
    }

    function cargarParticipantesAdicionales(tipoId, cursoId, contenedor) {
        const wrap = contenedor?.querySelector('#participantesAdicionalesContenedor');
        if (!wrap) return;
        wrap.innerHTML = '';
        if (!cursoId) return;
        ajax('get-participantes-adicionales-config.php', 'GET', { tipo_id: tipoId, curso_id: cursoId })
            .then(res => {
                const cfg = res.config;
                if (!cfg || !cfg.max || !cfg.fields?.length) return;
                const max = Math.min(parseInt(cfg.max, 10) || 0, 5);
                const fields = cfg.fields || ['documento', 'nombre', 'fechanacimiento', 'celular', 'email'];
                const labels = cfg.labels || {};
                const label = (f) => labels[f] || f;
                let html = '<div class="card mt-3"><div class="card-header"><h6 class="mb-0">' + escapeHtml(cfg.label || 'Participantes adicionales') + '</h6></div><div class="card-body">';
                for (let i = 0; i < max; i++) {
                    const n = i + 1;
                    html += '<div class="participante-adicional-item border rounded p-3 mb-3" data-index="' + n + '">';
                    html += '<h6 class="small fw-bold text-secondary mb-2">' + (cfg.label || 'Participante') + ' ' + n + '</h6>';
                    html += '<div class="row g-2">';
                    const primerRequerido = (i === 0);
                    fields.forEach(f => {
                        const id = 'part_adj_' + n + '_' + f;
                        const l = label(f);
                        const req = primerRequerido ? ' required' : '';
                        if (f === 'fechanacimiento') {
                            html += '<div class="col-md-6"><label class="form-label small">' + escapeHtml(l) + '</label><input type="date" class="form-control form-control-sm" name="participantes_adicionales[' + i + '][' + f + ']" id="' + id + '"' + req + '></div>';
                        } else if (f === 'email') {
                            html += '<div class="col-md-6"><label class="form-label small">' + escapeHtml(l) + '</label><input type="email" class="form-control form-control-sm" name="participantes_adicionales[' + i + '][' + f + ']" id="' + id + '"' + req + '></div>';
                        } else {
                            html += '<div class="col-md-6"><label class="form-label small">' + escapeHtml(l) + '</label><input type="text" class="form-control form-control-sm" name="participantes_adicionales[' + i + '][' + f + ']" id="' + id + '"' + req + '></div>';
                        }
                    });
                    html += '</div></div>';
                }
                html += '</div></div>';
                wrap.innerHTML = html;
            })
            .catch(() => {});
    }

    function escapeHtml(s) {
        if (s == null) return '';
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function mostrarModalExito(data, participante, responsable, res) {
        const cont = document.getElementById('modalExitoContenido');
        if (!cont) return;
        const tipo = parseInt(data.tipo_id, 10);
        let tipoTexto = '';
        if (tipo === 1) tipoTexto = 'Curso(s)';
        else if (tipo === 2) tipoTexto = 'Campamento';
        else if (tipo === 5) tipoTexto = 'Salida';
        else tipoTexto = 'Inscripción';
        let detalleHtml = '';
        if (tipo === 1 && data.nombres_curso && data.nombres_curso.length) {
            detalleHtml = '<ul class="mb-0">' + data.nombres_curso.map(n => `<li>${escapeHtml(n)}</li>`).join('') + '</ul>';
        } else if (data.nombreCurso) {
            detalleHtml = escapeHtml(data.nombreCurso);
        }
        let html = '<div class="row"><div class="col-md-6"><h6 class="fw-bold">Participante</h6>';
        html += '<p class="mb-1">' + escapeHtml(participante?.nombre || participante?.Nombre_Completo || data.participante_id || '') + '</p>';
        html += '<p class="mb-0 small text-muted">Doc: ' + escapeHtml(participante?.documento || data.participante_id || '') + '</p></div>';
        html += '<div class="col-md-6"><h6 class="fw-bold">Responsable</h6>';
        html += '<p class="mb-1">' + escapeHtml(responsable?.nombre || responsable?.Nombre_Completo || data.responsable_id || '') + '</p>';
        html += '<p class="mb-0 small text-muted">Doc: ' + escapeHtml(responsable?.documento || data.responsable_id || '') + '</p></div></div>';
        html += '<hr><h6 class="fw-bold">' + tipoTexto + '</h6>' + (detalleHtml || '-');
        if (data.Transporte && data.Transporte === 'Sí') {
            html += '<p class="mt-2 mb-0"><span class="badge transporte-badge">Transporte: Sí</span></p>';
        }
        html += '<p class="mt-3 mb-0 p-3 rounded bg-light border-start border-3 border-primary">Te hemos enviado un correo de confirmación. En los próximos días te estaremos enviando más información.</p>';
        cont.innerHTML = html;
        const modalEl = document.getElementById('modalExito');
        const modal = new bootstrap.Modal(modalEl);
        modalEl.addEventListener('shown.bs.modal', function onShown() {
            modalEl.removeEventListener('shown.bs.modal', onShown);
            const btn = modalEl.querySelector('.btn-primary[data-bs-dismiss="modal"]');
            if (btn) btn.focus();
        }, { once: true });
        modal.show();
    }

    // --- Enviar inscripción ---
    formInscripcion.addEventListener('submit', function (e) {
        e.preventDefault();
        docParticipante.setCustomValidity('');
        docResponsable.setCustomValidity('');
        if (!checkPoliticas.checked) {
            checkPoliticas.focus();
            formInscripcion.reportValidity();
            return;
        }
        if (!participanteActual) {
            docParticipante.setCustomValidity('Debe validar el documento del participante');
            docParticipante.reportValidity();
            return;
        }
        if (!responsableActual) {
            docResponsable.setCustomValidity('Debe validar el documento del responsable');
            docResponsable.reportValidity();
            return;
        }
        const tipo = parseInt(tipoInscripcion.value, 10);
        if (!tipo) {
            tipoInscripcion.focus();
            formInscripcion.reportValidity();
            return;
        }

        const anio = new Date().getFullYear();
        const formData = new FormData(formInscripcion);
        const data = {
            participante_id: participanteActual.id,
            responsable_id: responsableActual.id,
            tipo_id: tipo,
            fecha_inscripcion: new Date().toISOString().slice(0, 10),
            año: anio,
            Politicas: 'Si',
            Estado: 'ACTIVO',
            Transporte: tipo === 1 ? ($('#transporte')?.value || '') : ($('#transporte')?.value || 'No'),
        };
        formData.forEach((v, k) => {
            if (k !== 'docParticipante' && k !== 'docResponsable' && k !== 'tipoInscripcion' && !k.startsWith('curso_ids') && !k.startsWith('participantes_adicionales') && k !== 'mes' && k !== 'sede' && k !== 'linea' && k !== 'actividad' && k !== 'transporte') {
                data[k] = v;
            }
        });
        const partAdj = [];
        formData.forEach((v, k) => {
            const m = k.match(/^participantes_adicionales\[(\d+)\]\[(\w+)\]$/);
            if (m) {
                const i = parseInt(m[1], 10);
                if (!partAdj[i]) partAdj[i] = {};
                partAdj[i][m[2]] = v;
            }
        });
        if (partAdj.length) data.participantes_adicionales = partAdj.filter(Boolean);

        if (tipo === 1) {
            const transporte = $('#transporte');
            if (transporte && !transporte.value) {
                transporte.focus();
                formInscripcion.reportValidity();
                return;
            }
            const mes = $('#filtroMes')?.value;
            const sede = $('#filtroSede')?.value;
            const checks = camposDinamicos.querySelectorAll('input[name="curso_ids[]"]:checked');
            if (!checks.length) {
                const listaCursos = $('#listaCursos');
                if (listaCursos) listaCursos.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const validadorCurso = document.getElementById('cursoCheckValidador');
                if (validadorCurso) validadorCurso.setCustomValidity('Seleccione al menos un curso');
                formInscripcion.reportValidity();
                if (validadorCurso) validadorCurso.setCustomValidity('');
                return;
            }
            data.curso_ids = [...checks].map(c => c.value);
            data.nombres_curso = [...checks].map(c => c.getAttribute('data-nombre') || c.value);
            data.Sede = sede;
            data.Mes = mes;
            const anioCorto = anio % 100;
            data.Periodo = mes + String(anioCorto).padStart(2, '0');
        } else {
            const cfg = tiposConfig[tipo] || {};
            const sel = camposDinamicos.querySelector('select[required]');
            const hiddenCurso = camposDinamicos.querySelector('input[name="curso_id"], input[name="campamento_id"], input[name="salida_id"]');
            const hiddenNombre = camposDinamicos.querySelector('input[name="nombreCurso"]');
            if (cfg.hasSelector === false && hiddenCurso && hiddenNombre) {
                data.IDCurso = hiddenCurso.value || '';
                data.nombreCurso = hiddenNombre.value || '';
                data.curso_id = hiddenCurso.value || '';
            } else if (sel) {
                data.IDCurso = sel.value || '';
                data.nombreCurso = sel.selectedOptions[0]?.getAttribute('data-nombre') || sel.selectedOptions[0]?.text || '';
                if (sel.name === 'campamento_id') data.campamento_id = sel.value;
                if (sel.name === 'salida_id') data.salida_id = sel.value;
                if (sel.name && sel.name !== 'campamento_id' && sel.name !== 'salida_id') data[sel.name] = sel.value;
            }
            if (!data.IDCurso) {
                if (sel) { sel?.focus(); sel?.reportValidity(); }
                return;
            }
        }

        btnEnviar.disabled = true;
        const btnTxt = btnEnviar.querySelector('.btn-text');
        const btnSpinner = btnEnviar.querySelector('.spinner-border');
        if (btnTxt) btnTxt.classList.add('d-none');
        if (btnSpinner) btnSpinner.classList.remove('d-none');

        fetch(basePath + 'ajax/guardar-inscripcion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', ...getAuthHeaders() },
            body: JSON.stringify(data)
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    mostrarModalExito(data, participanteActual, responsableActual, res);
                    reiniciarFormulario();
                } else {
                    alert(res.error || 'Error al guardar inscripción.');
                }
            })
            .catch(() => alert('Error de conexión'))
            .finally(() => {
                btnEnviar.disabled = false;
                const txt = btnEnviar.querySelector('.btn-text');
                const sp = btnEnviar.querySelector('.spinner-border');
                if (txt) txt.classList.remove('d-none');
                if (sp) sp.classList.add('d-none');
            });
    });

    function reiniciarFormulario() {
        formInscripcion.reset();
        checkPoliticas.checked = false;
        camposFormulario.style.display = 'none';
        participanteActual = null;
        responsableActual = null;
        participanteInfo.style.display = 'none';
        responsableInfo.style.display = 'none';
        docParticipante.classList.remove('is-valid', 'is-invalid');
        docParticipante.value = '';
        docParticipante.setCustomValidity('');
        docResponsable.classList.remove('is-valid', 'is-invalid');
        docResponsable.value = '';
        docResponsable.placeholder = 'Ingrese documento y presione Validar';
        docResponsable.setCustomValidity('');
        docResponsable.disabled = true;
        tipoInscripcion.disabled = true;
        tipoInscripcion.value = '';
        camposDinamicos.innerHTML = '';
        btnEnviar.disabled = true;
        if (cardTipoInscripcion) cardTipoInscripcion.style.display = 'none';
        if (divBotones) divBotones.style.display = 'none';
        const cardDatosAdicionales = $('#cardDatosAdicionales');
        if (cardDatosAdicionales) {
            cardDatosAdicionales.style.display = 'none';
            cardDatosAdicionales.querySelectorAll('.select-si-no-text').forEach(s => { s.value = ''; });
            cardDatosAdicionales.querySelectorAll('select[name="autorizo_imagen"]').forEach(s => { s.value = ''; });
            cardDatosAdicionales.querySelectorAll('.wrap-texto-si-no').forEach(w => { w.style.display = 'none'; });
            cardDatosAdicionales.querySelectorAll('.wrap-texto-si-no textarea').forEach(t => {
                t.value = ''; t.disabled = true; t.required = false;
            });
        }
    }
})();
