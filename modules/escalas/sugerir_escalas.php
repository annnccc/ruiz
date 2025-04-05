<?php
/**
 * Módulo de Escalas Psicológicas - Sugerencia de Escalas
 * Proporciona recomendaciones de escalas psicológicas basadas en el perfil y síntomas del paciente
 */

// Incluir configuración
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/helpers/date_helper.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Obtener conexión a la base de datos
$db = getDB();

// Título de la página
$pageTitle = "Sugerir Escalas Psicológicas";

// Iniciar captura del contenido de la página
startPageContent();

// Obtener todas las escalas del catálogo
$stmt = $db->query("SELECT id, nombre, descripcion, poblacion, tiempo_estimado FROM escalas_catalogo ORDER BY nombre");
$todas_escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar si se ha enviado un paciente existente
$paciente = null;
$paciente_id = isset($_GET['paciente_id']) ? intval($_GET['paciente_id']) : 0;

if ($paciente_id > 0) {
    $stmt = $db->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$paciente_id]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Verificar si se ha enviado el formulario de síntomas
$escalas_sugeridas = [];
$sintomas_seleccionados = [];
$edad_paciente = isset($_POST['edad']) ? intval($_POST['edad']) : ($paciente ? calcularEdad($paciente['fecha_nacimiento']) : 0);
$genero_paciente = isset($_POST['genero']) ? $_POST['genero'] : ($paciente ? $paciente['genero'] : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sugerir'])) {
    // Obtener los síntomas seleccionados
    $sintomas_seleccionados = isset($_POST['sintomas']) ? $_POST['sintomas'] : [];
    
    // Algoritmo de sugerencia basado en síntomas y perfil demográfico
    $escalas_sugeridas = recomendarEscalas($sintomas_seleccionados, $edad_paciente, $genero_paciente, $todas_escalas);
}

/**
 * Algoritmo para recomendar escalas según síntomas y perfil
 */
function recomendarEscalas($sintomas, $edad, $genero, $todas_escalas) {
    $recomendaciones = [];
    
    // Mapeo de síntomas a escalas recomendadas con puntaje de relevancia
    $mapeo_sintomas = [
        'depresion' => [
            'Inventario de Depresión de Beck (BDI-II)' => 10,
            'Cuestionario de Salud General de Goldberg (GHQ-28)' => 5
        ],
        'ansiedad' => [
            'Inventario de Ansiedad Estado-Rasgo (STAI)' => 10,
            'Cuestionario de Salud General de Goldberg (GHQ-28)' => 5
        ],
        'autoestima' => [
            'Escala de Autoestima de Rosenberg' => 10
        ],
        'obsesiones' => [
            'Escala Yale-Brown para Trastorno Obsesivo-Compulsivo (Y-BOCS)' => 10
        ],
        'compulsiones' => [
            'Escala Yale-Brown para Trastorno Obsesivo-Compulsivo (Y-BOCS)' => 10
        ],
        'atencion' => [
            'Test de Atención D2' => 10
        ],
        'hiperactividad' => [
            'Test de Atención D2' => 7
        ],
        'conducta' => [
            'Sistema de Evaluación de Conducta de Niños y Adolescentes (BASC)' => 10
        ],
        'suicidio' => [
            'Inventario de Depresión de Beck (BDI-II)' => 8
        ],
        'trauma' => [
            'Cuestionario de Salud General de Goldberg (GHQ-28)' => 6
        ],
        'psicosis' => [
            'Cuestionario de Salud General de Goldberg (GHQ-28)' => 7
        ],
        'adiccion' => [
            'Cuestionario de Salud General de Goldberg (GHQ-28)' => 4
        ]
    ];
    
    // Calcular puntajes de recomendación para cada escala
    $puntajes = [];
    foreach ($sintomas as $sintoma) {
        if (isset($mapeo_sintomas[$sintoma])) {
            foreach ($mapeo_sintomas[$sintoma] as $escala => $puntaje) {
                if (!isset($puntajes[$escala])) {
                    $puntajes[$escala] = 0;
                }
                $puntajes[$escala] += $puntaje;
            }
        }
    }
    
    // Filtrar escalas según grupo de edad
    $escalas_filtradas = [];
    foreach ($todas_escalas as $escala) {
        $nombre_escala = $escala['nombre'];
        $poblacion = $escala['poblacion'];
        
        // Verificar si la escala es adecuada para la edad
        $es_adecuada_edad = false;
        switch ($poblacion) {
            case 'adultos':
                $es_adecuada_edad = $edad >= 18;
                break;
            case 'adolescentes':
                $es_adecuada_edad = $edad >= 12 && $edad < 18;
                break;
            case 'niños':
                $es_adecuada_edad = $edad < 12;
                break;
            case 'todos':
                $es_adecuada_edad = true;
                break;
        }
        
        if ($es_adecuada_edad && isset($puntajes[$nombre_escala])) {
            $escalas_filtradas[$escala['id']] = [
                'escala' => $escala,
                'puntaje' => $puntajes[$nombre_escala],
                'razones' => getSintomasRelacionados($nombre_escala, $sintomas, $mapeo_sintomas)
            ];
        }
    }
    
    // Ordenar por puntaje de mayor a menor
    uasort($escalas_filtradas, function($a, $b) {
        return $b['puntaje'] <=> $a['puntaje'];
    });
    
    return $escalas_filtradas;
}

