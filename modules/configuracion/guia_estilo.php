<?php
/**
 * Módulo de Configuración - Guía de Estilo
 * Muestra los elementos visuales y componentes de la aplicación.
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    $_SESSION['error'] = "Acceso denegado. Debe ser administrador para acceder a esta funcionalidad.";
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Título de la página
$pageTitle = "Guía de Estilo";

// Iniciar captura del contenido de la página
startPageContent();
?>

<div class="container-fluid px-4 guia-estilo">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">palette</span>
        Guía de Estilo
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/modules/configuracion/index.php">Configuración</a></li>
        <li class="breadcrumb-item active">Guía de Estilo</li>
    </ol>

    <p class="lead mb-4">Esta guía muestra los componentes visuales y estilos utilizados en la aplicación, basados en Bootstrap 5.</p>

    <div class="alert alert-info mt-4 mb-4">
        <h6 class="mb-2">Actualización de Iconos</h6>
        <p class="mb-1">Estamos migrando todos los iconos del sistema de Material Symbols a Heroicons. Por favor utilice Heroicons en todas las nuevas funcionalidades.</p>
        <p class="mb-0">Ejemplo: <code>&lt;?= heroicon_outline('eye', 'heroicon-sm') ?&gt;</code> en lugar de <code>&lt;span class="material-symbols-rounded"&gt;visibility&lt;/span&gt;</code></p>
    </div>

    <!-- Sección de Tipografía -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Tipografía</h5>
        </div>
        <div class="card-body">
            <h1>Encabezado H1</h1>
            <h2>Encabezado H2</h2>
            <h3>Encabezado H3</h3>
            <h4>Encabezado H4</h4>
            <h5>Encabezado H5</h5>
            <h6>Encabezado H6</h6>
            <hr>
            <p>Este es un párrafo de texto normal. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
            <p class="lead">Este es un párrafo destacado (clase lead).</p>
            <p><small>Este texto es más pequeño (etiqueta small).</small></p>
            <p><strong>Este texto está en negrita (etiqueta strong).</strong></p>
            <p><em>Este texto está en cursiva (etiqueta em).</em></p>
            <p><a href="#">Este es un enlace estándar</a>.</p>
            <ul>
                <li>Elemento de lista desordenada 1</li>
                <li>Elemento de lista desordenada 2</li>
            </ul>
            <ol>
                <li>Elemento de lista ordenada 1</li>
                <li>Elemento de lista ordenada 2</li>
            </ol>
            <code>Código en línea</code>
            <pre>Bloque de código preformateado</pre>
        </div>
    </div>

    <!-- Sección de Colores -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Paleta de Colores</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="p-3 mb-2 bg-primary text-white">Primary (.bg-primary)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3 mb-2 bg-secondary text-white">Secondary (.bg-secondary)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3 mb-2 bg-success text-white">Success (.bg-success)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3 mb-2 bg-danger text-white">Danger (.bg-danger)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3 mb-2 bg-warning text-dark">Warning (.bg-warning)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3 mb-2 bg-info text-dark">Info (.bg-info)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3 mb-2 bg-light text-dark border">Light (.bg-light)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3 mb-2 bg-dark text-white">Dark (.bg-dark)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="p-3 mb-2 bg-white text-dark border">White (.bg-white)</div>
                </div>
            </div>
            <hr>
            <p>Colores de texto:</p>
            <p class="text-primary">Texto Primary (.text-primary)</p>
            <p class="text-secondary">Texto Secondary (.text-secondary)</p>
            <p class="text-success">Texto Success (.text-success)</p>
            <p class="text-danger">Texto Danger (.text-danger)</p>
            <p class="text-warning">Texto Warning (.text-warning)</p>
            <p class="text-info">Texto Info (.text-info)</p>
            <p class="text-light bg-dark">Texto Light (.text-light)</p>
            <p class="text-dark">Texto Dark (.text-dark)</p>
            <p class="text-body">Texto Body (default) (.text-body)</p>
            <p class="text-muted">Texto Muted (.text-muted)</p>
        </div>
    </div>

    <!-- Sección de Botones -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Botones</h5>
        </div>
        <div class="card-body">
            <h6>Botones estándar</h6>
            <button type="button" class="btn btn-primary me-1 mb-2">Primary</button>
            <button type="button" class="btn btn-secondary me-1 mb-2">Secondary</button>
            <button type="button" class="btn btn-success me-1 mb-2">Success</button>
            <button type="button" class="btn btn-danger me-1 mb-2">Danger</button>
            <button type="button" class="btn btn-warning me-1 mb-2">Warning</button>
            <button type="button" class="btn btn-info me-1 mb-2">Info</button>
            <button type="button" class="btn btn-light me-1 mb-2">Light</button>
            <button type="button" class="btn btn-dark me-1 mb-2">Dark</button>
            <button type="button" class="btn btn-link me-1 mb-2">Link</button>
            <hr>
            <h6>Botones con iconos (Material Symbols)</h6>
            <button type="button" class="btn btn-primary me-1 mb-2">
                <span class="material-symbols-rounded align-middle me-1">add</span> Crear
            </button>
            <button type="button" class="btn btn-success me-1 mb-2">
                <span class="material-symbols-rounded align-middle me-1">check</span> Guardar
            </button>
            <button type="button" class="btn btn-danger me-1 mb-2">
                <span class="material-symbols-rounded align-middle me-1">delete</span> Eliminar
            </button>
            <button type="button" class="btn btn-info btn-sm me-1 mb-2">
                <span class="material-symbols-rounded align-middle">edit</span>
            </button>
            <hr>
            <h6>Botones con iconos (Heroicons)</h6>
            <button type="button" class="btn btn-primary me-1 mb-2">
                <?= heroicon_outline('plus', 'heroicon-sm') ?> Crear
            </button>
            <button type="button" class="btn btn-success me-1 mb-2">
                <?= heroicon_outline('check', 'heroicon-sm') ?> Guardar
            </button>
            <button type="button" class="btn btn-danger me-1 mb-2">
                <?= heroicon_outline('trash', 'heroicon-sm') ?> Eliminar
            </button>
            <button type="button" class="btn btn-info btn-sm me-1 mb-2">
                <?= heroicon_outline('pencil', 'heroicon-sm') ?>
            </button>
            <hr>
            <h6>Botones Outline</h6>
            <button type="button" class="btn btn-outline-primary me-1 mb-2">Primary</button>
            <button type="button" class="btn btn-outline-secondary me-1 mb-2">Secondary</button>
            <button type="button" class="btn btn-outline-success me-1 mb-2">Success</button>
            <button type="button" class="btn btn-outline-danger me-1 mb-2">Danger</button>
            <button type="button" class="btn btn-outline-warning me-1 mb-2">Warning</button>
            <button type="button" class="btn btn-outline-info me-1 mb-2">Info</button>
            <button type="button" class="btn btn-outline-light me-1 mb-2">Light</button>
            <button type="button" class="btn btn-outline-dark me-1 mb-2">Dark</button>
            <hr>
            <h6>Tamaños de Botones</h6>
            <button type="button" class="btn btn-primary btn-lg me-1 mb-2">Botón Grande</button>
            <button type="button" class="btn btn-secondary me-1 mb-2">Botón Normal</button>
            <button type="button" class="btn btn-success btn-sm me-1 mb-2">Botón Pequeño</button>
            <hr>
            <h6>Botones Deshabilitados</h6>
            <button type="button" class="btn btn-primary" disabled>Primary</button>
            <button type="button" class="btn btn-secondary" disabled>Secondary</button>
            <button type="button" class="btn btn-outline-primary" disabled>Outline Primary</button>
        </div>
    </div>

    <!-- Sección de Heroicons -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">
                <?= heroicon_outline('swatch', 'heroicon-sm me-1') ?> 
                Heroicons
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <?= heroicon_outline('information-circle', 'heroicon-sm me-1') ?>
                <span>Heroicons es una nueva biblioteca de iconos SVG incluida en el sistema. Estos iconos son escalables, modernos y se integran perfectamente con la identidad visual existente.</span>
            </div>
            
            <h6 class="mt-4">Estilos disponibles</h6>
            <div class="row text-center">
                <div class="col-md-6">
                    <h6>Outline</h6>
                    <div class="d-flex justify-content-center gap-4 mb-3">
                        <?= heroicon_outline('user', 'heroicon-lg') ?>
                        <?= heroicon_outline('home', 'heroicon-lg') ?>
                        <?= heroicon_outline('heart', 'heroicon-lg') ?>
                        <?= heroicon_outline('check', 'heroicon-lg') ?>
                    </div>
                    <code>heroicon_outline('nombre')</code>
                </div>
                <div class="col-md-6">
                    <h6>Solid</h6>
                    <div class="d-flex justify-content-center gap-4 mb-3">
                        <?= heroicon_solid('user', 'heroicon-lg') ?>
                        <?= heroicon_solid('home', 'heroicon-lg') ?>
                        <?= heroicon_solid('heart', 'heroicon-lg') ?>
                        <?= heroicon_solid('check', 'heroicon-lg') ?>
                    </div>
                    <code>heroicon_solid('nombre')</code>
                </div>
            </div>
            
            <h6 class="mt-4">Tamaños disponibles</h6>
            <div class="row align-items-center text-center">
                <div class="col">
                    <?= heroicon_outline('user', 'heroicon-xs') ?>
                    <p class="mt-2 mb-0"><code>heroicon-xs</code></p>
                </div>
                <div class="col">
                    <?= heroicon_outline('user', 'heroicon-sm') ?>
                    <p class="mt-2 mb-0"><code>heroicon-sm</code></p>
                </div>
                <div class="col">
                    <?= heroicon_outline('user', 'heroicon-md') ?>
                    <p class="mt-2 mb-0"><code>heroicon-md</code></p>
                </div>
                <div class="col">
                    <?= heroicon_outline('user', 'heroicon-lg') ?>
                    <p class="mt-2 mb-0"><code>heroicon-lg</code></p>
                </div>
                <div class="col">
                    <?= heroicon_outline('user', 'heroicon-xl') ?>
                    <p class="mt-2 mb-0"><code>heroicon-xl</code></p>
                </div>
            </div>
            
            <h6 class="mt-4">Colores</h6>
            <div class="row align-items-center text-center">
                <div class="col">
                    <?= heroicon_outline('heart', 'heroicon-lg heroicon-primary') ?>
                    <p class="mt-2 mb-0"><code>heroicon-primary</code></p>
                </div>
                <div class="col">
                    <?= heroicon_outline('heart', 'heroicon-lg heroicon-secondary') ?>
                    <p class="mt-2 mb-0"><code>heroicon-secondary</code></p>
                </div>
                <div class="col">
                    <?= heroicon_outline('heart', 'heroicon-lg heroicon-success') ?>
                    <p class="mt-2 mb-0"><code>heroicon-success</code></p>
                </div>
                <div class="col">
                    <?= heroicon_outline('heart', 'heroicon-lg heroicon-danger') ?>
                    <p class="mt-2 mb-0"><code>heroicon-danger</code></p>
                </div>
                <div class="col">
                    <?= heroicon_outline('heart', 'heroicon-lg heroicon-warning') ?>
                    <p class="mt-2 mb-0"><code>heroicon-warning</code></p>
                </div>
                <div class="col">
                    <?= heroicon_outline('heart', 'heroicon-lg heroicon-info') ?>
                    <p class="mt-2 mb-0"><code>heroicon-info</code></p>
                </div>
            </div>
            
            <h6 class="mt-4">Animaciones</h6>
            <div class="row">
                <div class="col-md-6 text-center">
                    <h6 class="mb-3">Rotación</h6>
                    <?= heroicon_outline('arrow-path', 'heroicon-lg heroicon-primary heroicon-spin') ?>
                    <p class="mt-3"><code>heroicon-spin</code></p>
                </div>
                <div class="col-md-6 text-center">
                    <h6 class="mb-3">En botones</h6>
                    <button class="btn btn-primary">
                        <?= heroicon_outline('arrow-path', 'heroicon-sm heroicon-spin') ?> Cargando...
                    </button>
                </div>
            </div>
            
            <h6 class="mt-4">Integraciones con componentes</h6>
            
            <!-- Alertas con heroicons -->
            <h6 class="mb-2 mt-3">Alertas con Heroicons</h6>
            <div class="alert alert-primary d-flex align-items-center mb-2" role="alert">
                <?= heroicon_outline('information-circle', 'heroicon-md me-2') ?>
                <div>Alerta informativa con icono Heroicon.</div>
            </div>
            <div class="alert alert-danger d-flex align-items-center mb-2" role="alert">
                <?= heroicon_outline('x-circle', 'heroicon-md me-2') ?>
                <div>Alerta de error con icono Heroicon.</div>
            </div>
            
            <!-- Badges con heroicons -->
            <h6 class="mb-2 mt-3">Badges con Heroicons</h6>
            <div class="mb-3">
                <span class="badge bg-primary d-inline-flex align-items-center">
                    <?= heroicon_outline('user', 'heroicon-xs me-1') ?> Usuario
                </span>
                <span class="badge bg-success d-inline-flex align-items-center ms-2">
                    <?= heroicon_outline('check', 'heroicon-xs me-1') ?> Completado
                </span>
                <span class="badge bg-danger d-inline-flex align-items-center ms-2">
                    <?= heroicon_outline('x-mark', 'heroicon-xs me-1') ?> Rechazado
                </span>
            </div>
            
            <!-- Ejemplo en tabla -->
            <h6 class="mt-3">Botones de Acción en Tablas</h6>
            <p>La transición de Material Symbols a Heroicons se está realizando gradualmente. Estos son los botones de acción con Heroicons:</p>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Consulta General</td>
                            <td>50,00 €</td>
                            <td>
                                <span class="badge bg-success d-inline-flex align-items-center">
                                    <?= heroicon_outline('check-circle', 'heroicon-xs me-1') ?> Activo
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-view">
                                        <?= heroicon_outline('eye', 'heroicon-sm') ?>
                                    </button>
                                    <button class="btn btn-edit">
                                        <?= heroicon_outline('pencil', 'heroicon-sm') ?>
                                    </button>
                                    <button class="btn btn-delete">
                                        <?= heroicon_outline('trash', 'heroicon-sm') ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <h6 class="mt-3">Código para implementar botones de acción:</h6>
            <pre><code>&lt;div class="table-actions"&gt;
    &lt;a href="view.php?id=&lt;?= $id ?&gt;" class="btn btn-view" data-bs-toggle="tooltip" title="Ver detalle"&gt;
        &lt;?= heroicon_outline('eye', 'heroicon-sm') ?&gt;
    &lt;/a&gt;
    &lt;a href="edit.php?id=&lt;?= $id ?&gt;" class="btn btn-edit" data-bs-toggle="tooltip" title="Editar"&gt;
        &lt;?= heroicon_outline('pencil', 'heroicon-sm') ?&gt;
    &lt;/a&gt;
    &lt;a href="delete.php?id=&lt;?= $id ?&gt;" class="btn btn-delete" data-bs-toggle="tooltip" title="Eliminar"&gt;
        &lt;?= heroicon_outline('trash', 'heroicon-sm') ?&gt;
    &lt;/a&gt;
&lt;/div&gt;</code></pre>
            
            <h6 class="mt-4">Código para implementar</h6>
            <p>Para usar Heroicons en cualquier parte de la aplicación:</p>
            <pre><code>&lt;?= heroicon_outline('nombre-del-icono', 'heroicon-sm') ?&gt;
&lt;?= heroicon_solid('nombre-del-icono', 'heroicon-primary') ?&gt;</code></pre>
            <p class="mt-3">Los iconos disponibles se pueden consultar en <a href="https://heroicons.com/" target="_blank">heroicons.com</a>.</p>
        </div>
    </div>

    <!-- Sección de Alertas -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Alertas</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-primary" role="alert">
                Una alerta simple de tipo primary. <a href="#" class="alert-link">Un enlace de ejemplo</a>.
            </div>
            <div class="alert alert-secondary" role="alert">
                Una alerta simple de tipo secondary.
            </div>
            <div class="alert alert-success" role="alert">
                <h4 class="alert-heading">¡Bien hecho!</h4>
                <p>Has completado la acción con éxito.</p>
                <hr>
                <p class="mb-0">Puedes añadir más detalles aquí si es necesario.</p>
            </div>
            <div class="alert alert-danger" role="alert">
                <span class="material-symbols-rounded align-middle me-1">error</span>
                Una alerta simple de tipo danger con icono.
            </div>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>¡Atención!</strong> Deberías revisar este campo.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <div class="alert alert-info" role="alert">
                Una alerta simple de tipo info.
            </div>
            <div class="alert alert-light" role="alert">
                Una alerta simple de tipo light.
            </div>
            <div class="alert alert-dark" role="alert">
                Una alerta simple de tipo dark.
            </div>
        </div>
    </div>

    <!-- Sección de Cards -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Tarjetas (Cards)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            Encabezado de Tarjeta
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Título de la Tarjeta</h5>
                            <p class="card-text">Contenido de ejemplo para la tarjeta. Puede incluir texto, enlaces, etc.</p>
                            <a href="#" class="btn btn-primary">Botón de Acción</a>
                        </div>
                        <div class="card-footer text-muted">
                            Pie de Tarjeta
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card border-primary shadow-sm">
                        <div class="card-header bg-primary text-white">Tarjeta con borde y cabecera Primary</div>
                        <div class="card-body text-primary">
                            <h5 class="card-title">Título Primary</h5>
                            <p class="card-text">Contenido con texto primary.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Formularios -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Formularios</h5>
        </div>
        <div class="card-body">
            <form>
                <div class="mb-3">
                    <label for="exampleInputEmail1" class="form-label">Dirección de Email</label>
                    <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="usuario@ejemplo.com">
                    <div id="emailHelp" class="form-text">Nunca compartiremos tu email con nadie.</div>
                </div>
                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Contraseña</label>
                    <input type="password" class="form-control is-invalid" id="exampleInputPassword1" placeholder="Contraseña">
                    <div class="invalid-feedback">
                        Contraseña incorrecta.
                    </div>
                </div>
                 <div class="mb-3">
                    <label for="exampleInputTextValid" class="form-label">Campo Válido</label>
                    <input type="text" class="form-control is-valid" id="exampleInputTextValid" value="Correcto">
                     <div class="valid-feedback">
                        ¡Campo válido!
                    </div>
                </div>
                <div class="mb-3">
                    <label for="exampleSelect1" class="form-label">Selección</label>
                    <select class="form-select" id="exampleSelect1">
                        <option selected>Abrir este menú de selección</option>
                        <option value="1">Opción Uno</option>
                        <option value="2">Opción Dos</option>
                        <option value="3">Opción Tres</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="exampleTextarea" class="form-label">Área de Texto</label>
                    <textarea class="form-control" id="exampleTextarea" rows="3" placeholder="Escribe algo aquí..."></textarea>
                </div>
                <div class="mb-3">
                     <label class="form-label">Opciones Checkbox</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                        <label class="form-check-label" for="flexCheckDefault">
                            Checkbox por defecto
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" checked>
                        <label class="form-check-label" for="flexCheckChecked">
                            Checkbox marcado
                        </label>
                    </div>
                </div>
                 <div class="mb-3">
                     <label class="form-label">Opciones Radio</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1">
                        <label class="form-check-label" for="flexRadioDefault1">
                            Radio por defecto
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault2" checked>
                        <label class="form-check-label" for="flexRadioDefault2">
                            Radio marcado
                        </label>
                    </div>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="exampleCheck1">
                    <label class="form-check-label" for="exampleCheck1">Recordarme</label>
                </div>
                <button type="submit" class="btn btn-primary">Enviar</button>
                 <button type="button" class="btn btn-secondary">Cancelar</button>
            </form>
        </div>
    </div>

    <!-- Sección de Iconos en Listas -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Iconos en Listas</h5>
        </div>
        <div class="card-body">
            <p>Los iconos son elementos visuales importantes que mejoran la usabilidad y el aspecto estético de la interfaz. Se utilizan principalmente Material Symbols para mantener consistencia visual en toda la aplicación.</p>
            
            <h6 class="mt-4">Iconos en Listados</h6>
            <p>En los listados, los iconos se utilizan para representar acciones y propiedades:</p>
            
            <h6 class="mt-3">1. Iconos de datos en las celdas</h6>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Icono</th>
                            <th>Código</th>
                            <th>Uso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;">phone</span> 666777888</td>
                            <td><code>&lt;span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;"&gt;phone&lt;/span&gt;</code></td>
                            <td>Teléfono en listado de pacientes</td>
                        </tr>
                        <tr>
                            <td><span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;">email</span> correo@ejemplo.com</td>
                            <td><code>&lt;span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;"&gt;email&lt;/span&gt;</code></td>
                            <td>Email en listado de pacientes</td>
                        </tr>
                        <tr>
                            <td><span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;">event</span> 5 citas</td>
                            <td><code>&lt;span class="material-symbols-rounded me-1 text-muted" style="font-size: 14px;"&gt;event&lt;/span&gt;</code></td>
                            <td>Contador de citas en listado de pacientes</td>
                        </tr>
                        <tr>
                            <td><span class="material-symbols-rounded text-success" style="font-size: 24px;">check_circle</span></td>
                            <td><code>&lt;span class="material-symbols-rounded text-success" style="font-size: 24px;"&gt;check_circle&lt;/span&gt;</code></td>
                            <td>Indicador afirmativo (ej: consentimiento firmado)</td>
                        </tr>
                        <tr>
                            <td><span class="material-symbols-rounded text-danger" style="font-size: 24px;">cancel</span></td>
                            <td><code>&lt;span class="material-symbols-rounded text-danger" style="font-size: 24px;"&gt;cancel&lt;/span&gt;</code></td>
                            <td>Indicador negativo (ej: consentimiento pendiente)</td>
                        </tr>
                        <tr>
                            <td><span class="material-symbols-rounded" style="font-size: 12px; vertical-align: middle;">confirmation_number</span> Bono</td>
                            <td><code>&lt;span class="material-symbols-rounded" style="font-size: 12px; vertical-align: middle;"&gt;confirmation_number&lt;/span&gt;</code></td>
                            <td>Indicador de bono en citas</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <h6 class="mt-4">2. Iconos de Acciones</h6>
            <p>Los iconos de acciones se utilizan en los botones de la columna de acciones:</p>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Acción</th>
                            <th>Botón</th>
                            <th>Código</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Ver detalle</td>
                            <td>
                                <a href="#" class="btn btn-view">
                                    <span class="material-symbols-rounded">visibility</span>
                                </a>
                            </td>
                            <td>
<pre><code>&lt;a href="#" class="btn btn-view"&gt;
    &lt;span class="material-symbols-rounded"&gt;visibility&lt;/span&gt;
&lt;/a&gt;</code></pre>
                            </td>
                        </tr>
                        <tr>
                            <td>Editar</td>
                            <td>
                                <a href="#" class="btn btn-edit">
                                    <span class="material-symbols-rounded">edit</span>
                                </a>
                            </td>
                            <td>
<pre><code>&lt;a href="#" class="btn btn-edit"&gt;
    &lt;span class="material-symbols-rounded"&gt;edit&lt;/span&gt;
&lt;/a&gt;</code></pre>
                            </td>
                        </tr>
                        <tr>
                            <td>Eliminar</td>
                            <td>
                                <a href="#" class="btn btn-delete">
                                    <span class="material-symbols-rounded">delete</span>
                                </a>
                            </td>
                            <td>
<pre><code>&lt;a href="#" class="btn btn-delete"&gt;
    &lt;span class="material-symbols-rounded"&gt;delete&lt;/span&gt;
&lt;/a&gt;</code></pre>
                            </td>
                        </tr>
                        <tr>
                            <td>Notificación</td>
                            <td>
                                <a href="#" class="btn btn-notify">
                                    <span class="material-symbols-rounded">notifications</span>
                                </a>
                            </td>
                            <td>
<pre><code>&lt;a href="#" class="btn btn-notify"&gt;
    &lt;span class="material-symbols-rounded"&gt;notifications&lt;/span&gt;
&lt;/a&gt;</code></pre>
                            </td>
                        </tr>
                        <tr>
                            <td>WhatsApp</td>
                            <td>
                                <a href="#" class="btn btn-whatsapp">
                                    <span class="material-symbols-rounded">smartphone</span>
                                </a>
                            </td>
                            <td>
<pre><code>&lt;a href="#" class="btn btn-whatsapp"&gt;
    &lt;span class="material-symbols-rounded"&gt;smartphone&lt;/span&gt;
&lt;/a&gt;</code></pre>
                            </td>
                        </tr>
                        <tr>
                            <td>Completar</td>
                            <td>
                                <a href="#" class="btn btn-complete">
                                    <span class="material-symbols-rounded">check_circle</span>
                                </a>
                            </td>
                            <td>
<pre><code>&lt;a href="#" class="btn btn-complete"&gt;
    &lt;span class="material-symbols-rounded"&gt;check_circle&lt;/span&gt;
&lt;/a&gt;</code></pre>
                            </td>
                        </tr>
                        <tr>
                            <td>Revertir</td>
                            <td>
                                <a href="#" class="btn btn-revert">
                                    <span class="material-symbols-rounded">restore</span>
                                </a>
                            </td>
                            <td>
<pre><code>&lt;a href="#" class="btn btn-revert"&gt;
    &lt;span class="material-symbols-rounded"&gt;restore&lt;/span&gt;
&lt;/a&gt;</code></pre>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <h6 class="mt-4">3. Estilos CSS para botones de acción</h6>
            <p>Los siguientes estilos CSS deben utilizarse para mantener la consistencia en toda la aplicación:</p>
            
<pre><code>/* Estilos para los botones de acción */
.btn-view, .btn-edit, .btn-delete, .btn-notify, .btn-whatsapp, .btn-complete, .btn-revert {
  /* Aspecto uniforme */
  padding: 0;
  font-size: 0.875rem;
  line-height: 1;
  border-radius: 50%; /* Los botones deben ser redondos */
  border: none;
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin: 0 2px;
  transition: all 0.2s;
  background-color: #f8f9fa;
}

