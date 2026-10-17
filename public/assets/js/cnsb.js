/**
 * public/assets/js/cnsb.js  —  UTILIDADES JAVASCRIPT COMPARTIDAS (Ventas y Contabilidad)
 * -------------------------------------------------------------------------
 *  api(url, datos)      Llama a un controlador (POST con JSON) y devuelve la respuesta ya
 *                       interpretada. Si la sesión expiró, redirige al login.
 *  fmt(n, dec)          Formato de número venezolano: 1.234,56
 *  usd(n) / bs(n)       "$ 1.234,56"  /  "Bs. 1.234,56"
 *  fechaVe('2026-10-14') -> '14/10/2026'
 *  hoyISO()             Fecha de hoy 'AAAA-MM-DD'
 *  toast(tipo, texto)   Aviso pequeño (SweetAlert2)
 *  confirmar(titulo, texto, botón) -> Promise<boolean>
 *  pedirMotivo(titulo)  -> Promise<string|null>  (para anulaciones)
 *  esc(texto)           Escapa HTML (evita XSS al armar tablas con innerHTML)
 *  refrescarIconos()    Redibuja los iconos Lucide tras cambios dinámicos
 */
(function () {
    'use strict';

    async function api(url, datos) {
        let res;
        try {
            res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos || {}),
            });
        } catch (e) {
            return { status: 'error', message: 'Sin conexión con el servidor. Revisa tu red e inténtalo de nuevo.' };
        }
        let json;
        try {
            json = await res.json();
        } catch (e) {
            return { status: 'error', message: 'El servidor devolvió una respuesta inesperada (código ' + res.status + ').' };
        }
        if (res.status === 401 || json.code === 'NO_AUTH') {
            window.location.href = '/';
        }
        return json;
    }

    function fmt(n, dec) {
        dec = (dec === undefined) ? 2 : dec;
        const v = Number(n);
        if (!isFinite(v)) return '0,00';
        return v.toLocaleString('es-VE', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }
    const usd = (n) => '$ ' + fmt(n);
    const bs = (n) => 'Bs. ' + fmt(n);

    function fechaVe(iso) {
        if (!iso) return '';
        const p = String(iso).substring(0, 10).split('-');
        return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
    }

    function hoyISO() {
        const d = new Date();
        const z = (x) => String(x).padStart(2, '0');
        return d.getFullYear() + '-' + z(d.getMonth() + 1) + '-' + z(d.getDate());
    }

    function esc(t) {
        return String(t === null || t === undefined ? '' : t)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function toast(tipo, texto) {
        if (!window.Swal) { alert(texto); return; }
        Swal.fire({
            toast: true, position: 'top-end', icon: tipo, title: texto,
            showConfirmButton: false, timer: tipo === 'error' ? 5000 : 2500, timerProgressBar: true,
        });
    }

    async function confirmar(titulo, texto, boton) {
        const r = await Swal.fire({
            title: titulo, text: texto || '', icon: 'question', showCancelButton: true,
            confirmButtonText: boton || 'Sí, continuar', cancelButtonText: 'Cancelar', confirmButtonColor: '#4f46e5',
        });
        return r.isConfirmed;
    }

    async function pedirMotivo(titulo) {
        const r = await Swal.fire({
            title: titulo, input: 'text', inputPlaceholder: 'Motivo (obligatorio)', showCancelButton: true,
            confirmButtonText: 'Confirmar', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc2626',
            inputValidator: (v) => (!v || v.trim().length < 3) ? 'Escribe el motivo (mínimo 3 letras).' : null,
        });
        return r.isConfirmed ? r.value.trim() : null;
    }

    function refrescarIconos() {
        if (window.lucide) setTimeout(() => lucide.createIcons(), 30);
    }

    Object.assign(window, { api, fmt, usd, bs, fechaVe, hoyISO, esc, toast, confirmar, pedirMotivo, refrescarIconos });
})();
