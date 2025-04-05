<?php
// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once 'header_fix.php';

// Verificar si el usuario ha iniciado sesión y es administrador
if (!isset($_SESSION['usuario_id']) || !esAdmin()) {
    header('Location: ../../login.php');
    exit();
}

// Título y breadcrumbs para la página
$titulo_pagina = "Estructura de Tabla Incorrecta";
$mensaje = "";
$tipo_alerta = "danger";

// Usar el header alternativo que no depende de la base de datos
mostrarHeadRecursos();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col">
            <!-- Título -->
            <h1 class="mb-4"><i class="material-symbols-rounded me-2">error</i>Error de estructura de tabla</h1>
            
            <!-- Alerta de resultado -->
            <div class="alert alert-danger" role="alert">
                <i class="material-symbols-rounded me-2">warning</i>
                <strong>Error detectado:</strong> La estructura de la tabla 'configuracion' no es compatible con esta versión del sistema.
            </div>
            
            <!-- Tarjeta de información -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="material-symbols-rounded me-2">info</i>Solución</h5>
                </div>
                <div class="card-body">
                    <p>Se detectó un problema con la estructura de la tabla <code>configuracion</code>. El sistema requiere que esta tabla tenga el formato de clave-valor para funcionar correctamente.</p>
                    
                    <p>Por favor, utilice la herramienta de corrección para actualizar automáticamente la estructura de la tabla y mantener todos sus datos.</p>
                    
                    <div class="alert alert-warning">
                        <i class="material-symbols-rounded me-2">warning</i>
                        <strong>Importante:</strong> Es recomendable hacer una copia de seguridad de la base de datos antes de ejecutar esta corrección.
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        <a href="corregir_tabla.php" class="btn btn-primary btn-lg">
                            <i class="material-symbols-rounded me-2">build</i>Ir a la herramienta de corrección
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Mostrar el footer
mostrarFooterRecursos();
?> 