<?php
/**
 * Widget de banner de bienvenida para el dashboard
 * Muestra un saludo personalizado con la fecha actual y el clima en Madrid
 */

// Evitar acceso directo
if (!defined('BASE_URL')) {
    exit('Acceso directo no permitido');
}

// Formato de fecha en español
$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

$dia_semana = $dias[date('w')];
$dia = date('j');
$mes = $meses[date('n')-1];
$anio = date('Y');
$dia_actual = "$dia_semana $dia de $mes de $anio";

// Determinar el saludo según la hora del día
$hora_actual = date('H');
$saludo = 'Buenos días';

if ($hora_actual >= 12 && $hora_actual < 20) {
    $saludo = 'Buenas tardes';
} elseif ($hora_actual >= 20) {
    $saludo = 'Buenas noches';
}

// Obtener nombre del usuario
$nombre_usuario = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : '';

// Función para obtener el clima de Madrid (simulación)
function obtenerClimaMadrid() {
    // En un entorno real, esto debería ser una API de clima real
    // Para este ejemplo, usamos datos estáticos o una probabilidad aleatoria
    $climas = [
        'soleado' => 'Soleado con temperaturas agradables',
        'nublado' => 'Nublado con algunas claras',
        'lluvioso' => 'Lluvioso con precipitaciones ligeras', 
        'tormentoso' => 'Tormentoso con posibles chubascos',
        'caluroso' => 'Caluroso con cielos despejados',
        'frío' => 'Frío con cielos despejados'
    ];
    
    // En un entorno real, este valor vendría de una API
    $clima_actual = array_rand($climas);
    
    return $climas[$clima_actual];
}

$clima_madrid = obtenerClimaMadrid();

// Verificar si la imagen existe
$ruta_imagen = __DIR__ . '/../../../assets/images/dashboard/welcome-banner.png';
$imagen_existe = file_exists($ruta_imagen);
?>

<div class="<?= isset($widget['tamano']) ? $widget['tamano'] : 'col-12' ?> mb-4">
    <div class="bg-light bg-opacity-50 rounded-3 overflow-hidden">
        <div class="p-0">
            <div class="row g-0">
                <?php if ($imagen_existe): ?>
                <div class="col-md-3 position-relative overflow-hidden d-none d-md-block" style="min-height: 200px;">
                    <img src="<?= BASE_URL ?>/assets/images/dashboard/welcome-banner.png" alt="Ilustración de bienvenida" class="position-absolute top-50 start-0 translate-middle-y" style="max-height: 140%; max-width: 140%; margin-left: -10px;">
                    <div class="position-absolute top-0 end-0 bottom-0 start-0" style="background: linear-gradient(270deg, rgba(255,255,255,0.2) 70%, rgba(255,255,255,0) 100%);"></div>
                </div>
                <?php endif; ?>
                
                <div class="col-md-<?= $imagen_existe ? '9' : '12' ?> d-flex flex-column justify-content-center p-4 ps-md-2">
                    <div class="<?= $imagen_existe ? 'ps-md-0' : '' ?>">
                        <h2 class="mb-2 fw-bold text-primary">¡<?= $saludo ?> <?= htmlspecialchars($nombre_usuario) ?>!</h2>
                        <p class="fs-5 mb-3">Aquí empieza tu día. Hoy es <span class="fw-semibold"><?= $dia_actual ?></span> y el tiempo en Madrid es <span class="fw-semibold"><?= $clima_madrid ?></span>.</p>
                        <p class="text-muted mb-0">Revisa tus próximas citas y prepárate para un día productivo.</p>
                        
                        <?php if (!$imagen_existe): ?>
                        <div class="mt-3 text-muted small">
                            <div class="alert alert-info">
                                <p class="mb-0"><strong>Nota:</strong> Para completar este banner, guarda la imagen proporcionada en la ruta <code>assets/images/dashboard/welcome-banner.png</code></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 