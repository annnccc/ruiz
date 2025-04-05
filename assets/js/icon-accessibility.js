/**
 * Script para mejorar la accesibilidad de los iconos de Material Symbols
 * 
 * Este script se encarga de añadir atributos aria-label a los iconos
 * para mejorar la accesibilidad con lectores de pantalla.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Mapeo de iconos a descripciones en español
    const iconDescriptions = {
        // Navegación
        'menu': 'Menú',
        'home': 'Inicio',
        'dashboard': 'Panel de control',
        'settings': 'Configuración',
        'logout': 'Cerrar sesión',
        'account_circle': 'Perfil de usuario',
        'person': 'Usuario',
        'people': 'Usuarios',
        
        // Acciones
        'add': 'Añadir',
        'add_circle': 'Añadir',
        'edit': 'Editar',
        'delete': 'Eliminar',
        'save': 'Guardar',
        'search': 'Buscar',
        'refresh': 'Actualizar',
        'close': 'Cerrar',
        'check': 'Verificar',
        'done': 'Completado',
        'cancel': 'Cancelar',
        
        // Comunicación
        'mail': 'Correo',
        'message': 'Mensaje',
        'chat': 'Chat',
        'notifications': 'Notificaciones',
        'phone': 'Teléfono',
        
        // Fechas y tiempo
        'calendar_today': 'Calendario',
        'event': 'Evento',
        'schedule': 'Horario',
        'date_range': 'Rango de fechas',
        'access_time': 'Hora',
        'alarm': 'Alarma',
        
        // Contenido
        'description': 'Descripción',
        'list': 'Lista',
        'grid_view': 'Vista de cuadrícula',
        'view_list': 'Vista de lista',
        'attach_file': 'Adjuntar archivo',
        'folder': 'Carpeta',
        'cloud_upload': 'Subir',
        'cloud_download': 'Descargar',
        'file_download': 'Descargar archivo',
        'file_upload': 'Subir archivo',
        
        // Navegadores y dispositivos
        'laptop': 'Portátil',
        'desktop_windows': 'Escritorio',
        'smartphone': 'Teléfono móvil',
        'tablet': 'Tablet',
        
        // Otros
        'info': 'Información',
        'help': 'Ayuda',
        'warning': 'Advertencia',
        'error': 'Error',
        'favorite': 'Favorito',
        'star': 'Estrella',
        'visibility': 'Ver',
        'visibility_off': 'Ocultar',
        'lock': 'Bloqueado',
        'lock_open': 'Desbloqueado'
    };
    
    // Aplicar atributos aria-label a todos los iconos Material Symbols
    document.querySelectorAll('.material-symbols-rounded, .material-symbols-outlined').forEach(icon => {
        // Obtener el texto del icono (nombre del icono)
        const iconName = icon.textContent.trim();
        
        // Si ya tiene un aria-label, respetarlo
        if (icon.hasAttribute('aria-label')) return;
        
        // Si el icono está en un botón con texto o tiene un texto adyacente, 
        // marcarlo como decorativo
        if (isDecorativeIcon(icon)) {
            icon.setAttribute('aria-hidden', 'true');
            return;
        }
        
        // Asignar una descripción si está disponible
        if (iconDescriptions[iconName]) {
            icon.setAttribute('aria-label', iconDescriptions[iconName]);
        } else {
            // Para iconos sin descripción específica, usar el nombre genérico
            icon.setAttribute('aria-label', 'Icono ' + iconName.replace(/_/g, ' '));
        }
    });
    
    // Función para determinar si un icono es decorativo
    function isDecorativeIcon(icon) {
        // Si el icono está dentro de un botón que ya tiene texto
        const parentButton = icon.closest('button, .btn, [role="button"]');
        if (parentButton) {
            const buttonText = parentButton.textContent.replace(icon.textContent, '').trim();
            if (buttonText.length > 0) {
                return true;
            }
        }
        
        // Si el icono tiene un texto adyacente y está en un contenedor pequeño
        const parent = icon.parentElement;
        if (parent && parent.childNodes.length > 1) {
            const siblingText = parent.textContent.replace(icon.textContent, '').trim();
            if (siblingText.length > 0 && parent.offsetWidth < 200) {
                return true;
            }
        }
        
        return false;
    }
}); 