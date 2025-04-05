/**
 * Funciones comunes para toda la aplicación
 */

const ResourceOptimizer = {
    resources: {},
    loaded: {},

    enqueue: function(resources, groupId) {
        if (!groupId) groupId = 'default';
        this.resources[groupId] = resources;
        
        document.addEventListener('resource:request', function(e) {
            const requestedGroupId = e.detail.groupId;
            if (requestedGroupId === groupId) {
                ResourceOptimizer.load(requestedGroupId);
            }
        });
    },
    
    load: function(groupId) {
        if (this.loaded[groupId]) return;
        if (!this.resources[groupId]) return;
        
        const resources = this.resources[groupId];
        for (let i = 0; i < resources.length; i++) {
            const resource = resources[i];
            if (resource.type === 'style') {
                this.loadStyle(resource.url);
            } else if (resource.type === 'script') {
                this.loadScript(resource.url, resource.async, resource.defer);
            }
        }
        
        this.loaded[groupId] = true;
    },
    
    loadStyle: function(url) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = url;
        document.head.appendChild(link);
    },
    
    loadScript: function(url, isAsync, isDefer) {
        const script = document.createElement('script');
        script.src = url;
        if (isAsync) script.async = true;
        if (isDefer) script.defer = true;
        document.head.appendChild(script);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    initDatepickers();
    initSelects();
    initDeleteConfirmations();
    initAnimations();
    initSidebarFixed();
    initSidebarDropdowns();
    initGlobalSearch();
    initNotifications();
    initThemeToggle();
    initDashboardNotes();
    
    // Ajustar texto de saludo en ventanas pequeñas
    const greetingElement = document.querySelector('header .greeting');
    if (greetingElement) {
        function adjustGreeting() {
            if (window.innerWidth < 768) {
                // Asegurar que el texto no se corte
                greetingElement.style.whiteSpace = 'normal';
                greetingElement.style.overflow = 'visible';
                greetingElement.style.maxWidth = '100%';
            } else {
                // Restablecer en pantallas grandes
                greetingElement.style.whiteSpace = '';
                greetingElement.style.overflow = '';
                greetingElement.style.maxWidth = '';
            }
        }
        
        // Ejecutar al cargar y en resize
        adjustGreeting();
        window.addEventListener('resize', adjustGreeting);
    }
    
    // Asegurarnos de que textos largos se vean bien en dispositivos pequeños
    function ajustarTextos() {
        // Encontrar y ajustar todos los elementos display-4 para móviles
        const headings = document.querySelectorAll('.display-4');
        const isMobile = window.innerWidth < 768;
        
        headings.forEach(function(heading) {
            if (isMobile) {
                heading.style.wordBreak = 'break-word';
                heading.style.overflowWrap = 'break-word';
                heading.style.hyphens = 'auto';
            } else {
                heading.style.wordBreak = '';
                heading.style.overflowWrap = '';
                heading.style.hyphens = '';
            }
        });
    }
    
    // Ejecutar al inicio y al cambiar tamaño
    ajustarTextos();
    window.addEventListener('resize', ajustarTextos);
    
    // Hacer que las tablas sean mejor visualizadas en móviles
    const tables = document.querySelectorAll('table');
    tables.forEach(function(table) {
        if (!table.parentElement.classList.contains('table-responsive') && 
            !table.classList.contains('dataTable')) {
            
            // Envolver en container responsivo si no lo está ya
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
});

// Nueva función unificada para manejar el sidebar en todos los dispositivos
function initSidebarFixed() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleButton = document.getElementById('toggleSidebar');
    const body = document.body;
    const SIDEBAR_COLLAPSED_CLASS = 'sidebar-collapsed';
    const STORAGE_KEY = 'sidebarCollapsed';
    
    if (!sidebar || !mainContent) return;
    
    // Crear backdrop para clic fuera del sidebar en móvil
    const backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
    
    // Función para manejar toggle del sidebar en móvil
    function toggleSidebarMobile() {
        sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');
        document.body.classList.toggle('sidebar-open');
    }
    
    // Función para manejar toggle del sidebar en desktop
    function toggleSidebarDesktop() {
        body.classList.toggle(SIDEBAR_COLLAPSED_CLASS);
        const isNowCollapsed = body.classList.contains(SIDEBAR_COLLAPSED_CLASS);
        localStorage.setItem(STORAGE_KEY, isNowCollapsed);
    }
    
    // Configurar el estado inicial del sidebar en desktop
    const savedState = localStorage.getItem(STORAGE_KEY);
    if (savedState === 'true') {
        body.classList.add(SIDEBAR_COLLAPSED_CLASS);
    }
    
    // Event listener para botón toggle
    if (toggleButton) {
        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // En móvil, usamos una función, en desktop otra
            if (window.innerWidth < 992) {
                toggleSidebarMobile();
            } else {
                toggleSidebarDesktop();
            }
        });
    }
    
    // Cerrar sidebar al hacer clic en backdrop
    backdrop.addEventListener('click', function() {
        toggleSidebarMobile();
    });
    
    // Cerrar sidebar al hacer clic en enlaces en móvil
    const sidebarLinks = sidebar.querySelectorAll('a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Solo en móvil y si el sidebar está abierto
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                // Pequeño delay para mejor UX
                setTimeout(function() {
                    toggleSidebarMobile();
                }, 150);
            }
        });
    });
    
    // Crear botón fijo para móvil si no existe
    function setupMobileToggle() {
        if (window.innerWidth < 992) {
            let fixedToggle = document.querySelector('.sidebar-toggle-fixed');
            
            if (!fixedToggle) {
                fixedToggle = document.createElement('button');
                fixedToggle.className = 'btn sidebar-toggle-fixed';
                fixedToggle.setAttribute('id', 'mobileToggleSidebar');
                fixedToggle.setAttribute('aria-label', 'Menú');
                
                // Agregar ícono
                const icon = document.createElement('span');
                icon.className = 'material-symbols-rounded';
                icon.textContent = 'menu';
                fixedToggle.appendChild(icon);
                
                // Agregar al body
                document.body.appendChild(fixedToggle);
                
                // Añadir event listener
                fixedToggle.addEventListener('click', function() {
                    toggleSidebarMobile();
                });
            }
        } else {
            // En desktop, eliminar botón fijo si existe
            const fixedToggle = document.querySelector('.sidebar-toggle-fixed');
            if (fixedToggle) {
                fixedToggle.remove();
            }
        }
    }
    
    // Ejecutar al cargar y cuando cambie el tamaño
    setupMobileToggle();
    window.addEventListener('resize', function() {
        setupMobileToggle();
        
        // Cerrar sidebar automáticamente si pasamos de móvil a desktop y estaba abierto
        if (window.innerWidth >= 992 && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    });
}

