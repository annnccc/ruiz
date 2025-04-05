<?php
// Sección que muestra la interpretación según baremos
if (isset($administracion) && $administracion['completada'] == 1):
    // Consultar si hay resultados disponibles
    $stmt = $db->prepare("
        SELECT r.*, pc.interpretacion, pc.descripcion AS interpretacion_desc, pc.nivel_alerta
        FROM escalas_resultados r
        LEFT JOIN escalas_puntos_corte pc ON pc.escala_id = ? 
            AND pc.subescala = r.subescala
            AND r.puntuacion_directa BETWEEN pc.puntuacion_min AND pc.puntuacion_max
        WHERE r.administracion_id = ?
        ORDER BY r.subescala
    ");
    $stmt->execute([$escala_id, $administracion_id]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Consultar baremos disponibles para esta escala
    $stmt = $db->prepare("
        SELECT b.id, b.subescala, b.media, b.desviacion_estandar, b.poblacion, 
               b.edad_min, b.edad_max, b.genero, b.descripcion
        FROM escalas_baremos b
        WHERE b.escala_id = ?
    ");
    $stmt->execute([$escala_id]);
    $baremos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar baremos por subescala
    $baremos_por_subescala = [];
    foreach ($baremos as $baremo) {
        $baremos_por_subescala[$baremo['subescala']][] = $baremo;
    }
    
    if (count($resultados) > 0):
?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="m-0">Interpretación de Resultados</h5>
        </div>
        <div class="card-body">
            <?php if (empty($baremos)): ?>
                <div class="alert alert-warning">
                    <span class="material-symbols-rounded me-1">info</span>
                    No se encontraron baremos normativos para esta escala. La interpretación se basa únicamente en puntos de corte.
                </div>
            <?php endif; ?>
            
            <?php foreach ($resultados as $resultado): ?>
                <div class="mb-4">
                    <h6><?= $resultado['subescala'] == 'total' ? 'Puntuación Total' : htmlspecialchars($resultado['subescala']) ?></h6>
                    
                    <div class="row align-items-center mb-3">
                        <div class="col-md-3">
                            <div class="text-muted small">Puntuación directa</div>
                            <div class="fw-bold"><?= number_format($resultado['puntuacion_directa'], 1) ?></div>
                        </div>
                        
                        <?php
                        // Calcular percentil y otras puntuaciones derivadas si hay baremos
                        $baremo_aplicado = null;
                        $percentil = null;
                        $puntuacion_T = null;
                        
                        if (isset($baremos_por_subescala[$resultado['subescala']])) {
                            // Seleccionar el baremo más adecuado para esta subescala
                            $baremo_aplicado = $baremos_por_subescala[$resultado['subescala']][0];
                            
                            // Buscar equivalencias para la puntuación directa
                            $stmt = $db->prepare("
                                SELECT * FROM escalas_equivalencias 
                                WHERE baremo_id = ? 
                                ORDER BY ABS(puntuacion_directa - ?) ASC 
                                LIMIT 1
                            ");
                            $stmt->execute([$baremo_aplicado['id'], $resultado['puntuacion_directa']]);
                            $equivalencia = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($equivalencia) {
                                $percentil = $equivalencia['percentil'];
                                $puntuacion_T = $equivalencia['puntuacion_T'];
                                
                                // Actualizar en la base de datos el percentil y baremo utilizados
                                $stmt = $db->prepare("
                                    UPDATE escalas_resultados 
                                    SET percentil = ?, baremo_id = ?, puntuacion_tipica = ? 
                                    WHERE id = ?
                                ");
                                $stmt->execute([$percentil, $baremo_aplicado['id'], $puntuacion_T, $resultado['id']]);
                            } else {
                                // Calcular percentil aproximado usando la distribución normal
                                $z = ($resultado['puntuacion_directa'] - $baremo_aplicado['media']) / $baremo_aplicado['desviacion_estandar'];
                                $percentil = round(100 * (0.5 + 0.5 * $this->erf($z / sqrt(2))));
                                $puntuacion_T = round(50 + (10 * $z));
                            }
                        }
                        ?>
                        
                        <?php if ($percentil !== null): ?>
                        <div class="col-md-3">
                            <div class="text-muted small">Percentil</div>
                            <div class="fw-bold"><?= $percentil ?>%</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Puntuación T</div>
                            <div class="fw-bold"><?= $puntuacion_T ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-3">
                            <div class="text-muted small">Interpretación</div>
                            <div class="fw-bold <?= $resultado['nivel_alerta'] ? 'text-danger' : '' ?>">
                                <?= htmlspecialchars($resultado['interpretacion'] ?? 'No disponible') ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($resultado['interpretacion_desc'])): ?>
                    <div class="alert alert-light">
                        <?= htmlspecialchars($resultado['interpretacion_desc']) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($baremo_aplicado): ?>
                    <div class="mt-3 mb-4">
                        <h6 class="text-muted">Comparación con datos normativos</h6>
                        <div class="progress" style="height: 25px;">
                            <?php
                            // Calcular posición en la barra de progreso (0-100)
                            $posicion = min(max($percentil, 1), 100);
                            
                            // Determinar color según posición (rojo para extremos, verde para centro)
                            $color_class = 'bg-success';
                            if ($posicion <= 10 || $posicion >= 90) {
                                $color_class = 'bg-danger';
                            } elseif ($posicion <= 25 || $posicion >= 75) {
                                $color_class = 'bg-warning';
                            }
                            ?>
                            <div class="progress-bar <?= $color_class ?>" role="progressbar" 
                                 style="width: <?= $posicion ?>%;" 
                                 aria-valuenow="<?= $posicion ?>" aria-valuemin="0" aria-valuemax="100">
                                <?= $posicion ?>%
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-1 small text-muted">
                            <span>Bajo</span>
                            <span>Promedio</span>
                            <span>Alto</span>
                        </div>
                        <div class="small text-muted mt-2">
                            <strong>Referencia:</strong> <?= htmlspecialchars($baremo_aplicado['descripcion']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$loop->last): ?>
                <hr class="my-4">
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if (count($resultados) > 1): ?>
            <div class="mt-4">
                <h6>Visualización gráfica</h6>
                <canvas id="resultadosChart" width="400" height="200"></canvas>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('resultadosChart').getContext('2d');
                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: [
                                <?php foreach ($resultados as $resultado): ?>
                                "<?= $resultado['subescala'] == 'total' ? 'Total' : $resultado['subescala'] ?>",
                                <?php endforeach; ?>
                            ],
                            datasets: [{
                                label: 'Puntuación T',
                                data: [
                                    <?php foreach ($resultados as $resultado): ?>
                                    <?= isset($resultado['puntuacion_tipica']) ? $resultado['puntuacion_tipica'] : 'null' ?>,
                                    <?php endforeach; ?>
                                ],
                                backgroundColor: [
                                    <?php foreach ($resultados as $resultado): ?>
                                    '<?= $resultado['nivel_alerta'] ? 'rgba(220, 53, 69, 0.7)' : 'rgba(40, 167, 69, 0.7)' ?>',
                                    <?php endforeach; ?>
                                ],
                                borderColor: [
                                    <?php foreach ($resultados as $resultado): ?>
                                    '<?= $resultado['nivel_alerta'] ? 'rgb(220, 53, 69)' : 'rgb(40, 167, 69)' ?>',
                                    <?php endforeach; ?>
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    min: 0,
                                    max: 100,
                                    title: {
                                        display: true,
                                        text: 'Puntuación T'
                                    }
                                }
                            },
                            plugins: {
                                annotation: {
                                    annotations: {
                                        line1: {
                                            type: 'line',
                                            yMin: 50,
                                            yMax: 50,
                                            borderColor: 'rgba(0, 0, 0, 0.5)',
                                            borderWidth: 2,
                                            borderDash: [6, 6],
                                            label: {
                                                display: true,
                                                content: 'Media',
                                                position: 'end'
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
                </script>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php
    endif;
endif;
?>

<?php
// Función erf para el cálculo de percentiles usando la distribución normal
function erf($x) {
    // Aproximación de la función de error para calcular percentiles
    $sign = ($x >= 0) ? 1 : -1;
    $x = abs($x);
    
    // Coeficientes para la aproximación
    $a1 =  0.254829592;
    $a2 = -0.284496736;
    $a3 =  1.421413741;
    $a4 = -1.453152027;
    $a5 =  1.061405429;
    $p  =  0.3275911;
    
    // Formula de Abramowitz and Stegun
    $t = 1.0 / (1.0 + $p * $x);
    $y = 1.0 - (((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t) + $a1) * $t * exp(-$x * $x);
    
    return $sign * $y;
} 