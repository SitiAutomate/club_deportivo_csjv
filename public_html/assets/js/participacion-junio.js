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
            Authorization: 'Bearer ' + token,
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
                Object.keys(data).forEach((k) => {
                    if (data[k] != null && data[k] !== '') params.set(k, data[k]);
                });
                path += (path.includes('?') ? '&' : '?') + params.toString();
            }
        }
        return fetch(basePath + 'ajax/' + path.replace(/^\//, ''), opts).then((r) => r.json());
    };

    const form = document.getElementById('formParticipacionJunio');
    const checkPoliticas = document.getElementById('checkPoliticas');
    const contenido = document.getElementById('contenidoFormulario');
    const documentoInput = document.getElementById('documentoParticipante');
    const btnValidar = document.getElementById('btnValidarParticipante');
    const participanteResumen = document.getElementById('participanteResumen');
    const participanteError = document.getElementById('participanteError');
    const hintValidarDocumento = document.getElementById('hintValidarDocumento');
    const cardCursos = document.getElementById('cardCursos');
    const listaCursos = document.getElementById('listaCursos');
    const sinCursos = document.getElementById('sinCursos');
    const badgeMes = document.getElementById('badgeMesConsulta');
    const btnEnviar = document.getElementById('btnEnviar');
    const resultadoFinal = document.getElementById('resultadoFinal');

    let participanteValidado = null;
    let cursosDisponibles = [];

    function getParticipara() {
        const checked = document.querySelector('input[name="participaraVacaciones"]:checked');
        return checked ? checked.value : '';
    }

    function setValidarSpinner(btn, show) {
        if (!btn) return;
        const txt = btn.querySelector('.btn-text');
        const sp = btn.querySelector('.spinner-border');
        if (txt) txt.classList.toggle('d-none', show);
        if (sp) sp.classList.toggle('d-none', !show);
        btn.disabled = show;
    }

    function mostrarError(el, mensaje) {
        el.textContent = mensaje;
        el.classList.remove('d-none');
    }

    function ocultarError(el) {
        el.textContent = '';
        el.classList.add('d-none');
    }

    function resetValidacion() {
        participanteValidado = null;
        cursosDisponibles = [];
        if (participanteResumen) participanteResumen.classList.add('d-none');
        cardCursos.style.display = 'none';
        listaCursos.innerHTML = '';
        if (sinCursos) sinCursos.classList.add('d-none');
        actualizarEnvio();
    }

    function actualizarHintValidar() {
        if (!hintValidarDocumento) return;
        if (getParticipara() === 'No' && !participanteValidado) {
            hintValidarDocumento.textContent = 'Ingrese el documento y presione Validar para habilitar la confirmación.';
            hintValidarDocumento.classList.remove('d-none');
            return;
        }
        hintValidarDocumento.textContent = '';
        hintValidarDocumento.classList.add('d-none');
    }

    function actualizarEnvio() {
        const participara = getParticipara();
        actualizarHintValidar();

        if (!participara || !participanteValidado) {
            btnEnviar.disabled = true;
            return;
        }

        if (participara === 'No') {
            btnEnviar.disabled = cursosDisponibles.length === 0;
            return;
        }

        if (participara === 'Si') {
            const seleccionados = listaCursos.querySelectorAll('input[type="checkbox"][data-curso-id]:checked:not(:disabled)');
            btnEnviar.disabled = seleccionados.length === 0;
            return;
        }

        btnEnviar.disabled = true;
    }

    function onParticiparaChange() {
        const val = getParticipara();
        if (val === 'Si') {
            cardCursos.style.display = participanteValidado ? '' : 'none';
        } else {
            cardCursos.style.display = 'none';
            listaCursos.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
                cb.checked = false;
            });
        }
        actualizarEnvio();
    }

    function renderCursos(data) {
        cursosDisponibles = data.cursos || [];
        listaCursos.innerHTML = '';
        badgeMes.textContent = 'Mes consultado: ' + (data.mes_consulta_nombre || data.mes_consulta || '');

        if (!cursosDisponibles.length) {
            sinCursos.classList.remove('d-none');
            cardCursos.style.display = 'none';
            actualizarEnvio();
            return;
        }

        sinCursos.classList.add('d-none');

        cursosDisponibles.forEach((curso) => {
            const id = 'curso_' + curso.id;
            const bloqueado = !!curso.bloqueado_junio;
            const item = document.createElement('div');
            item.className = 'border rounded p-3' + (bloqueado ? ' bg-light' : '');
            item.innerHTML =
                '<div class="form-check">' +
                '<input class="form-check-input" type="checkbox" id="' + id + '" data-curso-id="' + curso.id + '"' + (bloqueado ? ' disabled' : '') + '>' +
                '<label class="form-check-label w-100" for="' + id + '">' +
                '<strong>' + (curso.nombre || curso.id) + '</strong>' +
                (curso.sede ? '<br><span class="text-muted small">Sede: ' + curso.sede + '</span>' : '') +
                (bloqueado ? '<br><span class="text-warning small">' + (curso.motivo_bloqueo || 'Ya registrado para vacaciones.') + '</span>' : '') +
                '</label></div>';
            listaCursos.appendChild(item);
        });

        listaCursos.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
            cb.addEventListener('change', actualizarEnvio);
        });

        onParticiparaChange();
    }

    function validarParticipante() {
        const documento = documentoInput.value.trim();
        if (!documento) {
            return Promise.reject(new Error('Ingrese el documento del participante.'));
        }

        const participara = getParticipara();
        if (participara !== 'Si' && participara !== 'No') {
            return Promise.reject(new Error('Indique si participará en los entrenamientos durante el periodo del 15 al 30 de junio.'));
        }

        return ajax('validar-participante.php', 'POST', { documento })
            .then((res) => {
                if (!res.success) {
                    throw new Error(res.error || 'No fue posible validar el participante.');
                }
                if (!res.exists) {
                    throw new Error('No encontramos un participante registrado con ese documento.');
                }
                participanteValidado = res.participante;
                participanteResumen.textContent = 'Participante: ' + (participanteValidado.nombre || participanteValidado.documento);
                participanteResumen.classList.remove('d-none');
                return ajax('get-participacion-junio-cursos.php', 'POST', { documento: participanteValidado.documento });
            })
            .then((res) => {
                if (!res || !res.success) {
                    throw new Error((res && res.error) || 'No fue posible consultar los cursos.');
                }
                renderCursos(res);
                return res;
            });
    }

    function enviarParticipacion() {
        const participara = getParticipara();
        let cursoIds = [];

        if (participara === 'Si') {
            cursoIds = Array.from(
                listaCursos.querySelectorAll('input[type="checkbox"][data-curso-id]:checked:not(:disabled)')
            ).map((el) => el.getAttribute('data-curso-id'));
            if (!cursoIds.length) {
                return Promise.reject(new Error('Seleccione al menos un curso.'));
            }
        }

        if (participara === 'No' && cursosDisponibles.length === 0) {
            return Promise.reject(new Error('No hay cursos activos del mes actual para registrar el retiro.'));
        }

        btnEnviar.disabled = true;
        btnEnviar.textContent = 'Confirmando participación...';

        return ajax('guardar-participacion-junio.php', 'POST', {
            documento: participanteValidado.documento,
            participara: participara,
            curso_ids: cursoIds,
            politicas: 'Si'
        }).then((res) => {
            if (!res.success) {
                throw new Error(res.error || 'No fue posible confirmar la participación en vacaciones.');
            }

            let html = '<strong>Participación en vacaciones registrada correctamente.</strong>';
            if (res.participara === 'No') {
                html += '<p class="mb-0 mt-2 small">Se registró un retiro temporal por vacaciones en todos los cursos activos del mes actual para el periodo del 15 al 30 de junio. No implica salida definitiva del club deportivo.</p>';
            }
            if (res.creadas && res.creadas.length) {
                html += '<ul class="mb-0 mt-2">';
                res.creadas.forEach((c) => {
                    html += '<li>' + (c.nombre || c.id) + '</li>';
                });
                html += '</ul>';
            }
            if (res.omitidas && res.omitidas.length) {
                html += '<p class="mb-0 mt-2 small">Algunos cursos no se registraron porque ya tenían participación registrada para vacaciones.</p>';
            }
            if (res.emailEnviado) {
                html += '<p class="mb-0 mt-2 small">Se envió un correo de confirmación al responsable registrado.</p>';
            } else if (res.emailError) {
                html += '<p class="mb-0 mt-2 small text-warning">La participación quedó registrada, pero no fue posible enviar el correo: ' + res.emailError + '</p>';
            }

            resultadoFinal.className = 'alert alert-success';
            resultadoFinal.innerHTML = html;
            resultadoFinal.classList.remove('d-none');

            if (participanteValidado) {
                ajax('get-participacion-junio-cursos.php', 'POST', { documento: participanteValidado.documento })
                    .then((refresh) => {
                        if (refresh && refresh.success) renderCursos(refresh);
                    })
                    .catch(() => {});
            }
            return res;
        });
    }

    checkPoliticas.addEventListener('change', () => {
        contenido.style.display = checkPoliticas.checked ? '' : 'none';
        if (!checkPoliticas.checked) {
            resetValidacion();
            document.querySelectorAll('input[name="participaraVacaciones"]').forEach((r) => {
                r.checked = false;
            });
            btnEnviar.disabled = true;
        }
    });

    document.querySelectorAll('input[name="participaraVacaciones"]').forEach((radio) => {
        radio.addEventListener('change', onParticiparaChange);
    });

    if (documentoInput) {
        documentoInput.addEventListener('input', resetValidacion);
    }

    if (btnValidar) {
        btnValidar.addEventListener('click', () => {
            ocultarError(participanteError);
            ocultarError(resultadoFinal);
            resetValidacion();

            setValidarSpinner(btnValidar, true);
            validarParticipante()
                .catch((err) => {
                    mostrarError(participanteError, err.message || 'Ocurrió un error al validar.');
                })
                .finally(() => {
                    setValidarSpinner(btnValidar, false);
                });
        });
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        ocultarError(resultadoFinal);
        ocultarError(participanteError);

        if (!checkPoliticas.checked) {
            form.classList.add('was-validated');
            return;
        }

        const participara = getParticipara();
        if (participara !== 'Si' && participara !== 'No') {
            mostrarError(participanteError, 'Indique si participará en los entrenamientos durante el periodo del 15 al 30 de junio.');
            return;
        }

        if (!participanteValidado) {
            mostrarError(participanteError, 'Valide el documento del participante antes de continuar.');
            return;
        }

        enviarParticipacion()
            .catch((err) => {
                resultadoFinal.className = 'alert alert-danger';
                mostrarError(resultadoFinal, err.message || 'Ocurrió un error al confirmar la participación.');
            })
            .finally(() => {
                btnEnviar.textContent = 'Confirmar participación en vacaciones';
                actualizarEnvio();
            });
    });
})();
