# Clínica Ruiz - Registro de Cambios

Este documento registra todos los cambios importantes realizados en el sistema, agrupados por funcionalidades.

## Portal del Paciente

### Correcciones en el acceso al portal
- **Archivos modificados**:
  - `includes/layouts/portal.php` (nuevo)
  - `modules/portal_paciente/index.php`
  - `modules/portal_paciente/historial_completo.php`
  - `modules/portal_paciente/calendar_sync.php`
- **Cambios realizados**:
  - Creación del archivo de layout específico para el portal del paciente
  - Corrección de consultas SQL para eliminar referencias a tabla `usuarios` y columna `usuario_id`
  - Uso de valores genéricos para información del médico para evitar errores

### Configuración de acceso para pacientes
- **Archivos modificados**:
  - Base de datos: Tabla `pacientes` (columna `documento_identidad`)
- **Cambios realizados**:
  - Habilitado acceso mediante email y documento de identidad
  - URL de acceso: `https://app.ruizarrietapsicologia.com/ruiz/modules/portal_paciente/login.php`

## Sistema de Notificaciones

### Implementación de notificaciones
- **Archivos modificados**:
  - `ajax/notifications.php` (nuevo)
  - `ajax/mark_notification_read.php` (nuevo)
  - `ajax/mark_all_notifications_read.php` (nuevo)
  - `includes/functions.php`
- **Cambios realizados**:
  - Creación de endpoints AJAX para obtener notificaciones de citas
  - Implementación de sistema para marcar notificaciones como leídas
  - Sistema muestra citas pendientes para hoy y mañana

## Gestión de Pacientes

### Modificación de visualización de datos
- **Archivos modificados**:
  - `modules/pacientes/list.php`
  - `modules/pacientes/search.php`
- **Cambios realizados**:
  - Separación de columna "Contacto" en columnas "Teléfono" y "Email"
  - Ajuste de colspan en mensajes de error y carga
  - Mantenimiento del formato de consentimiento centrado

### Validación de DNI/NIE
- **Archivos modificados**:
  - `includes/helpers/validation_helper.php`
  - `includes/functions.php`
- **Cambios realizados**:
  - Desactivación de validación estricta del formato DNI/NIE
  - Modificación de la función `isValidDNI()` para aceptar cualquier formato

## Gestión de Citas

### Integración de WhatsApp para recordatorios
- **Archivos modificados**:
  - `modules/citas/list.php`
  - `assets/css/style.css`
- **Cambios realizados**:
  - Adición de botón para envío de recordatorios vía WhatsApp
  - Cambio de icono a `smartphone` para mejor identificación visual
  - Estilos personalizados para el botón de WhatsApp

### Mejoras en la interfaz de citas
- **Archivos modificados**:
  - `modules/citas/list.php`
- **Cambios realizados**:
  - Restauración del formato original de botones de acción
  - Centrado de botones de acción para mejor experiencia de usuario

## Consentimientos

### Sistema de gestión de consentimientos
- **Archivos modificados**:
  - `modules/consentimientos/schema.sql`
- **Cambios realizados**:
  - Implementación de tablas para modelos de consentimientos
  - Sistema de seguimiento de estado (pendiente, firmado, caducado)
  - Almacenamiento de firmas y datos de los firmantes

## Seguridad y Cumplimiento Normativo

### Sistema de Copias de Seguridad Automatizadas
- **Archivos modificados**:
  - `modules/configuracion/backup.php` (nuevo)
  - `modules/configuracion/backup_history.php` (nuevo)
  - `modules/configuracion/backup_detail.php` (nuevo)
  - `modules/configuracion/restore_backup.php` (nuevo)
  - `includes/classes/BackupManager.php` (nuevo)
  - `includes/classes/StorageHandler.php` (nuevo)
- **Cambios realizados**:
  - Implementación de sistema completo de copias de seguridad automatizadas
  - Opciones para diferentes tipos de backup (completo, incremental, diferencial)
  - Cifrado opcional de las copias de seguridad
  - Almacenamiento local y remoto (FTP, SFTP, S3)
  - Verificación y restauración de copias de seguridad
  - Limpieza automática de copias antiguas

### Acceso al Sistema de Copias de Seguridad
- **URL de acceso**: `https://app.ruizarrietapsicologia.com/ruiz/modules/configuracion/backup.php`
- **Funcionalidades disponibles**:
  - Crear nuevas configuraciones de copia de seguridad
  - Programar copias (manual, diaria, semanal, mensual)
  - Ejecutar copias de seguridad bajo demanda
  - Ver historial de copias realizadas
  - Verificar integridad de las copias
  - Restaurar copias de seguridad (solo administradores)

---

## Pendientes y Mejoras Futuras

- Implementación completa del portal del paciente
  - Solicitud de citas online
  - Gestión de documentos
  - Comunicación segura con profesionales
- Mejora del sistema de notificaciones
  - Notificaciones en tiempo real
  - Configuración de preferencias de notificaciones
- Optimización de la experiencia móvil
- Implementación de funcionalidades clínicas avanzadas
  - Historia clínica estructurada con formularios específicos para diferentes evaluaciones
  - Sistema de escalas psicológicas integrado (STAI, BDI, etc.) con puntuación automática
  - Recordatorios inteligentes basados en diagnóstico y evolución del paciente
- Ampliación del sistema de seguridad y cumplimiento
  - Sistema de auditoría de accesos
  - Cifrado avanzado de datos sensibles
  - Exportación RGPD completa

---

*Última actualización: Mayo 2023* 