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

<div class="container-fluid px-4">
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

     <!-- Sección de Iconos -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Iconos (Material Symbols)</h5>
        </div>
        <div class="card-body">
            <p>Utilizamos <a href="https://fonts.google.com/icons" target="_blank">Material Symbols</a> (Rounded) para los iconos.</p>
            <p>Ejemplos:</p>
            <span class="material-symbols-rounded fs-2 me-2">home</span>
            <span class="material-symbols-rounded fs-2 me-2">settings</span>
            <span class="material-symbols-rounded fs-2 me-2">person</span>
            <span class="material-symbols-rounded fs-2 me-2">backup</span>
            <span class="material-symbols-rounded fs-2 me-2">psychology</span>
            <span class="material-symbols-rounded fs-2 me-2">add_circle</span>
            <span class="material-symbols-rounded fs-2 me-2">edit</span>
            <span class="material-symbols-rounded fs-2 me-2">delete</span>
            <span class="material-symbols-rounded fs-2 me-2">visibility</span>
            <span class="material-symbols-rounded fs-2 me-2">check_circle</span>
            <span class="material-symbols-rounded fs-2 me-2">cancel</span>
             <span class="material-symbols-rounded fs-2 me-2">info</span>
        </div>
    </div>

    <!-- Sección de Uso de Iconos en Acciones -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Uso de Iconos en Acciones</h5>
        </div>
        <div class="card-body">
            <p>Ejemplos de cómo se usan los iconos en botones de acción comunes, como en tablas de datos.</p>
            
            <h6>Botones individuales (tamaño pequeño)</h6>
            <a href="#" class="btn btn-sm btn-outline-primary me-1" title="Editar">
                <span class="material-symbols-rounded align-middle">edit</span>
            </a>
            <a href="#" class="btn btn-sm btn-outline-danger me-1" title="Eliminar">
                <span class="material-symbols-rounded align-middle">delete</span>
            </a>
             <a href="#" class="btn btn-sm btn-outline-info me-1" title="Ver Detalles">
                <span class="material-symbols-rounded align-middle">visibility</span>
            </a>
             <a href="#" class="btn btn-sm btn-outline-success me-1" title="Activar">
                <span class="material-symbols-rounded align-middle">check_circle</span>
            </a>
            <a href="#" class="btn btn-sm btn-outline-warning me-1" title="Desactivar">
                <span class="material-symbols-rounded align-middle">cancel</span>
            </a>
            
            <hr>

            <h6>Grupo de botones (btn-group)</h6>
             <div class="btn-group mb-2" role="group" aria-label="Acciones de ejemplo">
                <a href="#" class="btn btn-sm btn-outline-primary" title="Editar">
                    <span class="material-symbols-rounded">edit</span>
                </a>
                <a href="#" class="btn btn-sm btn-outline-danger" title="Eliminar">
                    <span class="material-symbols-rounded">delete</span>
                </a>
                <a href="#" class="btn btn-sm btn-outline-info" title="Ver Detalles">
                    <span class="material-symbols-rounded">visibility</span>
                </a>
            </div>
            <p><small>Se utiliza <code>.btn-group</code> para agrupar acciones relacionadas.</small></p>

            <hr>

            <h6>Botones con texto e icono</h6>
             <a href="#" class="btn btn-primary me-1 mb-2">
                <span class="material-symbols-rounded align-middle me-1">add</span> Nuevo Elemento
            </a>
             <a href="#" class="btn btn-secondary me-1 mb-2">
                <span class="material-symbols-rounded align-middle me-1">arrow_back</span> Volver
            </a>
             <button type="submit" class="btn btn-success me-1 mb-2">
                 <span class="material-symbols-rounded align-middle me-1">save</span> Guardar Cambios
            </button>

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