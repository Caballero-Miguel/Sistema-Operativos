document.addEventListener('DOMContentLoaded', () => {
    mostrarToastPendiente();
    inicializarMapaReportes();
    inicializarMapaCrear();
    inicializarMapaDetalle();
    inicializarFormularioReporte();
    inicializarFormularioApoyo();
    inicializarConfirmaciones();
    inicializarAccionesAdmin();
});

function mostrarToast(mensaje, tipo = 'success') {
    let contenedor = document.getElementById('contenedor-toasts');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'contenedor-toasts';
        contenedor.className = 'toast-container position-fixed top-0 end-0 p-3';
        contenedor.style.zIndex = '1080';
        document.body.appendChild(contenedor);
    }

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${tipo} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${escaparHtml(mensaje)}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
    `;
    contenedor.appendChild(toast);

    if (typeof bootstrap !== 'undefined') {
        const instancia = new bootstrap.Toast(toast, { delay: 4000 });
        instancia.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }
}

function mostrarToastPendiente() {
    const mensaje = sessionStorage.getItem('toast_exito');
    if (mensaje) {
        sessionStorage.removeItem('toast_exito');
        mostrarToast(mensaje, 'success');
    }
}

async function apoyarReporte(reporteId, botonElemento) {
    const formulario = botonElemento.closest('form');
    const token = botonElemento.dataset.csrfToken
        || formulario?.dataset.csrfToken
        || formulario?.querySelector('[name="csrf_token"], [name="token_csrf"]')?.value;
    const textoOriginal = botonElemento.innerHTML;

    botonElemento.disabled = true;
    botonElemento.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';

    try {
        const datos = new FormData();
        datos.append('reporte_id', String(reporteId));
        datos.append('csrf_token', token || '');
        const respuesta = await fetch('?r=api.apoyar', { method: 'POST', body: datos });
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.error || 'No fue posible registrar tu apoyo.');
        }

        const contador = document.getElementById('contador-apoyos');
        if (contador) {
            contador.textContent = resultado.apoyos;
        }
        botonElemento.innerHTML = '<i class="bi bi-hand-thumbs-up" aria-hidden="true"></i> <span>Ya apoyaste este reporte</span>';
        mostrarToast(resultado.mensaje || '¡Gracias por tu apoyo!', 'success');
        return resultado;
    } catch (error) {
        botonElemento.disabled = false;
        botonElemento.innerHTML = textoOriginal;
        mostrarToast(error.message, 'danger');
        return null;
    }
}

function colorSeguro(color) {
    return /^#[0-9a-fA-F]{6}$/.test(color || '') ? color : '#6c757d';
}

function escaparHtml(valor) {
    const elemento = document.createElement('div');
    elemento.textContent = String(valor ?? '');
    return elemento.innerHTML;
}

function inicializarMapaReportes() {
    const contenedor = document.getElementById('mapa-reportes');
    if (!contenedor || typeof L === 'undefined') {
        return;
    }

    const latitud = Number(contenedor.dataset.lat);
    const longitud = Number(contenedor.dataset.lng);
    const mapa = L.map(contenedor).setView([latitud, longitud], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(mapa);

    const capaMarcadores = L.layerGroup().addTo(mapa);
    const filtroCategoria = document.getElementById('filtro-categoria');
    const filtroEstado = document.getElementById('filtro-estado');

    async function cargarReportes() {
        const parametros = new URLSearchParams();
        if (filtroCategoria?.value) {
            parametros.set('categoria', filtroCategoria.value);
        }
        if (filtroEstado?.value) {
            parametros.set('estado', filtroEstado.value);
        }

        const respuesta = await fetch(`?r=api.reportes&${parametros.toString()}`);
        if (!respuesta.ok) {
            throw new Error('No fue posible cargar los reportes.');
        }

        const datos = await respuesta.json();
        capaMarcadores.clearLayers();

        datos.reportes.forEach((reporte) => {
            const marcador = L.circleMarker([reporte.lat, reporte.lng], {
                radius: reporte.estado.nombre === 'Resuelto (por confirmar)'
                    ? 12
                    : (Number(reporte.apoyos) > 5 ? 14 : 9),
                color: colorSeguro(reporte.estado.color),
                fillColor: colorSeguro(reporte.estado.color),
                fillOpacity: 0.8,
                weight: 2,
            });
            const categoria = escaparHtml(reporte.categoria.nombre);
            const estado = escaparHtml(reporte.estado.nombre);
            const titulo = escaparHtml(reporte.titulo);
            const colorCategoria = colorSeguro(reporte.categoria.color);
            const colorEstado = colorSeguro(reporte.estado.color);
            const avisoConfirmacion = reporte.estado.nombre === 'Resuelto (por confirmar)'
                ? '<br><strong>&#9888; El municipio dice que está resuelto. Confirma o rechaza.</strong>'
                : '';
            marcador.bindPopup(`
                <strong>${titulo}</strong><br>
                <span style="color:${colorCategoria}">${categoria}</span><br>
                <span style="color:${colorEstado}">${estado}</span><br>
                &#128170; ${Number(reporte.apoyos)} apoyos<br>
                ${avisoConfirmacion}
                <a href="?r=reporte.ver&id=${Number(reporte.id)}">Ver detalle</a>
            `);
            marcador.addTo(capaMarcadores);
        });
    }

    const recargar = () => cargarReportes().catch((error) => console.error(error));
    filtroCategoria?.addEventListener('change', recargar);
    filtroEstado?.addEventListener('change', recargar);
    recargar();
}

function inicializarMapaCrear() {
    const contenedor = document.getElementById('mapa-crear');
    if (!contenedor || typeof L === 'undefined') {
        return;
    }

    const latitudInicial = Number(contenedor.dataset.lat);
    const longitudInicial = Number(contenedor.dataset.lng);
    const mapa = L.map(contenedor).setView([latitudInicial, longitudInicial], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(mapa);

    let marcador = null;
    const latitud = document.getElementById('latitud');
    const longitud = document.getElementById('longitud');
    const textoUbicacion = document.getElementById('ubicacion-seleccionada');

    function seleccionarUbicacion(lat, lng) {
        latitud.value = lat.toFixed(8);
        longitud.value = lng.toFixed(8);
        textoUbicacion.textContent = `Ubicación seleccionada: ${latitud.value}, ${longitud.value}`;
        if (marcador) {
            marcador.setLatLng([lat, lng]);
        } else {
            marcador = L.marker([lat, lng]).addTo(mapa);
        }
    }

    mapa.on('click', (evento) => seleccionarUbicacion(evento.latlng.lat, evento.latlng.lng));

    document.getElementById('usar-ubicacion')?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            textoUbicacion.textContent = 'Tu navegador no soporta geolocalización.';
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (posicion) => {
                const ubicacion = [posicion.coords.latitude, posicion.coords.longitude];
                mapa.setView(ubicacion, 16);
                seleccionarUbicacion(ubicacion[0], ubicacion[1]);
            },
            () => {
                textoUbicacion.textContent = 'No fue posible obtener tu ubicación.';
            }
        );
    });
}

function inicializarMapaDetalle() {
    const contenedor = document.getElementById('mapa-detalle');
    if (!contenedor || typeof L === 'undefined') {
        return;
    }

    const latitud = Number(contenedor.dataset.lat);
    const longitud = Number(contenedor.dataset.lng);
    const mapa = L.map(contenedor).setView([latitud, longitud], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(mapa);
    L.marker([latitud, longitud]).addTo(mapa);
}

function inicializarFormularioReporte() {
    const formulario = document.getElementById('formReporte');
    const modalElemento = document.getElementById('modal-duplicado');
    if (!formulario || !modalElemento || typeof bootstrap === 'undefined') {
        return;
    }

    const modal = new bootstrap.Modal(modalElemento);
    const forzar = document.getElementById('forzar');
    let reporteDuplicado = null;

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        try {
            const respuesta = await fetch(formulario.action, {
                method: 'POST',
                body: new FormData(formulario),
            });

            if (respuesta.redirected) {
                const html = await respuesta.text();
                document.open();
                document.write(html);
                document.close();
                return;
            }

            const tipo = respuesta.headers.get('content-type') || '';
            if (!tipo.includes('application/json')) {
                window.location.reload();
                return;
            }

            const datos = await respuesta.json();
            if (!datos.duplicado) {
                alert(datos.error || 'No fue posible crear el reporte.');
                return;
            }

            reporteDuplicado = datos.reporte;
            document.getElementById('texto-duplicado').innerHTML = `
                Ya existe un reporte similar a <strong>${Number(reporteDuplicado.distancia)} metros</strong>:
                <br>"${escaparHtml(reporteDuplicado.titulo)}" con ${Number(reporteDuplicado.apoyos)} apoyos.
                <br><br>¿Quieres apoyarlo en vez de crear uno nuevo?
            `;
            modal.show();
        } catch (error) {
            console.error(error);
            window.location.reload();
        }
    });

    document.getElementById('crear-duplicado')?.addEventListener('click', () => {
        forzar.value = '1';
        modal.hide();
        formulario.submit();
    });

    document.getElementById('apoyar-duplicado')?.addEventListener('click', async () => {
        if (!reporteDuplicado) {
            return;
        }
        const boton = document.getElementById('apoyar-duplicado');
        boton.dataset.csrfToken = formulario.querySelector('[name="token_csrf"]').value;
        const resultado = await apoyarReporte(reporteDuplicado.id, boton);
        if (resultado) {
            sessionStorage.setItem('toast_exito', '¡Apoyaste un reporte existente!');
            window.location.href = `?r=reporte.ver&id=${Number(reporteDuplicado.id)}`;
        }
    });
}

function inicializarFormularioApoyo() {
    const formulario = document.getElementById('form-apoyar');
    if (!formulario) {
        return;
    }

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const boton = formulario.querySelector('button[type="submit"]');
        await apoyarReporte(Number(formulario.dataset.reporteId), boton);
    });
}

function inicializarConfirmaciones() {
    document.querySelectorAll('.boton-confirmacion').forEach((boton) => {
        boton.addEventListener('click', () => {
            confirmarResolucion(Number(boton.dataset.reporteId), boton.dataset.voto, boton);
        });
    });
}

async function confirmarResolucion(reporteId, voto, botonElemento) {
    const token = botonElemento.dataset.csrfToken || '';
    const botones = document.querySelectorAll('.boton-confirmacion');
    botones.forEach((boton) => {
        boton.disabled = true;
    });
    const textoOriginal = botonElemento.innerHTML;
    botonElemento.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

    try {
        const datos = new FormData();
        datos.append('reporte_id', String(reporteId));
        datos.append('voto', voto);
        datos.append('csrf_token', token);
        const respuesta = await fetch('?r=api.confirmar', { method: 'POST', body: datos });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.error || 'No fue posible registrar tu confirmación.');
        }

        const contador = document.getElementById('contador-confirmaciones');
        if (contador) {
            contador.textContent = `Confirmaciones: ${resultado.votos_si} sí · ${resultado.votos_no} no`;
        }
        mostrarToast(resultado.mensaje || 'Gracias por tu confirmación', 'success');
        if (resultado.estado_actual === 'Resuelto' || resultado.estado_actual === 'En proceso') {
            const estado = document.getElementById('estado-reporte');
            if (estado) {
                estado.textContent = resultado.estado_actual;
                estado.style.backgroundColor = resultado.estado_actual === 'Resuelto' ? '#28a745' : '#17a2b8';
            }
            const tarjeta = document.querySelector('.boton-confirmacion')?.closest('section');
            if (tarjeta) {
                tarjeta.innerHTML = `<div class="alert alert-success mb-0">${escaparHtml(resultado.estado_actual === 'Resuelto' ? 'La comunidad confirmó la resolución.' : 'La comunidad indicó que el problema sigue igual.')}</div>`;
            }
        } else {
            botonElemento.innerHTML = voto === 'si' ? '✅ Ya votaste: sí' : '❌ Ya votaste: no';
        }
    } catch (error) {
        botones.forEach((boton) => {
            boton.disabled = false;
        });
        botonElemento.innerHTML = textoOriginal;
        mostrarToast(error.message, 'danger');
    }
}

function inicializarAccionesAdmin() {
    document.querySelectorAll('.cambiar-estado').forEach((boton) => {
        boton.addEventListener('click', () => {
            const nombres = { 2: 'En proceso', 3: 'Resuelto (por confirmar)', 5: 'Rechazado' };
            const estado = Number(boton.dataset.estado);
            const confirmado = window.confirm(`¿Estás seguro que quieres marcar este reporte como "${nombres[estado]}"?`);
            if (confirmado) {
                cambiarEstado(Number(boton.dataset.reporteId), estado, boton);
            }
        });
    });
}

async function cambiarEstado(reporteId, nuevoEstadoId, botonElemento) {
    const botonesFila = botonElemento.closest('tr')?.querySelectorAll('.cambiar-estado') || [botonElemento];
    botonesFila.forEach((boton) => {
        boton.disabled = true;
    });
    const textoOriginal = botonElemento.innerHTML;
    botonElemento.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

    try {
        const datos = new FormData();
        datos.append('reporte_id', String(reporteId));
        datos.append('nuevo_estado_id', String(nuevoEstadoId));
        datos.append('csrf_token', botonElemento.dataset.token || window.barrioAlertaCSRF || '');
        const respuesta = await fetch('?r=admin.cambiar_estado', {
            method: 'POST',
            body: datos,
            headers: { Accept: 'application/json' },
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.ok) {
            throw new Error(resultado.error || 'No fue posible cambiar el estado.');
        }
        mostrarToast(resultado.mensaje, 'success');
        window.setTimeout(() => window.location.reload(), 500);
    } catch (error) {
        botonesFila.forEach((boton) => {
            boton.disabled = false;
        });
        botonElemento.innerHTML = textoOriginal;
        mostrarToast(error.message, 'danger');
    }
}