function enhanceSidebarKeyboardNavigation(sidebar) {
    if (!sidebar) return;
    
    const focusableElements = sidebar.querySelectorAll('a, button, [tabindex="0"]');
    if (focusableElements.length === 0) return;
    
    focusableElements.forEach(element => {
        element.addEventListener('keydown', function(event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                
                const currentIndex = Array.from(focusableElements).indexOf(document.activeElement);
                let targetIndex;
                
                if (event.key === 'ArrowDown') {
                    targetIndex = (currentIndex + 1) % focusableElements.length;
                } else {
                    targetIndex = (currentIndex - 1 + focusableElements.length) % focusableElements.length;
                }
                
                focusableElements[targetIndex].focus();
            }
        });
    });
}

function announceForScreenReaders(message) {
    const announcer = document.getElementById('screen-reader-announcer');
    let announcerElement;
    
    if (!announcer) {
        announcerElement = document.createElement('div');
        announcerElement.setAttribute('id', 'screen-reader-announcer');
        announcerElement.setAttribute('aria-live', 'polite');
        announcerElement.setAttribute('aria-atomic', 'true');
        announcerElement.classList.add('sr-only');
        document.body.appendChild(announcerElement);
    } else {
        announcerElement = announcer;
    }
    
    announcerElement.textContent = message;
    
    setTimeout(() => {
        announcerElement.textContent = '';
    }, 3000);
}

function initAnimations() {
    const animatedElements = document.querySelectorAll('.animate-fade-in');
    animatedElements.forEach((element, index) => {
        element.style.animationDelay = (index * 0.1) + 's';
    });
}

function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover'
        });
    });
}

/**
 * Inicializa los datepickers en la página
 */
function initDatepickers() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".date-picker", {
            locale: "es",
            dateFormat: "d/m/Y",
            allowInput: true
        });
    }
}

/**
 * Inicializa los selectores avanzados en la página
 */
function initSelects() {
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
    }
}

/**
 * Inicializa las confirmaciones en botones de eliminar
 */
function initDeleteConfirmations() {
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este elemento?')) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Función para mostrar mensajes de alerta
 * 
 * @param {string} message Mensaje a mostrar
 * @param {string} type Tipo de alerta (success, danger, warning, info)
 */
function showAlert(message, type = 'info') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: type.charAt(0).toUpperCase() + type.slice(1),
            text: message,
            icon: type === 'danger' ? 'error' : type,
            confirmButtonText: 'Aceptar'
        });
    } else {
        alert(message);
    }
}

/**
 * Función para formatear fechas a formato local
 * 
 * @param {string} dateString Fecha en formato ISO (YYYY-MM-DD)
 * @return {string} Fecha formateada (DD/MM/YYYY)
 */
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).replace(/\//g, '/');
}

/**
 * Función para formatear horas
 * 
 * @param {string} timeString Hora en formato HH:MM:SS
 * @return {string} Hora formateada (HH:MM)
 */
function formatTime(timeString) {
    if (!timeString) return '';
    return timeString.substring(0, 5);
}

/**
 * Trunca un texto a una longitud específica
 * 
 * @param {string} text Texto a truncar
 * @param {number} length Longitud máxima
 * @param {string} suffix Sufijo a añadir si se trunca
 * @return {string} Texto truncado
 */
function truncateText(text, length = 100, suffix = '...') {
    if (!text || text.length <= length) {
        return text;
    }
    return text.substring(0, length) + suffix;
}

/**
 * Array de provincias españolas
 */
