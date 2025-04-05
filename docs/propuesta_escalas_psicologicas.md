# Propuesta: Sistema de Escalas Psicológicas

## Resumen Ejecutivo

Esta propuesta define la implementación de un sistema integrado de escalas psicológicas para la Clínica Ruiz. El sistema permitirá administrar, evaluar y realizar seguimiento de las evaluaciones psicológicas de forma digital, mejorando la precisión diagnóstica y facilitando el seguimiento de la evolución de los pacientes.

## Objetivos

1. Digitalizar las principales escalas e instrumentos psicológicos utilizados en la práctica clínica
2. Automatizar la puntuación y la interpretación básica de resultados
3. Integrar los resultados con el historial médico del paciente
4. Facilitar la comparación de resultados a lo largo del tiempo
5. Ofrecer visualizaciones gráficas de la evolución del paciente

## Escalas a Implementar (Fase 1)

### Instrumentos para Adultos

1. **Inventario de Depresión de Beck (BDI-II)**
   - Evaluación de síntomas depresivos
   - 21 ítems con escala de 0-3
   - Puntuación automática e interpretación según rangos establecidos

2. **Inventario de Ansiedad Estado-Rasgo (STAI)**
   - Evaluación diferenciada de ansiedad como estado y como rasgo
   - Dos subescalas de 20 ítems cada una
   - Visualización comparativa entre ambas dimensiones

3. **Escala de Autoestima de Rosenberg**
   - Evaluación de la autoestima global
   - 10 ítems con puntuación de 1-4
   - Interpretación automatizada de resultados

4. **Cuestionario de Salud General de Goldberg (GHQ-28)**
   - Cribado de malestar psicológico general
   - 28 ítems divididos en 4 subescalas
   - Perfiles visuales por áreas de funcionamiento

### Instrumentos para Niños y Adolescentes

1. **Sistema de Evaluación de Conducta de Niños y Adolescentes (BASC)**
   - Versiones para padres, profesores y autoinforme
   - Evaluación multidimensional
   - Generación de perfiles comparativos entre informantes

2. **Test de Atención D2**
   - Evaluación de la atención selectiva y concentración
   - Corrección automatizada
   - Comparación con baremos por edad

## Arquitectura Propuesta

### Base de Datos

Para implementar el sistema de escalas, se crearán las siguientes tablas:

```sql
-- Tabla para catálogo de escalas disponibles
CREATE TABLE IF NOT EXISTS `escalas_catalogo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text,
  `poblacion` enum('adultos', 'adolescentes', 'niños', 'todos') NOT NULL DEFAULT 'todos',
  `instrucciones` text,
  `tiempo_estimado` varchar(50),
  `referencia_bibliografica` text,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para ítems de las escalas
CREATE TABLE IF NOT EXISTS `escalas_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `escala_id` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `texto` text NOT NULL,
  `tipo_respuesta` enum('likert3', 'likert4', 'likert5', 'si_no', 'numerica', 'seleccion_multiple') NOT NULL,
  `opciones_respuesta` text,
  `inversion` tinyint(1) NOT NULL DEFAULT '0',
  `subescala` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `escala_id` (`escala_id`),
  CONSTRAINT `fk_items_escala` FOREIGN KEY (`escala_id`) REFERENCES `escalas_catalogo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para administraciones de escalas a pacientes
