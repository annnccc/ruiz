# Propuesta: Historia Clínica Estructurada

## Descripción General

Esta propuesta detalla la implementación de un sistema de Historia Clínica Estructurada para la Clínica Ruiz. El objetivo es crear un conjunto de formularios específicos para diferentes tipos de evaluación psicológica, permitiendo un registro más completo, sistemático y útil de la información clínica.

## Objetivos

1. Estandarizar la recogida de información clínica mediante formularios estructurados
2. Adaptar las plantillas a diferentes poblaciones (adultos, adolescentes, niños) y motivos de consulta
3. Facilitar el seguimiento de casos mediante datos comparables
4. Mejorar la calidad de los informes clínicos mediante datos estructurados
5. Permitir análisis estadísticos de la población atendida

## Tipos de Formularios a Implementar

### 1. Entrevista Inicial General

**Objetivo**: Recopilar información básica de todo paciente que acude por primera vez.

**Secciones**:
- Datos sociodemográficos extendidos
- Motivo de consulta (problema actual)
- Historia del problema (inicio, evolución, factores precipitantes)
- Antecedentes personales (médicos, psicológicos, psiquiátricos)
- Antecedentes familiares
- Tratamientos previos o actuales
- Áreas de funcionamiento (laboral, social, familiar, académico)
- Hábitos de salud (sueño, alimentación, ejercicio, consumo de sustancias)

### 2. Evaluación Específica para Adultos

#### 2.1 Trastornos del Estado de Ánimo
- Síntomas depresivos específicos
- Ideación suicida (evaluación de riesgo)
- Antecedentes de episodios previos
- Factores estresantes actuales
- Recursos y apoyo social

#### 2.2 Trastornos de Ansiedad
- Perfil de síntomas ansiosos
- Situaciones de evitación
- Análisis funcional de situaciones ansiógenas
- Respuestas cognitivas, fisiológicas y conductuales
- Estrategias de afrontamiento utilizadas

#### 2.3 Problemas de Pareja
- Historia de la relación
- Áreas de conflicto
- Patrones de comunicación
- Expectativas sobre la relación
- Áreas de satisfacción/insatisfacción

### 3. Evaluación Específica para Niños y Adolescentes

#### 3.1 Evaluación Infantil (3-12 años)
- Desarrollo evolutivo
- Rendimiento académico
- Relaciones con iguales
- Conductas problema en casa y escuela
- Hábitos de crianza y estilo parental

#### 3.2 Evaluación de Adolescentes (13-17 años)
- Desarrollo identitario
- Relaciones familiares
- Grupo de iguales y presión social
- Conductas de riesgo
- Proyecto vital y expectativas futuras

### 4. Seguimiento de Casos

- Evolución de síntomas principales
- Cumplimiento de objetivos terapéuticos
- Cambios en medicación o circunstancias vitales
- Nuevas problemáticas surgidas
- Reevaluación de objetivos terapéuticos

### 5. Informe de Alta o Derivación

- Resumen de motivo de consulta
- Intervenciones realizadas
- Resultados alcanzados
- Recomendaciones futuras
- Información para profesionales en caso de derivación

## Estructura Técnica Propuesta

### Base de Datos

Para implementar la historia clínica estructurada, se crearán las siguientes tablas:

