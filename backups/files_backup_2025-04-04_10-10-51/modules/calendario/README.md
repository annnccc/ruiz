# Módulo de Calendario Interactivo

Este módulo proporciona una interfaz de calendario completa para la gestión de citas médicas, con funcionalidades avanzadas que incluyen arrastrar y soltar para reprogramación y redimensionamiento para cambiar la duración.

## Características Principales

- **Visualización Flexible**: Vistas de mes, semana y día
- **Reprogramación mediante Arrastrar y Soltar**: Mueve citas fácilmente a nuevas fechas/horas
- **Ajuste de Duración**: Redimensiona eventos para cambiar su duración
- **Filtros por Estado**: Filtra citas por pendientes, completadas o canceladas
- **Detección de Conflictos**: Evita colisiones entre citas
- **Registro de Cambios**: Historial completo de modificaciones
- **Interfaz Intuitiva**: Diseño moderno compatible con Bootstrap 5
- **Adaptado para Dispositivos Móviles**: Funciona en pantallas táctiles
- **Cambio de Estado**: Actualiza el estado de las citas directamente desde el calendario
- **Ayuda Contextual**: Modal de instrucciones para nuevos usuarios

## Archivos del Módulo

1. **index.php**: Vista principal del calendario con la interfaz de usuario y la lógica JavaScript
2. **update_date.php**: API para actualizar fechas cuando se arrastran eventos
3. **update_time.php**: API para actualizar horarios cuando se redimensionan eventos

## Tecnologías Utilizadas

- **FullCalendar 5**: Biblioteca JavaScript para calendario interactivo
- **Bootstrap 5**: Framework para estilos y componentes UI
- **Vanilla JavaScript**: Para manipulación DOM y peticiones AJAX
- **PHP/MySQL**: Para el backend y almacenamiento de datos

## Uso

Los usuarios pueden:
- Ver todas las citas en un formato de calendario intuitivo
- Arrastrar y soltar citas para reprogramarlas
- Ajustar la duración de las citas mediante redimensionamiento
- Filtrar citas por su estado para una mejor visualización
- Ver detalles completos haciendo clic en cada cita
- Crear nuevas citas haciendo clic en intervalos libres

## Integración

Este módulo se integra con:
- Sistema de pacientes
- Gestión de citas
- Sistema de historiales
- Notificaciones (futuro)

## Implementación Técnica

El sistema utiliza transacciones de base de datos para garantizar la integridad de los datos al reprogramar citas, y validaciones para evitar:
- Conflictos entre citas
- Duraciones inválidas (mínimo 15 minutos)
- Modificaciones no autorizadas 