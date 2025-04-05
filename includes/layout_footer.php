<?php
/**
 * Pie del layout principal
 * Contiene la estructura HTML de cierre y carga de scripts
 */

// Prevenir acceso directo al archivo
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Acceso prohibido');
}
?>
        
            <!-- Footer -->
            <?php include ROOT_PATH . '/includes/footer.php'; ?>
        </div>
    </div>

    <!-- Contenedor de notificaciones toast -->
    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    
    <!-- Custom JS -->
    <!-- No incluir common.js aquí ya que se carga en layout.php -->
    
    <!-- Inicialización de Flatpickr para selectores de fecha -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar Flatpickr para inputs con clase date-picker
            const datePickers = document.querySelectorAll('.date-picker');
            if (datePickers.length > 0) {
                flatpickr(datePickers, {
                    locale: 'es',
                    dateFormat: 'd/m/Y',
                    allowInput: true
                });
            }
            
            // Inicializar Flatpickr para inputs con clase time-picker
            const timePickers = document.querySelectorAll('.time-picker');
            if (timePickers.length > 0) {
                flatpickr(timePickers, {
                    locale: 'es',
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 15,
                    allowInput: true
                });
            }
        });
    </script>

    <!-- Scripts específicos de la página -->
    <?php if(isset($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>
    
    <!-- Script para accesibilidad de iconos -->
    <script src="<?= BASE_URL ?>/assets/js/icon-accessibility.js"></script>
</body>
</html> 