# Guía de Estilo de Código

Esta guía define los estándares de codificación y documentación para el Sistema de Gestión Clínica. El objetivo es mantener un código limpio, consistente y bien documentado para facilitar el mantenimiento y colaboración.

## Tabla de Contenidos

1. [Reglas Generales](#reglas-generales)
2. [Estructura de Archivos](#estructura-de-archivos)
3. [Comentarios y Documentación](#comentarios-y-documentación)
4. [Estilo de Código PHP](#estilo-de-código-php)
5. [Estilo de Código JavaScript](#estilo-de-código-javascript)
6. [Estilo de Código CSS](#estilo-de-código-css)
7. [Base de Datos](#base-de-datos)
8. [Control de Versiones](#control-de-versiones)

## Reglas Generales

- Usar UTF-8 como codificación para todos los archivos
- Usar LF (Unix) para saltos de línea
- Eliminar espacios en blanco al final de las líneas
- Terminar cada archivo con una línea en blanco
- Usar 4 espacios para la indentación (no tabs)
- Limitar las líneas a 100 caracteres cuando sea posible

## Estructura de Archivos

- Un archivo de clase debe contener solo una clase
- Los nombres de archivo deben coincidir con el nombre de la clase
- Seguir estrictamente la estructura de directorios del proyecto

## Comentarios y Documentación

### 1. Bloques de Comentarios para Archivos

Cada archivo debe comenzar con un bloque de comentario que describa su propósito:

```php
/**
 * Controlador para la gestión de pacientes
 *
 * Este archivo contiene el controlador que maneja todas las operaciones
 * relacionadas con pacientes (listar, crear, editar, eliminar)
 *
 * @package App\Controllers
 * @author Nombre del Autor <email@dominio.com>
 * @version 1.0
 */
```

### 2. Comentarios para Clases

Cada clase debe documentarse siguiendo el estándar PHPDoc:

```php
/**
 * Gestiona los datos y operaciones relacionadas con pacientes
 *
 * Esta clase proporciona métodos para acceder y manipular
 * la información de pacientes en la base de datos
 *
 * @package App\Models
 */
class Paciente extends Model
{
    // Contenido de la clase
}
```

### 3. Comentarios para Métodos y Funciones

Cada método público debe tener, como mínimo:

```php
/**
 * Busca pacientes por nombre o apellido
 *
 * @param string $termino Texto a buscar
 * @param bool $exacto Si es true, busca coincidencia exacta
 * @return array Lista de pacientes encontrados
 * @throws \PDOException Si hay error de conexión a la BD
 */
public function buscarPorNombre($termino, $exacto = false)
{
    // Implementación
}
```

### 4. Comentarios en Línea

Usar comentarios en línea para explicar secciones complejas o no obvias:

```php
// Calcular edad del paciente basado en la fecha de nacimiento
$edad = date_diff(date_create($fechaNacimiento), date_create('now'))->y;

/* 
 * Verificar restricciones especiales para pacientes menores
 * según normativa actual (actualizado enero 2023)
 */
if ($edad < 18) {
    $requiereConsentimiento = true;
}
```

### 5. Documentación de Propiedades y Atributos

Las propiedades y atributos deben documentarse:

```php
/**
 * @var string Tabla de base de datos asociada al modelo
 */
protected $table = 'pacientes';

/**
 * @var array Campos que se pueden llenar masivamente
 */
protected $fillable = ['nombre', 'apellidos', 'email', 'telefono'];
```

### 6. Marcadores TODO, FIXME, NOTE

Usar marcadores estándar para señalar tareas pendientes:

```php
// TODO: Implementar validación de formato de teléfono internacional
// FIXME: Esta función tiene un problema con fechas en formato americano
// NOTE: Esta implementación cambiará cuando se actualice la API
```

## Estilo de Código PHP

- Seguir PSR-12 (https://www.php-fig.org/psr/psr-12/)
- Usar declaraciones de tipo siempre que sea posible
- Preferir declaraciones explícitas sobre implícitas
- Usar namespaces para organizar clases
- No usar constructor de array antiguo ([] vs array())

### Ejemplo de Clase Bien Documentada

```php
<?php
/**
 * Controlador para la gestión de pacientes
 *
 * @package App\Controllers
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Paciente;

/**
 * Maneja las solicitudes relacionadas con pacientes
 */
class PacientesController extends Controller
{
    /**
     * @var Paciente Instancia del modelo de pacientes
     */
    private Paciente $pacienteModel;
    
    /**
     * Constructor del controlador
     */
    public function __construct()
    {
        $this->pacienteModel = new Paciente();
    }
    
    /**
     * Muestra la lista de pacientes
     *
     * @return void
     */
    public function index(): void
    {
        // Implementación
    }
}
```

## Estilo de Código JavaScript

- Seguir estándar ESLint con configuración AirBnB
- Usar ES6+ siempre que sea posible
- Documentar con JSDoc
- Mantener comentarios descriptivos para funciones complejas

### Ejemplo de JavaScript Bien Documentado

```javascript
/**
 * Módulo de gestión de notificaciones
 * @module NotificationSystem
 */

/**
 * Inicializa el sistema de notificaciones
 * @param {Object} options - Opciones de configuración
 * @param {string} options.selector - Selector CSS para el contenedor
 * @param {number} options.timeout - Tiempo de expiración en ms
 * @returns {Object} API pública del sistema de notificaciones
 */
function initNotifications(options = {}) {
    // Valores por defecto
    const selector = options.selector || '.notifications-container';
    const timeout = options.timeout || 5000;
    
    // Implementación
}
```

## Estilo de Código CSS

- Seguir convención BEM para nombrar clases
- Documentar secciones principales
- Usar variables CSS para mayor consistencia
- Comentar bloques funcionales

### Ejemplo de CSS Bien Documentado

```css
/**
 * Componentes de notificaciones
 *
 * Este bloque contiene los estilos para el sistema de notificaciones
 * incluyendo diferentes tipos (éxito, error, info) y animaciones.
 */

/* Variables del componente */
:root {
    --notification-bg: #ffffff;
    --notification-success: #28a745;
    --notification-error: #dc3545;
    --notification-info: #17a2b8;
}

/* Contenedor principal */
.notification {
    display: flex;
    align-items: center;
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-sm);
    transition: opacity 0.3s ease;
    /* Por defecto ocultamos las notificaciones nuevas */
    opacity: 0; 
}

/* Variantes por tipo */
.notification--success {
    background-color: var(--notification-success);
    color: white;
}

/* ... más estilos ... */
```

## Base de Datos

- Documentar cada tabla y sus relaciones
- Añadir comentarios a campos con lógica especial
- Mantener un diccionario de datos actualizado

## Control de Versiones

- Mensajes de commit descriptivos y concisos
- Referenciar números de issue cuando corresponda
- Seguir convención para ramas (feature/, hotfix/, etc.) 