var provinciasEspanolas = [
    "Álava", "Albacete", "Alicante", "Almería", "Asturias", "Ávila", "Badajoz", "Barcelona", "Burgos", "Cáceres",
    "Cádiz", "Cantabria", "Castellón", "Ciudad Real", "Córdoba", "Cuenca", "Girona", "Granada", "Guadalajara",
    "Guipúzcoa", "Huelva", "Huesca", "Islas Baleares", "Jaén", "La Coruña", "La Rioja", "Las Palmas", "León",
    "Lleida", "Lugo", "Madrid", "Málaga", "Murcia", "Navarra", "Ourense", "Palencia", "Pontevedra", "Salamanca",
    "Santa Cruz de Tenerife", "Segovia", "Sevilla", "Soria", "Tarragona", "Teruel", "Toledo", "Valencia",
    "Valladolid", "Vizcaya", "Zamora", "Zaragoza", "Ceuta", "Melilla"
];

/**
 * Carga el listado de provincias españolas en un elemento select
 * 
 * @param {string} selectId - El ID del elemento select donde cargar las provincias
 * @param {string} [provinciaSeleccionada=''] - Provincia que debe aparecer seleccionada (opcional)
 */
function cargarProvincias(selectId, provinciaSeleccionada = '') {
    const selectElement = document.getElementById(selectId);
    
    if (!selectElement) {
        return;
    }
    
    // Limpiar las opciones existentes excepto la primera (placeholder)
    while (selectElement.options.length > 1) {
        selectElement.remove(1);
    }
    
    // Añadir las provincias
    provinciasEspanolas.forEach(provincia => {
        const option = document.createElement('option');
        option.value = provincia;
        option.textContent = provincia;
        
        if (provincia === provinciaSeleccionada) {
            option.selected = true;
        }
        
        selectElement.appendChild(option);
    });
}

/**
 * Inicializa los menús desplegables del sidebar
 */
function initSidebarDropdowns() {
    const sidebar = document.querySelector('.sidebar');
    const submenuItems = document.querySelectorAll('.sidebar-item');
    
    if (!sidebar || !submenuItems.length) return;
    
    // Función para comprobar si el sidebar está colapsado
    const isSidebarCollapsed = () => sidebar.classList.contains('collapsed');
    
    // Manejar hover en modo colapsado
    submenuItems.forEach(item => {
        const submenu = item.querySelector('.collapse');
        const toggle = item.querySelector('[data-bs-toggle="collapse"]');
        
        if (!submenu || !toggle) return;
        
        // Detectar la página activa para mantener el menú abierto si es necesario
        const currentPath = window.location.pathname;
        const hasActiveItem = Array.from(submenu.querySelectorAll('a')).some(link => {
            const href = link.getAttribute('href');
            return href && currentPath.includes(href);
        });
        
        // Si hay un ítem activo, abrir el menú
        if (hasActiveItem && !isSidebarCollapsed()) {
            // Usar Bootstrap Collapse para abrir el menú
            const bsCollapse = new bootstrap.Collapse(submenu, {
                toggle: false
            });
            bsCollapse.show();
            toggle.setAttribute('aria-expanded', 'true');
        }
        
        // Manejar eventos de mouse para modo colapsado
        item.addEventListener('mouseenter', () => {
            if (isSidebarCollapsed()) {
                // Posicionar el submenu correctamente
                const itemRect = item.getBoundingClientRect();
                submenu.style.top = `${itemRect.top}px`;
            }
        });
        
        // Manejar cambios en el sidebar
        window.addEventListener('resize', () => {
            if (!isSidebarCollapsed() && hasActiveItem) {
                // Si no está colapsado y tiene un ítem activo, abrir el menú
                const bsCollapse = bootstrap.Collapse.getInstance(submenu) || 
                                  new bootstrap.Collapse(submenu, { toggle: false });
                bsCollapse.show();
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    });
}

/**
 * Inicialización de la búsqueda global
 */
function initGlobalSearch() {
    const searchBox = document.getElementById('globalSearchBox');
    const searchResults = document.getElementById('globalSearchResults');
    const searchDropdown = document.getElementById('globalSearchDropdown');
    
    if (!searchBox || !searchResults) return;
    
    // Crear instancia de bootstrap dropdown si existe
    let dropdown = null;
    if (searchDropdown && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
        dropdown = new bootstrap.Dropdown(searchDropdown);
    }
    
    let debounceTimeout;
    const debounceTime = 300; // ms
    
    searchBox.addEventListener('keyup', function(e) {
        // No procesamos la tecla Escape aquí, la dejamos para que cierre el dropdown
        if (e.key === 'Escape') return;
        
        // Limpiar el timeout anterior
        clearTimeout(debounceTimeout);
        
        const query = this.value.trim();
        
        // Si la consulta está vacía, ocultamos los resultados
        if (query.length === 0) {
            searchResults.innerHTML = '';
            if (dropdown) dropdown.hide();
            return;
        }
        
        // Configurar un nuevo timeout
        debounceTimeout = setTimeout(function() {
            // Mostrar un indicador de carga
            searchResults.innerHTML = '<div class="p-3 text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Buscando...</div>';
            if (dropdown && !dropdown._isShown()) dropdown.show();
            
            // Hacer la petición AJAX
            fetch(BASE_URL + '/ajax/search.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    // Mostrar resultados
                    if (data.results && data.results.length > 0) {
                        let html = '';
                        
                        data.results.forEach(result => {
                            html += `
                                <a href="${result.url}" class="dropdown-item py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <span class="material-symbols-rounded me-2 text-primary">${result.icon || 'search'}</span>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <div class="fw-semibold">${result.title}</div>
                                            <div class="small text-muted">${result.description || ''}</div>
                                        </div>
                                    </div>
                                </a>
                            `;
                        });
                        
                        html += `
                            <div class="dropdown-divider"></div>
                            <a href="${BASE_URL}/search.php?q=${encodeURIComponent(query)}" class="dropdown-item text-center py-2 text-primary">
                                <small>Ver todos los resultados</small>
                            </a>
                        `;
                        
                        searchResults.innerHTML = html;
                    } else {
                        searchResults.innerHTML = '<div class="p-3 text-center text-muted">No se encontraron resultados</div>';
                    }
                    
                    // Asegurar que el dropdown sigue abierto
                    if (dropdown && !dropdown._isShown()) dropdown.show();
                })
                .catch(error => {
                    searchResults.innerHTML = '<div class="p-3 text-center text-danger">Error al realizar la búsqueda</div>';
                    if (dropdown && !dropdown._isShown()) dropdown.show();
                });
        }, debounceTime);
    });
    
    // Cerrar el dropdown al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!searchBox.contains(e.target) && !searchResults.contains(e.target)) {
            if (dropdown) dropdown.hide();
            searchResults.innerHTML = '';
        }
    });
    
    // Manejar la tecla Escape para cerrar el dropdown
    searchBox.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (dropdown) dropdown.hide();
            searchResults.innerHTML = '';
            this.value = '';
        }
    });
}

