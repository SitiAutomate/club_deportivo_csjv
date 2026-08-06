(function () {
    'use strict';

    const MAX_EQUIPOS = 10;
    const TEL_PATTERN = /^3\d{9}$/;
    const TEL_INPUT_ATTRS = 'type="tel" inputmode="numeric" pattern="3[0-9]{9}" maxlength="10" minlength="10" placeholder="Ej: 3001234567"';
    const URL_PARAMS = new URLSearchParams(window.location.search);
    const PREFILL_RESPONSABLE = (URL_PARAMS.get('responsable') || URL_PARAMS.get('doc') || '').trim();
    const PREFILL_DISCIPLINA = (URL_PARAMS.get('disciplina') || '').trim();
    const PREFILL_CURSO = (URL_PARAMS.get('curso_id') || URL_PARAMS.get('evento') || '').trim();

    function formatFechaDDMMYYYY(valor) {
        if (!valor) return '';
        const m = String(valor).match(/^(\d{4})-(\d{2})-(\d{2})/);
        return m ? `${m[3]}/${m[2]}/${m[1]}` : String(valor);
    }

    const basePath = (() => {
        const p = window.location.pathname;
        if (p.endsWith('/')) return p;
        const idx = p.lastIndexOf('/');
        return idx >= 0 ? p.slice(0, idx + 1) : '/';
    })();

    const getAuthHeaders = () => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) return {};
        return { Authorization: 'Bearer ' + token, 'X-CSRF-Token': token };
    };

    const ajax = (path, method, data) => {
        const opts = { method: method || 'GET', headers: { ...getAuthHeaders() } };
        if (data && method === 'POST') {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(data);
        } else if (data && method === 'GET') {
            const params = new URLSearchParams();
            Object.keys(data).forEach((k) => {
                if (data[k] != null && data[k] !== '') params.set(k, data[k]);
            });
            path += (path.includes('?') ? '&' : '?') + params.toString();
        }
        return fetch(basePath + 'ajax/' + path.replace(/^\//, ''), opts).then((r) => r.json());
    };

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    // ---- DOM refs ----
    const form = document.getElementById('formInscripcionEquipos');
    const checkPoliticas = document.getElementById('checkPoliticas');
    const contenido = document.getElementById('contenidoFormulario');
    const docResponsable = document.getElementById('docResponsable');
    const btnValidarResp = document.getElementById('btnValidarResponsable');
    const responsableInfo = document.getElementById('responsableInfo');
    const responsableError = document.getElementById('responsableError');
    const cardEvento = document.getElementById('cardEvento');
    const eventoSelect = document.getElementById('eventoSelect');
    const eventoInfo = document.getElementById('eventoInfo');
    const cantidadEquipos = document.getElementById('cantidadEquipos');
    const contenedorEquipos = document.getElementById('contenedorEquipos');
    const btnEnviar = document.getElementById('btnEnviar');
    const resultadoFinal = document.getElementById('resultadoFinal');
    const modalResponsableEl = document.getElementById('modalResponsable');
    const formResponsable = document.getElementById('formResponsable');
    const modalExitoEl = document.getElementById('modalExito');
    const modalExitoBody = document.getElementById('modalExitoBody');
    const btnCerrarExito = document.getElementById('btnCerrarExito');
    const barEquiposInscritos = document.getElementById('barEquiposInscritos');
    const listaEquiposExistentes = document.getElementById('listaEquiposExistentes');
    const badgeEquiposExistentes = document.getElementById('badgeEquiposExistentes');
    const textoEquiposExistentes = document.getElementById('textoEquiposExistentes');
    const btnVerEquiposInscritos = document.getElementById('btnVerEquiposInscritos');
    const modalEquiposInscritosEl = document.getElementById('modalEquiposInscritos');

    let modalResponsable = null;
    let modalExito = null;
    let modalEquiposInscritos = null;

    let responsableActual = null;
    let eventosCache = [];
    let inscripcionExistenteCache = null;
    let copaVegasConfig = null;
    let tipoEventoActual = 20;

    function disciplinasEquipoOptions() {
        const allowed = copaVegasConfig?.disciplinas_equipo || ['Fútbol', 'Baloncesto', 'Voleibol'];
        return (copaVegasConfig?.disciplinas || []).filter((d) => allowed.includes(d.nombre));
    }

    function categoriasDeDisciplina(nombre) {
        const d = (copaVegasConfig?.disciplinas || []).find((x) => x.nombre === nombre);
        return d?.categorias || [];
    }

    function cargarConfigCopaVegas() {
        return ajax('get-copa-vegas-config.php', 'GET')
            .then((res) => {
                if (res && res.success) copaVegasConfig = res;
                return res;
            })
            .catch(() => null);
    }

    // ---- Helpers ----
    function spinner(btn, show) {
        if (!btn) return;
        const txt = btn.querySelector('.btn-text');
        const sp = btn.querySelector('.spinner-border');
        if (txt) txt.classList.toggle('d-none', show);
        if (sp) sp.classList.toggle('d-none', !show);
        btn.disabled = show;
    }

    function showMsg(el, msg, isError) {
        el.textContent = msg;
        el.classList.remove('d-none');
        if (isError !== undefined) {
            el.classList.toggle('text-danger', !!isError);
            el.classList.toggle('text-muted', !isError);
        }
    }

    function hideMsg(el) {
        el.textContent = '';
        el.classList.add('d-none');
    }

    function actualizarEnvio() {
        const cant = parseInt(cantidadEquipos.value, 10);
        const eventoOk = !!eventoSelect.value;
        const respOk = !!responsableActual;
        const cupoLleno = inscripcionExistenteCache && inscripcionExistenteCache.total_equipos >= MAX_EQUIPOS;
        btnEnviar.disabled = !(respOk && eventoOk && cant > 0) || !!cupoLleno;
    }

    function actualizarOpcionesCantidad(maxPermitido) {
        const valorPrev = cantidadEquipos.value;
        cantidadEquipos.innerHTML = '<option value="">-- Seleccione --</option>';
        for (let i = 1; i <= maxPermitido; i++) {
            const opt = document.createElement('option');
            opt.value = String(i);
            opt.textContent = i === 1 ? '1 equipo' : `${i} equipos`;
            cantidadEquipos.appendChild(opt);
        }
        if (valorPrev && parseInt(valorPrev, 10) <= maxPermitido) {
            cantidadEquipos.value = valorPrev;
        } else {
            cantidadEquipos.value = '';
        }
    }

    function soloNumeros(input) {
        input.addEventListener('input', () => {
            input.value = (input.value || '').replace(/\D+/g, '').slice(0, parseInt(input.maxLength, 10) || 10);
        });
    }

    // ---- Eventos (Copa Vegas) ----
    function cargarEventos() {
        eventoSelect.innerHTML = '<option value="">-- Cargando eventos... --</option>';
        return ajax('get-eventos-equipos.php', 'GET')
            .then((res) => {
                if (!res.success || !Array.isArray(res.items)) {
                    eventoSelect.innerHTML = '<option value="">-- No hay eventos disponibles --</option>';
                    return;
                }
                eventosCache = res.items;
                tipoEventoActual = parseInt(res.tipo_id || '20', 10) || 20;
                if (res.items.length === 0) {
                    eventoSelect.innerHTML = '<option value="">-- No hay eventos disponibles --</option>';
                    return;
                }
                eventoSelect.innerHTML = '<option value="">-- Seleccione un evento --</option>';
                res.items.forEach((it) => {
                    const opt = document.createElement('option');
                    opt.value = it.id;
                    opt.textContent = it.nombre_display || it.nombre;
                    opt.dataset.nombre = it.nombre;
                    eventoSelect.appendChild(opt);
                });
                const def = PREFILL_CURSO || res.default_id;
                if (def && res.items.some((it) => String(it.id) === String(def))) {
                    eventoSelect.value = def;
                    eventoSelect.dispatchEvent(new Event('change'));
                } else if (res.items.length === 1) {
                    eventoSelect.value = res.items[0].id;
                    eventoSelect.dispatchEvent(new Event('change'));
                }
            })
            .catch(() => {
                eventoSelect.innerHTML = '<option value="">-- Error al cargar --</option>';
            });
    }

    // ---- Render de tarjetas de equipos preservando datos ----
    function renderEquipos(cant) {
        if (!cant || cant < 1) {
            contenedorEquipos.innerHTML = '';
            actualizarEnvio();
            return;
        }
        const existentes = contenedorEquipos.querySelectorAll('.equipo-card');
        const actuales = existentes.length;
        if (cant > actuales) {
            for (let i = actuales + 1; i <= cant; i++) {
                contenedorEquipos.appendChild(buildEquipoCard(i));
            }
        } else if (cant < actuales) {
            for (let i = actuales - 1; i >= cant; i--) {
                existentes[i].remove();
            }
        }
        actualizarEnvio();
    }

    function buildEquipoCard(idx) {
        const disciplinas = disciplinasEquipoOptions();
        const optsDisc = disciplinas.map((d) => {
            const selected = PREFILL_DISCIPLINA && d.nombre === PREFILL_DISCIPLINA ? ' selected' : '';
            const precio = d.precio_display ? ` — ${esc(d.precio_display)}` : '';
            return `<option value="${esc(d.nombre)}"${selected}>${esc(d.nombre)}${precio}</option>`;
        }).join('');

        const card = document.createElement('div');
        card.className = 'card mb-4 equipo-card';
        card.dataset.equipo = idx;
        card.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Equipo ${idx}</h5>
                <span class="badge text-bg-secondary" data-rol="badge-cat">Categoría: -</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nombre del equipo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" data-field="nombre_equipo" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Disciplina <span class="text-danger">*</span></label>
                        <select class="form-select" data-field="disciplina" required>
                            <option value="">-- Seleccione --</option>
                            ${optsDisc}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Rama <span class="text-danger">*</span></label>
                        <select class="form-select" data-field="rama" required>
                            <option value="">-- Seleccione --</option>
                            <option value="Femenina">Femenina</option>
                            <option value="Masculina">Masculina</option>
                            <option value="Mixta">Mixta</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Categoría <span class="text-danger">*</span></label>
                        <select class="form-select" data-field="categoria" required>
                            <option value="">-- Primero seleccione disciplina --</option>
                        </select>
                    </div>
                </div>

                <h6 class="fw-bold text-uppercase small text-muted mt-3">Entrenador</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label">Nombre del entrenador <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" data-field="entrenador_nombre" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Documento <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" data-field="entrenador_documento" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Celular de contacto <span class="text-danger">*</span></label>
                        <input class="form-control" data-field="entrenador_contacto" ${TEL_INPUT_ATTRS} required>
                        <div class="invalid-feedback">Ingrese un celular válido de 10 dígitos (debe empezar por 3).</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                    <h6 class="fw-bold text-uppercase small text-muted mb-0">Deportistas del equipo</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-deportista">+ Deportista</button>
                </div>
                <div class="alert alert-info py-2 small" data-rol="aviso-categoria">Registre al menos un deportista.</div>
                <div data-rol="lista-deportistas" class="vstack gap-2"></div>
            </div>
        `;

        const lista = card.querySelector('[data-rol="lista-deportistas"]');
        const badge = card.querySelector('[data-rol="badge-cat"]');
        const selDisc = card.querySelector('[data-field="disciplina"]');
        const selCat = card.querySelector('[data-field="categoria"]');
        const addBtn = card.querySelector('[data-action="add-deportista"]');

        card.querySelectorAll('input[data-field$="_contacto"]').forEach(soloNumeros);

        function refillCategorias() {
            const disc = selDisc.value;
            const cats = categoriasDeDisciplina(disc);
            const prev = selCat.value;
            selCat.innerHTML = '<option value="">-- Seleccione --</option>';
            cats.forEach((c) => {
                const opt = document.createElement('option');
                opt.value = c;
                opt.textContent = c;
                selCat.appendChild(opt);
            });
            if (prev && cats.includes(prev)) selCat.value = prev;
            badge.textContent = 'Categoría: ' + (selCat.value || '-');
        }

        selDisc.addEventListener('change', refillCategorias);
        selCat.addEventListener('change', () => {
            badge.textContent = 'Categoría: ' + (selCat.value || '-');
        });
        if (selDisc.value) refillCategorias();

        addBtn.addEventListener('click', () => {
            addDeportistaRow(lista, lista.querySelectorAll('[data-rol="deportista-row"]').length);
        });

        addDeportistaRow(lista, 0);
        return card;
    }

    function addDeportistaRow(lista, idx) {
        const row = document.createElement('div');
        row.className = 'border rounded p-2 deportista-row';
        row.dataset.rol = 'deportista-row';
        row.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small mb-1">Nombre completo del/la deportista <span data-rol="num">#${idx + 1}</span> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" data-field-dep="nombre_completo" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Fecha de nacimiento <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm" data-field-dep="fecha_nacimiento" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Número de documento <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" data-field-dep="documento" required>
                </div>
                <div class="col-12 col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-action="del-deportista" title="Eliminar">×</button>
                </div>
            </div>
        `;
        row.querySelector('[data-action="del-deportista"]').addEventListener('click', () => {
            const total = lista.querySelectorAll('[data-rol="deportista-row"]').length;
            if (total <= 1) {
                alert('Debe mantener al menos un deportista en el equipo.');
                return;
            }
            row.remove();
            renumerarFilas(lista);
        });
        lista.appendChild(row);
        renumerarFilas(lista);
    }

    function renumerarFilas(lista) {
        lista.querySelectorAll('[data-rol="deportista-row"]').forEach((r, i) => {
            const num = r.querySelector('[data-rol="num"]');
            if (num) num.textContent = '#' + (i + 1);
        });
    }

    function leerDeportistaRow(row) {
        return {
            nombre_completo: (row.querySelector('[data-field-dep="nombre_completo"]')?.value || '').trim(),
            fecha_nacimiento: (row.querySelector('[data-field-dep="fecha_nacimiento"]')?.value || '').trim(),
            documento: (row.querySelector('[data-field-dep="documento"]')?.value || '').trim(),
        };
    }

    function deportistaRowActiva(d) {
        return !!(d.nombre_completo || d.fecha_nacimiento || d.documento);
    }

    function recolectarDeportistasActivos(card) {
        return Array.from(card.querySelectorAll('[data-rol="deportista-row"]'))
            .map(leerDeportistaRow)
            .filter(deportistaRowActiva);
    }

    function validarDeportistasEquipo(card, k) {
        const rows = card.querySelectorAll('[data-rol="deportista-row"]');
        let activos = 0;
        for (let j = 0; j < rows.length; j++) {
            const d = leerDeportistaRow(rows[j]);
            if (!deportistaRowActiva(d)) continue;
            activos++;
            const n = j + 1;
            if (!d.nombre_completo) {
                return `Equipo ${k}, deportista #${n}: ingrese el nombre completo.`;
            }
            if (!d.fecha_nacimiento) {
                return `Equipo ${k}, deportista #${n}: ingrese la fecha de nacimiento.`;
            }
            if (!d.documento) {
                return `Equipo ${k}, deportista #${n}: ingrese el número de documento.`;
            }
        }
        if (activos === 0) {
            return `Equipo ${k}: registre al menos un deportista con todos sus datos.`;
        }
        return null;
    }

    function recolectarEquipos() {
        const cards = contenedorEquipos.querySelectorAll('.equipo-card');
        return Array.from(cards).map((card) => {
            const get = (sel) => (card.querySelector(`[data-field="${sel}"]`)?.value || '').trim();
            const deportistas = recolectarDeportistasActivos(card);
            return {
                nombre_equipo: get('nombre_equipo'),
                disciplina: get('disciplina'),
                rama: get('rama'),
                categoria: get('categoria'),
                entrenador_nombre: get('entrenador_nombre'),
                entrenador_documento: get('entrenador_documento'),
                entrenador_contacto: get('entrenador_contacto'),
                asistente_nombre: null,
                asistente_documento: null,
                asistente_contacto: null,
                deportistas,
            };
        });
    }

    // ---- Inscripción existente (solo lectura en modal) ----
    function mostrarSpinnerExistente() {
        barEquiposInscritos.classList.remove('d-none');
        badgeEquiposExistentes.textContent = '...';
        textoEquiposExistentes.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Consultando equipos inscritos...';
        if (btnVerEquiposInscritos) {
            btnVerEquiposInscritos.style.display = 'none';
            btnVerEquiposInscritos.disabled = true;
        }
        listaEquiposExistentes.innerHTML = '';
    }

    function verificarInscripcionExistente() {
        inscripcionExistenteCache = null;
        if (!responsableActual || !eventoSelect.value) {
            actualizarEnvio();
            return Promise.resolve(null);
        }
        mostrarSpinnerExistente();
        return ajax('get-inscripcion-equipos-existente.php', 'POST', {
            responsable_documento: responsableActual.documento,
            curso_id: eventoSelect.value,
            tipo_id: tipoEventoActual
        }).then((res) => {
            if (res && res.success && res.exists) {
                inscripcionExistenteCache = res;
                mostrarInscripcionExistente(res);
                return res;
            }
            limpiarInscripcionExistente();
            actualizarEnvio();
            return null;
        }).catch(() => {
            limpiarInscripcionExistente();
            actualizarEnvio();
            return null;
        });
    }

    function buildEquipoVisualHtml(eq, idx) {
        const deportistasRows = (eq.deportistas || []).map((d, i) => `
            <tr>
                <td>${i + 1}</td>
                <td>${esc(d.nombre_completo)}</td>
                <td>${esc(d.documento)}</td>
                <td>${esc(formatFechaDDMMYYYY(d.fecha_nacimiento))}</td>
            </tr>
        `).join('');
        return `
            <div class="card mb-3 equipo-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Equipo ${idx + 1}: ${esc(eq.nombre_equipo)}</h6>
                    <span class="badge text-bg-secondary">${eq.disciplina ? esc(eq.disciplina) + ' · ' : ''}${esc(eq.rama)} / ${esc(eq.categoria)}</span>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-2 small">
                        <div class="col-md-12"><strong>Entrenador:</strong> ${esc(eq.entrenador_nombre) || '-'}<br>
                            <span class="text-muted">Doc: ${esc(eq.entrenador_documento) || '-'} · Cel: ${esc(eq.entrenador_contacto) || '-'}</span>
                        </div>
                    </div>
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Deportista</th><th>Documento</th><th>F. nacimiento</th></tr>
                        </thead>
                        <tbody>${deportistasRows || '<tr><td colspan="4" class="text-muted small">Sin deportistas registrados.</td></tr>'}</tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function mostrarInscripcionExistente(res) {
        const totalExistentes = parseInt(res.total_equipos || (res.equipos || []).length || 0, 10);
        const disponibles = Math.max(0, MAX_EQUIPOS - totalExistentes);

        const equiposHtml = (res.equipos || []).map(buildEquipoVisualHtml).join('');
        listaEquiposExistentes.innerHTML = equiposHtml || '<p class="text-muted small mb-0">Sin equipos registrados.</p>';
        badgeEquiposExistentes.textContent = totalExistentes + ' / ' + MAX_EQUIPOS;
        textoEquiposExistentes.textContent = disponibles > 0
            ? `Ya tiene ${totalExistentes} equipo(s) inscrito(s). Puede agregar hasta ${disponibles} más en este envío.`
            : `Ya alcanzó el máximo de ${MAX_EQUIPOS} equipos para este evento. No es posible agregar más.`;
        barEquiposInscritos.classList.remove('d-none');
        if (btnVerEquiposInscritos) {
            btnVerEquiposInscritos.style.display = totalExistentes > 0 ? '' : 'none';
            btnVerEquiposInscritos.disabled = totalExistentes <= 0;
        }

        contenedorEquipos.innerHTML = '';
        actualizarOpcionesCantidad(disponibles);
        cantidadEquipos.disabled = disponibles <= 0;
        actualizarEnvio();
    }

    function limpiarInscripcionExistente() {
        inscripcionExistenteCache = null;
        cantidadEquipos.disabled = false;
        barEquiposInscritos.classList.add('d-none');
        listaEquiposExistentes.innerHTML = '';
        if (btnVerEquiposInscritos) {
            btnVerEquiposInscritos.style.display = 'none';
            btnVerEquiposInscritos.disabled = true;
        }
        actualizarOpcionesCantidad(MAX_EQUIPOS);
    }

    function abrirModalEquiposInscritos() {
        if (!modalEquiposInscritosEl || !listaEquiposExistentes.innerHTML.trim()) return;
        modalEquiposInscritos = modalEquiposInscritos || new bootstrap.Modal(modalEquiposInscritosEl);
        modalEquiposInscritos.show();
    }

    // ---- Modal responsable ----
    function abrirModalResponsable(doc) {
        if (!modalResponsableEl) return;
        document.getElementById('modalResponsableDocumento').value = doc;
        document.getElementById('modalResponsableDocumentoInicial').value = doc;
        cargarDepartamentosModal();
        modalResponsable = modalResponsable || new bootstrap.Modal(modalResponsableEl);
        modalResponsable.show();
    }

    function cargarDepartamentosModal() {
        const sel = document.getElementById('modalResponsableDepto');
        const ciudadSel = document.getElementById('modalResponsableCiudad');
        const sp = document.querySelector('.spinner-select-depto');
        if (ciudadSel) ciudadSel.innerHTML = '<option value="">-- Seleccione departamento primero --</option>';
        if (!sel) return;
        sel.innerHTML = '<option value="">-- Cargando... --</option>';
        sel.disabled = true;
        if (sp) sp.classList.remove('d-none');
        ajax('get-departamentos.php', 'GET').then((res) => {
            sel.innerHTML = '<option value="">-- Seleccione --</option>';
            (res.departamentos || []).forEach((d) => {
                const opt = document.createElement('option');
                opt.value = d.Depto || '';
                opt.textContent = d.Nombre_Dpto || '';
                sel.appendChild(opt);
            });
        }).catch(() => {
            sel.innerHTML = '<option value="">-- Error al cargar --</option>';
        }).finally(() => {
            sel.disabled = false;
            if (sp) sp.classList.add('d-none');
        });
    }

    document.getElementById('modalResponsableDepto')?.addEventListener('change', function () {
        const depto = this.value;
        const ciudadSel = document.getElementById('modalResponsableCiudad');
        const sp = document.querySelector('.spinner-select-ciudad');
        if (!ciudadSel) return;
        if (!depto) {
            ciudadSel.innerHTML = '<option value="">-- Seleccione departamento primero --</option>';
            return;
        }
        ciudadSel.innerHTML = '<option value="">-- Cargando... --</option>';
        ciudadSel.disabled = true;
        if (sp) sp.classList.remove('d-none');
        ajax('get-ciudades.php', 'GET', { depto }).then((res) => {
            ciudadSel.innerHTML = '<option value="">-- Seleccione --</option>';
            (res.ciudades || []).forEach((c) => {
                const opt = document.createElement('option');
                opt.value = c.Ciudad || '';
                opt.textContent = c.Nombre_Ciudad || '';
                ciudadSel.appendChild(opt);
            });
        }).catch(() => {
            ciudadSel.innerHTML = '<option value="">-- Error al cargar --</option>';
        }).finally(() => {
            ciudadSel.disabled = false;
            if (sp) sp.classList.add('d-none');
        });
    });

    formResponsable.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        const doc = document.getElementById('modalResponsableDocumento').value;
        const docInicial = document.getElementById('modalResponsableDocumentoInicial').value;
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
            departamento: fd.get('departamento') || null,
            ciudad: fd.get('ciudad') || null,
            direccion: fd.get('direccion') || null,
        };
        const submitBtn = this.querySelector('button[type="submit"]');
        spinner(submitBtn, true);
        fetch(basePath + 'ajax/guardar-responsable.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', ...getAuthHeaders() },
            body: JSON.stringify(data)
        }).then((r) => r.json()).then((res) => {
            if (!res.success) {
                alert(res.error || 'Error al guardar responsable.');
                return;
            }
            responsableActual = res.responsable;
            docResponsable.value = res.responsable.documento;
            docResponsable.classList.remove('is-invalid');
            docResponsable.classList.add('is-valid');
            showMsg(responsableInfo, `Responsable: ${res.responsable.nombre || res.responsable.documento}`, false);
            hideMsg(responsableError);
            cardEvento.style.display = '';
            modalResponsable && modalResponsable.hide();
            formResponsable.reset();
            actualizarEnvio();
        }).catch(() => {
            alert('Error de conexión.');
        }).finally(() => {
            spinner(submitBtn, false);
        });
    });

    // ---- Reset general ----
    function resetFormulario() {
        form.reset();
        responsableActual = null;
        limpiarInscripcionExistente();
        cardEvento.style.display = 'none';
        contenedorEquipos.innerHTML = '';
        eventoInfo.textContent = '';
        hideMsg(responsableError);
        responsableInfo.classList.add('d-none');
        responsableInfo.textContent = '';
        docResponsable.classList.remove('is-valid', 'is-invalid');
        contenido.style.display = 'none';
        resultadoFinal.classList.add('d-none');
        btnEnviar.disabled = true;
    }

    // ---- Listeners ----
    checkPoliticas.addEventListener('change', () => {
        contenido.style.display = checkPoliticas.checked ? '' : 'none';
        if (!checkPoliticas.checked) {
            responsableActual = null;
            cardEvento.style.display = 'none';
            contenedorEquipos.innerHTML = '';
            btnEnviar.disabled = true;
        } else if (eventosCache.length === 0) {
            cargarEventos();
        }
    });

    const pageLoading = document.getElementById('pageEquiposLoading');

    function setPageLoading(show, mensaje) {
        if (!pageLoading) return;
        if (mensaje) {
            const p = pageLoading.querySelector('p.fw-semibold');
            if (p) p.textContent = mensaje;
        }
        pageLoading.classList.toggle('d-none', !show);
        pageLoading.setAttribute('aria-busy', show ? 'true' : 'false');
    }

    function validarResponsableDocumento(documento) {
        hideMsg(responsableError);
        responsableInfo.classList.add('d-none');
        responsableActual = null;
        cardEvento.style.display = 'none';
        contenedorEquipos.innerHTML = '';
        limpiarInscripcionExistente();
        btnEnviar.disabled = true;

        if (!documento) {
            showMsg(responsableError, 'Ingrese el documento del responsable.', true);
            return Promise.resolve(false);
        }

        spinner(btnValidarResp, true);
        return ajax('validar-responsable.php', 'POST', {
            documento,
            participante_id: documento
        }).then((res) => {
            if (!res.success) throw new Error(res.error || 'Error al validar responsable.');
            if (!res.exists) {
                abrirModalResponsable(documento);
                showMsg(responsableInfo, 'No encontramos este responsable. Complete sus datos en el formulario.', true);
                return false;
            }
            responsableActual = res.responsable;
            docResponsable.value = res.responsable.documento || documento;
            docResponsable.classList.remove('is-invalid');
            docResponsable.classList.add('is-valid');
            showMsg(responsableInfo, `Responsable: ${res.responsable.nombre || res.responsable.documento}`, false);
            cardEvento.style.display = '';
            if (eventosCache.length === 0) {
                return cargarEventos().then(() => {
                    actualizarEnvio();
                    return eventoSelect.value ? verificarInscripcionExistente().then(() => true) : true;
                });
            }
            actualizarEnvio();
            return eventoSelect.value ? verificarInscripcionExistente().then(() => true) : true;
        }).catch((err) => {
            showMsg(responsableError, err.message || 'Error de conexión.', true);
            return false;
        }).finally(() => {
            spinner(btnValidarResp, false);
        });
    }

    btnValidarResp.addEventListener('click', () => {
        validarResponsableDocumento(docResponsable.value.trim());
    });

    docResponsable.addEventListener('input', () => {
        responsableActual = null;
        cardEvento.style.display = 'none';
        contenedorEquipos.innerHTML = '';
        limpiarInscripcionExistente();
        btnEnviar.disabled = true;
        responsableInfo.classList.add('d-none');
    });

    eventoSelect.addEventListener('change', () => {
        const id = eventoSelect.value;
        const item = eventosCache.find((it) => String(it.id) === String(id));
        eventoInfo.textContent = item ? (item.fecha_inicio || '') + (item.fecha_fin ? ' al ' + item.fecha_fin : '') : '';
        limpiarInscripcionExistente();
        contenedorEquipos.innerHTML = '';
        cantidadEquipos.value = '';
        actualizarEnvio();
        if (responsableActual && id) verificarInscripcionExistente();
    });

    cantidadEquipos.addEventListener('change', () => {
        const cant = parseInt(cantidadEquipos.value, 10);
        renderEquipos(cant);
    });

    btnVerEquiposInscritos && btnVerEquiposInscritos.addEventListener('click', abrirModalEquiposInscritos);

    // ---- Submit ----
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        hideMsg(responsableError);
        resultadoFinal.classList.add('d-none');

        if (!checkPoliticas.checked) {
            alert('Debe aceptar la autorización para el tratamiento de datos personales.');
            return;
        }
        if (!responsableActual) {
            showMsg(responsableError, 'Valide o registre el responsable antes de continuar.', true);
            return;
        }
        if (!eventoSelect.value) {
            alert('Seleccione un evento.');
            return;
        }
        if (inscripcionExistenteCache && inscripcionExistenteCache.total_equipos >= MAX_EQUIPOS) {
            alert(`Este responsable ya alcanzó el máximo de ${MAX_EQUIPOS} equipos para este evento.`);
            return;
        }
        const cant = parseInt(cantidadEquipos.value, 10);
        if (!cant || cant < 1) {
            alert('Seleccione la cantidad de equipos a inscribir.');
            return;
        }
        if (inscripcionExistenteCache) {
            const disponibles = MAX_EQUIPOS - (inscripcionExistenteCache.total_equipos || 0);
            if (cant > disponibles) {
                alert(`Solo puede registrar hasta ${disponibles} equipo(s) adicional(es).`);
                return;
            }
        }

        const cards = contenedorEquipos.querySelectorAll('.equipo-card');

        // Documentos ya registrados en inscripciones previas del mismo responsable+evento
        const docsExistentes = new Set();
        if (inscripcionExistenteCache && Array.isArray(inscripcionExistenteCache.equipos)) {
            inscripcionExistenteCache.equipos.forEach((eq) => {
                (eq.deportistas || []).forEach((d) => {
                    const docu = String(d.documento || '').trim();
                    if (docu) docsExistentes.add(docu);
                });
            });
        }
        const docsVistos = new Set(docsExistentes);

        for (let i = 0; i < cards.length; i++) {
            const card = cards[i];
            const k = i + 1;
            const get = (sel) => (card.querySelector(`[data-field="${sel}"]`)?.value || '').trim();
            const nombreEquipo = get('nombre_equipo');
            const disciplina = get('disciplina');
            const rama = get('rama');
            const categoria = get('categoria');
            const entrenadorNombre = get('entrenador_nombre');
            const entrenadorDocumento = get('entrenador_documento');
            const entrenadorContacto = get('entrenador_contacto');

            if (!nombreEquipo) return alert(`Equipo ${k}: ingrese el nombre del equipo.`);
            if (!disciplina) return alert(`Equipo ${k}: seleccione la disciplina.`);
            if (!rama) return alert(`Equipo ${k}: seleccione la rama.`);
            if (!categoria) return alert(`Equipo ${k}: seleccione la categoría.`);
            if (!entrenadorNombre) return alert(`Equipo ${k}: ingrese el nombre del entrenador.`);
            if (!entrenadorDocumento) return alert(`Equipo ${k}: ingrese el documento del entrenador.`);
            if (!entrenadorContacto) return alert(`Equipo ${k}: ingrese el celular del entrenador.`);
            if (!TEL_PATTERN.test(entrenadorContacto)) {
                return alert(`Equipo ${k}: el celular del entrenador debe tener 10 dígitos y empezar por 3.`);
            }

            const errDep = validarDeportistasEquipo(card, k);
            if (errDep) return alert(errDep);

            const deportistas = recolectarDeportistasActivos(card);
            for (let j = 0; j < deportistas.length; j++) {
                const docu = String(deportistas[j].documento || '').trim();
                if (docsVistos.has(docu)) {
                    if (docsExistentes.has(docu)) {
                        return alert(`Equipo ${k}, deportista #${j + 1}: el documento ${docu} ya está registrado en un equipo previamente inscrito.`);
                    }
                    return alert(`Equipo ${k}, deportista #${j + 1}: el documento ${docu} está repetido en esta inscripción.`);
                }
                docsVistos.add(docu);
            }
        }

        const equipos = recolectarEquipos();

        spinner(btnEnviar, true);
        ajax('guardar-inscripcion-equipos.php', 'POST', {
            responsable_documento: responsableActual.documento,
            curso_id: eventoSelect.value,
            politicas: 'Si',
            equipos
        }).then((res) => {
            if (!res.success) throw new Error(res.error || 'No fue posible registrar la inscripción.');
            const nombreEvento = eventosCache.find((it) => String(it.id) === String(eventoSelect.value))?.nombre || res.evento || '';

            const equiposExistentesPrev = (inscripcionExistenteCache && Array.isArray(inscripcionExistenteCache.equipos))
                ? inscripcionExistenteCache.equipos
                : [];
            const existentesHtml = equiposExistentesPrev.map((e, i) => `
                <li>Equipo ${i + 1}: ${esc(e.nombre_equipo)} <span class="text-muted">(${esc(e.disciplina || '')}${e.disciplina ? ' / ' : ''}${esc(e.rama)} / ${esc(e.categoria)})</span> <span class="badge text-bg-secondary ms-1">Previo</span></li>
            `).join('');
            const offset = equiposExistentesPrev.length;
            const nuevosHtml = equipos.map((e, i) => `
                <li>Equipo ${offset + i + 1}: ${esc(e.nombre_equipo)} <span class="text-muted">(${esc(e.disciplina || '')}${e.disciplina ? ' / ' : ''}${esc(e.rama)} / ${esc(e.categoria)})</span> <span class="badge text-bg-success ms-1">Nuevo</span></li>
            `).join('');
            const totalEquipos = res.total_equipos || (offset + equipos.length);
            const equiposExistentesCount = res.equipos_existentes != null ? res.equipos_existentes : offset;
            const equiposNuevosCount = res.equipos_nuevos != null ? res.equipos_nuevos : equipos.length;

            const detalleHtml = equiposExistentesCount > 0
                ? `<p class="mb-2 small text-muted">Ya había <strong>${esc(equiposExistentesCount)}</strong> equipo(s) inscrito(s) previamente. Se agregaron <strong>${esc(equiposNuevosCount)}</strong>.</p>`
                : '';
            const emailHtml = res.emailEnviado
                ? '<p class="small text-muted mb-0">Se envió un correo de confirmación al responsable con el listado de equipos.</p>'
                : res.emailError
                    ? `<p class="small text-warning mb-0">La inscripción quedó registrada, pero el correo no se pudo enviar: ${esc(res.emailError)}</p>`
                    : res.emailPendiente
                        ? '<p class="small text-muted mb-0">El correo de confirmación se está enviando al responsable. Si no lo recibe en unos minutos, contáctenos.</p>'
                        : '';
            modalExitoBody.innerHTML = `
                <p class="mb-2"><strong>Evento:</strong> ${esc(nombreEvento)}</p>
                <p class="mb-2"><strong>Total equipos inscritos para este responsable:</strong> ${esc(totalEquipos)}</p>
                ${detalleHtml}
                <ul class="mb-3 ps-3 small">${existentesHtml}${nuevosHtml}</ul>
                ${emailHtml}
            `;
            modalExito = modalExito || new bootstrap.Modal(modalExitoEl);
            modalExito.show();
        }).catch((err) => {
            resultadoFinal.className = 'alert alert-danger';
            resultadoFinal.textContent = err.message || 'Error al registrar la inscripción.';
            resultadoFinal.classList.remove('d-none');
        }).finally(() => {
            spinner(btnEnviar, false);
        });
    });

    btnCerrarExito && btnCerrarExito.addEventListener('click', () => {
        modalExito && modalExito.hide();
    });

    modalExitoEl && modalExitoEl.addEventListener('hidden.bs.modal', () => {
        resetFormulario();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    actualizarOpcionesCantidad(MAX_EQUIPOS);

    if (PREFILL_RESPONSABLE) {
        setPageLoading(true, 'Preparando inscripción de equipos...');
        checkPoliticas.checked = true;
        contenido.style.display = '';
        docResponsable.value = PREFILL_RESPONSABLE;

        Promise.all([cargarConfigCopaVegas(), cargarEventos()])
            .then(() => {
                setPageLoading(true, 'Validando responsable...');
                docResponsable.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return validarResponsableDocumento(PREFILL_RESPONSABLE);
            })
            .finally(() => {
                setPageLoading(false);
                const cardResp = docResponsable.closest('.card');
                if (cardResp) {
                    cardResp.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    docResponsable.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
    } else {
        Promise.all([cargarConfigCopaVegas(), cargarEventos()]);
    }
})();