CREATE TABLE IF NOT EXISTS `escalas_administraciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `escala_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL,
  `completada` tinyint(1) NOT NULL DEFAULT '0',
  `motivo` text,
  `observaciones` text,
  `usuario_id` int(11),
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_modificacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `escala_id` (`escala_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_admin_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_admin_escala` FOREIGN KEY (`escala_id`) REFERENCES `escalas_catalogo` (`id`),
  CONSTRAINT `fk_admin_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para respuestas individuales a ítems
CREATE TABLE IF NOT EXISTS `escalas_respuestas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `administracion_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `respuesta` varchar(255) NOT NULL,
  `puntuacion` float,
  PRIMARY KEY (`id`),
  KEY `administracion_id` (`administracion_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `fk_resp_admin` FOREIGN KEY (`administracion_id`) REFERENCES `escalas_administraciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resp_item` FOREIGN KEY (`item_id`) REFERENCES `escalas_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para resultados de las escalas
CREATE TABLE IF NOT EXISTS `escalas_resultados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `administracion_id` int(11) NOT NULL,
  `subescala` varchar(100) DEFAULT 'total',
  `puntuacion_directa` float NOT NULL,
  `puntuacion_tipica` float,
  `percentil` int(11),
  `interpretacion` text,
  `alerta` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `administracion_id` (`administracion_id`),
  CONSTRAINT `fk_result_admin` FOREIGN KEY (`administracion_id`) REFERENCES `escalas_administraciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Módulos del Sistema

1. **Administración de Escalas**
   - Selección de escala para un paciente
   - Programación de evaluaciones
   - Seguimiento de evaluaciones pendientes

2. **Aplicación de Cuestionarios**
   - Interfaz adaptada a cada tipo de escala
   - Versión para profesional y autoaplicada
   - Guardado automático de avances

3. **Corrección e Interpretación**
   - Algoritmos específicos para cada escala
   - Cálculo de puntuaciones directas y derivadas
   - Interpretación según baremos establecidos

4. **Visualización y Reportes**
   - Gráficos de resultados individuales
   - Comparativas de evaluaciones a lo largo del tiempo
   - Generación de informes en PDF

## Integración con el Sistema Actual

El sistema de escalas se integrará con el historial médico existente, apareciendo como una nueva sección en la ficha del paciente. Los resultados de las evaluaciones podrán:

1. Incorporarse automáticamente al historial clínico
2. Influir en los recordatorios inteligentes según puntuaciones de alerta
3. Servir como anexos en informes clínicos

## Flujo de Trabajo Propuesto

1. El profesional asigna una escala a un paciente desde su ficha
2. El paciente puede completar la evaluación:
   - En sala de espera mediante tablet
   - Remotamente a través del portal del paciente
   - Asistido por el profesional durante la sesión
3. El sistema calcula automáticamente los resultados
4. Los resultados se muestran gráficamente y se almacenan
5. El profesional puede añadir interpretaciones cualitativas
6. El sistema genera alertas si hay puntuaciones de riesgo

## Beneficios Esperados

1. Reducción del tiempo dedicado a corrección manual de tests
2. Mayor precisión en la puntuación e interpretación
3. Facilidad para el seguimiento longitudinal de casos
4. Detección temprana de cambios significativos
5. Estadísticas agregadas de población clínica atendida

## Próximos Pasos

1. Definir prioridades de escalas a implementar
2. Adquirir permisos de uso si son instrumentos con copyright
3. Desarrollar la estructura de base de datos
4. Implementar interfaces de usuario
5. Realizar pruebas con datos simulados
6. Capacitar al personal clínico

## Fase 2: Expansión del Sistema

En fases posteriores, se planea:

1. Incluir escalas específicas para trastornos concretos (TOC, TDAH, etc.)
2. Desarrollar algoritmos de sugerencia de escalas según perfil del paciente
3. Implementar comparación con datos normativos actualizados
4. Integrar modelos predictivos basados en evaluaciones secuenciales

---

*Este documento es una propuesta inicial y está sujeto a revisión y modificación según las necesidades específicas de la Clínica Ruiz.* 

---

# Actualización: Estado de Implementación (Junio 2023)

## ✅ Fase 1: Completamente implementada

### Escalas para Adultos:
- **Inventario de Depresión de Beck (BDI-II)**
- **Inventario de Ansiedad Estado-Rasgo (STAI)**
- **Escala de Autoestima de Rosenberg**
- **Cuestionario de Salud General (GHQ-28)**

### Escalas para Niños y Adolescentes:
- **Sistema de Evaluación BASC**
- **Test de Atención D2**

### Funcionalidades básicas:
- Administración de escalas a pacientes
- Cálculo automático de puntuaciones
- Gestión de ítems y subescalas
- Visualización básica de resultados

## ✅ Fase 2: Parcialmente implementada

### Implementado:

1. **Nuevas escalas específicas para trastornos concretos:**
   - ✅ Escala Yale-Brown para TOC (Y-BOCS)
   - ✅ ADHD Rating Scale-IV para TDAH
   - ✅ Escala de Ansiedad de Hamilton (HARS)

2. **Algoritmo de sugerencia de escalas:**
   - ✅ Módulo `sugerir_escalas.php` funcionando
   - ✅ Filtrado por síntomas y demografía
   - ✅ Ordenación por relevancia

3. **Datos normativos y baremos:**
   - ✅ Estructura de tablas para baremos
   - ✅ Implementación para BDI-II
   - ✅ Sistema de puntos de corte e interpretación
   - ✅ Visualización de percentiles y puntuaciones derivadas

### Pendiente:

1. **Comparación con datos normativos:**
   - ⏳ Completar baremos para STAI (ansiedad estado/rasgo)
   - ⏳ Completar baremos para HARS (ansiedad)
   - ⏳ Añadir baremos para escalas infantiles (BASC, D2, TDAH)

2. **Módulo de evolución longitudinal:**
   - ⏳ Mejora de visualización de múltiples evaluaciones
   - ⏳ Gráficos comparativos de evolución temporal
   - ⏳ Detección automática de cambios significativos

3. **Modelos predictivos:**
   - ❌ No iniciado aún
   - ❌ Pendiente diseño del modelo de datos
   - ❌ Pendiente algoritmos de predicción

## 📋 Plan de trabajo propuesto

### Corto plazo (1-2 meses):
1. Completar implementación de baremos para todas las escalas principales
   - Priorizar STAI y escalas de alta utilización
   - Incluir baremos diferenciados por edad y género donde existan

2. Mejorar la visualización de resultados
   - Añadir gráficos comparativos más intuitivos
   - Refinar las interpretaciones automáticas

### Medio plazo (3-6 meses):
1. Desarrollar completamente el módulo de seguimiento longitudinal
   - Crear dashboard de evolución por paciente
   - Implementar alertas de cambios significativos

2. Preparar infraestructura para modelos predictivos
   - Diseñar estructura de datos para predicciones
   - Establecer métricas de seguimiento
   - Documentar algoritmos de predicción

### Largo plazo (6+ meses):
1. Implementar modelos predictivos básicos
   - Predicción de respuesta a tratamiento
   - Identificación de patrones de riesgo
   - Sugerencias de intervención basadas en resultados

2. Integración con otros módulos clínicos
   - Notificaciones inteligentes
   - Sugerencias para informes clínicos

## 💻 Estructura técnica implementada

### Base de datos:
- `escalas_catalogo`: Definición de escalas
- `escalas_items`: Ítems de cada escala
- `escalas_administraciones`: Registro de aplicaciones
- `escalas_respuestas`: Respuestas individuales
- `escalas_resultados`: Resultados calculados
- `escalas_baremos`: Datos normativos por población
- `escalas_puntos_corte`: Interpretación según rangos
- `escalas_equivalencias`: Conversión a percentiles/puntuaciones T

### Módulos funcionales:
- Administración de escalas
- Sugerencia inteligente
- Visualización e interpretación
- Baremos y datos normativos 