/**
 * Inicialización del sistema de notificaciones
 */
function initNotifications() {
    const notificationsButton = document.getElementById('notifications-toggle');
    const notificationsContainer = document.getElementById('notifications-container');
    const notificationCountBadge = document.getElementById('notification-count');
    
    if (!notificationsButton || !notificationsContainer || !notificationCountBadge) {
        return;
    }
    
    let notificationsLoaded = false;
    
    // Cargar notificaciones al hacer clic en el botón
    notificationsButton.addEventListener('click', function() {
        if (!notificationsLoaded) {
            loadNotifications();
            notificationsLoaded = true;
        }
    });
    
    // Función para cargar notificaciones
    function loadNotifications() {
        notificationsContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando notificaciones...</p>
            </div>
        `;
        
        fetch(BASE_URL + '/ajax/notifications.php')
            .then(response => response.json())
            .then(data => {
                updateNotifications(data);
            })
            .catch(error => {
                notificationsContainer.innerHTML = `
                    <div class="text-center py-4 text-danger">
                        <span class="material-symbols-rounded display-4">error</span>
                        <p class="mt-2">Error al cargar las notificaciones</p>
                    </div>
                `;
            });
    }
    
    // Función para actualizar notificaciones
    function updateNotifications(data) {
        if (!data || !data.notifications) {
            notificationsContainer.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <span class="material-symbols-rounded display-4">notifications_off</span>
                    <p class="mt-2">No hay notificaciones</p>
                </div>
            `;
            notificationCountBadge.textContent = '0';
            notificationCountBadge.style.display = 'none';
            return;
        }
        
        const count = data.unreadCount || 0;
        notificationCountBadge.textContent = count > 99 ? '99+' : count;
        notificationCountBadge.style.display = count > 0 ? 'inline-block' : 'none';
        
        let html = `
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h6 class="mb-0">Notificaciones</h6>
                <button type="button" class="btn btn-sm btn-link" id="mark-all-read">Marcar todo como leído</button>
            </div>
        `;
        
        if (data.notifications.length === 0) {
            html += `
                <div class="text-center py-4 text-muted">
                    <span class="material-symbols-rounded display-4">notifications_off</span>
                    <p class="mt-2">No hay notificaciones</p>
                </div>
            `;
        } else {
            html += '<div class="overflow-auto" style="max-height: 400px;">';
            
            data.notifications.forEach(notification => {
                const isUnread = !notification.read;
                const timeAgo = formatTimeAgo(new Date(notification.created_at));
                
                html += `
                    <div class="notification-item p-3 border-bottom ${isUnread ? 'bg-light' : ''}" data-id="${notification.id}">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <span class="material-symbols-rounded notification-icon ${getNotificationIconClass(notification.type)}">
                                    ${getNotificationIcon(notification.type)}
                                </span>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h6 class="mb-1 ${isUnread ? 'fw-bold' : ''}">${notification.title}</h6>
                                    <small class="text-muted ms-2">${timeAgo}</small>
                                </div>
                                <p class="mb-1 small">${notification.message}</p>
                                ${notification.link ? `<a href="${notification.link}" class="small">Ver detalles</a>` : ''}
                                ${isUnread ? `
                                    <button type="button" class="btn btn-sm btn-link p-0 mt-1 mark-read" data-id="${notification.id}">
                                        Marcar como leída
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
        }
        
        html += `
            <div class="text-center p-2 border-top">
                <a href="${BASE_URL}/notifications.php" class="btn btn-link btn-sm">Ver todas las notificaciones</a>
            </div>
        `;
        
        notificationsContainer.innerHTML = html;
        
        // Agregar eventos para marcar como leídas
        document.querySelectorAll('.mark-read').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                markAsRead(id);
            });
        });
        
        // Evento para marcar todas como leídas
        const markAllReadBtn = document.getElementById('mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                markAllAsRead();
            });
        }
        
        // Hacer que al hacer clic en la notificación también marque como leída
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                const link = this.querySelector('a');
                if (link && link.contains(e.target)) {
                    // No hacer nada si se hizo clic en el enlace, dejarlo manejar
                    return;
                }
                
                if (this.classList.contains('bg-light')) {
                    const id = this.getAttribute('data-id');
                    markAsRead(id);
                }
            });
        });
    }
    
    // Función para marcar una notificación como leída
    function markAsRead(id) {
        fetch(BASE_URL + '/ajax/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la UI
                const notificationItem = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('bg-light');
                    const title = notificationItem.querySelector('h6');
                    if (title) title.classList.remove('fw-bold');
                    const markReadBtn = notificationItem.querySelector('.mark-read');
                    if (markReadBtn) markReadBtn.remove();
                }
                
                // Actualizar contador
                let count = parseInt(notificationCountBadge.textContent);
                if (!isNaN(count) && count > 0) {
                    count--;
                    notificationCountBadge.textContent = count;
                    if (count === 0) {
                        notificationCountBadge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            // Error silencioso
        });
    }
    
    // Función para marcar todas las notificaciones como leídas
    function markAllAsRead() {
        fetch(BASE_URL + '/ajax/mark_all_notifications_read.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la UI
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('bg-light');
                    const title = item.querySelector('h6');
                    if (title) title.classList.remove('fw-bold');
                    const markReadBtn = item.querySelector('.mark-read');
                    if (markReadBtn) markReadBtn.remove();
                });
                
                // Actualizar contador
                notificationCountBadge.textContent = '0';
                notificationCountBadge.style.display = 'none';
            }
        })
        .catch(error => {
            // Error silencioso
        });
    }
    
    // Funciones auxiliares para las notificaciones
    function getNotificationIcon(type) {
        switch (type) {
            case 'appointment':
                return 'event';
            case 'payment':
                return 'payments';
            case 'message':
                return 'message';
            case 'alert':
                return 'warning';
            case 'update':
                return 'system_update';
            default:
                return 'notifications';
        }
    }
    
    function getNotificationIconClass(type) {
        switch (type) {
            case 'appointment':
                return 'text-primary';
            case 'payment':
                return 'text-success';
            case 'message':
                return 'text-info';
            case 'alert':
                return 'text-warning';
            case 'update':
                return 'text-secondary';
            default:
                return 'text-primary';
        }
    }
    
    function formatTimeAgo(date) {
        const now = new Date();
        const diffMs = now - date;
        const diffSecs = Math.floor(diffMs / 1000);
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);
        
        if (diffSecs < 60) {
            return 'Ahora mismo';
        } else if (diffMins < 60) {
            return `Hace ${diffMins} ${diffMins === 1 ? 'minuto' : 'minutos'}`;
        } else if (diffHours < 24) {
            return `Hace ${diffHours} ${diffHours === 1 ? 'hora' : 'horas'}`;
        } else if (diffDays < 7) {
            return `Hace ${diffDays} ${diffDays === 1 ? 'día' : 'días'}`;
        } else {
            return date.toLocaleDateString();
        }
    }
}