/* Estados hover para cada tipo de botón */
.btn-view:hover {
  background-color: rgba(13, 110, 253, 0.15);
  color: #0d6efd;
}

.btn-edit:hover {
  background-color: rgba(255, 193, 7, 0.15);
  color: #ffc107;
}

.btn-delete:hover {
  background-color: rgba(220, 53, 69, 0.15);
  color: #dc3545;
}

.btn-notify:hover {
  background-color: rgba(23, 162, 184, 0.15);
  color: #17a2b8;
}

.btn-whatsapp:hover {
  background-color: rgba(37, 211, 102, 0.15);
  color: #25d366;
}

.btn-complete:hover {
  background-color: rgba(25, 135, 84, 0.15);
  color: #198754;
}

.btn-revert:hover {
  background-color: rgba(108, 117, 125, 0.15);
  color: #6c757d;
}
</code></pre>
            
            <div class="alert alert-info mt-4">
                <span class="material-symbols-rounded align-top me-2">info</span>
                <div class="d-inline-block">
                    <strong>Importante:</strong> Para mantener la coherencia visual, usar siempre Material Symbols en lugar de otros sistemas de iconos. Todos los botones de acción deben:<br>
                    - Tener forma circular (border-radius: 50%)<br>
                    - Incluir un tooltip mediante el atributo <code>data-bs-toggle="tooltip"</code> y <code>title="Descripción"</code><br>
                    - Utilizar las clases específicas (btn-view, btn-edit, etc.) en lugar de clases genéricas de Bootstrap
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Badges (Insignias) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Badges (Insignias)</h5>
        </div>
        <div class="card-body">
            <p>Ejemplos de badges utilizados para destacar información.</p>
            <span class="badge bg-primary me-1">Primary</span>
            <span class="badge bg-secondary me-1">Secondary</span>
            <span class="badge bg-success me-1">Success</span>
            <span class="badge bg-danger me-1">Danger</span>
            <span class="badge bg-warning text-dark me-1">Warning</span>
            <span class="badge bg-info text-dark me-1">Info</span>
            <span class="badge bg-light text-dark me-1">Light</span>
            <span class="badge bg-dark me-1">Dark</span>
            <hr>
            <h6>Badges tipo "Pill"</h6>
            <span class="badge rounded-pill bg-primary me-1">Primary</span>
            <span class="badge rounded-pill bg-success me-1">Success</span>
             <span class="badge rounded-pill bg-info text-dark me-1">Info</span>
            <hr>
            <h6>Badges dentro de botones</h6>
            <button type="button" class="btn btn-primary">
                Notificaciones <span class="badge bg-secondary">4</span>
            </button>
        </div>
    </div>
    
    <!-- Sección de Progress Bars -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Barras de Progreso</h5>
        </div>
        <div class="card-body">
             <p>Ejemplos de barras de progreso.</p>
             <div class="progress mb-3">
                <div class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">25%</div>
            </div>
            <div class="progress mb-3">
                <div class="progress-bar bg-success" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">50%</div>
            </div>
            <div class="progress mb-3">
                <div class="progress-bar bg-info" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75%</div>
            </div>
            <div class="progress mb-3">
                <div class="progress-bar bg-warning" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">100%</div>
            </div>
             <div class="progress mb-3">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 75%">Animada</div>
            </div>
        </div>
    </div>

    <!-- Sección de Paginación -->
     <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Paginación</h5>
        </div>
        <div class="card-body">
            <p>Ejemplo de componente de paginación.</p>
            <nav aria-label="Ejemplo de paginación">
                <ul class="pagination">
                    <li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Anterior</a></li>
                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                    <li class="page-item active" aria-current="page"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Siguiente</a></li>
                </ul>
            </nav>
            <hr>
            <h6>Paginación con iconos</h6>
             <nav aria-label="Ejemplo de paginación con iconos">
                 <ul class="pagination">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                            <span class="material-symbols-rounded align-middle">chevron_left</span>
                        </a>
                    </li>
                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                    <li class="page-item active" aria-current="page"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">
                             <span class="material-symbols-rounded align-middle">chevron_right</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Sección de Listas (List Group) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Listas (List Group)</h5>
        </div>
        <div class="card-body">
            <p>Ejemplos de listas.</p>
            <h6>Lista básica</h6>
            <ul class="list-group mb-3">
                <li class="list-group-item">Elemento 1</li>
                <li class="list-group-item">Elemento 2</li>
                <li class="list-group-item">Elemento 3</li>
            </ul>
            
            <h6>Lista con elementos activos y deshabilitados</h6>
            <ul class="list-group mb-3">
                <li class="list-group-item active" aria-current="true">Elemento activo</li>
                <li class="list-group-item">Elemento normal</li>
                <li class="list-group-item disabled" aria-disabled="true">Elemento deshabilitado</li>
            </ul>
            
            <h6>Lista con enlaces (o botones)</h6>
            <div class="list-group mb-3">
                <a href="#" class="list-group-item list-group-item-action active" aria-current="true">
                    Enlace activo
                </a>
                <a href="#" class="list-group-item list-group-item-action">Enlace normal</a>
                <button type="button" class="list-group-item list-group-item-action">Botón normal</button>
                <a href="#" class="list-group-item list-group-item-action disabled" tabindex="-1" aria-disabled="true">Enlace deshabilitado</a>
            </div>

            <h6>Lista con badges</h6>
             <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Elemento con badge
                    <span class="badge bg-primary rounded-pill">14</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Otro elemento
                    <span class="badge bg-success rounded-pill">2</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Un tercer elemento
                    <span class="badge bg-danger rounded-pill">1</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Sección de Tablas -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Tablas</h5>
        </div>
        <div class="card-body">
            <p>Ejemplo de tabla estándar utilizada en la aplicación.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Juan Pérez</td>
                            <td>juan.perez@ejemplo.com</td>
                            <td><span class="badge bg-primary">Admin</span></td>
                            <td><span class="badge bg-success">Activo</span></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="#" class="btn btn-sm btn-outline-info" title="Ver Detalles">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <span class="material-symbols-rounded">edit</span>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <span class="material-symbols-rounded">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>María García</td>
                            <td>maria.garcia@ejemplo.com</td>
                            <td><span class="badge bg-secondary">Usuario</span></td>
                            <td><span class="badge bg-success">Activo</span></td>
                             <td>
                                <div class="btn-group" role="group">
                                    <a href="#" class="btn btn-sm btn-outline-info" title="Ver Detalles">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <span class="material-symbols-rounded">edit</span>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <span class="material-symbols-rounded">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                         <tr>
                            <td>3</td>
                            <td>Carlos López</td>
                            <td>carlos.lopez@ejemplo.com</td>
                             <td><span class="badge bg-secondary">Usuario</span></td>
                            <td><span class="badge bg-danger">Inactivo</span></td>
                             <td>
                                <div class="btn-group" role="group">
                                    <a href="#" class="btn btn-sm btn-outline-info" title="Ver Detalles">
                                        <span class="material-symbols-rounded">visibility</span>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <span class="material-symbols-rounded">edit</span>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <span class="material-symbols-rounded">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 