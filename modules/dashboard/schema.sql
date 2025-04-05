-- Crear tabla de configuración del dashboard
CREATE TABLE IF NOT EXISTS dashboard_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    widgets TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY usuario_dashboard (usuario_id)
);

-- Crear tabla de widgets disponibles
CREATE TABLE IF NOT EXISTS dashboard_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    icono VARCHAR(50) DEFAULT 'widgets',
    tamano_predeterminado VARCHAR(50) DEFAULT 'col-md-6',
    orden_predeterminado INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY codigo_widget (codigo)
);

-- Insertar widgets predeterminados
INSERT INTO dashboard_widgets 
    (codigo, nombre, descripcion, icono, tamano_predeterminado, orden_predeterminado, activo) 
VALUES
    ('welcome_banner', 'Banner de Bienvenida', 'Muestra un saludo personalizado con la fecha actual y el clima en Madrid', 'waving_hand', 'col-12', 1, 1),
    ('stats_cards', 'Estadísticas Generales', 'Muestra estadísticas generales de pacientes y citas', 'dashboard', 'col-12', 5, 1),
    ('appointments_today', 'Citas para Hoy', 'Muestra las citas programadas para hoy', 'today', 'col-md-6', 10, 1),
    ('upcoming_appointments', 'Próximas Citas', 'Muestra las próximas citas programadas', 'date_range', 'col-md-6', 15, 1),
    ('latest_patients', 'Últimos Pacientes', 'Muestra los últimos pacientes añadidos', 'group', 'col-md-6', 20, 1),
    ('latest_appointments', 'Últimas Citas', 'Muestra las últimas citas registradas', 'schedule', 'col-md-6', 25, 1),
    ('quick_actions', 'Acciones Rápidas', 'Accesos directos a acciones comunes', 'bolt', 'col-md-6 col-lg-4', 30, 1),
    ('notes', 'Notas Rápidas', 'Permite crear y gestionar notas rápidas', 'sticky_note_2', 'col-md-6 col-lg-8', 35, 1)
ON DUPLICATE KEY UPDATE 
    nombre = VALUES(nombre),
    descripcion = VALUES(descripcion),
    icono = VALUES(icono),
    tamano_predeterminado = VALUES(tamano_predeterminado),
    orden_predeterminado = VALUES(orden_predeterminado),
    activo = VALUES(activo); 