/**
 * Inicializa el toggle del tema (claro/oscuro)
 */
function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const lightIcon = document.querySelector('.theme-icon-light');
    const darkIcon = document.querySelector('.theme-icon-dark');
    
    if (!themeToggle || !lightIcon || !darkIcon) return;
    
    // Verificar si hay un tema guardado en localStorage
    const savedTheme = localStorage.getItem('theme');
    
    // Aplicar tema guardado o por defecto
    if (savedTheme === 'dark') {
        body.setAttribute('data-theme', 'dark');
        lightIcon.classList.add('d-none');
        darkIcon.classList.remove('d-none');
    } else {
        body.setAttribute('data-theme', 'light');
        darkIcon.classList.add('d-none');
        lightIcon.classList.remove('d-none');
    }
    
    // Alternar tema al hacer clic en el botón
    themeToggle.addEventListener('click', function() {
        console.log('Theme toggle clicked');
        if (body.getAttribute('data-theme') === 'dark') {
            body.setAttribute('data-theme', 'light');
            darkIcon.classList.add('d-none');
            lightIcon.classList.remove('d-none');
            localStorage.setItem('theme', 'light');
        } else {
            body.setAttribute('data-theme', 'dark');
            lightIcon.classList.add('d-none');
            darkIcon.classList.remove('d-none');
            localStorage.setItem('theme', 'dark');
        }
    });
}

