<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(($tituloPagina ?? 'Panel') . ' · CNSB Lab') ?></title>
    <!-- Tailwind CSS (Local) -->
    <script src="/assets/vendor/tailwindcss/tailwind.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Tabulator CSS & JS (Local) -->
    <link href="/assets/vendor/tabulator/tabulator.min.css" rel="stylesheet">
    <script type="text/javascript" src="/assets/vendor/tabulator/tabulator.min.js"></script>
    <!-- Lucide Icons (Local) -->
    <script src="/assets/vendor/lucide/lucide.min.js"></script>
    <!-- QR Code Generator & Scanner (Local) -->
    <script src="/assets/vendor/qrcode/qrcode.min.js"></script>
    <script src="/assets/vendor/qrcode/html5-qrcode.min.js"></script>
    <style>
        /* Ajustes personalizados para Tabulator forzando el estilo Tailwind */
        .tabulator {
            border: none !important;
            background-color: transparent !important;
            font-family: inherit !important;
        }

        /* Cabeceras de la tabla */
        .tabulator .tabulator-header {
            background-color: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .tabulator .tabulator-header .tabulator-col {
            background-color: transparent !important;
            border-right: none !important;
        }

        .tabulator .tabulator-header .tabulator-col-content {
            padding: 12px 16px !important;
            color: #64748b !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        /* Filas de la tabla */
        .tabulator .tabulator-row {
            background-color: #ffffff !important;
            border-bottom: 1px solid #f1f5f9 !important;
            transition: background-color 0.2s ease;
        }

        .tabulator .tabulator-row:hover {
            background-color: #f8fafc !important;
        }

        .tabulator .tabulator-cell {
            border-right: none !important;
            padding: 12px 14px !important;
            vertical-align: middle !important;
            box-sizing: border-box !important;
        }

        .tabulator-row .tabulator-cell {
            display: inline-block;
        }

        /* ----- SOLUCIÓN VISTA MÓVIL (COLUMNAS COLAPSADAS) ----- */
        .tabulator-row .tabulator-responsive-collapse {
            padding: 12px 16px !important;
            background-color: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0;
        }

        .tabulator-row .tabulator-responsive-collapse table {
            width: 100%;
            border-collapse: collapse;
        }

        .tabulator-row .tabulator-responsive-collapse table tr {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 8px 0;
            border-bottom: 1px dashed #cbd5e1;
        }
        .tabulator-row .tabulator-responsive-collapse table tr:last-child {
            border-bottom: none;
        }
        
        .tabulator-row .tabulator-responsive-collapse table tr td {
            border: none !important;
            padding: 0 !important;
            font-size: 0.875rem;
        }

        /* Título de la columna colapsada (Izquierda) */
        .tabulator-row .tabulator-responsive-collapse table tr td:first-child {
            font-weight: 600;
            color: #475569;
            text-align: left;
            width: 35%;
            padding-right: 8px !important;
        }

        /* Valor de la columna colapsada (Derecha) */
        .tabulator-row .tabulator-responsive-collapse table tr td:last-child {
            font-weight: 500;
            color: #0f172a;
            text-align: right;
            width: 65%;
            word-break: break-word;
        }

        /* Botón de toggle de columnas responsivas */
        .tabulator-responsive-collapse-toggle {
            color: #4f46e5 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background-color: #eef2ff;
            transition: background-color 0.2s;
            padding: 4px;
        }

        .tabulator-responsive-collapse-toggle:hover {
            background-color: #e0e7ff;
        }

        .tabulator-responsive-collapse-toggle svg {
            width: 14px;
            height: 14px;
            stroke-width: 3px;
        }

        /* Paginación Tabulator adaptada a Tailwind */
        .tabulator-footer {
            background-color: #ffffff !important;
            border-top: 1px solid #e2e8f0 !important;
            padding: 12px 16px !important;
            display: flex !important;
            flex-wrap: wrap;
            justify-content: center !important;
            align-items: center !important;
            gap: 12px !important;
        }

        @media (min-width: 640px) {
            .tabulator-footer {
                justify-content: space-between !important;
            }
        }
        
        .tabulator-footer .tabulator-paginator {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 8px !important;
            width: 100% !important;
        }

        @media (min-width: 640px) {
            .tabulator-footer .tabulator-paginator {
                width: auto !important;
            }
        }

        /* Selector de cantidad por página */
        .tabulator-footer .tabulator-page-size {
            margin: 0 !important;
            padding: 6px 28px 6px 10px !important;
            border-radius: 8px !important;
            border: 1px solid #e2e8f0 !important;
            background-color: white !important;
            font-size: 0.75rem !important;
            color: #475569 !important;
            cursor: pointer;
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.5rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.25em 1.25em !important;
        }

        .tabulator-footer .tabulator-pages {
            margin: 0 !important;
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 4px !important;
        }

        .tabulator-footer .tabulator-page {
            padding: 6px 12px !important;
            border-radius: 6px !important;
            border: 1px solid #e2e8f0 !important;
            color: #475569 !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            background: white !important;
            transition: all 0.2s !important;
            cursor: pointer;
        }

        .tabulator-footer .tabulator-page:not(.disabled):hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }

        .tabulator-footer .tabulator-page.active {
            background-color: #4f46e5 !important;
            color: white !important;
            border-color: #4f46e5 !important;
        }

        .tabulator-footer .tabulator-page:disabled,
        .tabulator-footer .tabulator-page.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f8fafc !important;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }
        .animate-shake { animation: shake 0.3s ease-in-out; }
    </style>
</head>