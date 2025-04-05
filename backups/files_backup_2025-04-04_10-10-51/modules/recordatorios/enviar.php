<?php
/**
 * Script para enviar recordatorios de citas
 * Este script puede ser ejecutado de dos formas:
 * 1. Desde el navegador (requiere autenticación)
 * 2. Desde una tarea programada (cron) en línea de comandos
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once 'funciones.php';

// Determinar si se está ejecutando desde línea de comandos
$es_cli = php_sapi_name() === 'cli';

if (!$es_cli) {
    // Si se ejecuta desde el navegador, requiere autenticación
    requiereLogin();
    
    // Preparar datos para la página
    $title = "Enviar Recordatorios";
    $breadcrumbs = [
        'Recordatorios' => 'configurar.php',
        'Enviar' => '#'
    ];
    
    // Iniciar el buffer de salida para capturar el contenido
    startPageContent();
    ?>
    
    <div class="container-fluid py-4 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">
                <span class="material-symbols-rounded me-2">send</span>Envío de Recordatorios
            </h1>
            <a href="configurar.php" class="btn btn-outline-secondary">
                <span class="material-symbols-rounded me-2">settings</span>Configuración
            </a>
        </div>
        
        <!-- Breadcrumbs -->
        <?php include ROOT_PATH . '/includes/partials/breadcrumb.php'; ?>
        
        <!-- Alertas -->
        <?php include ROOT_PATH . '/includes/partials/alerts.php'; ?>
        
    <?php
}

// Procesar recordatorios pendientes
$resultados = procesarRecordatoriosPendientes();

// Mostrar resultados
if ($es_cli) {
    // Formato para línea de comandos
    echo "=== RESULTADOS DEL PROCESAMIENTO DE RECORDATORIOS ===\n";
    echo "Total de citas procesadas: {$resultados['total']}\n";
    echo "Recordatorios enviados: {$resultados['enviados']}\n";
    echo "Errores: {$resultados['errores']}\n\n";
    
    if (!empty($resultados['detalles'])) {
        echo "DETALLES:\n";
        foreach ($resultados['detalles'] as $detalle) {
            echo "- Cita ID: {$detalle['cita_id']}, Fecha: {$detalle['fecha']}, Resultado: {$detalle['resultado']}\n";
        }
    }
} else {
    // Formato para navegador web con Bootstrap
    ?>
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">summarize</span>Resultados del Envío
            </h5>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center">
                            <h3 class="display-4"><?= $resultados['total'] ?></h3>
                            <p class="text-muted mb-0">Total de citas procesadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body text-center">
                            <h3 class="display-4"><?= $resultados['enviados'] ?></h3>
                            <p class="mb-0">Recordatorios enviados</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body text-center">
                            <h3 class="display-4"><?= $resultados['errores'] ?></h3>
                            <p class="mb-0">Errores</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($resultados['detalles'])): ?>
            <h5 class="mb-3">Detalles:</h5>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID Cita</th>
                            <th>Fecha</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados['detalles'] as $detalle): ?>
                        <tr class="<?= $detalle['resultado'] == 'enviado' ? 'table-success' : 'table-danger' ?>">
                            <td><?= $detalle['cita_id'] ?></td>
                            <td><?= formatDateToView($detalle['fecha']) ?></td>
                            <td>
                                <?php if ($detalle['resultado'] === 'enviado'): ?>
                                <span class="badge bg-success">Enviado</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Error</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                No hay citas pendientes de recordatorio para la fecha configurada.
            </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="enviar.php" class="btn btn-primary d-inline-flex align-items-center me-2 mb-2 mb-sm-0">
                    <span class="material-symbols-rounded me-2">refresh</span>Procesar de nuevo
                </a>
                <a href="configurar.php" class="btn btn-outline-secondary d-inline-flex align-items-center mb-2 mb-sm-0">
                    <span class="material-symbols-rounded me-2">settings</span>Ajustar configuración
                </a>
            </div>
        </div>
    </div>
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <span class="material-symbols-rounded me-2">terminal</span>Configuración Automática
            </h5>
        </div>
        <div class="card-body">
            <p>Para automatizar el envío de recordatorios, configure una tarea programada (cron job) en su servidor:</p>
            <div class="bg-light p-3 rounded overflow-auto">
                <pre class="mb-0" style="white-space: pre-wrap; word-break: break-all;">0 8 * * * php <?= __DIR__ ?>/enviar.php</pre>
            </div>
            <p class="mb-0 small text-muted mt-3">Este ejemplo ejecutará el script todos los días a las 8:00 AM.</p>
        </div>
    </div>
    
    </div>
    
    <?php
    // Finalizar el buffer de salida y mostrar el contenido
    endPageContent();
}
?> 