/**
 * Inicializa la funcionalidad de notas personales en el dashboard
 */
function initDashboardNotes() {
    // Comprobar si estamos en una página con el widget de notas
    const notesContainer = document.getElementById('dashboardNotes');
    if (!notesContainer) return;
    
    const notesForm = document.getElementById('noteForm');
    const notesList = document.querySelector('.notes-list');
    const notesModal = document.getElementById('noteModal');
    const modal = notesModal ? new bootstrap.Modal(notesModal) : null;
    
    // Elementos del formulario
    const noteIdInput = document.getElementById('noteId');
    const noteContentInput = document.getElementById('noteContent');
    const noteColorBtns = document.querySelectorAll('.color-selector .color-btn');
    const noteColorInput = document.getElementById('noteColor');
    
    // Botones
    const addNoteBtn = document.getElementById('addNoteBtn');
    
    // Escuchar eventos
    if (addNoteBtn) {
        addNoteBtn.addEventListener('click', function() {
            // Limpiar el formulario
            if (noteIdInput) noteIdInput.value = '';
            if (noteContentInput) noteContentInput.value = '';
            if (noteColorInput) noteColorInput.value = 'primary';
            
            // Actualizar selección de color
            if (noteColorBtns) {
                noteColorBtns.forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.dataset.color === 'primary') {
                        btn.classList.add('active');
                    }
                });
            }
            
            // Actualizar título del modal
            if (notesModal) {
                const modalTitle = notesModal.querySelector('.modal-title');
                if (modalTitle) modalTitle.textContent = 'Añadir nueva nota';
            }
            
            // Mostrar modal
            if (modal) modal.show();
        });
    }
    
    // Manejar la selección de color
    if (noteColorBtns && noteColorInput) {
        noteColorBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Quitar clase active de todos los botones
                noteColorBtns.forEach(b => b.classList.remove('active'));
                
                // Añadir active al botón seleccionado
                this.classList.add('active');
                
                // Actualizar el valor del input hidden
                noteColorInput.value = this.dataset.color;
            });
        });
    }
    
    // Guardar nota
    if (notesForm) {
        notesForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar
            if (!noteContentInput || !noteContentInput.value.trim()) {
                showAlert('El contenido de la nota es obligatorio', 'warning');
                return;
            }
            
            // Crear FormData
            const formData = new FormData(notesForm);
            const noteId = noteIdInput ? noteIdInput.value : '';
            
            // Determinar acción
            const action = noteId ? 'update' : 'create';
            formData.append('action', action);
            
            // Deshabilitar botón submit para evitar duplicados
            const submitBtn = notesForm.querySelector('[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            
            // Enviar datos al servidor
            fetch('modules/dashboard/save_note.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Ocultar modal
                    if (modal) modal.hide();
                    
                    // Mostrar mensaje de éxito
                    showAlert(data.message, 'success');
                    
                    // Recargar notas
                    loadNotes();
                } else {
                    showAlert(data.message || 'Error al guardar la nota', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al comunicarse con el servidor', 'danger');
            })
            .finally(() => {
                // Rehabilitar botón
                if (submitBtn) submitBtn.disabled = false;
            });
        });
    }
    
    // Cargar notas iniciales
    loadNotes();
    
    // Función para cargar notas
    function loadNotes() {
        if (!notesList) return;
        
        // Mostrar indicador de carga
        notesList.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
        
        // Obtener notas
        fetch('modules/dashboard/widgets/notes.php?action=get_notes')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar las notas');
                }
                return response.text();
            })
            .then(html => {
                notesList.innerHTML = html;
                
                // Inicializar eventos después de cargar notas
                initNoteEvents();
            })
            .catch(error => {
                console.error('Error:', error);
                notesList.innerHTML = '<div class="alert alert-danger">Error al cargar las notas</div>';
            });
    }
    
    // Inicializar eventos en las notas
    function initNoteEvents() {
        // Botones de editar
        const editBtns = notesList.querySelectorAll('.edit-note');
        
        editBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const noteId = this.dataset.id;
                const noteContent = this.closest('.note-item').querySelector('.note-content').textContent;
                const noteColor = this.closest('.note-item').dataset.color || 'primary';
                
                // Actualizar formulario
                if (noteIdInput) noteIdInput.value = noteId;
                if (noteContentInput) noteContentInput.value = noteContent;
                if (noteColorInput) noteColorInput.value = noteColor;
                
                // Actualizar selección de color
                if (noteColorBtns) {
                    noteColorBtns.forEach(btn => {
                        btn.classList.remove('active');
                        if (btn.dataset.color === noteColor) {
                            btn.classList.add('active');
                        }
                    });
                }
                
                // Actualizar título del modal
                if (notesModal) {
                    const modalTitle = notesModal.querySelector('.modal-title');
                    if (modalTitle) modalTitle.textContent = 'Editar nota';
                }
                
                // Mostrar modal
                if (modal) modal.show();
            });
        });
        
        // Botones de eliminar
        const deleteBtns = notesList.querySelectorAll('.delete-note');
        
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('¿Estás seguro de que deseas eliminar esta nota? Esta acción no se puede deshacer.')) {
                    return;
                }
                
                const noteId = this.dataset.id;
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', noteId);
                
                // Deshabilitar botón
                this.disabled = true;
                
                // Enviar datos al servidor
                fetch('modules/dashboard/save_note.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        showAlert(data.message, 'success');
                        
                        // Eliminar nota del DOM o recargar todas
                        const noteItem = this.closest('.note-item');
                        if (noteItem) {
                            noteItem.remove();
                            
                            // Si no quedan notas, mostrar mensaje
                            if (notesList.querySelectorAll('.note-item').length === 0) {
                                notesList.innerHTML = '<div class="text-center p-3 text-muted">No hay notas. Añade una nueva para empezar.</div>';
                            }
                        } else {
                            loadNotes();
                        }
                    } else {
                        showAlert(data.message || 'Error al eliminar la nota', 'danger');
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error al comunicarse con el servidor', 'danger');
                    this.disabled = false;
                });
            });
        });
    }
}

