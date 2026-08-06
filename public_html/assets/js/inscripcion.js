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
    let actividadesTipo1 = [];

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

    function refreshRequiredAsterisks(root = document) {
        root.querySelectorAll('.required-asterisk').forEach(el => el.remove());
        const requiredFields = root.querySelectorAll('input[required], select[required], textarea[required]');
        requiredFields.forEach(field => {
            let label = null;
            if (field.id) {
                label = root.querySelector(`label[for="${field.id}"]`) || document.querySelector(`label[for="${field.id}"]`);
            }
            if (!label) {
                label = field.closest('.mb-3, .col-md-6, .col-md-12, .row')?.querySelector('label.form-label, label');
            }
            if (!label || label.querySelector('.required-asterisk')) return;
            const star = document.createElement('span');
            star.className = 'required-asterisk text-danger ms-1';
            star.textContent = '*';
            label.appendChild(star);
        });
    }

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
    refreshRequiredAsterisks(document);

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

        const necesitaSpinner = tipo > 0 && (tipo === 1 || tipo === 2 || tipo === 4 || tipo === 5 || tipo === 18 || tipo === 19 || tipo === 20 || cfg.hasSelector !== false);
        if (necesitaSpinner) {
            camposDinamicos.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted small">Cargando...</p></div>';
        }
        if (tipo === 1) cargarCamposTipo1();
        else if (tipo === 2) cargarCampamentos();
        else if (tipo === 5) cargarSalidas();
        else if (tipo === 4) cargarLevelUp();
        else if (tipo === 18) cargarEventosTipo18();
        else if (tipo === 19) cargarArquitectosCerebros();
        else if (tipo === 20) cargarCopaVegas();
        else if (cfg.hasSelector === false) cargarTipoDirecto(tipo);
        else cargarCamposPorTipo(tipo);
        actualizarVisibilidadCamposAdicionales(tipo, '');
        refreshRequiredAsterisks(document);
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
        refreshRequiredAsterisks(document);
    });

    function resetCampoDatoAdicional(item) {
        if (!item) return;
        item.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.tagName === 'SELECT') el.value = '';
            else if (el.type === 'checkbox' || el.type === 'radio') el.checked = false;
            else el.value = '';
        });
        item.querySelectorAll('.wrap-texto-si-no').forEach(w => { w.style.display = 'none'; });
        item.querySelectorAll('.wrap-texto-si-no textarea').forEach(t => {
            t.value = '';
            t.disabled = true;
            t.required = false;
        });
    }

    function actualizarVisibilidadCamposAdicionales(tipoId, cursoId = '') {
        const card = document.getElementById('cardDatosAdicionales');
        if (!card) return;
        const tipo = String(tipoId || '');
        const curso = String(cursoId || '');
        card.querySelectorAll('.campo-dato-adicional').forEach(item => {
            const tiposRaw = item.getAttribute('data-show-only-tipos') || '';
            const cursosRaw = item.getAttribute('data-show-only-cursos') || '';
            const tipos = tiposRaw.split(',').map(v => v.trim()).filter(Boolean);
            const cursos = cursosRaw.split(',').map(v => v.trim()).filter(Boolean);
            const matchTipo = !tipos.length || tipos.includes(tipo);
            const matchCurso = !cursos.length || cursos.includes(curso);
            const visible = matchTipo && matchCurso;
            item.style.display = visible ? '' : 'none';
            if (!visible) resetCampoDatoAdicional(item);
        });
    }

    function cargarTipoDirecto(tipo) {
        const cfg = tiposConfig[tipo] || {};
        ajax('get-cursos-por-tipo.php', 'GET', { tipo_id: tipo })
            .then(res => {
                if (!res.success || !res.items || !res.items.length) {
                    camposDinamicos.innerHTML = '<p class="text-muted">No hay opciones disponibles para este tipo.</p>';
                    return;
                }
                const defaultItemId = String(cfg.defaultItemId || '');
                const item = res.items.find(it => String(it.id) === defaultItemId) || res.items[0];
                const selectorName = cfg.selectorName || 'curso_id';
                let html = '<input type="hidden" name="' + escapeHtml(selectorName) + '" value="' + escapeHtml(item.id) + '">';
                html += '<input type="hidden" name="nombreCurso" value="' + escapeHtml(item.nombre_curso || item.nombre || item.nombre_display || '') + '">';
                if (cfg.tieneTemplate) html += '<div class="detalle-template-contenedor"></div>';
                html += '<div id="participantesAdicionalesContenedor"></div>';
                camposDinamicos.innerHTML = html;
                if (cfg.tieneTemplate) {
                    const cont = camposDinamicos.querySelector('.detalle-template-contenedor');
                    if (cont) cargarDetalleTemplate(tipo, item.id, cont);
                }
                cargarParticipantesAdicionales(tipo, item.id, camposDinamicos);
                actualizarVisibilidadCamposAdicionales(tipo, item.id);
                refreshRequiredAsterisks(document);
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
                html += '<div id="participantesAdicionalesContenedor"></div>';
                camposDinamicos.innerHTML = html;
                if (cfg.tieneTemplate) {
                    const sel = camposDinamicos.querySelector('.select-con-detalle');
                    const cont = camposDinamicos.querySelector('.detalle-template-contenedor');
                    if (sel && cont) {
                        sel.addEventListener('change', () => {
                            cargarDetalleTemplate(tipo, sel.value, cont);
                            cargarParticipantesAdicionales(tipo, sel.value, camposDinamicos);
                            actualizarVisibilidadCamposAdicionales(tipo, sel.value || '');
                        });
                        cargarParticipantesAdicionales(tipo, sel.value, camposDinamicos);
                        actualizarVisibilidadCamposAdicionales(tipo, sel.value || '');
                    }
                }
                refreshRequiredAsterisks(document);
                btnEnviar.disabled = false;
            })
            .catch(() => {
                camposDinamicos.innerHTML = '<p class="text-danger">Error al cargar opciones.</p>';
            });
    }

    let levelupConfigCache = null;
    let levelupAsignaturasCache = [];
    let levelupAsignaturasNuevas = [];

    function resetLevelUpAsignaturasSeleccion() {
        levelupAsignaturasNuevas = [];
        const lista = $('#levelupAsignaturasAgregadas');
        if (lista) lista.innerHTML = '';
        camposDinamicos.querySelectorAll('input[name="asignatura_ids[]"]').forEach((cb) => { cb.checked = false; });
        onLevelUpModalidadChange();
    }

    function getLevelUpNivelActual() {
        return parseInt($('#levelupNivel')?.value || '0', 10);
    }

    function getLevelUpModalidadActual() {
        const nivel = getLevelUpNivelActual();
        if (nivel === 2) return 'Individual';
        return ($('#levelupModalidad')?.value || '').trim();
    }

    function esLevelUpSeleccionUnica() {
        return getLevelUpNivelActual() === 1 && getLevelUpModalidadActual() === 'Grupal';
    }

    function contarAsignaturasLevelup() {
        const checked = camposDinamicos.querySelectorAll('input[name="asignatura_ids[]"]:checked').length;
        return checked + levelupAsignaturasNuevas.length;
    }

    function onAsignaturaCheckboxLevelupChange(e) {
        if (!esLevelUpSeleccionUnica() || !e.target?.checked) return;
        camposDinamicos.querySelectorAll('input[name="asignatura_ids[]"]').forEach((cb) => {
            if (cb !== e.target) cb.checked = false;
        });
        if (levelupAsignaturasNuevas.length) {
            levelupAsignaturasNuevas = [];
            const lista = $('#levelupAsignaturasAgregadas');
            if (lista) lista.innerHTML = '';
        }
        onLevelUpModalidadChange();
    }

    function onLevelUpModalidadChange() {
        const unica = esLevelUpSeleccionUnica();
        const ayuda = $('#levelupAyudaAsignaturas');
        const wrapAdd = $('#wrapLevelupAgregarAsignatura');
        if (ayuda) {
            ayuda.textContent = unica
                ? 'En modalidad grupal solo puede seleccionar una asignatura.'
                : 'Puede seleccionar una o varias asignaturas.';
        }
        if (wrapAdd) {
            wrapAdd.style.display = unica && contarAsignaturasLevelup() >= 1 ? 'none' : '';
        }
        if (!unica) return;

        const checks = [...camposDinamicos.querySelectorAll('input[name="asignatura_ids[]"]:checked')];
        if (checks.length > 1) {
            checks.slice(1).forEach((cb) => { cb.checked = false; });
        }
        if (levelupAsignaturasNuevas.length > 1) {
            levelupAsignaturasNuevas = [levelupAsignaturasNuevas[0]];
            const lista = $('#levelupAsignaturasAgregadas');
            if (lista) {
                lista.innerHTML = '';
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.dataset.nombre = levelupAsignaturasNuevas[0];
                li.innerHTML = `<span>${escapeHtml(levelupAsignaturasNuevas[0])}</span><button type="button" class="btn btn-sm btn-outline-danger" data-action="quitar-asig-nueva">Quitar</button>`;
                li.querySelector('[data-action="quitar-asig-nueva"]').addEventListener('click', () => {
                    levelupAsignaturasNuevas = [];
                    li.remove();
                    onLevelUpModalidadChange();
                });
                lista.appendChild(li);
            }
        }
        if (contarAsignaturasLevelup() > 1) {
            const firstCheck = checks[0];
            if (firstCheck) {
                camposDinamicos.querySelectorAll('input[name="asignatura_ids[]"]').forEach((cb) => {
                    if (cb !== firstCheck) cb.checked = false;
                });
                levelupAsignaturasNuevas = [];
                const lista = $('#levelupAsignaturasAgregadas');
                if (lista) lista.innerHTML = '';
            } else if (levelupAsignaturasNuevas.length) {
                levelupAsignaturasNuevas = [levelupAsignaturasNuevas[0]];
            }
        }
    }

    function renderAsignaturasLevelup() {
        const wrap = $('#wrapLevelupAsignaturas');
        const lista = $('#levelupListaAsignaturas');
        if (!wrap || !lista) return;

        let html = '';
        if (levelupAsignaturasCache.length) {
            levelupAsignaturasCache.forEach((a) => {
                html += `<div class="col-md-6 col-lg-4"><div class="form-check border rounded p-2 h-100">`;
                html += `<input class="form-check-input" type="checkbox" name="asignatura_ids[]" value="${escapeHtml(a.id)}" id="asig_${escapeHtml(a.id)}">`;
                html += `<label class="form-check-label" for="asig_${escapeHtml(a.id)}">${escapeHtml(a.nombre)}</label>`;
                html += `</div></div>`;
            });
        } else {
            html = '<p class="text-muted small col-12">No hay asignaturas registradas. Agregue al menos una abajo.</p>';
        }
        lista.innerHTML = html;
        lista.querySelectorAll('input[name="asignatura_ids[]"]').forEach((cb) => {
            cb.addEventListener('change', onAsignaturaCheckboxLevelupChange);
        });
        wrap.style.display = '';
        resetLevelUpAsignaturasSeleccion();
        onLevelUpModalidadChange();
        refreshRequiredAsterisks(wrap);
    }

    function ocultarLevelUpDesdeNivel() {
        const wrapPost = $('#wrapLevelupPostNivel');
        const wrapMod = $('#wrapLevelupModalidad');
        const selMod = $('#levelupModalidad');
        const contTpl = $('#levelupTemplateContenedor');
        const wrapAsig = $('#wrapLevelupAsignaturas');
        const wrapGrado = $('#wrapLevelupGradoAcademico');
        const inpGrado = $('#levelupGradoAcademico');
        const wrapDiag = $('#wrapLevelupDiagnostico');
        const inpDiag = $('#levelupDiagnostico');
        const nota = $('#levelupNotaCostos');
        if (wrapPost) wrapPost.style.display = 'none';
        if (wrapMod) wrapMod.style.display = 'none';
        if (selMod) { selMod.required = false; selMod.value = ''; }
        if (contTpl) contTpl.innerHTML = '';
        if (wrapAsig) wrapAsig.style.display = 'none';
        if (wrapGrado) wrapGrado.style.display = 'none';
        if (inpGrado) { inpGrado.required = false; inpGrado.value = ''; }
        if (wrapDiag) wrapDiag.style.display = 'none';
        if (inpDiag) { inpDiag.required = false; inpDiag.value = ''; }
        if (nota) nota.style.display = 'none';
        if ($('#levelupCursoId')) $('#levelupCursoId').value = '';
        if ($('#levelupNombreCurso')) $('#levelupNombreCurso').value = '';
        resetLevelUpAsignaturasSeleccion();
    }

    function onLevelUpSedeChange() {
        const sede = ($('#levelupSede')?.value || '').trim();
        const wrapNivel = $('#wrapLevelupNivel');
        const nivelSel = $('#levelupNivel');
        if (!wrapNivel || !nivelSel) return;

        ocultarLevelUpDesdeNivel();

        if (!sede) {
            wrapNivel.style.display = 'none';
            nivelSel.disabled = true;
            nivelSel.required = false;
            nivelSel.innerHTML = '<option value="">-- Seleccione sede primero --</option>';
            nivelSel.value = '';
            return;
        }

        wrapNivel.style.display = '';
        nivelSel.disabled = false;
        nivelSel.required = true;
        nivelSel.innerHTML = '<option value="">-- Seleccione --</option>'
            + '<option value="1">Nivel 1 – Fortalecimiento de habilidades</option>'
            + '<option value="2">Nivel 2 – Ruta de acompañamiento especializada</option>';
        nivelSel.value = '';
        refreshRequiredAsterisks(camposDinamicos);
    }

    function actualizarLevelUpVista() {
        if (!levelupConfigCache) return;
        const sede = ($('#levelupSede')?.value || '').trim();
        const nivel = parseInt($('#levelupNivel')?.value || '0', 10);
        const wrapPost = $('#wrapLevelupPostNivel');
        const wrapMod = $('#wrapLevelupModalidad');
        const selMod = $('#levelupModalidad');
        const curso = levelupConfigCache.cursos_por_sede_nivel?.[sede]?.[nivel];
        const contTpl = $('#levelupTemplateContenedor');

        if (!sede || !nivel || !curso) {
            ocultarLevelUpDesdeNivel();
            return;
        }

        if (wrapPost) wrapPost.style.display = '';

        if ($('#levelupCursoId')) $('#levelupCursoId').value = curso.id;
        if ($('#levelupNombreCurso')) $('#levelupNombreCurso').value = curso.nombre || '';

        if (nivel === 1) {
            if (wrapMod) wrapMod.style.display = '';
            if (selMod) selMod.required = true;
        } else {
            if (wrapMod) wrapMod.style.display = 'none';
            if (selMod) { selMod.required = false; selMod.value = 'Individual'; }
        }

        const nota = $('#levelupNotaCostos');
        if (nota) nota.style.display = nivel === 1 ? '' : 'none';

        const wrapGrado = $('#wrapLevelupGradoAcademico');
        const inpGrado = $('#levelupGradoAcademico');
        if (wrapGrado) wrapGrado.style.display = '';
        if (inpGrado) inpGrado.required = true;

        const wrapAsig = $('#wrapLevelupAsignaturas');
        const wrapDiag = $('#wrapLevelupDiagnostico');
        const inpDiag = $('#levelupDiagnostico');
        if (nivel === 1) {
            if (wrapAsig) wrapAsig.style.display = '';
            renderAsignaturasLevelup();
            if (wrapDiag) wrapDiag.style.display = 'none';
            if (inpDiag) { inpDiag.required = false; inpDiag.value = ''; }
        } else {
            if (wrapAsig) wrapAsig.style.display = 'none';
            resetLevelUpAsignaturasSeleccion();
            if (wrapDiag) wrapDiag.style.display = '';
            if (inpDiag) inpDiag.required = true;
        }

        if (contTpl) cargarDetalleTemplate(4, curso.id, contTpl);
        onLevelUpModalidadChange();
        refreshRequiredAsterisks(camposDinamicos);
    }

    function cargarLevelUp() {
        levelupAsignaturasNuevas = [];
        levelupAsignaturasCache = [];
        Promise.all([
            ajax('get-levelup-config.php', 'GET'),
            ajax('get-asignaturas.php', 'GET'),
        ]).then(([cfgRes, asigRes]) => {
            if (!cfgRes.success) {
                camposDinamicos.innerHTML = '<p class="text-danger">No fue posible cargar la configuración de Level Up.</p>';
                return;
            }
            levelupConfigCache = cfgRes;
            levelupAsignaturasCache = (asigRes.success && Array.isArray(asigRes.items)) ? asigRes.items : [];
            const sedes = cfgRes.sedes || ['MEDELLÍN', 'RETIRO'];

            let html = '<div class="levelup-form">';
            html += '<div class="row g-3 mb-3">';
            html += '<div class="col-md-4"><label class="form-label fw-bold">Sede</label>';
            html += '<select class="form-select" id="levelupSede" name="levelup_sede" required><option value="">-- Seleccione --</option>';
            sedes.forEach(s => { html += `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`; });
            html += '</select></div>';

            html += '<div class="col-md-4" id="wrapLevelupNivel" style="display:none;">';
            html += '<label class="form-label fw-bold">Nivel</label>';
            html += '<select class="form-select" id="levelupNivel" name="levelup_nivel" disabled>';
            html += '<option value="">-- Seleccione sede primero --</option>';
            html += '</select></div>';
            html += '</div>';

            html += '<div id="wrapLevelupPostNivel" style="display:none;">';
            html += '<div class="row g-3 mb-3">';
            html += '<div class="col-md-4" id="wrapLevelupModalidad" style="display:none;">';
            html += '<label class="form-label fw-bold">Modalidad</label>';
            html += '<select class="form-select" id="levelupModalidad" name="levelup_modalidad">';
            html += '<option value="">-- Seleccione --</option>';
            html += '<option value="Individual">Individual</option>';
            html += '<option value="Grupal">Grupal</option>';
            html += '</select></div>';
            html += '</div>';

            html += '<input type="hidden" id="levelupCursoId" name="curso_id" value="">';
            html += '<input type="hidden" id="levelupNombreCurso" name="nombreCurso" value="">';

            html += '<div class="detalle-template-contenedor mb-3" id="levelupTemplateContenedor"></div>';

            html += '<div class="alert alert-warning border-warning small mb-3" id="levelupNotaCostos" style="display:none;" role="note">';
            html += '<strong>Nota importante:</strong> Los valores de referencia publicados corresponden a <strong>una asignatura</strong> por inscripción. ';
            html += 'En caso de requerir acompañamiento en varias asignaturas, se realizará el diseño de un <strong>plan personalizado</strong> de acuerdo con las necesidades del estudiante.';
            html += '</div>';

            html += '<div class="mb-3" id="wrapLevelupGradoAcademico" style="display:none;">';
            html += '<label class="form-label fw-bold" for="levelupGradoAcademico">Grado académico</label>';
            html += '<input type="text" class="form-control" id="levelupGradoAcademico" name="levelup_grado_academico" maxlength="50" placeholder="Ej: 5°, Transición, Prejardín">';
            html += '</div>';

            html += '<div id="wrapLevelupAsignaturas" style="display:none;" class="mb-3">';
            html += '<label class="form-label fw-bold">Asignaturas</label>';
            html += '<p class="small text-muted mb-2" id="levelupAyudaAsignaturas">Seleccione al menos una asignatura.</p>';
            html += '<div id="levelupListaAsignaturas" class="row g-2 mb-2"></div>';
            html += '<div class="input-group mb-2" id="wrapLevelupAgregarAsignatura">';
            html += '<input type="text" class="form-control" id="levelupNuevaAsignatura" placeholder="Otra asignatura (ej. Matemáticas)">';
            html += '<button type="button" class="btn btn-outline-primary" id="btnAgregarAsignaturaLevelup">Agregar</button>';
            html += '</div>';
            html += '<ul id="levelupAsignaturasAgregadas" class="list-group list-group-flush small mb-0"></ul>';
            html += '</div>';

            html += '<div class="mb-3" id="wrapLevelupDiagnostico" style="display:none;">';
            html += '<label class="form-label fw-bold" for="levelupDiagnostico">Diagnóstico</label>';
            html += '<textarea class="form-control" id="levelupDiagnostico" name="levelup_diagnostico" rows="3" maxlength="500" placeholder="Describa el diagnóstico o la necesidad de apoyo del estudiante"></textarea>';
            html += '</div></div></div>';

            camposDinamicos.innerHTML = html;

            $('#levelupSede')?.addEventListener('change', onLevelUpSedeChange);
            $('#levelupNivel')?.addEventListener('change', actualizarLevelUpVista);
            $('#levelupModalidad')?.addEventListener('change', onLevelUpModalidadChange);
            $('#btnAgregarAsignaturaLevelup')?.addEventListener('click', agregarAsignaturaLevelup);
            $('#levelupNuevaAsignatura')?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    agregarAsignaturaLevelup();
                }
            });

            refreshRequiredAsterisks(document);
            btnEnviar.disabled = false;
        }).catch(() => {
            camposDinamicos.innerHTML = '<p class="text-danger">Error al cargar Level Up.</p>';
        });
    }

    function agregarAsignaturaLevelup() {
        const input = $('#levelupNuevaAsignatura');
        const lista = $('#levelupAsignaturasAgregadas');
        if (!input || !lista) return;
        if (esLevelUpSeleccionUnica() && contarAsignaturasLevelup() >= 1) {
            alert('En modalidad grupal solo puede registrar una asignatura. Si necesita más de una, elija modalidad individual.');
            return;
        }
        const nombre = (input.value || '').trim();
        if (!nombre) {
            alert('Escriba el nombre de la asignatura a agregar.');
            return;
        }
        const dup = levelupAsignaturasNuevas.some(n => n.toLowerCase() === nombre.toLowerCase());
        const labels = camposDinamicos.querySelectorAll('#levelupListaAsignaturas .form-check-label');
        let yaEnLista = false;
        labels.forEach(lb => {
            if ((lb.textContent || '').trim().toLowerCase() === nombre.toLowerCase()) yaEnLista = true;
        });
        if (dup || yaEnLista) {
            alert('Esa asignatura ya está en la lista.');
            return;
        }
        levelupAsignaturasNuevas.push(nombre);
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center';
        li.dataset.nombre = nombre;
        li.innerHTML = `<span>${escapeHtml(nombre)}</span><button type="button" class="btn btn-sm btn-outline-danger" data-action="quitar-asig-nueva">Quitar</button>`;
        li.querySelector('[data-action="quitar-asig-nueva"]').addEventListener('click', () => {
            levelupAsignaturasNuevas = levelupAsignaturasNuevas.filter(n => n !== nombre);
            li.remove();
            onLevelUpModalidadChange();
        });
        lista.appendChild(li);
        input.value = '';
        if (esLevelUpSeleccionUnica()) {
            camposDinamicos.querySelectorAll('input[name="asignatura_ids[]"]').forEach((cb) => { cb.checked = false; });
        }
        onLevelUpModalidadChange();
    }

    function actualizarOpenKewmgangCombate() {
        const comb = $('#openkModCombate')?.checked;
        const wrap = $('#wrapOpenkCombate');
        if (!wrap) return;
        const mostrar = !!comb;
        wrap.style.display = mostrar ? '' : 'none';
        wrap.querySelectorAll('select, input').forEach((el) => {
            if (!mostrar) {
                el.required = false;
                if (el.tagName === 'SELECT') el.value = '';
                else el.value = '';
            }
        });
        ['openkRama', 'openkDivision', 'openkGrado'].forEach((id) => {
            const el = $('#' + id);
            if (el) el.required = mostrar;
        });
        if (mostrar) {
            actualizarOpenKewmgangMedicion();
        }
        refreshRequiredAsterisks(camposDinamicos);
    }

    function actualizarOpenKewmgangMedicion() {
        const division = $('#openkDivision')?.value || '';
        const wrapEst = $('#wrapOpenkEstatura');
        const wrapPeso = $('#wrapOpenkPeso');
        const inpEst = $('#openkEstatura');
        const inpPeso = $('#openkPeso');
        if (!wrapEst || !wrapPeso) return;
        const esJunior = division === 'Junior';
        wrapEst.style.display = esJunior ? 'none' : '';
        wrapPeso.style.display = esJunior ? '' : 'none';
        if (inpEst) {
            inpEst.required = !esJunior && !!division;
            if (esJunior) inpEst.value = '';
        }
        if (inpPeso) {
            inpPeso.required = esJunior;
            if (!esJunior) inpPeso.value = '';
        }
        refreshRequiredAsterisks(camposDinamicos);
    }

    const TIPO18_LABELS = {
        '1802': 'Open Kewmgang',
        '1803': 'Med Cheer Championships',
        '1804': 'Campeonato Nacional Inter clubes de Taekwondo',
        '1805': 'Copa Ciudad de Flores Gimnasia',
        '1806': 'Volleyball Fest',
        '1807': 'Baby Voleibol',
        '1808': 'The Big Show',
    };

    const TIPO18_CATEGORIAS = {
        '1803': ['Tiny Diamonds', 'Mini Diamonds', 'Youth Diamonds', 'Senior'],
        '1805': ['Pre nivel', 'Test de habilidades', 'Nivel 1', 'Nivel 2', 'Nivel 3'],
        '1806': ['Sub 14 (Premenores e Infantil)'],
        '1807': ['Mini', 'Infantil'],
        '1808': ['Tiny gold', 'Mini gold', 'Youth gold', 'Junior', 'Youth emerald retiro'],
    };

    const TIPO18_CATEGORIA_LABELS = {
        '1808': 'Seleccione la categoría de participación de la deportista',
    };

    const TKD_MODALIDADES = [
        { value: 'Festival infantil', label: 'Festival infantil ($120.000)' },
        { value: 'Combate individual', label: 'Combate individual ($150.000)' },
        { value: 'Poomsae', label: 'Poomsae ($150.000)' },
        { value: 'Combate y poomsae', label: 'Combate y poomsae ($230.000)' },
    ];
    const TKD_CATEGORIAS = [
        'Infantil (menores de 12 años)',
        'Precadete A (2015-2016)',
        'Precadete B (2017-2018)',
        'Cadetes (2012-2014)',
        'Junior (2009 a 2011)',
    ];
    const TKD_GRADOS = [
        'Blanco',
        'Blanco franja amarilla',
        'Amarillo',
        'Amarillo franja verde',
        'Verde',
        'Verde franja azul',
        'Azul',
        'Azul franja roja',
        'Rojo',
        'Rojo franja negra',
    ];

    function renderFormOpenKewmgang(cursoId, nombreCurso) {
        let html = '<div class="open-kewmgang-form" id="formEventoTipo18">';
        html += '<input type="hidden" id="t18CursoId" name="curso_id" value="' + escapeHtml(cursoId) + '">';
        html += '<input type="hidden" name="nombreCurso" value="' + escapeHtml(nombreCurso) + '">';
        html += '<div class="mb-3"><label class="form-label fw-bold d-block">Modalidad</label>';
        html += '<p class="small text-muted">Puede elegir una o dos modalidades. El valor es acumulable.</p>';
        html += '<div class="form-check mb-2">';
        html += '<input class="form-check-input" type="checkbox" name="openk_modalidades[]" value="Festival infantil" id="openkModFestival">';
        html += '<label class="form-check-label" for="openkModFestival">1. Festival infantil ($60.000) — 4 a 11 años, no importa el cinturón</label>';
        html += '</div>';
        html += '<div class="form-check mb-3">';
        html += '<input class="form-check-input" type="checkbox" name="openk_modalidades[]" value="Combate individual" id="openkModCombate">';
        html += '<label class="form-check-label" for="openkModCombate">2. Combate individual ($75.000) — Sistema convencional, solo blanco a verde</label>';
        html += '</div></div>';
        html += '<div id="wrapOpenkCombate" style="display:none;">';
        html += '<div class="row g-3 mb-3">';
        html += '<div class="col-md-4"><label class="form-label fw-bold">Rama</label>';
        html += '<select class="form-select" id="openkRama" name="openk_rama"><option value="">-- Seleccione --</option>';
        html += '<option value="Femenino">Femenino</option><option value="Masculino">Masculino</option></select></div>';
        html += '<div class="col-md-4"><label class="form-label fw-bold">División</label>';
        html += '<select class="form-select" id="openkDivision" name="openk_division"><option value="">-- Seleccione --</option>';
        html += '<option value="Benjamin">Benjamín (8 a 9 años)</option>';
        html += '<option value="Pre cadetes">Pre cadetes (10 a 11 años)</option>';
        html += '<option value="Cadetes">Cadetes (12 a 14 años)</option>';
        html += '<option value="Junior">Junior (15 a 17 años)</option></select></div>';
        html += '<div class="col-md-4"><label class="form-label fw-bold">Grado</label>';
        html += '<select class="form-select" id="openkGrado" name="openk_grado"><option value="">-- Seleccione --</option>';
        html += '<option value="Blancos">Blancos</option><option value="Amarillo">Amarillo</option><option value="Verde">Verde</option></select></div>';
        html += '</div>';
        html += '<div class="row g-3 mb-3">';
        html += '<div class="col-md-4" id="wrapOpenkEstatura"><label class="form-label fw-bold">Estatura (cm)</label>';
        html += '<input type="number" class="form-control" id="openkEstatura" name="openk_estatura" min="1" step="0.1" placeholder="Ej: 135"></div>';
        html += '<div class="col-md-4" id="wrapOpenkPeso" style="display:none;"><label class="form-label fw-bold">Peso (kg)</label>';
        html += '<input type="number" class="form-control" id="openkPeso" name="openk_peso" min="1" step="0.1" placeholder="Ej: 52"></div>';
        html += '</div></div></div>';
        return html;
    }

    function renderFormCategoriaOnly(cursoId, nombreCurso) {
        const cats = TIPO18_CATEGORIAS[cursoId] || [];
        const labelCat = TIPO18_CATEGORIA_LABELS[cursoId] || 'Categoría';
        let html = '<div class="evento-categoria-form" id="formEventoTipo18">';
        html += '<input type="hidden" id="t18CursoId" name="curso_id" value="' + escapeHtml(cursoId) + '">';
        html += '<input type="hidden" name="nombreCurso" value="' + escapeHtml(nombreCurso) + '">';
        html += '<div class="mb-3"><label class="form-label fw-bold">' + escapeHtml(labelCat) + '</label>';
        html += '<select class="form-select" id="eventoCategoria" name="evento_categoria" required>';
        html += '<option value="">-- Seleccione --</option>';
        cats.forEach((c) => {
            html += `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`;
        });
        html += '</select></div></div>';
        return html;
    }

    function renderFormTkdNacional(cursoId, nombreCurso) {
        let html = '<div class="tkd-nacional-form" id="formEventoTipo18">';
        html += '<input type="hidden" id="t18CursoId" name="curso_id" value="' + escapeHtml(cursoId) + '">';
        html += '<input type="hidden" name="nombreCurso" value="' + escapeHtml(nombreCurso) + '">';

        html += '<div class="mb-3"><label class="form-label fw-bold d-block">Modalidad</label>';
        html += '<p class="small text-muted">Puede seleccionar una o más modalidades.</p>';
        TKD_MODALIDADES.forEach((m, i) => {
            const id = 'tkdMod' + i;
            html += '<div class="form-check mb-2">';
            html += `<input class="form-check-input" type="checkbox" name="tkd_modalidades[]" value="${escapeHtml(m.value)}" id="${id}">`;
            html += `<label class="form-check-label" for="${id}">${escapeHtml(m.label)}</label>`;
            html += '</div>';
        });
        html += '</div>';

        html += '<div class="row g-3 mb-3">';
        html += '<div class="col-md-6"><label class="form-label fw-bold">Categoría</label>';
        html += '<select class="form-select" id="tkdCategoria" name="tkd_categoria" required><option value="">-- Seleccione --</option>';
        TKD_CATEGORIAS.forEach((c) => {
            html += `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`;
        });
        html += '</select></div>';
        html += '<div class="col-md-6"><label class="form-label fw-bold">Grado</label>';
        html += '<select class="form-select" id="tkdGrado" name="tkd_grado" required><option value="">-- Seleccione --</option>';
        TKD_GRADOS.forEach((g) => {
            html += `<option value="${escapeHtml(g)}">${escapeHtml(g)}</option>`;
        });
        html += '</select></div>';
        html += '</div>';

        html += '<div class="row g-3 mb-3">';
        html += '<div class="col-md-4"><label class="form-label fw-bold">Género</label>';
        html += '<select class="form-select" id="tkdGenero" name="tkd_genero" required><option value="">-- Seleccione --</option>';
        html += '<option value="Femenino">Femenino</option><option value="Masculino">Masculino</option></select></div>';
        html += '<div class="col-md-4"><label class="form-label fw-bold">Peso (kg)</label>';
        html += '<input type="number" class="form-control" id="tkdPeso" name="tkd_peso" min="1" step="0.1" placeholder="Ej: 45" required></div>';
        html += '<div class="col-md-4"><label class="form-label fw-bold">Estatura (cm)</label>';
        html += '<input type="number" class="form-control" id="tkdEstatura" name="tkd_estatura" min="1" step="0.1" placeholder="Ej: 150" required></div>';
        html += '</div></div>';
        return html;
    }

    function onEventoTipo18Change() {
        const sel = $('#t18EventoSelect');
        const wrapForm = $('#t18FormContenedor');
        const contTpl = $('#t18TemplateContenedor');
        if (!sel || !wrapForm) return;
        const cursoId = sel.value || '';
        const nombre = sel.selectedOptions[0]?.getAttribute('data-nombre') || sel.selectedOptions[0]?.text || '';
        wrapForm.innerHTML = '';
        if (contTpl) contTpl.innerHTML = '';
        if (!cursoId) {
            refreshRequiredAsterisks(camposDinamicos);
            return;
        }
        if (contTpl) cargarDetalleTemplate(18, cursoId, contTpl);
        if (cursoId === '1802') {
            wrapForm.innerHTML = renderFormOpenKewmgang(cursoId, nombre);
            $('#openkModFestival')?.addEventListener('change', actualizarOpenKewmgangCombate);
            $('#openkModCombate')?.addEventListener('change', actualizarOpenKewmgangCombate);
            $('#openkDivision')?.addEventListener('change', actualizarOpenKewmgangMedicion);
        } else if (cursoId === '1804') {
            wrapForm.innerHTML = renderFormTkdNacional(cursoId, nombre);
        } else if (TIPO18_CATEGORIAS[cursoId]) {
            wrapForm.innerHTML = renderFormCategoriaOnly(cursoId, nombre);
        } else {
            wrapForm.innerHTML = '<p class="text-danger">Evento no configurado en el formulario.</p>';
        }
        refreshRequiredAsterisks(camposDinamicos);
    }

    function cargarEventosTipo18() {
        ajax('get-cursos-por-tipo.php', 'GET', { tipo_id: 18, contexto: 'principal' }).catch(() => ({ success: false }))
            .then((res) => {
                if (!res.success || !res.items?.length) {
                    camposDinamicos.innerHTML = '<p class="text-danger">No hay eventos disponibles en este momento.</p>';
                    return;
                }
                let html = '<div class="eventos-tipo18-form">';
                html += '<div class="mb-3"><label class="form-label fw-bold">Seleccione el evento</label>';
                html += '<select class="form-select" id="t18EventoSelect" required>';
                html += '<option value="">-- Seleccione --</option>';
                res.items.forEach((it) => {
                    const id = String(it.id);
                    const label = TIPO18_LABELS[id] || it.nombre_display || it.nombre || id;
                    const nombre = it.nombre || it.nombre_curso || label;
                    const fecha = it.fecha_display ? ` (${it.fecha_display})` : '';
                    html += `<option value="${escapeHtml(id)}" data-nombre="${escapeHtml(nombre)}">${escapeHtml(label)}${escapeHtml(fecha)}</option>`;
                });
                html += '</select></div>';
                html += '<div class="detalle-template-contenedor mb-3" id="t18TemplateContenedor"></div>';
                html += '<div id="t18FormContenedor"></div></div>';
                camposDinamicos.innerHTML = html;
                $('#t18EventoSelect')?.addEventListener('change', onEventoTipo18Change);
                refreshRequiredAsterisks(document);
                btnEnviar.disabled = false;
            })
            .catch(() => {
                camposDinamicos.innerHTML = '<p class="text-danger">Error al cargar los eventos.</p>';
            });
    }

    const AC_ROLES = [
        'Madre',
        'Padre',
        'Cuidador(a)',
        'Docente',
        'Profesional psicosocial',
        'Agente educativo',
        'Otro',
    ];

    const AC_CURSOS_LABEL = {
        '4901': 'Arquitectos de Cerebros I (0 a 10 años)',
        '4902': 'Arquitectos de Cerebros II (11 a 18 años)',
    };

    function actualizarAcFamiliaSjv() {
        const val = $('#acFamiliaSjv')?.value || '';
        const wrap = $('#wrapAcOrganizacion');
        const inp = $('#acOrganizacion');
        if (!wrap || !inp) return;
        const mostrar = val === 'No';
        wrap.style.display = mostrar ? '' : 'none';
        inp.required = mostrar;
        if (!mostrar) inp.value = '';
        refreshRequiredAsterisks(camposDinamicos);
    }

    function actualizarAcRol() {
        const val = $('#acRol')?.value || '';
        const wrap = $('#wrapAcRolOtro');
        const inp = $('#acRolOtro');
        if (!wrap || !inp) return;
        const mostrar = val === 'Otro';
        wrap.style.display = mostrar ? '' : 'none';
        inp.required = mostrar;
        if (!mostrar) inp.value = '';
        refreshRequiredAsterisks(camposDinamicos);
    }

    function actualizarAcCursoSeleccionado() {
        const sel = camposDinamicos.querySelector('input[name="ac_curso_id"]:checked');
        const cursoId = sel?.value || '';
        const nombre = sel?.getAttribute('data-nombre') || '';
        const hidId = $('#acCursoId');
        const hidNom = $('#acNombreCurso');
        if (hidId) hidId.value = cursoId;
        if (hidNom) hidNom.value = nombre;
        const contTpl = $('#acTemplateContenedor');
        if (contTpl && cursoId) cargarDetalleTemplate(19, cursoId, contTpl);
    }

    function cargarArquitectosCerebros() {
        ajax('get-cursos-por-tipo.php', 'GET', { tipo_id: 19 }).catch(() => ({ success: false }))
            .then((res) => {
                if (!res.success || !res.items?.length) {
                    camposDinamicos.innerHTML = '<p class="text-danger">No hay cursos de Arquitectos de Cerebros disponibles en este momento.</p>';
                    return;
                }

                let html = '<div class="arquitectos-cerebros-form">';
                html += '<input type="hidden" id="acCursoId" name="curso_id" value="">';
                html += '<input type="hidden" id="acNombreCurso" name="nombreCurso" value="">';

                html += '<div class="mb-3">';
                html += '<label class="form-label fw-bold d-block">¿En cuál curso deseas inscribirte?</label>';
                res.items.forEach((it) => {
                    const id = String(it.id);
                    const label = AC_CURSOS_LABEL[id] || it.nombre_display || it.nombre || id;
                    const nombre = it.nombre || it.nombre_curso || label;
                    const fecha = it.fecha_display ? ` <span class="text-muted small">(${escapeHtml(it.fecha_display)})</span>` : '';
                    html += '<div class="form-check mb-2">';
                    html += `<input class="form-check-input" type="radio" name="ac_curso_id" value="${escapeHtml(id)}" id="acCurso_${escapeHtml(id)}" data-nombre="${escapeHtml(nombre)}" required>`;
                    html += `<label class="form-check-label" for="acCurso_${escapeHtml(id)}">${escapeHtml(label)}${fecha}</label>`;
                    html += '</div>';
                });
                html += '</div>';

                html += '<div class="detalle-template-contenedor mb-3" id="acTemplateContenedor"></div>';

                html += '<div class="mb-3">';
                html += '<label class="form-label fw-bold">¿Eres familia San José de las Vegas?</label>';
                html += '<select class="form-select" id="acFamiliaSjv" name="familia_sjv" required>';
                html += '<option value="">-- Seleccione --</option>';
                html += '<option value="Sí">Sí</option><option value="No">No</option>';
                html += '</select></div>';

                html += '<div class="mb-3" id="wrapAcOrganizacion" style="display:none;">';
                html += '<label class="form-label fw-bold">¿A qué organización perteneces?</label>';
                html += '<input type="text" class="form-control" id="acOrganizacion" name="organizacion" maxlength="150" placeholder="Nombre de la organización">';
                html += '</div>';

                html += '<div class="mb-3">';
                html += '<label class="form-label fw-bold">¿Cuál es tu rol principal?</label>';
                html += '<select class="form-select" id="acRol" name="ac_rol" required>';
                html += '<option value="">-- Seleccione --</option>';
                AC_ROLES.forEach((r) => {
                    html += `<option value="${escapeHtml(r)}">${escapeHtml(r)}</option>`;
                });
                html += '</select></div>';

                html += '<div class="mb-3" id="wrapAcRolOtro" style="display:none;">';
                html += '<label class="form-label fw-bold">Especifique su rol</label>';
                html += '<input type="text" class="form-control" id="acRolOtro" name="ac_rol_otro" maxlength="100" placeholder="Describa su rol">';
                html += '</div></div>';

                camposDinamicos.innerHTML = html;

                $('#acFamiliaSjv')?.addEventListener('change', actualizarAcFamiliaSjv);
                $('#acRol')?.addEventListener('change', actualizarAcRol);
                camposDinamicos.querySelectorAll('input[name="ac_curso_id"]').forEach((rb) => {
                    rb.addEventListener('change', actualizarAcCursoSeleccionado);
                });

                const first = camposDinamicos.querySelector('input[name="ac_curso_id"]');
                if (first) {
                    first.checked = true;
                    actualizarAcCursoSeleccionado();
                }

                refreshRequiredAsterisks(document);
                btnEnviar.disabled = false;
            })
            .catch(() => {
                camposDinamicos.innerHTML = '<p class="text-danger">Error al cargar Arquitectos de Cerebros.</p>';
            });
    }

    let copaVegasConfigCache = null;

    function formatearPrecioCopaVegas(valor) {
        const n = Number(valor) || 0;
        return '$' + n.toLocaleString('es-CO');
    }

    function actualizarCategoriasCopaVegas() {
        const disc = $('#cvDisciplina')?.value || '';
        const selCat = $('#cvCategoria');
        const badge = $('#cvPrecioBadge');
        if (!selCat) return;

        const prev = selCat.value;
        selCat.innerHTML = '<option value="">-- Seleccione --</option>';
        const info = (copaVegasConfigCache?.disciplinas || []).find((d) => d.nombre === disc);
        if (!info) {
            if (badge) badge.style.display = 'none';
            return;
        }
        (info.categorias || []).forEach((c) => {
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = c;
            selCat.appendChild(opt);
        });
        if (prev && Array.from(selCat.options).some((o) => o.value === prev)) {
            selCat.value = prev;
        }
        if (badge) {
            badge.innerHTML = 'Inversión: <span class="precio-destacado">' + (info.precio_display || formatearPrecioCopaVegas(info.precio)) + '</span>';
            badge.style.display = 'block';
        }
    }

    function actualizarColegioCopaVegas() {
        const procedencia = $('#cvInternoExterno')?.value || '';
        const esExterno = procedencia === 'Externo';
        const wrap = $('#wrapCvColegio');
        const inp = $('#cvColegioClub');
        if (wrap) wrap.style.display = esExterno ? 'block' : 'none';
        if (inp) {
            inp.required = esExterno;
            if (!esExterno) inp.value = '';
        }
        actualizarCantidadPorrismoCopaVegas();
        refreshRequiredAsterisks(document);
    }

    function actualizarCantidadPorrismoCopaVegas() {
        const disc = $('#cvDisciplina')?.value || '';
        const procedencia = $('#cvInternoExterno')?.value || '';
        const show = disc === 'Porrismo' && procedencia === 'Externo';
        const wrap = $('#wrapCvCantidadDeportistas');
        const inp = $('#cvCantidadDeportistas');
        if (wrap) wrap.style.display = show ? 'block' : 'none';
        if (inp) {
            inp.required = show;
            if (!show) inp.value = '';
        }
        refreshRequiredAsterisks(document);
    }

    function esDisciplinaEquipoCopaVegas(nombre) {
        const list = copaVegasConfigCache?.disciplinas_equipo || ['Fútbol', 'Baloncesto', 'Voleibol'];
        return list.includes(nombre);
    }

    function redirigirCopaVegasEquipos(disciplina) {
        if (!responsableActual) {
            alert('Primero valide el documento del responsable para continuar con la inscripción por equipos.');
            docResponsable?.focus();
            return;
        }
        const cursoId = $('#cvCursoId')?.value || copaVegasConfigCache?.curso_id || '2001';
        const params = new URLSearchParams({
            responsable: String(responsableActual.documento || responsableActual.id || ''),
            curso_id: String(cursoId),
            tipo: '20',
            disciplina: disciplina || '',
        });
        window.location.href = basePath + 'inscripcion-equipos.php?' + params.toString();
    }

    function onDisciplinaCopaVegasChange() {
        const disc = $('#cvDisciplina')?.value || '';
        if (esDisciplinaEquipoCopaVegas(disc)) {
            redirigirCopaVegasEquipos(disc);
            return;
        }
        actualizarCategoriasCopaVegas();
        actualizarCantidadPorrismoCopaVegas();
    }

    function cargarCopaVegas() {
        Promise.all([
            ajax('get-cursos-por-tipo.php', 'GET', { tipo_id: 20 }).catch(() => ({ success: false })),
            ajax('get-copa-vegas-config.php', 'GET', {}).catch(() => ({ success: false })),
        ]).then(([resCursos, resCfg]) => {
            if (!resCursos.success || !resCursos.items?.length) {
                camposDinamicos.innerHTML = '<p class="text-danger">Copa Vegas no está disponible en este momento. Verifique que el curso esté activo y dentro de la fecha límite de inscripción.</p>';
                return;
            }
            if (!resCfg.success || !resCfg.disciplinas?.length) {
                camposDinamicos.innerHTML = '<p class="text-danger">No se pudo cargar la configuración de Copa Vegas.</p>';
                return;
            }

            copaVegasConfigCache = resCfg;
            const cursoId = String(resCfg.curso_id || resCursos.items[0].id || '2001');
            const item = resCursos.items.find((it) => String(it.id) === cursoId) || resCursos.items[0];
            const nombreCurso = item.nombre_curso || item.nombre || resCfg.nombre || 'Copa Vegas';
            const sedes = resCfg.sedes || ['MEDELLÍN', 'RETIRO'];
            const procedenciaOpts = resCfg.interno_externo || ['San José de Las Vegas', 'Externo'];
            const disciplinasEquipo = resCfg.disciplinas_equipo || ['Fútbol', 'Baloncesto', 'Voleibol'];

            let html = '<div class="copa-vegas-form">';
            html += `<input type="hidden" id="cvCursoId" name="curso_id" value="${escapeHtml(String(item.id))}">`;
            html += `<input type="hidden" id="cvNombreCurso" name="nombreCurso" value="${escapeHtml(nombreCurso)}">`;
            html += '<div class="detalle-template-contenedor mb-3" id="cvTemplateContenedor"></div>';

            html += '<div class="mb-3">';
            html += '<label class="form-label fw-bold" for="cvDisciplina">Disciplina</label>';
            html += '<select class="form-select" id="cvDisciplina" name="cv_disciplina" required>';
            html += '<option value="">-- Seleccione --</option>';
            resCfg.disciplinas.forEach((d) => {
                const esEquipo = disciplinasEquipo.includes(d.nombre);
                const hint = esEquipo ? ' (inscripción por equipos)' : '';
                html += `<option value="${escapeHtml(d.nombre)}" data-precio="${escapeHtml(String(d.precio || 0))}" data-equipo="${esEquipo ? '1' : '0'}">${escapeHtml(d.nombre)}${hint}</option>`;
            });
            html += '</select>';
            html += '<p class="cv-precio" id="cvPrecioBadge" style="display:none;"></p>';
            html += '<p class="small text-muted mb-0 mt-1">Fútbol, baloncesto y voleibol se inscriben por equipos.</p>';
            html += '</div>';

            html += '<div id="cvCamposIndividuales">';
            html += '<div class="mb-3">';
            html += '<label class="form-label fw-bold" for="cvCategoria">Categoría</label>';
            html += '<select class="form-select" id="cvCategoria" name="cv_categoria" required>';
            html += '<option value="">-- Primero seleccione disciplina --</option>';
            html += '</select></div>';

            html += '<div class="mb-3">';
            html += '<label class="form-label fw-bold" for="cvSede">Sede de participación</label>';
            html += '<select class="form-select" id="cvSede" name="cv_sede" required>';
            html += '<option value="">-- Seleccione --</option>';
            sedes.forEach((s) => {
                html += `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`;
            });
            html += '</select></div>';

            html += '<div class="mb-3">';
            html += '<label class="form-label fw-bold" for="cvInternoExterno">¿El deportista es de San José de Las Vegas o externo?</label>';
            html += '<select class="form-select" id="cvInternoExterno" name="cv_interno_externo" required>';
            html += '<option value="">-- Seleccione --</option>';
            procedenciaOpts.forEach((v) => {
                html += `<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`;
            });
            html += '</select></div>';

            html += '<div class="mb-3" id="wrapCvColegio" style="display:none;">';
            html += '<label class="form-label fw-bold" for="cvColegioClub">Nombre del colegio o club</label>';
            html += '<input type="text" class="form-control" id="cvColegioClub" name="cv_colegio_club" maxlength="150" placeholder="Colegio o club al que pertenece">';
            html += '</div>';

            html += '<div class="mb-3" id="wrapCvCantidadDeportistas" style="display:none;">';
            html += '<label class="form-label fw-bold" for="cvCantidadDeportistas">¿Cuántos deportistas participarán?</label>';
            html += '<input type="number" class="form-control" id="cvCantidadDeportistas" name="cv_cantidad_deportistas" min="1" max="99" step="1" placeholder="Cantidad">';
            html += '</div>';

            html += '<div class="row g-3 mb-3">';
            html += '<div class="col-md-6"><label class="form-label fw-bold" for="cvEntrenadorNombre">Nombre del entrenador</label>';
            html += '<input type="text" class="form-control" id="cvEntrenadorNombre" name="cv_entrenador_nombre" maxlength="120" required placeholder="Nombre completo"></div>';
            html += '<div class="col-md-6"><label class="form-label fw-bold" for="cvEntrenadorContacto">Contacto del entrenador</label>';
            html += '<input type="text" class="form-control" id="cvEntrenadorContacto" name="cv_entrenador_contacto" maxlength="80" required placeholder="Celular o correo"></div>';
            html += '</div>';

            html += '<div class="mb-3">';
            html += '<label class="form-label fw-bold" for="cvEps">EPS</label>';
            html += '<input type="text" class="form-control" id="cvEps" name="cv_eps" maxlength="100" required placeholder="Nombre de la EPS">';
            html += '</div></div></div>';

            camposDinamicos.innerHTML = html;
            cargarDetalleTemplate(20, item.id, $('#cvTemplateContenedor'));
            $('#cvDisciplina')?.addEventListener('change', onDisciplinaCopaVegasChange);
            $('#cvInternoExterno')?.addEventListener('change', actualizarColegioCopaVegas);
            actualizarVisibilidadCamposAdicionales(20, item.id);
            refreshRequiredAsterisks(document);
            btnEnviar.disabled = false;
        }).catch(() => {
            camposDinamicos.innerHTML = '<p class="text-danger">Error al cargar Copa Vegas.</p>';
        });
    }

    function actualizarActividadesPorLinea() {
        const linea = $('#filtroLinea')?.value;
        const sel = $('#filtroActividad');
        if (!sel) return;

        const prev = sel.value;
        sel.innerHTML = '<option value="">-- Seleccione --</option>';
        if (!linea) return;

        actividadesTipo1
            .filter(a => String(a.IDNegocio ?? '') === String(linea))
            .forEach(a => {
                const opt = document.createElement('option');
                opt.value = a.IDActividad;
                opt.textContent = a.Nombre_Actividad || '';
                sel.appendChild(opt);
            });

        if (prev && Array.from(sel.options).some(o => o.value === prev)) {
            sel.value = prev;
        }
    }

    function cargarCamposTipo1() {
        const anio = new Date().getFullYear();
        ajax('get-filtros-tipo1.php', 'GET', { tipo_id: 1 }).then(res => {
            const meses = res.meses || [];
            const lineas = res.lineas || [];
            actividadesTipo1 = res.actividades || [];

            let html = '';
            html += '<div class="row g-3 mb-3">';
            html += '<div class="col-md-6 col-lg-3"><label class="form-label fw-bold">Mes en el que desea comenzar</label><select class="form-select filtro-curso" name="mes" id="filtroMes"><option value="">-- Seleccione --</option>';
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
            html += '<div class="col-md-6 col-lg-3"><label class="form-label fw-bold">Actividad</label><select class="form-select filtro-curso" name="actividad" id="filtroActividad"><option value="">-- Seleccione --</option></select></div></div>';

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
                el.addEventListener('change', () => {
                    if (el.id === 'filtroLinea') {
                        actualizarActividadesPorLinea();
                    }
                    cargarCursosTipo1();
                });
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

    function cargarCamposExtraSalida(salidaId) {
        const wrap = $('#salidaCamposExtraContenedor');
        if (!wrap) return;
        wrap.innerHTML = '';
        if (!salidaId) return;
        ajax('get-salida-campos-config.php', 'GET', { salida_id: salidaId })
            .then((res) => {
                const cfg = res.config;
                if (!cfg?.categoria?.options) return;
                const cat = cfg.categoria;
                let html = '<div class="mb-3">';
                html += `<label class="form-label fw-bold">${escapeHtml(cat.label || 'Categoría')}</label>`;
                html += '<select class="form-select" id="salidaCategoria" name="salida_categoria" required>';
                html += '<option value="">-- Seleccione --</option>';
                Object.entries(cat.options).forEach(([val, label]) => {
                    html += `<option value="${escapeHtml(val)}">${escapeHtml(label)}</option>`;
                });
                html += '</select></div>';
                wrap.innerHTML = html;
                refreshRequiredAsterisks(wrap);
            })
            .catch(() => {});
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
                html += '<div id="salidaCamposExtraContenedor"></div>';
                camposDinamicos.innerHTML = html;
                if (cfg.tieneTemplate) {
                    const sel = camposDinamicos.querySelector('.select-con-detalle');
                    const cont = camposDinamicos.querySelector('.detalle-template-contenedor');
                    if (sel && cont) {
                        sel.addEventListener('change', () => {
                            cargarDetalleTemplate(tipoId, sel.value, cont);
                            cargarCamposExtraSalida(sel.value);
                            actualizarVisibilidadCamposAdicionales(tipoId, sel.value || '');
                        });
                    }
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
                if (res.success && res.html) {
                    contenedor.innerHTML = res.html;
                    return;
                }
                contenedor.innerHTML = '<p class="text-muted small mt-2 mb-0">Esta salida no tiene detalle disponible en este momento.</p>';
            })
            .catch(() => {
                contenedor.innerHTML = '<p class="text-danger small mt-2 mb-0">No se pudo cargar el detalle de la salida.</p>';
            });
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
                            actualizarVisibilidadCamposAdicionales(tipoId, sel.value || '');
                        });
                        cargarParticipantesAdicionales(tipoId, sel.value, camposDinamicos);
                        actualizarVisibilidadCamposAdicionales(tipoId, sel.value || '');
                    }
                }
                refreshRequiredAsterisks(document);
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
                refreshRequiredAsterisks(document);
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
        else if (tipo === 4) tipoTexto = 'Level Up';
        else if (tipo === 5) tipoTexto = 'Salida';
        else if (tipo === 18) tipoTexto = 'Evento';
        else if (tipo === 19) tipoTexto = 'Arquitectos de Cerebros';
        else if (tipo === 20) tipoTexto = 'Copa Vegas';
        else tipoTexto = 'Inscripción';
        let detalleHtml = '';
        if (tipo === 1 && data.nombres_curso && data.nombres_curso.length) {
            detalleHtml = '<ul class="mb-0">' + data.nombres_curso.map(n => `<li>${escapeHtml(n)}</li>`).join('') + '</ul>';
        } else if (tipo === 4) {
            const cursoNom = data.nombreCurso || '';
            const asigs = data.nombres_asignaturas || data.asignatura_nombres || [];
            detalleHtml = escapeHtml(cursoNom);
            if (data.levelup_modalidad || data.Sesión) {
                detalleHtml += '<p class="mb-1 small text-muted">Modalidad: ' + escapeHtml(data.levelup_modalidad || data.Sesión) + '</p>';
            }
            if (data.levelup_sede || data.Sede) {
                detalleHtml += '<p class="mb-1 small text-muted">Sede: ' + escapeHtml(data.levelup_sede || data.Sede) + '</p>';
            }
            if (data.categoria || data.levelup_grado_academico) {
                detalleHtml += '<p class="mb-1 small text-muted">Grado académico: ' + escapeHtml(data.categoria || data.levelup_grado_academico) + '</p>';
            }
            if (data.levelup_diagnostico) {
                detalleHtml += '<p class="mb-1 small text-muted">Diagnóstico: ' + escapeHtml(data.levelup_diagnostico) + '</p>';
            }
            if (asigs.length) {
                detalleHtml += '<ul class="mb-0 mt-2">' + asigs.map(n => `<li>${escapeHtml(n)}</li>`).join('') + '</ul>';
            }
        } else if (tipo === 18) {
            detalleHtml = escapeHtml(data.nombreCurso || 'Evento');
            if (data.openk_modalidades?.length) {
                detalleHtml += '<ul class="mb-0 mt-2">' + data.openk_modalidades.map(m => `<li>${escapeHtml(m)}</li>`).join('') + '</ul>';
            }
            if (data.tkd_modalidades?.length) {
                detalleHtml += '<ul class="mb-0 mt-2">' + data.tkd_modalidades.map(m => `<li>${escapeHtml(m)}</li>`).join('') + '</ul>';
            }
            if (data.cheer_categoria || data.evento_categoria || data.categoria) {
                detalleHtml += '<p class="mb-1 small text-muted">Categoría: ' + escapeHtml(data.cheer_categoria || data.evento_categoria || data.categoria) + '</p>';
            }
            if (data.tkd_grado) {
                detalleHtml += '<p class="mb-1 small text-muted">Grado: ' + escapeHtml(data.tkd_grado) + '</p>';
            }
            if (data.tkd_genero) {
                detalleHtml += '<p class="mb-1 small text-muted">Género: ' + escapeHtml(data.tkd_genero) + '</p>';
            }
            if (res.valor_total) {
                detalleHtml += '<p class="mb-0 small text-muted">Valor total: $' + Number(res.valor_total).toLocaleString('es-CO') + '</p>';
            }
        } else if (tipo === 19) {
            detalleHtml = escapeHtml(data.nombreCurso || 'Arquitectos de Cerebros');
            if (data.Modalidad || data.ac_rol) {
                detalleHtml += '<p class="mb-1 small text-muted">Rol: ' + escapeHtml(data.Modalidad || data.ac_rol) + '</p>';
            }
            if (data.familia_sjv) {
                detalleHtml += '<p class="mb-1 small text-muted">Familia SJV: ' + escapeHtml(data.familia_sjv) + '</p>';
            }
            if (data.organizacion) {
                detalleHtml += '<p class="mb-0 small text-muted">Organización: ' + escapeHtml(data.organizacion) + '</p>';
            }
        } else if (tipo === 20) {
            detalleHtml = escapeHtml(data.nombreCurso || 'Copa Vegas');
            if (data.Modalidad || data.cv_disciplina) {
                detalleHtml += '<p class="mb-1 small text-muted">Disciplina: ' + escapeHtml(data.Modalidad || data.cv_disciplina) + '</p>';
            }
            if (data.categoria || data.cv_categoria) {
                detalleHtml += '<p class="mb-1 small text-muted">Categoría: ' + escapeHtml(data.categoria || data.cv_categoria) + '</p>';
            }
            if (data.Sede || data.cv_sede) {
                detalleHtml += '<p class="mb-1 small text-muted">Sede: ' + escapeHtml(data.Sede || data.cv_sede) + '</p>';
            }
            if (data.IDAsign || data.cv_interno_externo) {
                detalleHtml += '<p class="mb-1 small text-muted">' + escapeHtml(data.IDAsign || data.cv_interno_externo) + '</p>';
            }
            if (data.organizacion || data.cv_colegio_club) {
                detalleHtml += '<p class="mb-1 small text-muted">Colegio/club: ' + escapeHtml(data.organizacion || data.cv_colegio_club) + '</p>';
            }
            if (data.Asignatura || data.cv_eps) {
                detalleHtml += '<p class="mb-1 small text-muted">EPS: ' + escapeHtml(data.Asignatura || data.cv_eps) + '</p>';
            }
            if (res.valor_total) {
                detalleHtml += '<p class="mb-0 small text-muted">Valor: $' + Number(res.valor_total).toLocaleString('es-CO') + '</p>';
            }
        } else if (data.nombreCurso) {
            detalleHtml = escapeHtml(data.nombreCurso);
            if (tipo === 5 && data.Modalidad) {
                detalleHtml += '<p class="mb-0 small text-muted">Categoría: ' + escapeHtml(data.Modalidad) + '</p>';
            }
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
        } else if (tipo === 4) {
            const sede = $('#levelupSede')?.value;
            const nivel = parseInt($('#levelupNivel')?.value || '0', 10);
            const cursoId = $('#levelupCursoId')?.value;
            const nombreCurso = $('#levelupNombreCurso')?.value;
            if (!formInscripcion.reportValidity()) {
                return;
            }
            if (!sede || !nivel || !cursoId) {
                alert('Complete sede y nivel para continuar.');
                return;
            }
            const gradoAcademico = ($('#levelupGradoAcademico')?.value || '').trim();
            if (!gradoAcademico) {
                alert('Ingrese el grado académico del participante.');
                $('#levelupGradoAcademico')?.focus();
                return;
            }
            if (nivel === 2) {
                const diagnostico = ($('#levelupDiagnostico')?.value || '').trim();
                if (!diagnostico) {
                    alert('Ingrese el diagnóstico del participante.');
                    $('#levelupDiagnostico')?.focus();
                    return;
                }
                data.levelup_modalidad = 'Individual';
                data.Sesión = 'Individual';
                data.levelup_diagnostico = diagnostico;
            } else {
                const checks = camposDinamicos.querySelectorAll('input[name="asignatura_ids[]"]:checked');
                const asignaturaIds = [...checks].map(c => c.value);
                if (!asignaturaIds.length && !levelupAsignaturasNuevas.length) {
                    alert('Seleccione o agregue al menos una asignatura.');
                    return;
                }
                const mod = $('#levelupModalidad')?.value;
                if (!mod) {
                    alert('Seleccione la modalidad Individual o Grupal.');
                    return;
                }
                const totalAsig = asignaturaIds.length + levelupAsignaturasNuevas.length;
                if (mod === 'Grupal' && totalAsig !== 1) {
                    alert('En modalidad grupal solo puede inscribir una asignatura.');
                    return;
                }
                data.levelup_modalidad = mod;
                data.Sesión = mod;
                data.asignatura_ids = asignaturaIds;
                data.asignaturas_nuevas = [...levelupAsignaturasNuevas];
            }
            data.levelup_sede = sede;
            data.levelup_nivel = nivel;
            data.levelup_grado_academico = gradoAcademico;
            data.categoria = gradoAcademico;
            data.Sede = sede;
            data.curso_id = cursoId;
            data.IDCurso = cursoId;
            data.nombreCurso = nombreCurso || '';
            const mesActual = String(new Date().getMonth() + 1).padStart(2, '0');
            data.Mes = mesActual;
            data.Periodo = mesActual + String(anio % 100).padStart(2, '0');
        } else if (tipo === 18) {
            const cursoId = $('#t18CursoId')?.value || $('#t18EventoSelect')?.value || '';
            const nombreCurso = camposDinamicos.querySelector('input[name="nombreCurso"]')?.value
                || $('#t18EventoSelect')?.selectedOptions?.[0]?.getAttribute('data-nombre')
                || '';
            if (!cursoId) {
                alert('Seleccione el evento.');
                $('#t18EventoSelect')?.focus();
                return;
            }
            data.curso_id = cursoId;
            data.IDCurso = cursoId;
            data.nombreCurso = nombreCurso;

            if (cursoId === '1802') {
                const mods = [...camposDinamicos.querySelectorAll('input[name="openk_modalidades[]"]:checked')].map((c) => c.value);
                if (!mods.length) {
                    alert('Seleccione al menos una modalidad.');
                    return;
                }
                const tieneCombate = mods.includes('Combate individual');
                if (tieneCombate) {
                    const rama = $('#openkRama')?.value || '';
                    const division = $('#openkDivision')?.value || '';
                    const grado = $('#openkGrado')?.value || '';
                    if (!rama || !division || !grado) {
                        alert('Complete rama, división y grado para combate individual.');
                        return;
                    }
                    if (division === 'Junior') {
                        if (!$('#openkPeso')?.value) {
                            alert('Ingrese el peso en kg para la división Junior.');
                            $('#openkPeso')?.focus();
                            return;
                        }
                    } else if (!$('#openkEstatura')?.value) {
                        alert('Ingrese la estatura en cm.');
                        $('#openkEstatura')?.focus();
                        return;
                    }
                    data.openk_rama = rama;
                    data.openk_division = division;
                    data.openk_grado = grado;
                    data.openk_estatura = $('#openkEstatura')?.value || '';
                    data.openk_peso = $('#openkPeso')?.value || '';
                }
                data.openk_modalidades = mods;
            } else if (cursoId === '1804') {
                const mods = [...camposDinamicos.querySelectorAll('input[name="tkd_modalidades[]"]:checked')].map((c) => c.value);
                if (!mods.length) {
                    alert('Seleccione al menos una modalidad.');
                    return;
                }
                const categoria = $('#tkdCategoria')?.value || '';
                const grado = $('#tkdGrado')?.value || '';
                const genero = $('#tkdGenero')?.value || '';
                const peso = $('#tkdPeso')?.value || '';
                const estatura = $('#tkdEstatura')?.value || '';
                if (!categoria || !grado || !genero) {
                    alert('Complete categoría, grado y género.');
                    return;
                }
                if (!peso) {
                    alert('Ingrese el peso en kg.');
                    $('#tkdPeso')?.focus();
                    return;
                }
                if (!estatura) {
                    alert('Ingrese la estatura en cm.');
                    $('#tkdEstatura')?.focus();
                    return;
                }
                data.tkd_modalidades = mods;
                data.tkd_categoria = categoria;
                data.tkd_grado = grado;
                data.tkd_genero = genero;
                data.tkd_peso = peso;
                data.tkd_estatura = estatura;
            } else if (TIPO18_CATEGORIAS[cursoId]) {
                const cat = $('#eventoCategoria')?.value || '';
                if (!cat) {
                    alert('Seleccione la categoría.');
                    $('#eventoCategoria')?.focus();
                    return;
                }
                data.evento_categoria = cat;
                data.categoria = cat;
            } else {
                alert('Evento no configurado.');
                return;
            }
            const mesActual = String(new Date().getMonth() + 1).padStart(2, '0');
            data.Mes = mesActual;
            data.Periodo = mesActual + String(anio % 100).padStart(2, '0');
        } else if (tipo === 19) {
            const familiaSjv = $('#acFamiliaSjv')?.value || '';
            if (!familiaSjv) {
                alert('Indique si pertenece a la familia San José de las Vegas.');
                $('#acFamiliaSjv')?.focus();
                return;
            }
            if (familiaSjv === 'No' && !($('#acOrganizacion')?.value || '').trim()) {
                alert('Indique a qué organización pertenece.');
                $('#acOrganizacion')?.focus();
                return;
            }
            const rol = $('#acRol')?.value || '';
            if (!rol) {
                alert('Seleccione su rol principal.');
                $('#acRol')?.focus();
                return;
            }
            if (rol === 'Otro' && !($('#acRolOtro')?.value || '').trim()) {
                alert('Especifique su rol.');
                $('#acRolOtro')?.focus();
                return;
            }
            const cursoSel = camposDinamicos.querySelector('input[name="ac_curso_id"]:checked');
            if (!cursoSel) {
                alert('Seleccione el curso en el que desea inscribirse.');
                return;
            }
            data.familia_sjv = familiaSjv;
            data.organizacion = familiaSjv === 'No' ? ($('#acOrganizacion')?.value || '').trim() : '';
            data.ac_rol = rol;
            data.ac_rol_otro = rol === 'Otro' ? ($('#acRolOtro')?.value || '').trim() : '';
            data.curso_id = cursoSel.value;
            data.IDCurso = cursoSel.value;
            data.nombreCurso = cursoSel.getAttribute('data-nombre') || $('#acNombreCurso')?.value || '';
            const mesActual = String(new Date().getMonth() + 1).padStart(2, '0');
            data.Mes = mesActual;
            data.Periodo = mesActual + String(anio % 100).padStart(2, '0');
        } else if (tipo === 20) {
            const disciplina = $('#cvDisciplina')?.value || '';
            if (esDisciplinaEquipoCopaVegas(disciplina)) {
                redirigirCopaVegasEquipos(disciplina);
                return;
            }
            const categoria = $('#cvCategoria')?.value || '';
            const sede = $('#cvSede')?.value || '';
            const internoExterno = $('#cvInternoExterno')?.value || '';
            const colegioClub = ($('#cvColegioClub')?.value || '').trim();
            const cantidadDeportistas = ($('#cvCantidadDeportistas')?.value || '').trim();
            const entrenadorNombre = ($('#cvEntrenadorNombre')?.value || '').trim();
            const entrenadorContacto = ($('#cvEntrenadorContacto')?.value || '').trim();
            const eps = ($('#cvEps')?.value || '').trim();
            if (!disciplina) {
                alert('Seleccione la disciplina.');
                $('#cvDisciplina')?.focus();
                return;
            }
            if (!categoria) {
                alert('Seleccione la categoría.');
                $('#cvCategoria')?.focus();
                return;
            }
            if (!sede) {
                alert('Seleccione la sede de participación.');
                $('#cvSede')?.focus();
                return;
            }
            if (!internoExterno) {
                alert('Indique si el deportista es de San José de Las Vegas o externo.');
                $('#cvInternoExterno')?.focus();
                return;
            }
            if (internoExterno === 'Externo' && !colegioClub) {
                alert('Ingrese el nombre del colegio o club.');
                $('#cvColegioClub')?.focus();
                return;
            }
            if (disciplina === 'Porrismo' && internoExterno === 'Externo') {
                const n = parseInt(cantidadDeportistas, 10);
                if (!n || n < 1) {
                    alert('Indique cuántos deportistas participarán.');
                    $('#cvCantidadDeportistas')?.focus();
                    return;
                }
            }
            if (!entrenadorNombre) {
                alert('Ingrese el nombre del entrenador.');
                $('#cvEntrenadorNombre')?.focus();
                return;
            }
            if (!entrenadorContacto) {
                alert('Ingrese el contacto del entrenador.');
                $('#cvEntrenadorContacto')?.focus();
                return;
            }
            if (!eps) {
                alert('Ingrese la EPS.');
                $('#cvEps')?.focus();
                return;
            }
            data.curso_id = $('#cvCursoId')?.value || '';
            data.IDCurso = data.curso_id;
            data.nombreCurso = $('#cvNombreCurso')?.value || 'Copa Vegas';
            data.cv_disciplina = disciplina;
            data.cv_categoria = categoria;
            data.cv_sede = sede;
            data.cv_interno_externo = internoExterno;
            data.cv_colegio_club = internoExterno === 'Externo' ? colegioClub : '';
            data.cv_cantidad_deportistas = (disciplina === 'Porrismo' && internoExterno === 'Externo') ? cantidadDeportistas : '';
            data.cv_entrenador_nombre = entrenadorNombre;
            data.cv_entrenador_contacto = entrenadorContacto;
            data.cv_eps = eps;
            data.Modalidad = disciplina;
            data.categoria = categoria;
            data.Sede = sede;
            data.IDAsign = internoExterno;
            data.organizacion = data.cv_colegio_club;
            data.Asignatura = eps;
            const mesActual = String(new Date().getMonth() + 1).padStart(2, '0');
            data.Mes = mesActual;
            data.Periodo = mesActual + String(anio % 100).padStart(2, '0');
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
            if (tipo === 5) {
                const catInp = $('#salidaCategoria');
                if (catInp?.required && !catInp.value) {
                    alert('Seleccione la categoría.');
                    catInp.focus();
                    return;
                }
                if (catInp?.value) {
                    data.salida_categoria = catInp.value;
                    data.Modalidad = catInp.value;
                }
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
                    if (tipo === 4 && res.nombres_asignaturas) {
                        data.nombres_asignaturas = res.nombres_asignaturas;
                    }
                    if (tipo === 4 && res.levelup_diagnostico) {
                        data.levelup_diagnostico = res.levelup_diagnostico;
                    }
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
        refreshRequiredAsterisks(document);
    }
})();
