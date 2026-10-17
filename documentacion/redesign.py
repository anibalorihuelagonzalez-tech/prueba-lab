import os
import re

files = ['clinicas.html', 'envios.html', 'facturacion.html', 'ordenes.html', 'pacientes.html', 'servicios.html']

head_content = """<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{title}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            900: '#0c4a6e',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>"""

def get_sidebar(active_file):
    links = [
        ('ordenes.html', 'fa-clipboard-list', 'Órdenes de Trabajo'),
        ('envios.html', 'fa-truck-fast', 'Control de Envíos'),
        ('separator1', 'DIRECTORIOS'),
        ('clinicas.html', 'fa-hospital', 'Clínicas y Sedes'),
        ('pacientes.html', 'fa-users', 'Pacientes'),
        ('servicios.html', 'fa-list-check', 'Servicios y Tarifas'),
        ('separator2', 'ADMINISTRACIÓN'),
        ('facturacion.html', 'fa-file-invoice-dollar', 'Facturación')
    ]
    
    sidebar = """<aside class="w-64 bg-white border-r border-gray-200 flex flex-col transition-all duration-300 shrink-0 z-20">
    <div class="flex items-center justify-center h-16 border-b border-gray-100 shrink-0">
        <i class="fa-solid fa-tooth text-2xl mr-3 text-brand-600"></i>
        <span class="text-lg font-bold tracking-tight text-gray-900">CNSB LAB</span>
    </div>
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1">"""
    
    for link in links:
        if link[0].startswith('separator'):
            sidebar += f'\n            <li class="pt-5 pb-2 px-8 text-[10px] font-bold text-gray-400 uppercase tracking-widest">{link[1]}</li>'
        else:
            is_active = (link[0] == active_file)
            if is_active:
                sidebar += f"""
            <li>
                <a href="{link[0]}" class="flex items-center px-6 py-2.5 mx-4 rounded-lg transition-colors bg-brand-50 text-brand-700 font-medium border-l-4 border-brand-500">
                    <i class="fa-solid {link[1]} w-5 mr-3 text-brand-600"></i> {link[2]}
                </a>
            </li>"""
            else:
                sidebar += f"""
            <li>
                <a href="{link[0]}" class="flex items-center px-6 py-2.5 mx-4 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium border-l-4 border-transparent">
                    <i class="fa-solid {link[1]} w-5 mr-3 text-gray-400"></i> {link[2]}
                </a>
            </li>"""
    
    sidebar += """
        </ul>
    </nav>
</aside>"""
    return sidebar

def get_header(title):
    return f"""<header class="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-6 shrink-0 z-10 shadow-sm">
    <div class="flex items-center">
        <button class="text-gray-500 hover:text-gray-700 focus:outline-none lg:hidden">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
        <h1 class="ml-4 text-xl font-bold text-gray-800 tracking-tight">{title}</h1>
    </div>
    <div class="flex items-center space-x-4">
        <button class="text-gray-400 hover:text-brand-600 transition-colors relative">
            <i class="fa-regular fa-bell text-xl"></i>
            <span class="absolute top-0 right-0 -mt-1 -mr-1 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            </span>
        </button>
        <div class="h-6 w-px bg-gray-200 mx-2"></div>
        <div class="flex items-center">
            <div class="w-8 h-8 rounded-full bg-brand-50 border border-brand-100 flex items-center justify-center text-brand-700 font-bold text-sm mr-2">DM</div>
            <span class="text-sm font-medium text-gray-700 hidden md:block">Deiver Martínez</span>
        </div>
        <a href="index.html" class="text-gray-400 hover:text-red-500 transition-colors ml-4" title="Cerrar Sesión">
            <i class="fa-solid fa-arrow-right-from-bracket text-xl"></i>
        </a>
    </div>
</header>"""

for filename in files:
    if not os.path.exists(filename):
        continue
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Extract title
    title_match = re.search(r'<title>(.*?)</title>', content)
    title = title_match.group(1) if title_match else "CNSB Lab"
    
    # Extract header title
    h1_match = re.search(r'<h1[^>]*>(.*?)</h1>', content)
    header_title = h1_match.group(1) if h1_match else title
    
    # Replace Head
    content = re.sub(r'<head>.*?</head>', head_content.replace('{title}', title), content, flags=re.DOTALL)
    
    # Replace Sidebar
    content = re.sub(r'<aside.*?</aside>', get_sidebar(filename), content, flags=re.DOTALL)
    
    # Replace Header
    content = re.sub(r'<header.*?</header>', get_header(header_title), content, flags=re.DOTALL)
    
    # Make layout cleaner
    content = content.replace('bg-brand-600 hover:bg-brand-700 text-white', 'bg-gray-900 hover:bg-gray-800 text-white')
    content = content.replace('bg-brand-900 text-white', 'bg-white text-gray-900')
    content = content.replace('border-brand-600/30', 'border-gray-100')
    
    with open(filename, 'w', encoding='utf-8') as f:
        f.write(content)

print("Actualización completada.")