/**
 * Envía un evento personalizado para cargar recursos
 * @param {string} groupId - ID del grupo de recursos a cargar
 */
function requestResources(groupId) {
    document.dispatchEvent(new CustomEvent('resource:request', { detail: { groupId: groupId } }));
}

/**
 * Inicialización del sidebar responsive
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar el sidebar
    initSidebar();
    
    // Hacer las tablas responsivas
    makeTablesResponsive();
    
    // Ajustar textos largos en los elementos .display-4
    adjustLongText();
    
    // Agregar listener para redimensionamiento de ventana
    window.addEventListener('resize', function() {
        adjustLongText();
    });
});

/**
 * Inicializa todas las funcionalidades del sidebar
 */
function initSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const body = document.body;
    
    if (!sidebar) return;
    
    // 1. Crear backdrop para cerrar el sidebar en móvil
    const backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
    
    // 2. Botón de toggle para móviles
    const mobileToggle = document.createElement('button');
    mobileToggle.className = 'btn sidebar-toggle-fixed d-lg-none';
    mobileToggle.innerHTML = '<span class="material-symbols-rounded">menu</span>';
    document.body.appendChild(mobileToggle);
    
    // 3. Funcionalidad para el botón de toggle en móvil
    mobileToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');
    });
    
    // 4. Cerrar sidebar al hacer clic en el backdrop
    backdrop.addEventListener('click', function() {
        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
    });
    
    // 5. Funcionalidad para el botón de toggle en desktop (header)
    const desktopToggle = document.querySelector('#sidebarToggle');
    if (desktopToggle) {
        desktopToggle.addEventListener('click', function() {
            body.classList.toggle('sidebar-collapsed');
            // Guardar estado en localStorage
            localStorage.setItem('sidebar-collapsed', body.classList.contains('sidebar-collapsed'));
        });
    }
    
    // 6. Restaurar estado del sidebar desde localStorage
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        body.classList.add('sidebar-collapsed');
    }
    
    // 7. Cerrar sidebar al hacer clic en enlaces en móvil
    const sidebarLinks = sidebar.querySelectorAll('a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                setTimeout(function() {
                    sidebar.classList.remove('show');
                    backdrop.classList.remove('show');
                }, 150);
            }
        });
    });
}

/**
 * Hace que todas las tablas sean responsivas
 */
function makeTablesResponsive() {
    const tables = document.querySelectorAll('table:not(.responsive-table)');
    tables.forEach(table => {
        // Si la tabla no está ya dentro de un contenedor responsivo
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
            table.classList.add('responsive-table');
        }
    });
}

/**
 * Ajusta los textos largos para asegurar que se muestren correctamente
 */
function adjustLongText() {
    const longTextElements = document.querySelectorAll('.display-4, h1.large-text, .greeting');
    longTextElements.forEach(element => {
        // Asegurar word-break y overflow wrap para evitar que el texto se salga
        element.style.wordBreak = 'break-word';
        element.style.overflowWrap = 'break-word';
        
        // Ajustar tamaño de texto según el ancho de pantalla
        if (window.innerWidth < 576) {
            element.style.fontSize = '1.8rem';
        } else if (window.innerWidth < 768) {
            element.style.fontSize = '2.2rem';
        } else if (window.innerWidth < 992) {
            element.style.fontSize = '2.5rem';
        } else {
            element.style.fontSize = '';  // Valores predeterminados
        }
    });
}

/**
 * Funciones para el módulo de Escalas Psicológicas
 */

// Función para confirmar eliminación
function confirmarEliminacion(mensaje, url) {
    if (confirm(mensaje)) {
        window.location.href = url;
    }
    return false;
}

