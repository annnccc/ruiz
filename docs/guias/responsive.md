# Guía de Diseño Responsivo

Esta guía establece los estándares y mejores prácticas para asegurar que el Sistema de Gestión Clínica ofrezca una experiencia óptima en todos los dispositivos, con especial énfasis en el enfoque mobile-first.

## Tabla de Contenidos

1. [Principios Mobile-First](#principios-mobile-first)
2. [Breakpoints Estándar](#breakpoints-estándar)
3. [Estructura de Grids](#estructura-de-grids)
4. [Componentes Responsivos](#componentes-responsivos)
5. [Imágenes y Medios](#imágenes-y-medios)
6. [Tipografía Responsiva](#tipografía-responsiva)
7. [Navegación Responsiva](#navegación-responsiva)
8. [Testing y Validación](#testing-y-validación)

## Principios Mobile-First

El enfoque mobile-first significa diseñar y desarrollar primero para dispositivos móviles, y luego ir aumentando la complejidad para pantallas más grandes.

### Beneficios

- Fuerza a priorizar contenido esencial
- Mejora rendimiento en dispositivos con recursos limitados
- Facilita la escalabilidad del diseño

### Implementación

1. **Comenzar con el CSS básico para móviles**:

```css
/* Estilo base para todos los dispositivos (móvil primero) */
.card {
    padding: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
}

/* Luego añadir media queries para pantallas más grandes */
@media (min-width: 768px) {
    .card {
        padding: var(--spacing-md);
        margin-bottom: var(--spacing-lg);
    }
}
```

2. **Diseñar interfaces para entrada táctil primero**:

- Botones y controles con tamaño mínimo de 44x44px para facilitar la interacción táctil
- Espaciado adecuado entre elementos interactivos
- Evitar acciones que dependan de hover (que no existe en móviles)

## Breakpoints Estándar

Utilizamos los siguientes breakpoints estándar (basados en Bootstrap):

| Nombre | Dimensión | Dispositivos Objetivo |
|--------|-----------|----------------------|
| xs     | < 576px   | Móviles en vertical  |
| sm     | ≥ 576px   | Móviles en horizontal |
| md     | ≥ 768px   | Tablets              |
| lg     | ≥ 992px   | Desktops             |
| xl     | ≥ 1200px  | Desktops grandes     |
| xxl    | ≥ 1400px  | Pantallas extra grandes |

### Uso en CSS

```css
/* Móvil (base) */
.elemento {
    width: 100%;
}

/* Tablet */
@media (min-width: 768px) {
    .elemento {
        width: 50%;
    }
}

/* Desktop */
@media (min-width: 992px) {
    .elemento {
        width: 33.333%;
    }
}
```

### Uso en Bootstrap

```html
<div class="row">
    <!-- Apilado en móvil, 2 columnas en tablet, 3 en desktop -->
    <div class="col-12 col-md-6 col-lg-4">Contenido</div>
    <div class="col-12 col-md-6 col-lg-4">Contenido</div>
    <div class="col-12 col-md-6 col-lg-4">Contenido</div>
</div>
```

## Estructura de Grids

Utilizamos el sistema de grid de Bootstrap, siguiendo estas pautas:

### Reglas Básicas

1. Usar contenedores apropiados:
   - `.container` para ancho fijo centrado
   - `.container-fluid` para ancho completo
   - `.container-{breakpoint}` para ancho fijo que se convierte en fluido por debajo del breakpoint

2. Estructura jerárquica correcta:
   - `.container` → `.row` → `.col-*`

3. Mantener orden de columnas consistente:
   - Usar clases de orden para modificar la presentación visual sin alterar el DOM

### Ejemplo de Grid Responsivo

```html
<div class="container">
    <div class="row">
        <!-- En móvil: apilado -->
        <!-- En tablet: contenido principal primero, sidebar abajo -->
        <!-- En desktop: sidebar a la izquierda, contenido principal a la derecha -->
        
        <!-- Sidebar -->
        <div class="col-12 col-lg-3 order-lg-1 order-2">
            <div class="card">Sidebar</div>
        </div>
        
        <!-- Contenido principal -->
        <div class="col-12 col-lg-9 order-lg-2 order-1">
            <div class="card">Contenido principal</div>
        </div>
    </div>
</div>
```

## Componentes Responsivos

### Tablas Responsivas

Todas las tablas deben ser responsivas:

```html
<div class="table-responsive">
    <table class="table table-modern">
        <!-- Contenido de la tabla -->
    </table>
</div>
```

Para tablas complejas con muchas columnas, considerar:

1. **Priorización de columnas**:
   - Ocultar columnas menos importantes en pantallas pequeñas
   - Ejemplo: `<th class="d-none d-md-table-cell">Columna menos importante</th>`

2. **Tablas apiladas**: 
   - En móviles, mostrar cada fila como una "tarjeta" con etiquetas

### Formularios Responsivos

1. **Ancho completo en móvil**:
   ```html
   <div class="row">
       <div class="col-12 col-md-6">
           <div class="mb-3">
               <label for="nombre" class="form-label">Nombre</label>
               <input type="text" class="form-control" id="nombre">
           </div>
       </div>
   </div>
   ```

2. **Grupos de campos adaptables**:
   ```html
   <div class="row">
       <!-- En móvil: campos apilados -->
       <!-- En tablet y superior: campos en línea -->
       <div class="col-12 col-md-6 mb-3">
           <label for="nombre" class="form-label">Nombre</label>
           <input type="text" class="form-control" id="nombre">
       </div>
       <div class="col-12 col-md-6 mb-3">
           <label for="apellido" class="form-label">Apellido</label>
           <input type="text" class="form-control" id="apellido">
       </div>
   </div>
   ```

### Tarjetas Adaptables

```html
<div class="row">
    <!-- En móvil: 1 tarjeta por fila -->
    <!-- En tablet: 2 tarjetas por fila -->
    <!-- En desktop: 3 tarjetas por fila -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Título de la tarjeta</h5>
                <p class="card-text">Contenido de la tarjeta</p>
            </div>
        </div>
    </div>
    <!-- Repetir para más tarjetas -->
</div>
```

## Imágenes y Medios

### Imágenes Responsivas

1. **Clase básica**:
   ```html
   <img src="imagen.jpg" alt="Descripción" class="img-fluid">
   ```

2. **Usando srcset para diferentes densidades de píxeles**:
   ```html
   <img src="imagen.jpg" 
        srcset="imagen.jpg 1x, imagen@2x.jpg 2x" 
        alt="Descripción" 
        class="img-fluid">
   ```

3. **Usando picture para diferentes formatos/dimensiones**:
   ```html
   <picture>
       <source media="(min-width: 992px)" srcset="imagen-grande.jpg">
       <source media="(min-width: 768px)" srcset="imagen-mediana.jpg">
       <img src="imagen-pequeña.jpg" alt="Descripción" class="img-fluid">
   </picture>
   ```

4. **Usando Lazy Loading optimizado**:
   ```php
   <?= optimizedImage('/assets/img/ejemplo.jpg', 'Descripción', [
       'responsive_sizes' => true,
       'sizes' => '(max-width: 768px) 100vw, 50vw'
   ]) ?>
   ```

### Vídeos Responsivos

```html
<div class="ratio ratio-16x9">
    <iframe src="https://www.youtube.com/embed/VIDEO_ID" allowfullscreen></iframe>
</div>
```

## Tipografía Responsiva

### Reglas Generales

1. **Unidades Relativas**:
   - Usar `rem` para tamaños de fuente
   - Usar `em` para espaciado relacionado con texto
   - Evitar píxeles fijos

2. **Escala Tipográfica Responsiva**:
   ```css
   :root {
       --font-size-base: 1rem;      /* 16px normalmente */
       --font-size-sm: 0.875rem;    /* 14px */
       --font-size-lg: 1.25rem;     /* 20px */
   }
   
   @media (min-width: 992px) {
       :root {
           --font-size-base: 1.125rem;  /* 18px en pantallas grandes */
       }
   }
   
   body {
       font-size: var(--font-size-base);
   }
   ```

3. **Encabezados Responsivos**:
   ```css
   h1 {
       font-size: 1.75rem;  /* Más pequeño en móvil */
       line-height: 1.2;
   }
   
   @media (min-width: 768px) {
       h1 {
           font-size: 2.25rem;  /* Más grande en tablet/desktop */
       }
   }
   ```

## Navegación Responsiva

### Menú Lateral (Sidebar)

Nuestro sidebar debe adaptarse a diferentes tamaños de pantalla:

1. **Móviles**: Oculto por defecto, mostrable con botón
2. **Tablets**: Versión compacta (iconos+texto o sólo iconos)
3. **Desktop**: Versión completa

Implementación:

```html
<!-- Botón para mostrar/ocultar en móvil -->
<button class="d-lg-none btn btn-icon sidebar-toggle">
    <span class="material-symbols-rounded">menu</span>
</button>

<!-- Sidebar con diferentes modos -->
<div class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <!-- Logo y título -->
    </div>
    <div class="sidebar-body">
        <!-- Enlaces de navegación -->
        <a href="#" class="sidebar-link">
            <span class="sidebar-icon material-symbols-rounded">dashboard</span>
            <span class="sidebar-text">Dashboard</span>
        </a>
    </div>
</div>
```

```css
/* Comportamiento responsivo del sidebar */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: var(--sidebar-width);
    transform: translateX(-100%);  /* Oculto por defecto en móvil */
    transition: transform 0.3s ease;
    z-index: 1000;
}

.sidebar.show {
    transform: translateX(0);  /* Mostrar en móvil cuando tiene la clase .show */
}

@media (min-width: 992px) {
    .sidebar {
        transform: translateX(0);  /* Siempre visible en desktop */
    }
    
    /* Ajustar contenido principal */
    .main-content {
        margin-left: var(--sidebar-width);
    }
}
```

### Menú Superior (Header)

```html
<header class="main-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <!-- Logo/Título -->
            <div class="header-brand d-flex align-items-center">
                <button class="d-lg-none btn btn-icon me-2 sidebar-toggle">
                    <span class="material-symbols-rounded">menu</span>
                </button>
                <span class="header-title">Sistema Clínico</span>
            </div>
            
            <!-- Menú de navegación -->
            <nav class="header-nav d-none d-md-flex">
                <!-- Enlaces visibles en tablet/desktop -->
            </nav>
            
            <!-- Iconos de acción (visibles en todos los tamaños) -->
            <div class="header-actions d-flex align-items-center">
                <div class="dropdown">
                    <!-- Perfil/Notificaciones -->
                </div>
            </div>
        </div>
    </div>
</header>
```

## Testing y Validación

### Herramientas de Testing

1. **Chrome DevTools**: 
   - Usar modo responsivo para probar diferentes dispositivos
   - Simular conexiones lentas con throttling

2. **Pruebas en Dispositivos Reales**:
   - Probar en al menos un dispositivo iOS y uno Android
   - Verificar interacciones táctiles

3. **Checklist de Verificación Responsiva**:

   - [ ] Todas las páginas se ven correctamente en móvil, tablet y desktop
   - [ ] No hay scroll horizontal en ningún breakpoint
   - [ ] Los formularios son usables en dispositivos móviles
   - [ ] Los botones e interacciones táctiles funcionan correctamente
   - [ ] Las imágenes se cargan apropiadamente para cada tamaño de dispositivo
   - [ ] Las tablas son legibles en todos los tamaños de pantalla
   - [ ] El contraste y legibilidad del texto es adecuado
   - [ ] El rendimiento es aceptable en dispositivos con capacidades limitadas

### Herramientas Recomendadas

- Lighthouse (Chrome DevTools): para auditar rendimiento y accesibilidad
- BrowserStack: para probar en múltiples navegadores y dispositivos
- Can I Use (caniuse.com): para verificar soporte de características modernas 