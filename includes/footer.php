    </div>
</div>

<footer class="footer bg-light border-top">
    <div class="container-fluid px-4 py-3">
        <div class="d-flex justify-content-between small">
            <div>
                <span class="text-muted">&copy; <?= date('Y') ?> Clínica Ruiz - Sistema de Gestión</span>
            </div>
            <div>
                <span class="text-muted">Versión 1.0</span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="<?= BASE_URL ?>/assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Flatpickr -->
<script src="<?= BASE_URL ?>/assets/lib/flatpickr/dist/flatpickr.min.js"></script>
<script src="<?= BASE_URL ?>/assets/lib/flatpickr/dist/l10n/es.js"></script>
<!-- FullCalendar -->
<script src='<?= BASE_URL ?>/assets/lib/fullcalendar/fullcalendar.min.js'></script>
<!-- Sweet Alert 2 -->
<script src="<?= BASE_URL ?>/assets/lib/sweetalert2/sweetalert2.min.js"></script>
<!-- Custom JS -->
<!-- No incluir common.js aquí ya que se carga en layout.php -->

<script>
    // Activar los tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Configuración de Flatpickr en español
    flatpickr.localize(flatpickr.l10ns.es);
    
    // Inicializar datepickers
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelectorAll('.datepicker').length > 0) {
            flatpickr('.datepicker', {
                dateFormat: "d/m/Y",
                allowInput: true
            });
        }
    });
</script>
</body>
</html> 