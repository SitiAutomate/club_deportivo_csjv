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
        return { Authorization: 'Bearer ' + token, 'X-CSRF-Token': token };
    };

    const $ = (sel) => document.querySelector(sel);
    const escapeHtml = (s) => String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    let participanteActual = null;
    let cursosData = [];
    let categorias = {};

    const docInput = $('#docParticipante');
    const btnValidar = $('#btnValidarParticipante');
    const info = $('#participanteInfo');
    const cardCursos = $('#cardCursos');
    const lista = $('#listaCursos');
    const btnEnviar = $('#btnEnviar');
    const form = $('#formReservaCupo');
    const msgExito = $('#msgExito');

    function ajax(path, method, data) {
        const opts = { method: method || 'GET', headers: { ...getAuthHeaders() } };
        let url = basePath + 'ajax/' + path.replace(/^\//, '');
        if (method === 'POST') {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(data || {});
        } else if (data) {
            const params = new URLSearchParams(data);
            url += (url.includes('?') ? '&' : '?') + params.toString();
        }
        return fetch(url, opts).then((r) => r.json());
    }

    function validarParticipante() {
        const doc = (docInput?.value || '').trim();
        if (!doc) {
            alert('Ingrese el documento del participante.');
            return;
        }
        info.textContent = 'Validando...';
        info.className = 'mt-2 mb-0 small text-muted';
        cardCursos.style.display = 'none';
        cursosData = [];
        syncBtnEnviar();
        lista.innerHTML = '';

        ajax('validar-participante.php', 'POST', { documento: doc })
            .then((res) => {
                if (!res.success) {
                    info.textContent = res.error || 'No fue posible validar.';
                    info.className = 'mt-2 mb-0 small text-danger';
                    return;
                }
                if (!res.exists || !res.participante) {
                    info.textContent = 'Participante no encontrado. Debe estar registrado previamente.';
                    info.className = 'mt-2 mb-0 small text-danger';
                    return;
                }
                participanteActual = res.participante;
                info.textContent = (res.participante.nombre || doc) + ' — validado';
                info.className = 'mt-2 mb-0 small text-success';
                cargarCursos(doc);
            })
            .catch(() => {
                info.textContent = 'Error de conexión al validar.';
                info.className = 'mt-2 mb-0 small text-danger';
            });
    }

    function syncBtnEnviar() {
        if (!btnEnviar) return;
        const pendientes = cursosData.some((c) => !c.ya_reservado && !c.ya_no_continua);
        if (!cursosData.length || !pendientes) {
            btnEnviar.style.display = 'none';
            btnEnviar.disabled = true;
            return;
        }
        btnEnviar.style.display = '';
        btnEnviar.disabled = false;
    }

    function cargarCursos(doc) {
        lista.innerHTML = '<p class="text-muted small mb-0">Cargando cursos...</p>';
        cardCursos.style.display = 'block';
        syncBtnEnviar();
        ajax('get-reserva-cupo-cursos.php', 'GET', { documento: doc })
            .then((res) => {
                if (!res.success) {
                    lista.innerHTML = '<p class="text-danger mb-0">' + escapeHtml(res.error || 'No se pudieron cargar los cursos.') + '</p>';
                    cursosData = [];
                    syncBtnEnviar();
                    return;
                }
                categorias = res.categorias || {};
                cursosData = res.cursos || [];
                if (!cursosData.length) {
                    lista.innerHTML = '<p class="text-muted mb-0">No se encontraron cursos activos para el periodo de referencia.</p>';
                    syncBtnEnviar();
                    return;
                }
                renderCursos();
                syncBtnEnviar();
            })
            .catch(() => {
                lista.innerHTML = '<p class="text-danger mb-0">Error de conexión al cargar cursos.</p>';
                cursosData = [];
                syncBtnEnviar();
            });
    }

    function optionsCategorias() {
        let html = '<option value="">-- Seleccione la categoría --</option>';
        Object.keys(categorias).forEach((k) => {
            html += '<option value="' + escapeHtml(k) + '">' + escapeHtml(categorias[k]) + '</option>';
        });
        return html;
    }

    function renderCursos() {
        let html = '';
        cursosData.forEach((c, i) => {
            const yaRespondido = !!(c.ya_reservado || c.ya_no_continua);
            html += '<div class="reserva-row" data-idx="' + i + '">';
            html += '<div class="row g-3 align-items-start">';
            html += '<div class="col-md-5"><div class="small text-muted">Curso actual</div>';
            html += '<div class="fw-semibold">' + escapeHtml(c.curso_actual_nombre) + '</div>';
            if (c.sede) html += '<div class="small text-muted">' + escapeHtml(c.sede) + '</div>';
            html += '</div>';
            html += '<div class="col-md-4"><div class="small text-muted">Curso recomendado 2027</div>';
            html += '<div class="curso-recomendado">' + escapeHtml(c.curso_recomendado_nombre) + '</div></div>';
            html += '<div class="col-md-3"><div class="small text-muted mb-1">Acción</div>';
            if (c.ya_reservado) {
                html += '<span class="badge text-bg-success">Ya reservado</span>';
            } else if (c.ya_no_continua) {
                html += '<span class="badge text-bg-secondary">No continúa</span>';
            } else {
                html += '<div class="btn-group reserva-acciones w-100" role="group">';
                html += '<input type="radio" class="btn-check" name="accion_' + i + '" id="acc_si_' + i + '" value="continuar">';
                html += '<label class="btn btn-outline-success btn-sm" for="acc_si_' + i + '">Continúo</label>';
                html += '<input type="radio" class="btn-check" name="accion_' + i + '" id="acc_no_' + i + '" value="no_continuar">';
                html += '<label class="btn btn-outline-danger btn-sm" for="acc_no_' + i + '">No continúo</label>';
                html += '</div>';
            }
            html += '</div></div>';
            if (!yaRespondido) {
                html += '<div class="wrap-no-continua mt-3" id="wrapNo_' + i + '">';
                html += '<div class="row g-3">';
                html += '<div class="col-md-6"><label class="form-label fw-bold">Categoría</label>';
                html += '<select class="form-select form-select-sm" id="cat_' + i + '">' + optionsCategorias() + '</select></div>';
                html += '</div></div>';
            }
            html += '</div>';
        });
        lista.innerHTML = html;

        cursosData.forEach((c, i) => {
            if (c.ya_reservado || c.ya_no_continua) return;
            const si = document.getElementById('acc_si_' + i);
            const no = document.getElementById('acc_no_' + i);
            const wrap = document.getElementById('wrapNo_' + i);
            const sync = () => {
                if (wrap) wrap.style.display = no?.checked ? 'block' : 'none';
            };
            si?.addEventListener('change', sync);
            no?.addEventListener('change', sync);
        });
    }

    function recolectarDecisiones() {
        const out = [];
        for (let i = 0; i < cursosData.length; i++) {
            const c = cursosData[i];
            if (c.ya_reservado || c.ya_no_continua) continue;
            const checked = document.querySelector('input[name="accion_' + i + '"]:checked');
            if (!checked) {
                alert('Indique Continúo o No continúo para: ' + c.curso_actual_nombre);
                return null;
            }
            const accion = checked.value;
            const item = {
                accion,
                curso_actual_id: c.curso_actual_id,
                curso_recomendado_id: c.curso_recomendado_id,
                curso_recomendado_nombre: c.curso_recomendado_nombre,
                sede: c.sede,
                transporte: c.transporte,
                responsable_documento: c.responsable_documento || participanteActual?.responsable_documento || '',
            };
            if (accion === 'no_continuar') {
                item.categoria = document.getElementById('cat_' + i)?.value || '';
                if (!item.categoria) {
                    alert('Seleccione la categoría para: ' + c.curso_actual_nombre);
                    return null;
                }
            }
            out.push(item);
        }
        return out;
    }

    btnValidar?.addEventListener('click', validarParticipante);
    docInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            validarParticipante();
        }
    });

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        if (!participanteActual) {
            alert('Valide el participante primero.');
            return;
        }
        const decisiones = recolectarDecisiones();
        if (!decisiones) return;
        if (!decisiones.length) {
            alert('No hay cursos pendientes por responder.');
            return;
        }

        const btnTxt = btnEnviar.querySelector('.btn-text');
        const sp = btnEnviar.querySelector('.spinner-border');
        btnEnviar.disabled = true;
        if (btnTxt) btnTxt.classList.add('d-none');
        if (sp) sp.classList.remove('d-none');

        ajax('guardar-reserva-cupo.php', 'POST', {
            participante_id: participanteActual.documento || participanteActual.id,
            decisiones,
        })
            .then((res) => {
                if (!res.success) {
                    alert(res.error || 'No fue posible guardar las respuestas.');
                    return;
                }
                let msg = res.mensaje || 'Respuestas guardadas.';
                if (res.emailEnviado) {
                    msg += ' Se envió un correo de confirmación al responsable.';
                } else if (res.emailError) {
                    msg += ' El correo no se pudo enviar: ' + res.emailError;
                }
                if (res.errores && res.errores.length) {
                    msg += ' Avisos: ' + res.errores.join(' ');
                }
                if (msgExito) {
                    msgExito.textContent = msg;
                    msgExito.classList.remove('d-none');
                }
                alert(msg);
                (res.creadas || []).forEach((creada) => {
                    const match = cursosData.find((c) =>
                        String(c.curso_recomendado_id) === String(creada.curso_id)
                    );
                    if (!match) return;
                    if (creada.accion === 'continuar') match.ya_reservado = true;
                    else match.ya_no_continua = true;
                });
                renderCursos();
                syncBtnEnviar();
                cargarCursos(participanteActual.documento || participanteActual.id);
            })
            .catch(() => alert('Error de conexión'))
            .finally(() => {
                if (btnTxt) btnTxt.classList.remove('d-none');
                if (sp) sp.classList.add('d-none');
                syncBtnEnviar();
            });
    });
})();