/**
 * Obtiene los síntomas relacionados con una escala específica
 */
function getSintomasRelacionados($nombre_escala, $sintomas_seleccionados, $mapeo_sintomas) {
    $razones = [];
    foreach ($sintomas_seleccionados as $sintoma) {
        if (isset($mapeo_sintomas[$sintoma]) && isset($mapeo_sintomas[$sintoma][$nombre_escala])) {
            switch ($sintoma) {
                case 'depresion':
                    $razones[] = "Síntomas depresivos";
                    break;
                case 'ansiedad':
                    $razones[] = "Síntomas de ansiedad";
                    break;
                case 'autoestima':
                    $razones[] = "Problemas de autoestima";
                    break;
                case 'obsesiones':
                    $razones[] = "Pensamientos obsesivos";
                    break;
                case 'compulsiones':
                    $razones[] = "Conductas compulsivas";
                    break;
                case 'atencion':
                    $razones[] = "Dificultades de atención";
                    break;
                case 'hiperactividad':
                    $razones[] = "Hiperactividad";
                    break;
                case 'conducta':
                    $razones[] = "Problemas conductuales";
                    break;
                case 'suicidio':
                    $razones[] = "Ideación suicida";
                    break;
                case 'trauma':
                    $razones[] = "Experiencias traumáticas";
                    break;
                case 'psicosis':
                    $razones[] = "Síntomas psicóticos";
                    break;
                case 'adiccion':
                    $razones[] = "Conductas adictivas";
                    break;
            }
        }
    }
    return $razones;
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <span class="material-symbols-rounded me-2">psychology_alt</span>
        Sugerir Escalas Psicológicas
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php">Escalas Psicológicas</a></li>
        <li class="breadcrumb-item active">Sugerir Escalas</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-8">
            <?php if ($paciente): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <span class="material-symbols-rounded me-1">person</span>
                    Información del Paciente
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="fw-bold mb-1">Paciente:</p>
                            <p><?= htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellidos']) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p class="fw-bold mb-1">Edad:</p>
                            <p><?= calcularEdad($paciente['fecha_nacimiento']) ?> años</p>
                        </div>
                        <div class="col-md-4">
                            <p class="fw-bold mb-1">Género:</p>
                            <p><?= ucfirst(htmlspecialchars($paciente['genero'] ?: 'No especificado')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <span class="material-symbols-rounded me-1">checklist</span>
                    Indicar Síntomas y Características
                </div>
                <div class="card-body">
                    <p class="text-muted">Seleccione los síntomas o características que presenta el paciente para recibir recomendaciones de escalas psicológicas adecuadas.</p>
                    
                    <form method="post" action="">
                        <?php if (!$paciente): ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edad" class="form-label">Edad del paciente</label>
                                <input type="number" class="form-control" id="edad" name="edad" value="<?= $edad_paciente ?>" min="0" max="120">
                            </div>
                            <div class="col-md-6">
                                <label for="genero" class="form-label">Género</label>
                                <select class="form-select" id="genero" name="genero">
                                    <option value="">No especificado</option>
                                    <option value="masculino" <?= $genero_paciente === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                                    <option value="femenino" <?= $genero_paciente === 'femenino' ? 'selected' : '' ?>>Femenino</option>
                                    <option value="otro" <?= $genero_paciente === 'otro' ? 'selected' : '' ?>>Otro</option>
                                </select>
                            </div>
                        </div>
                        <?php else: ?>
                            <input type="hidden" name="edad" value="<?= calcularEdad($paciente['fecha_nacimiento']) ?>">
                            <input type="hidden" name="genero" value="<?= htmlspecialchars($paciente['genero']) ?>">
                            <input type="hidden" name="paciente_id" value="<?= $paciente_id ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Síntomas o Características</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="depresion" id="sintoma-depresion" <?= in_array('depresion', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-depresion">
                                            Estado de ánimo depresivo
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="ansiedad" id="sintoma-ansiedad" <?= in_array('ansiedad', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-ansiedad">
                                            Ansiedad/nerviosismo
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="autoestima" id="sintoma-autoestima" <?= in_array('autoestima', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-autoestima">
                                            Baja autoestima
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="obsesiones" id="sintoma-obsesiones" <?= in_array('obsesiones', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-obsesiones">
                                            Pensamientos obsesivos
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="compulsiones" id="sintoma-compulsiones" <?= in_array('compulsiones', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-compulsiones">
                                            Conductas compulsivas
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="trauma" id="sintoma-trauma" <?= in_array('trauma', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-trauma">
                                            Experiencias traumáticas
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="atencion" id="sintoma-atencion" <?= in_array('atencion', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-atencion">
                                            Problemas de atención
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="hiperactividad" id="sintoma-hiperactividad" <?= in_array('hiperactividad', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-hiperactividad">
                                            Hiperactividad
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="conducta" id="sintoma-conducta" <?= in_array('conducta', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-conducta">
                                            Problemas de conducta
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="suicidio" id="sintoma-suicidio" <?= in_array('suicidio', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-suicidio">
                                            Ideación suicida
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="psicosis" id="sintoma-psicosis" <?= in_array('psicosis', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-psicosis">
                                            Síntomas psicóticos
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="sintomas[]" value="adiccion" id="sintoma-adiccion" <?= in_array('adiccion', $sintomas_seleccionados) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="sintoma-adiccion">
                                            Conductas adictivas
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="sugerir" value="1" class="btn btn-primary">
                                <span class="material-symbols-rounded me-1">recommend</span> 
                                Sugerir Escalas
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary ms-2">
                                <span class="material-symbols-rounded me-1">arrow_back</span> 
                                Volver
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (!empty($escalas_sugeridas)): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <span class="material-symbols-rounded me-1">psychology</span>
                    Escalas Recomendadas
                </div>
                <div class="card-body">
                    <p>Basado en la información proporcionada, estas son las escalas psicológicas recomendadas ordenadas por relevancia:</p>
                    
                    <div class="list-group">
                        <?php foreach ($escalas_sugeridas as $id => $sugerida): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?= htmlspecialchars($sugerida['escala']['nombre']) ?></h5>
                                    <small class="text-primary">Puntuación: <?= $sugerida['puntaje'] ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($sugerida['escala']['descripcion']) ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted">
                                        <strong>Razones:</strong> <?= implode(', ', $sugerida['razones']) ?>
                                    </small>
                                    <div>
                                        <a href="info_escala.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary me-1">
                                            <span class="material-symbols-rounded">info</span>
                                        </a>
                                        <a href="nueva_administracion.php?escala_id=<?= $id ?><?= $paciente_id ? '&paciente_id='.$paciente_id : '' ?>" class="btn btn-sm btn-primary">
                                            <span class="material-symbols-rounded">assignment</span> Aplicar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($escalas_sugeridas) === 0): ?>
                        <div class="alert alert-warning mt-3">
                            <p class="mb-0">No se encontraron escalas que coincidan con los síntomas y el perfil del paciente. Intente seleccionar otros síntomas o consulte el catálogo completo de escalas.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <span class="material-symbols-rounded me-1">help</span>
                    Información
                </div>
                <div class="card-body">
                    <h5>Cómo funciona</h5>
                    <p>Este sistema de sugerencia analiza los síntomas e información del paciente para recomendar las escalas psicológicas más adecuadas para su evaluación.</p>
                    
                    <h6 class="mt-3">Beneficios:</h6>
                    <ul>
                        <li>Recomendaciones basadas en síntomas</li>
                        <li>Filtrado por edad apropiada</li>
                        <li>Ordenadas por relevancia clínica</li>
                        <li>Explicación de las razones de cada recomendación</li>
                    </ul>
                    
                    <div class="alert alert-info mt-3">
                        <p class="mb-0"><strong>Nota:</strong> Las recomendaciones son orientativas y el criterio clínico del profesional siempre prevalece en la selección final de instrumentos de evaluación.</p>
                    </div>
                    
                    <?php if (!$paciente): ?>
                    <div class="mt-3">
                        <a href="nueva_administracion.php" class="btn btn-outline-primary w-100">
                            <span class="material-symbols-rounded me-1">add</span>
                            Nueva Administración
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Finalizar la captura y renderizar la página
endPageContent($pageTitle);
?> 