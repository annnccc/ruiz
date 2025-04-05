<?php
/**
 * Módulo de Videoconsulta - Sala de videollamada para invitados
 * 
 * Esta página implementa la sala de videoconsulta con WebRTC para invitados
 * que acceden con un código de acceso único, sin necesidad de login.
 */

// Incluir archivos necesarios
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Verificar parámetros necesarios
if ((!isset($_GET['id']) || empty($_GET['id'])) && 
    (!isset($_GET['codigo']) || empty($_GET['codigo']))) {
    die('Acceso no válido. Por favor, use el enlace completo enviado por correo electrónico.');
}

// Verificar que tenemos un PIN
if (!isset($_GET['pin']) || empty($_GET['pin'])) {
    die('PIN no proporcionado. Por favor, use el formulario de acceso para ingresar su PIN de 4 dígitos.');
}

$pin = $_GET['pin'];
$videoconsulta_id = isset($_GET['id']) ? $_GET['id'] : null;
$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : null;

// Obtener datos de la videoconsulta
try {
    $db = getDB();
    
    if ($videoconsulta_id) {
        // Si tenemos el ID directamente
        $stmt = $db->prepare("
            SELECT v.*, 
                   CONCAT(u.nombre, ' ', u.apellidos) as medico_nombre,
                   p.id as paciente_id,
                   CONCAT(p.nombre, ' ', p.apellidos) as paciente_nombre,
                   p.email as paciente_email
            FROM videoconsultas v
            JOIN usuarios u ON v.medico_id = u.id
            JOIN pacientes p ON v.paciente_id = p.id
            WHERE v.id = :id
            AND v.pin_acceso = :pin
        ");
        $stmt->bindParam(':id', $videoconsulta_id);
        $stmt->bindParam(':pin', $pin);
    } else {
        // Si tenemos el código de enlace
        $stmt = $db->prepare("
            SELECT v.*, 
                   CONCAT(u.nombre, ' ', u.apellidos) as medico_nombre,
                   p.id as paciente_id,
                   CONCAT(p.nombre, ' ', p.apellidos) as paciente_nombre,
                   p.email as paciente_email
            FROM videoconsultas v
            JOIN usuarios u ON v.medico_id = u.id
            JOIN pacientes p ON v.paciente_id = p.id
            WHERE v.enlace_acceso = :codigo
            AND v.pin_acceso = :pin
        ");
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':pin', $pin);
    }
    
    $stmt->execute();
    
    $videoconsulta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$videoconsulta) {
        die('El PIN no es válido para esta videoconsulta.');
    }
    
    // Si la videoconsulta está cancelada o finalizada, mostrar mensaje
    if ($videoconsulta['estado'] == 'cancelada') {
        die('Esta videoconsulta ha sido cancelada. Contacte con su médico para más información.');
    } else if ($videoconsulta['estado'] == 'finalizada') {
        die('Esta videoconsulta ya ha finalizado. Contacte con su médico si necesita una nueva cita.');
    }
    
    // Actualizar estado a "en_curso" si está programada
    if ($videoconsulta['estado'] == 'programada') {
        $stmt = $db->prepare("
            UPDATE videoconsultas 
            SET estado = 'en_curso' 
            WHERE id = :id AND estado = 'programada'
        ");
        $stmt->bindParam(':id', $videoconsulta['id'], PDO::PARAM_INT);
        $stmt->execute();
    }
    
    // Configurar usuario invitado (paciente)
    $usuario_actual = [
        'id' => $videoconsulta['paciente_id'],
        'nombre' => $videoconsulta['paciente_nombre'],
        'tipo' => 'paciente'
    ];
    
} catch (PDOException $e) {
    die('Error al obtener datos: ' . $e->getMessage());
}

// No incluir el header/footer normal para esta página
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Permissions-Policy" content="camera=*, microphone=*">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Videoconsulta | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            overflow: hidden;
            background-color: #f8f9fa;
        }
        
        .videocall-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #121212;
            color: white;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background-color: rgba(0,0,0,0.5);
            z-index: 10;
        }
        
        .video-area {
            flex: 1;
            display: flex;
            position: relative;
        }
        
        .remote-video {
            width: 100%;
            height: 100%;
            background-color: #2c2c2c;
            object-fit: cover;
        }
        
        .local-video-container {
            position: absolute;
            width: 25%;
            max-width: 300px;
            min-width: 160px;
            bottom: 20px;
            right: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 5;
            border: 2px solid white;
        }
        
        .local-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* Espejo */
        }
        
        .control-bar {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding: 1rem;
            background-color: rgba(0,0,0,0.5);
            z-index: 10;
        }
        
        .control-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-mic-on { background-color: #4CAF50; }
        .btn-mic-off { background-color: #FF5722; }
        .btn-cam-on { background-color: #2196F3; }
        .btn-cam-off { background-color: #FF5722; }
        .btn-chat { background-color: #9C27B0; }
        .btn-end-call { background-color: #F44336; }
        
        .waiting-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 20;
        }
        
        .chat-container {
            position: absolute;
            width: 300px;
            right: 0;
            top: 0;
            bottom: 0;
            background-color: white;
            display: none;
            flex-direction: column;
            z-index: 15;
            box-shadow: -5px 0 15px rgba(0,0,0,0.1);
        }
        
        .chat-header {
            padding: 1rem;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .chat-input {
            display: flex;
            padding: 0.5rem;
            border-top: 1px solid #dee2e6;
        }
        
        .chat-input input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-right: 0.5rem;
        }
        
        .message-bubble {
            max-width: 80%;
            padding: 0.75rem;
            border-radius: 1rem;
            position: relative;
        }
        
        .message-from-me {
            background-color: #e3f2fd;
            align-self: flex-end;
            border-bottom-right-radius: 0;
        }
        
        .message-from-other {
            background-color: #f1f1f1;
            align-self: flex-start;
            border-bottom-left-radius: 0;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
            text-align: right;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .local-video-container {
                width: 40%;
            }
            
            .chat-container {
                width: 100%;
            }
        }
    </style>
    
    <script>
        // Polyfill para navegadores antiguos que no tienen navigator.mediaDevices
        if (navigator.mediaDevices === undefined) {
            navigator.mediaDevices = {};
        }

        // Polyfill para navegadores que no tienen navigator.mediaDevices.getUserMedia
        if (navigator.mediaDevices.getUserMedia === undefined) {
            navigator.mediaDevices.getUserMedia = function(constraints) {
                const getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;

                if (!getUserMedia) {
                    return Promise.reject(new Error('getUserMedia no está implementado en este navegador'));
                }

                return new Promise(function(resolve, reject) {
                    getUserMedia.call(navigator, constraints, resolve, reject);
                });
            }
        }

        // Función para solicitar permisos de cámara y micrófono al cargar la página
        window.onload = function() {
            // Detectar si es un dispositivo iOS
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
            
            // Mostrar opciones específicas para iOS
            if (isIOS) {
                document.getElementById('ios-solution').classList.remove('d-none');
                
                // Configurar botón para abrir en Safari
                document.getElementById('openInSafari').addEventListener('click', function() {
                    // Obtener la URL actual
                    const currentUrl = window.location.href;
                    // Abrir en Safari usando esquema URL
                    window.location.href = 'x-web-search://?' + encodeURIComponent(currentUrl);
                });
            }
            
            // Solicitar permisos inmediatamente
            requestMediaPermissions();
            
            // Función para solicitar permisos
            function requestMediaPermissions() {
                try {
                    // Si navigator.mediaDevices no está disponible, mostrar error
                    if (!navigator.mediaDevices) {
                        throw new Error('La API de MediaDevices no está disponible en este navegador');
                    }
                    
                    // Mostrar mensaje específico para iOS
                    if (isIOS) {
                        document.getElementById('waitingOverlay').innerHTML = `
                            <div class="text-center">
                                <h3>Permisos necesarios en iOS</h3>
                                <p class="text-light mb-4">Para usar la videoconsulta en iPhone o iPad:</p>
                                <ol class="text-start text-light mx-auto" style="max-width: 400px;">
                                    <li class="mb-2">Cuando se le solicite, pulse "Permitir" para cámara y micrófono</li>
                                    <li class="mb-2">Si ve un mensaje de bloqueo, vaya a Ajustes > Safari > Permisos > Acceso a cámara y micrófono</li>
                                    <li class="mb-2">Asegúrese de que Safari tiene permiso para acceder a estos dispositivos</li>
                                </ol>
                                <button class="btn btn-primary mt-3" onclick="retryPermissions()">
                                    Continuar
                                </button>
                            </div>
                        `;
                    }
                    
                    // Configuración simple para máxima compatibilidad
                    const constraints = { 
                        audio: true, 
                        video: true 
                    };
                    
                    // Mostrar diálogo de solicitud de permisos
                    navigator.mediaDevices.getUserMedia(constraints)
                        .then(function(stream) {
                            console.log('Permisos de cámara y micrófono concedidos');
                            // Detener el stream inmediatamente, solo necesitamos solicitar permisos
                            stream.getTracks().forEach(track => track.stop());
                            // Cambiar mensaje si es iOS
                            if (isIOS) {
                                document.getElementById('waitingOverlay').innerHTML = `
                                    <div class="spinner-border text-light mb-4" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <h3>Permisos concedidos</h3>
                                    <p class="text-light">Conectando con la videoconsulta...</p>
                                `;
                            }
                        })
                        .catch(function(err) {
                            console.error('Error al solicitar permisos:', err);
                            // Para debug en iOS, mostrar detalles del error
                            document.getElementById('error-container').classList.remove('d-none');
                            let errorDetails = `
                                <strong>Error: ${err.name}</strong>
                                <p>${err.message}</p>
                                <div class="mt-2">
                                    <small class="text-muted">Error técnico para soporte:</small>
                                    <pre class="bg-light p-2 mt-1" style="font-size: 10px; overflow: auto; max-height: 100px;">${JSON.stringify(err, Object.getOwnPropertyNames(err), 2)}</pre>
                                </div>
                            `;
                            document.getElementById('error-message').innerHTML = errorDetails;

                            // Mostrar mensaje de error específico según el tipo de error
                            let errorMessage = '';
                            let instructions = '';
                            
                            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                                errorMessage = 'Permisos de cámara o micrófono denegados';
                                if (isIOS) {
                                    instructions = `
                                        <p>En iPhone/iPad:</p>
                                        <ol class="text-start">
                                            <li>Ve a Ajustes > Safari</li>
                                            <li>Desplázate hasta "Configuración para sitios web"</li>
                                            <li>Toca "Cámara" y "Micrófono"</li>
                                            <li>Selecciona "Preguntar" o "Permitir" para este sitio</li>
                                            <li>Vuelve a cargar esta página</li>
                                        </ol>
                                        <button class="btn btn-warning mt-3" onclick="forceContinueVideoCall()">
                                            Forzar inicio de videoconsulta
                                        </button>
                                    `;
                                } else {
                                    instructions = 'Haga clic en el icono de cámara/micrófono en la barra de direcciones y conceda los permisos necesarios.';
                                }
                            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                                errorMessage = 'No se encontró cámara o micrófono en su dispositivo';
                                instructions = 'Compruebe que su dispositivo tiene cámara y micrófono, y que no están siendo utilizados por otra aplicación.';
                            } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                                errorMessage = 'No se pudo acceder a la cámara o micrófono';
                                instructions = 'Es posible que otro programa esté usando la cámara o el micrófono. Cierre otras aplicaciones y recargue la página.';
                            } else {
                                errorMessage = 'Error al acceder a la cámara o micrófono: ' + err.name;
                                instructions = `
                                    Intente recargar la página o utilice otro navegador como Chrome o Firefox.
                                    <button class="btn btn-warning mt-3" onclick="forceContinueVideoCall()">
                                        Forzar inicio de videoconsulta sin cámara
                                    </button>
                                `;
                            }
                            
                            // Mostrar error en pantalla
                            document.getElementById('error-container').classList.remove('d-none');
                            document.getElementById('error-message').textContent = errorMessage;
                            document.getElementById('error-instructions').innerHTML = instructions;
                        });
                } catch (error) {
                    console.error('Error general:', error);
                    document.getElementById('error-container').classList.remove('d-none');
                    document.getElementById('error-message').textContent = 'Error de compatibilidad: ' + error.message;
                    document.getElementById('error-instructions').innerHTML = 'Este navegador no es compatible con videollamadas. Por favor, utilice Google Chrome, Microsoft Edge o Safari en su versión más reciente.';
                }
            }
            
            // Función para reintentar los permisos (para iOS)
            window.retryPermissions = function() {
                requestMediaPermissions();
            };
            
            // Función para forzar el inicio de la videoconsulta sin cámara ni micrófono
            window.forceContinueVideoCall = function() {
                document.getElementById('waitingOverlay').innerHTML = `
                    <div class="spinner-border text-light mb-4" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h3>Iniciando videoconsulta en modo reducido</h3>
                    <p class="text-light">Conectando sin acceso a cámara/micrófono...</p>
                `;
                
                setTimeout(function() {
                    document.getElementById('waitingOverlay').style.display = 'none';
                    
                    // Mostrar aviso permanente de que no hay cámara
                    const videoArea = document.querySelector('.video-area');
                    const alertEl = document.createElement('div');
                    alertEl.className = 'alert alert-warning position-absolute top-0 start-0 m-3';
                    alertEl.style.zIndex = '100';
                    alertEl.innerHTML = '<strong>Modo limitado:</strong> No se pudo acceder a su cámara/micrófono';
                    videoArea.appendChild(alertEl);
                    
                    // Inicializar VideoCall en modo sin cámara
                    if (typeof VideoCall !== 'undefined') {
                        const salaId = '<?= $videoconsulta['sala_id'] ?>';
                        const usuarioTipo = 'paciente';
                        VideoCall.init(salaId, usuarioTipo);
                    }
                }, 1500);
            };
        };
    </script>
</head>
<body>
    <div class="videocall-container">
        <div class="header">
            <div class="d-flex align-items-center">
                <a href="javascript:history.back()" class="btn btn-sm btn-outline-light me-3">
                    <span class="material-symbols-rounded">arrow_back</span>
                </a>
                <div>
                    <h5 class="mb-0">Videoconsulta</h5>
                    <small class="text-white-50">
                        Dr./Dra. <?= htmlspecialchars($videoconsulta['medico_nombre']) ?>
                    </small>
                </div>
            </div>
            <div class="text-white-50">
                <span id="duracionLlamada">00:00</span>
            </div>
        </div>
        
        <div class="video-area">
            <video id="remoteVideo" class="remote-video" autoplay playsinline></video>
            
            <div class="local-video-container">
                <video id="selfVideo" class="local-video" autoplay playsinline muted></video>
            </div>
            
            <!-- Overlay de espera -->
            <div id="waitingOverlay" class="overlay">
                <div class="overlay-content">
                    <div class="spinner mb-3"></div>
                    <h3 id="waitingTitle">Esperando al médico</h3>
                    <p id="waitingMessage">La videoconsulta comenzará pronto...</p>
                </div>
            </div>
            
            <!-- Contenedor de errores -->
            <div id="error-container" class="alert alert-danger position-absolute top-0 start-0 m-3 d-none" style="z-index: 100;">
                <h5 id="error-title">Error de Conexión</h5>
                <p id="error-message"></p>
                <p id="error-instructions"></p>
            </div>
            
            <div class="chat-container" id="chatContainer">
                <div class="chat-header">
                    <h6 class="mb-0">Chat de la videoconsulta</h6>
                    <button type="button" class="btn-close" id="closeChat" aria-label="Cerrar chat"></button>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <!-- Los mensajes se cargarán dinámicamente aquí -->
                </div>
                <div class="chat-input">
                    <input type="text" id="chatMessageInput" placeholder="Escriba su mensaje...">
                    <button class="btn btn-primary" id="sendChatMessage">
                        <span class="material-symbols-rounded">send</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="control-bar">
            <div class="control-button btn-mic-on" id="toggleMic" title="Silenciar micrófono">
                <span class="material-symbols-rounded">mic</span>
            </div>
            <div class="control-button btn-cam-on" id="toggleCamera" title="Apagar cámara">
                <span class="material-symbols-rounded">videocam</span>
            </div>
            <div class="control-button btn-chat" id="toggleChat" title="Abrir chat">
                <span class="material-symbols-rounded">chat</span>
            </div>
            <div class="control-button btn-end-call" id="endCall" title="Finalizar videoconsulta">
                <span class="material-symbols-rounded">call_end</span>
            </div>
        </div>
    </div>
    
    <!-- Scripts necesarios -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Definir variables necesarias para videocall.js -->
    <script>
        // Variables necesarias para videocall.js
        // Asegurarse de que sean globales para que videocall.js pueda accederlas
        window.baseUrl = '<?= BASE_URL ?>';
        window.isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
    </script>
    
    <!-- Cargar script modificado -->
    <script src="assets/js/videocall.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar la videollamada cuando se carga la página
            // Asignar valores a las variables ya declaradas en videocall.js
            if (typeof salaId !== 'undefined') salaId = '<?= $videoconsulta['sala_id'] ?>';
            if (typeof isInitiator !== 'undefined') isInitiator = false; // Paciente no es iniciador
            if (typeof isAdmin !== 'undefined') isAdmin = false; // Paciente no es admin
            if (typeof noMedia !== 'undefined') noMedia = false; // Por defecto con medios
            
            // Configurar botones de control
            const toggleMic = document.getElementById('toggleMic');
            const toggleCamera = document.getElementById('toggleCamera');
            const toggleChat = document.getElementById('toggleChat');
            const endCall = document.getElementById('endCall');
            const closeChat = document.getElementById('closeChat');
            const chatContainer = document.getElementById('chatContainer');
            const sendChatMessage = document.getElementById('sendChatMessage');
            const chatMessageInput = document.getElementById('chatMessageInput');
            
            // Toggle micrófono
            if (toggleMic) {
                toggleMic.addEventListener('click', function() {
                    toggleMute();
                    const micBtn = document.getElementById('muteBtn');
                    const isMuted = micBtn.classList.contains('btn-danger');
                    if (isMuted) {
                        toggleMic.classList.remove('btn-mic-on');
                        toggleMic.classList.add('btn-mic-off');
                        toggleMic.querySelector('span').textContent = 'mic_off';
                    } else {
                        toggleMic.classList.remove('btn-mic-off');
                        toggleMic.classList.add('btn-mic-on');
                        toggleMic.querySelector('span').textContent = 'mic';
                    }
                });
            }
            
            // Toggle cámara
            if (toggleCamera) {
                toggleCamera.addEventListener('click', function() {
                    toggleVideo();
                    const videoBtn = document.getElementById('videoBtn');
                    const isOff = videoBtn.classList.contains('btn-danger');
                    if (isOff) {
                        toggleCamera.classList.remove('btn-cam-on');
                        toggleCamera.classList.add('btn-cam-off');
                        toggleCamera.querySelector('span').textContent = 'videocam_off';
                    } else {
                        toggleCamera.classList.remove('btn-cam-off');
                        toggleCamera.classList.add('btn-cam-on');
                        toggleCamera.querySelector('span').textContent = 'videocam';
                    }
                });
            }
            
            // Toggle chat (mantenemos este código por ahora)
            if (toggleChat && chatContainer) {
                toggleChat.addEventListener('click', function() {
                    chatContainer.style.display = chatContainer.style.display === 'flex' ? 'none' : 'flex';
                });
            }
            
            // Cerrar chat
            if (closeChat && chatContainer) {
                closeChat.addEventListener('click', function() {
                    chatContainer.style.display = 'none';
                });
            }
            
            // Enviar mensaje de chat (implementaremos esto más tarde)
            if (sendChatMessage && chatMessageInput) {
                sendChatMessage.addEventListener('click', function() {
                    const message = chatMessageInput.value.trim();
                    if (message) {
                        // Implementaremos el envío de mensajes cuando agreguemos chat
                        console.log("Mensaje a enviar:", message);
                        chatMessageInput.value = '';
                    }
                });
                
                // También enviar con Enter
                chatMessageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const message = this.value.trim();
                        if (message) {
                            // Implementaremos el envío de mensajes cuando agreguemos chat
                            console.log("Mensaje a enviar:", message);
                            this.value = '';
                        }
                    }
                });
            }
            
            // Finalizar llamada
            if (endCall) {
                endCall.addEventListener('click', function() {
                    if (confirm('¿Está seguro que desea finalizar la videoconsulta?')) {
                        hangUp();
                        window.location.href = '<?= BASE_URL ?>/index.php';
                    }
                });
            }
        });
    </script>
</body>
</html> 