// Función para inicializar DataTables en las tablas del módulo
function inicializarTablaEscalas(tableId, config = {}) {
    // Configuración por defecto
    const defaultConfig = {
        language: {
            processing: "Procesando...",
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            infoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
            infoFiltered: "(filtrado de un total de _MAX_ registros)",
            infoPostFix: "",
            loadingRecords: "Cargando...",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "Ningún dato disponible en esta tabla",
            paginate: {
                first: "Primero",
                previous: "Anterior",
                next: "Siguiente",
                last: "Último"
            },
            aria: {
                sortAscending: ": Activar para ordenar la columna de manera ascendente",
                sortDescending: ": Activar para ordenar la columna de manera descendente"
            }
        },
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                text: 'Copiar',
                className: 'btn btn-sm btn-outline-secondary me-1'
            },
            {
                extend: 'excel',
                text: 'Excel',
                className: 'btn btn-sm btn-outline-success me-1'
            },
            {
                extend: 'pdf',
                text: 'PDF',
                className: 'btn btn-sm btn-outline-danger me-1'
            },
            {
                extend: 'print',
                text: 'Imprimir',
                className: 'btn btn-sm btn-outline-primary'
            }
        ]
    };

    // Combinar configuración por defecto con la configuración personalizada
    const mergedConfig = { ...defaultConfig, ...config };
    
    // Inicializar DataTable
    return $(tableId).DataTable(mergedConfig);
}

// Validación de formularios de escalas
function validarFormularioEscala(formId) {
    const form = document.getElementById(formId);
    
    if (!form) return true;
    
    let isValid = true;
    
    // Validar campos requeridos
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
            
            // Crear mensaje de error si no existe
            if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('invalid-feedback')) {
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = 'Este campo es obligatorio';
                field.parentNode.insertBefore(feedback, field.nextElementSibling);
            }
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Función para añadir dinámicamente nuevos ítems a una escala
function agregarNuevoItem() {
    const itemsContainer = document.getElementById('items-container');
    if (!itemsContainer) return;
    
    const itemCount = itemsContainer.querySelectorAll('.item-row').length;
    const newItemNumber = itemCount + 1;
    
    const itemTemplate = `
        <div class="card mb-3 item-row">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Ítem #${newItemNumber}</span>
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarItem(this)">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label for="items[${newItemNumber}][numero]" class="form-label">Número</label>
                        <input type="number" class="form-control" id="items[${newItemNumber}][numero]" name="items[${newItemNumber}][numero]" value="${newItemNumber}" min="1" required>
                    </div>
                    <div class="col-md-8">
                        <label for="items[${newItemNumber}][texto]" class="form-label">Texto del ítem</label>
                        <input type="text" class="form-control" id="items[${newItemNumber}][texto]" name="items[${newItemNumber}][texto]" required>
                    </div>
                    <div class="col-md-2">
                        <label for="items[${newItemNumber}][subescala]" class="form-label">Subescala</label>
                        <input type="text" class="form-control" id="items[${newItemNumber}][subescala]" name="items[${newItemNumber}][subescala]">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="items[${newItemNumber}][tipo_respuesta]" class="form-label">Tipo de respuesta</label>
                        <select class="form-select" id="items[${newItemNumber}][tipo_respuesta]" name="items[${newItemNumber}][tipo_respuesta]" onchange="mostrarOpcionesRespuesta(${newItemNumber}, this.value)" required>
                            <option value="">Seleccione...</option>
                            <option value="likert3">Likert 3 puntos</option>
                            <option value="likert4">Likert 4 puntos</option>
                            <option value="likert5">Likert 5 puntos</option>
                            <option value="si_no">Sí/No</option>
                            <option value="numerica">Numérica</option>
                            <option value="seleccion_multiple">Selección múltiple</option>
                        </select>
                    </div>
                    <div class="col-md-6 opciones-respuesta-container" id="opciones-respuesta-${newItemNumber}" style="display:none;">
                        <label for="items[${newItemNumber}][opciones_respuesta]" class="form-label">Opciones de respuesta</label>
                        <input type="text" class="form-control" id="items[${newItemNumber}][opciones_respuesta]" name="items[${newItemNumber}][opciones_respuesta]" placeholder="Separadas por comas">
                        <small class="form-text text-muted">Ejemplo: Opción 1, Opción 2, Opción 3</small>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="items[${newItemNumber}][inversion]" name="items[${newItemNumber}][inversion]" value="1">
                            <label class="form-check-label" for="items[${newItemNumber}][inversion]">
                                Puntuación invertida
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Añadir nuevo ítem al contenedor
    itemsContainer.insertAdjacentHTML('beforeend', itemTemplate);
}

// Función para eliminar un ítem
function eliminarItem(button) {
    const itemRow = button.closest('.item-row');
    itemRow.remove();
    
    // Renumerar los ítems restantes
    const itemRows = document.querySelectorAll('.item-row');
    itemRows.forEach((row, index) => {
        const itemNumber = index + 1;
        row.querySelector('.card-header span').textContent = `Ítem #${itemNumber}`;
    });
}

// Función para mostrar/ocultar el campo de opciones de respuesta
function mostrarOpcionesRespuesta(itemNumber, tipoRespuesta) {
    const opcionesContainer = document.getElementById(`opciones-respuesta-${itemNumber}`);
    
    if (tipoRespuesta === 'seleccion_multiple') {
        opcionesContainer.style.display = 'block';
        opcionesContainer.querySelector('input').setAttribute('required', 'required');
    } else {
        opcionesContainer.style.display = 'none';
        opcionesContainer.querySelector('input').removeAttribute('required');
    }
} 