```sql
-- Tabla principal de historias clínicas
CREATE TABLE IF NOT EXISTS `historias_clinicas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_formulario` varchar(100) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_modificacion` datetime NOT NULL,
  `estado` enum('borrador', 'completado', 'archivado') NOT NULL DEFAULT 'borrador',
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_historia_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_historia_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para las secciones de los formularios
CREATE TABLE IF NOT EXISTS `historias_secciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_formulario` varchar(100) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text,
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tipo_formulario` (`tipo_formulario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para campos específicos en cada sección
CREATE TABLE IF NOT EXISTS `historias_campos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seccion_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `tipo` enum('texto', 'texto_largo', 'seleccion', 'multiple', 'fecha', 'numero', 'booleano', 'escala') NOT NULL,
  `opciones` text,
  `requerido` tinyint(1) NOT NULL DEFAULT 0,
  `ayuda` text,
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `seccion_id` (`seccion_id`),
  CONSTRAINT `fk_campo_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `historias_secciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para las respuestas a los campos
CREATE TABLE IF NOT EXISTS `historias_respuestas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `historia_id` int(11) NOT NULL,
  `campo_id` int(11) NOT NULL,
  `respuesta` text,
  PRIMARY KEY (`id`),
  KEY `historia_id` (`historia_id`),
  KEY `campo_id` (`campo_id`),
  CONSTRAINT `fk_respuesta_historia` FOREIGN KEY (`historia_id`) REFERENCES `historias_clinicas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_respuesta_campo` FOREIGN KEY (`campo_id`) REFERENCES `historias_campos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para plantillas de formularios predefinidos
CREATE TABLE IF NOT EXISTS `historias_plantillas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text,
  `tipo_formulario` varchar(100) NOT NULL,
  `publico` tinyint(1) NOT NULL DEFAULT 0,
  `usuario_id` int(11),
  `fecha_creacion` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_plantilla_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Interfaz de Usuario

### Módulo para profesionales

1. **Selector de formularios**
   - Filtrado por tipo de paciente y motivo de consulta
   - Posibilidad de usar plantillas anteriores
   - Creación de plantillas personalizadas

2. **Editor de formularios**
   - Interfaz de edición con secciones colapsables
   - Autoguardado periódico
   - Validación de campos obligatorios

3. **Visualizador de historias**
   - Línea temporal de entradas
   - Filtrado por tipo de formulario
   - Comparación entre evaluaciones sucesivas

### Integración con el portal del paciente

1. **Formularios previos a consulta**
   - Cuestionarios específicos asignados por el profesional
   - Posibilidad de completarlos en casa

2. **Visualización de resúmenes**
   - Acceso a información relevante para el paciente
   - Recomendaciones y pautas

## Beneficios del Sistema

1. **Para profesionales:**
   - Reducción del tiempo de documentación
   - Mayor consistencia en la recogida de información
   - Acceso rápido a información clave
   - Generación semiautomática de informes

2. **Para pacientes:**
   - Participación activa en el proceso terapéutico
   - Mejor comprensión de su evolución
   - Reducción de tiempo en consulta dedicado a recogida de datos

3. **Para la clínica:**
   - Estandarización de procesos
   - Mejora de la calidad asistencial
   - Posibilidad de analizar datos agregados
   - Cumplimiento de estándares clínicos

## Flujo de Trabajo Propuesto

1. El profesional selecciona el tipo de evaluación adecuada
2. El sistema presenta el formulario estructurado correspondiente
3. Los datos se completan durante la sesión o como tarea para casa
4. La información se almacena de forma estructurada
5. Se pueden generar informes basados en los datos recogidos
6. En sesiones posteriores, el profesional puede acceder rápidamente a la información previa

## Fases de Implementación

### Fase 1: Formularios Básicos
- Historia clínica general
- Evaluación específica para trastornos de ansiedad y depresión
- Seguimiento básico de casos

### Fase 2: Formularios Específicos
- Evaluaciones específicas para diferentes trastornos
- Formularios pediátricos completos
- Integración con escalas psicométricas

### Fase 3: Integración Avanzada
- Integración con el sistema de recordatorios inteligentes
- Generación automática de informes clínicos
- Análisis estadístico y de tendencias

## Consideraciones Legales y Éticas

- Cumplimiento con normativa RGPD
- Protección de datos de categoría especial
- Gestión de consentimientos específicos
- Protocolos para información sensible

---

*Este documento es una propuesta inicial y está sujeto a revisión y modificación según las necesidades específicas de la Clínica Ruiz.* 