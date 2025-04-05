# Propuesta: Sistema de Recordatorios Inteligentes

## Resumen Ejecutivo

Esta propuesta detalla la implementación de un sistema de recordatorios inteligentes para la Clínica Ruiz. El sistema analizará diagnósticos, patrones de citas, y datos clínicos para sugerir automáticamente el momento óptimo para seguimientos, evaluaciones y otras intervenciones, mejorando tanto la eficiencia clínica como los resultados terapéuticos.

## Objetivos

1. Automatizar la programación de seguimientos según diagnóstico y plan terapéutico
2. Reducir abandonos prematuros del tratamiento mediante recordatorios oportunos
3. Optimizar la frecuencia de las sesiones según la evolución del paciente
4. Facilitar la detección temprana de recaídas o empeoramiento
5. Mejorar la adherencia al tratamiento de los pacientes

## Características Principales

### 1. Seguimientos basados en diagnóstico

El sistema propondrá automáticamente patrones de seguimiento según:

- **Tipo de diagnóstico**:
  - Depresión: Seguimientos semanales iniciales, quincenales en fase de mejoría
  - Ansiedad: Seguimientos semanales o quincenales según intensidad
  - Trastorno bipolar: Seguimientos más frecuentes durante cambios de medicación
  
- **Severidad de la condición**:
  - Casos leves: Seguimientos espaciados con mayor autonomía
  - Casos moderados: Seguimientos regulares con evaluación de progreso
  - Casos severos: Seguimientos frecuentes con protocolos de crisis

- **Fase del tratamiento**:
  - Fase inicial: Evaluación completa y establecimiento de alianza terapéutica
  - Fase intermedia: Seguimiento de progreso de objetivos
  - Fase final: Prevención de recaídas y cierre

### 2. Alertas según puntuaciones en escalas

El sistema se integrará con el módulo de escalas psicológicas para:

- Generar alertas cuando puntuaciones indiquen riesgo (ej. ideación suicida)
- Sugerir reevaluaciones periódicas según el instrumento
- Detectar cambios significativos que requieran atención inmediata

### 3. Patrones personalizados según respuesta al tratamiento

A partir del análisis de datos históricos, el sistema podrá:

- Identificar patrones individuales de respuesta terapéutica
- Ajustar recomendaciones según la velocidad de progreso
- Sugerir modificaciones en la frecuencia de sesiones

### 4. Recordatorios para el profesional

El sistema generará diferentes tipos de recordatorios:

- **Recordatorios críticos**: Requieren acción inmediata (ej. pacientes con riesgo)
- **Recordatorios de programación**: Sugieren programar una próxima sesión
- **Recordatorios de seguimiento**: Verificación de progreso entre sesiones
- **Recordatorios de reevaluación**: Aplicación de instrumentos de evaluación

### 5. Mensajes para pacientes

Implementación de mensajes automatizados para pacientes:

- Confirmación de citas programadas
- Recordatorios de tareas terapéuticas
- Cuestionarios breves de seguimiento
- Avisos para completar escalas de evaluación

## Arquitectura del Sistema

### Base de Datos

Para implementar el sistema de recordatorios inteligentes, se crearán las siguientes tablas:

