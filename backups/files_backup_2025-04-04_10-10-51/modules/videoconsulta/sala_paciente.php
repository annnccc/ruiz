    <!-- Scripts necesarios -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Polyfill para WebRTC en navegadores antiguos -->
    <script>
        // Polyfill para navegadores antiguos que no tienen navigator.mediaDevices
        if (navigator.mediaDevices === undefined) {
            navigator.mediaDevices = {};
            console.log("Añadiendo polyfill para mediaDevices");
        }

        // Polyfill para navegadores que no tienen navigator.mediaDevices.getUserMedia
        if (navigator.mediaDevices.getUserMedia === undefined) {
            navigator.mediaDevices.getUserMedia = function(constraints) {
                console.log("Usando polyfill para getUserMedia");
                const getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;

                if (!getUserMedia) {
                    console.error("getUserMedia no está implementado en este navegador");
                    return Promise.reject(new Error('getUserMedia no está implementado en este navegador'));
                }

                return new Promise(function(resolve, reject) {
                    getUserMedia.call(navigator, constraints, resolve, reject);
                });
            }
        }
    </script>
    
    <!-- Definir baseUrl para uso en videocall.js -->
    <script>
        const baseUrl = '<?= BASE_URL ?>';
        const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
    </script>
    
    <!-- Usar el script corregido de videocall.js en lugar de videoconsulta.js -->
    <script src="assets/js/videocall.js"></script> 