```sql
-- Tabla para reglas de recordatorios según diagnóstico
CREATE TABLE IF NOT EXISTS `recordatorios_reglas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `diagnostico` varchar(255) NOT NULL,
  `severidad` enum('leve', 'moderado', 'grave') DEFAULT NULL,
  `fase_tratamiento` enum('inicial', 'intermedia', 'final') DEFAULT NULL,
  `dias_seguimiento` int(11) NOT NULL,
  `descripcion` text,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `diagnostico` (`diagnostico`),
  KEY `severidad` (`severidad`),
  KEY `fase_tratamiento` (`fase_tratamiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para reglas basadas en puntuaciones de escalas
CREATE TABLE IF NOT EXISTS `recordatorios_reglas_escalas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `escala_id` int(11) NOT NULL,
  `subescala` varchar(100) DEFAULT NULL,
  `condicion` enum('mayor_que', 'menor_que', 'igual_a', 'entre') NOT NULL,
  `valor1` float NOT NULL,
  `valor2` float DEFAULT NULL,
  `prioridad` enum('baja', 'media', 'alta', 'urgente') NOT NULL DEFAULT 'media',
  `mensaje` text NOT NULL,
  `dias_seguimiento` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `escala_id` (`escala_id`),
  CONSTRAINT `fk_regla_escala` FOREIGN KEY (`escala_id`) REFERENCES `escalas_catalogo` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para recordatorios generados
CREATE TABLE IF NOT EXISTS `recordatorios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tipo` enum('critico', 'programacion', 'seguimiento', 'reevaluacion') NOT NULL,
  `origen` enum('diagnostico', 'escala', 'patron', 'manual') NOT NULL,
  `origen_id` int(11) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `fecha_sugerida` date NOT NULL,
  `estado` enum('pendiente', 'programado', 'completado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_accion` datetime DEFAULT NULL,
  `notas` text,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `estado` (`estado`),
  KEY `fecha_sugerida` (`fecha_sugerida`),
  CONSTRAINT `fk_recordatorio_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recordatorio_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para mensajes a pacientes
CREATE TABLE IF NOT EXISTS `recordatorios_mensajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recordatorio_id` int(11) NOT NULL,
  `tipo_mensaje` enum('sms', 'email', 'whatsapp', 'portal') NOT NULL,
  `contenido` text NOT NULL,
  `programado_para` datetime NOT NULL,
  `estado` enum('pendiente', 'enviado', 'fallido', 'cancelado') NOT NULL DEFAULT 'pendiente',
  `respuesta` text,
  `fecha_envio` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recordatorio_id` (`recordatorio_id`),
  CONSTRAINT `fk_mensaje_recordatorio` FOREIGN KEY (`recordatorio_id`) REFERENCES `recordatorios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para análisis de patrones
CREATE TABLE IF NOT EXISTS `recordatorios_patrones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `tipo_patron` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `confianza` float NOT NULL COMMENT 'Valor entre 0 y 1',
  `fecha_deteccion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `fk_patron_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Lógica de Recordatorios

El sistema implementará los siguientes algoritmos:

1. **Motor de reglas diagnósticas**:
   - Analiza el diagnóstico principal y secundario
   - Considera factores como edad, comorbilidades y recursos personales
   - Aplica reglas definidas por el equipo clínico

2. **Analizador de escalas**:
   - Monitoriza puntuaciones en escalas clínicas
   - Aplica reglas de alerta según umbrales predefinidos
   - Genera recordatorios según severidad

3. **Detector de patrones**:
   - Analiza asistencia, cancelaciones y resultados
   - Identifica periodos óptimos para cada paciente
   - Sugiere ajustes personalizados al plan

## Interfaz de Usuario

### Para Profesionales

1. **Dashboard de recordatorios**:
   - Visualización priorizada por urgencia
   - Filtrado por tipo y estado
   - Acciones rápidas (programar, completar, cancelar)

2. **Integración con agenda**:
   - Sugerencias al programar citas
   - Visualización de seguimientos recomendados
   - Recordatorios dentro del flujo de trabajo

3. **Configuración personalizada**:
   - Definición de reglas propias
   - Ajuste de umbrales de alerta
   - Personalización de mensajes

### Para Pacientes

1. **Portal del paciente**:
   - Recordatorios de citas próximas
   - Notificaciones para completar cuestionarios
   - Mensajes motivacionales según fase de tratamiento

2. **Sistema de mensajería**:
   - WhatsApp, SMS o correo electrónico según preferencia
   - Confirmaciones y recordatorios
   - Opción para responder o solicitar cambios

## Flujo de Trabajo Ejemplo

1. Al registrar un diagnóstico de Depresión Mayor moderada:
   - El sistema consulta las reglas para este diagnóstico
   - Genera recordatorio para seguimiento en 7-10 días
   - Sugiere aplicación de BDI-II en próxima sesión

2. Al completar el BDI-II con puntuación elevada (29 puntos):
   - Se activa regla de alerta por puntuación de riesgo
   - Se genera recordatorio prioritario
   - Se sugiere seguimiento más frecuente (5-7 días)

3. Tras 3 sesiones con mejoría en BDI-II (baja a 18 puntos):
   - El sistema detecta patrón de mejoría
   - Ajusta recomendación a seguimiento quincenal
   - Sugiere reevaluación en 1 mes

4. Al detectar espaciamiento excesivo entre citas:
   - Se genera alerta de posible abandono
   - Se sugiere contacto proactivo con el paciente
   - Se propone evaluación de barreras al tratamiento

## Beneficios Esperados

1. **Clínicos**:
   - Prevención de abandonos prematuros
   - Detección temprana de recaídas
   - Optimización de recursos terapéuticos

2. **Operativos**:
   - Reducción de citas perdidas
   - Mejor gestión de la agenda profesional
   - Priorización efectiva de casos urgentes

3. **Económicos**:
   - Aumento de la retención de pacientes
   - Mejora en eficiencia de proceso administrativo
   - Mejor utilización de espacios disponibles

## Indicadores de Éxito

- Reducción de tasa de abandonos en un 15-20%
- Disminución de no-shows en un 25%
- Aumento de satisfacción de pacientes en un 15%
- Reducción de tiempo entre recaídas y atención
- Aumento de adherencia a planes de tratamiento

## Fases de Implementación

### Fase 1: Recordatorios Básicos (1-2 meses)
- Implementación de reglas para diagnósticos comunes
- Integración con el sistema de citas actual
- Recordatorios manuales con sugerencias

### Fase 2: Integración con Escalas (2-4 meses)
- Conexión con el sistema de evaluación
- Implementación de reglas basadas en puntuaciones
- Alertas automáticas por puntuaciones de riesgo

### Fase 3: Sistema Predictivo (4-6 meses)
- Análisis de patrones históricos
- Implementación de algoritmos de predicción
- Personalización avanzada de recomendaciones

## Consideraciones Importantes

- **Supervisión clínica**: El sistema no reemplaza el juicio profesional
- **Flexibilidad**: Todas las sugerencias pueden ser modificadas
- **Privacidad**: Cumplimiento con normativas de protección de datos
- **Adaptabilidad**: Mejora continua basada en retroalimentación

---

*Este documento es una propuesta inicial para el sistema de recordatorios inteligentes y está sujeto a revisión por el equipo clínico y técnico de la Clínica Ruiz.* 