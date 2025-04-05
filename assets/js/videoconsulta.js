/**
 * Sistema de Videoconsulta con WebRTC
 * 
 * Implementa comunicación peer-to-peer directa entre navegadores
 * utilizando WebRTC para video y audio sin servidores intermedios.
 */

// Objeto global para la videollamada
const VideoCall = {
    // Configuración
    config: {
        // Servidores ICE públicos para atravesar NAT/Firewall
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
            { urls: 'stun:stun2.l.google.com:19302' },
            { urls: 'stun:stun3.l.google.com:19302' },
            { urls: 'stun:stun4.l.google.com:19302' }
        ]
    },
    
    // Variables internas
    peerConnection: null,
    localStream: null,
    remoteStream: null,
    salaId: null,
    usuarioTipo: null,
    signaling: null,
    isAudioMuted: false,
    isVideoOff: false,
    
    /**
     * Inicializa la videollamada
     * @param {string} salaId ID único de la sala
     * @param {string} usuarioTipo Tipo de usuario (paciente o medico)
     */
    init: function(salaId, usuarioTipo) {
        this.salaId = salaId;
        this.usuarioTipo = usuarioTipo;
        
        // Iniciar la conexión WebRTC
        this.startConnection();
        
        // Establecer un timeout máximo para ocultar el overlay de espera (20 segundos)
        setTimeout(() => {
            const waitingOverlay = document.getElementById('waitingOverlay');
            if (waitingOverlay && waitingOverlay.style.display !== 'none') {
                console.log('Ocultando overlay por timeout máximo (20s)');
                waitingOverlay.style.display = 'none';
                
                // Mostrar mensaje informativo sobre posibles problemas
                const videoArea = document.querySelector('.video-area');
                if (videoArea) {
                    const alertEl = document.createElement('div');
                    alertEl.className = 'alert alert-info position-absolute top-0 start-0 m-3';
                    alertEl.style.zIndex = '100';
                    alertEl.innerHTML = '<strong>Estado de conexión:</strong> La videollamada podría estar funcionando en modo limitado. Si no ve o escucha al otro participante, intente recargar la página.';
                    videoArea.appendChild(alertEl);
                }
            }
        }, 20000);
        
        // Manejar el cierre de la ventana o navegación
        window.addEventListener('beforeunload', () => {
            this.endCall();
        });
    },
    
    /**
     * Comprueba si el navegador soporta WebRTC
     */
    checkBrowserSupport: function() {
        // Verificar si MediaDevices API y getUserMedia están disponibles
        const hasMediaDevices = !!(navigator.mediaDevices && 
                                  navigator.mediaDevices.getUserMedia);
        
        // Verificar RTCPeerConnection
        const hasRTCPeerConnection = !!(window.RTCPeerConnection || 
                                      window.webkitRTCPeerConnection || 
                                      window.mozRTCPeerConnection);
        
        // Verificar Firefox específicamente
        const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        
        // Registrar información para depuración
        console.log("Comprobando compatibilidad del navegador:");
        console.log("- MediaDevices API:", hasMediaDevices);
        console.log("- RTCPeerConnection:", hasRTCPeerConnection);
        console.log("- Navegador:", navigator.userAgent);
        
        // Si es Firefox en Mac, verificar versión
        if (isFirefox && isMac) {
            // Firefox en Mac es compatible, pero puede necesitar ajustes
            console.log("Detectado Firefox en Mac - Ajustando configuración...");
            return true; // Permitir Firefox en Mac, manejaremos errores específicos más adelante
        }
        
        return hasMediaDevices && hasRTCPeerConnection;
    },
    
    /**
     * Inicia la conexión WebRTC
     */
    startConnection: async function() {
        try {
            // Verificar soporte del navegador para WebRTC
            if (!this.checkBrowserSupport()) {
                throw new Error('Tu navegador no soporta videollamadas. Por favor, actualiza a Chrome, Firefox, Safari o Edge en su versión más reciente.');
            }
            
            console.log('Solicitando permisos de cámara y micrófono...');
            
            // Mostrar mensaje inicial mientras esperamos permisos
            const waitingOverlay = document.getElementById('waitingOverlay');
            if (waitingOverlay) {
                const heading = waitingOverlay.querySelector('h3');
                const message = waitingOverlay.querySelector('p');
                
                if (heading && message) {
                    heading.textContent = 'Solicitando acceso a cámara y micrófono';
                    message.textContent = 'Por favor, acepte los permisos cuando se lo solicite el navegador';
                }
            }
            
            // Primero comprobar si hay permisos ya concedidos
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                const hasMediaPermissions = devices.some(device => 
                    (device.kind === 'videoinput' || device.kind === 'audioinput') && 
                    device.label && device.label.length > 0
                );
                
                if (!hasMediaPermissions) {
                    console.log('No hay permisos previos, solicitando...');
                }
            } catch (err) {
                console.warn('No se pudo comprobar permisos previos:', err);
            }
            
            try {
                // Detectar iOS
                const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                
                // Detectar Firefox en Mac
                const isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
                const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
                const isFirefoxMac = isFirefox && isMac;
                
                // Configuraciones específicas para iOS
                let constraints = {
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    },
                    video: {
                        width: { ideal: 1280, min: 320 },
                        height: { ideal: 720, min: 240 },
                        facingMode: 'user'
                    }
                };
                
                // Ajustar configuración para Firefox en Mac
                if (isFirefoxMac) {
                    console.log("Usando configuración específica para Firefox en Mac");
                    // Firefox en Mac necesita configuraciones más básicas
                    constraints = {
                        audio: true,
                        video: true
                    };
                }
                
                // Verificar primero que la API está disponible y en buen estado
                if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                    throw new Error('La API de MediaDevices no está disponible en este navegador');
                }
                
                // En iOS, intentar primero solo audio si falla el video
                try {
                    // Solicitar acceso a la cámara y micrófono
                    const stream = await navigator.mediaDevices.getUserMedia(constraints);
                    
                    // Guardar y mostrar stream local
                    this.localStream = stream;
                    const localVideo = document.getElementById('localVideo');
                    if (localVideo) {
                        console.log('Asignando stream local principal al video pequeño');
                        localVideo.srcObject = stream;
                        
                        // Asegurar que localVideo está correctamente configurado
                        localVideo.style.width = '100%';
                        localVideo.style.height = '100%';
                        localVideo.style.objectFit = 'cover';
                        localVideo.style.transform = 'scaleX(-1)'; // Efecto espejo
                        localVideo.muted = true; // Silenciar para evitar feedback

                        // Verificar que el remoteVideo esté vacío (para evitar confusiones)
                        const remoteVideo = document.getElementById('remoteVideo');
                        if (remoteVideo && remoteVideo.srcObject === stream) {
                            console.warn('Corrigiendo asignación incorrecta: remoteVideo tenía el stream local');
                            remoteVideo.srcObject = null;
                        }
                        
                        // Actualizar overlay cuando el video local esté listo
                        localVideo.onloadedmetadata = () => {
                            console.log('Video local cargado, esperando conexión remota...');
                            if (waitingOverlay) {
                                const heading = waitingOverlay.querySelector('h3');
                                const message = waitingOverlay.querySelector('p');
                                
                                if (heading && message) {
                                    heading.textContent = 'Esperando a que se conecte el otro participante';
                                    message.textContent = 'Por favor, espere un momento...';
                                }
                            }
                        };
                    } else {
                        console.error('Elemento localVideo no encontrado en el DOM');
                    }
                } catch (mediaError) {
                    // Verificar si es el error "The object can not be found here"
                    console.warn("Error obteniendo video y audio:", mediaError);
                    
                    // Para errores específicos de "objeto no encontrado", probar otra configuración
                    if (mediaError.message && (
                        mediaError.message.includes("The object can not be found here") || 
                        mediaError.message.includes("Could not start") ||
                        mediaError.message.includes("No se puede encontrar el objeto")
                    )) {
                        console.log("Detectado error de objeto no encontrado, intentando configuración alternativa");
                        
                        // Probar con una configuración más básica
                        try {
                            const basicStream = await navigator.mediaDevices.getUserMedia({
                                audio: true,
                                video: true
                            });
                            
                            // Si tenemos éxito, usar este stream
                            this.localStream = basicStream;
                            const localVideo = document.getElementById('localVideo');
                            if (localVideo) {
                                localVideo.srcObject = basicStream;
                            }
                            console.log("Configuración básica funcionó correctamente");
                        } catch (basicMediaError) {
                            // Intentar solo con audio como último recurso
                            console.warn("No se pudo obtener stream básico, intentando solo audio:", basicMediaError);
                            
                            try {
                                const audioOnlyStream = await navigator.mediaDevices.getUserMedia({
                                    audio: true,
                                    video: false
                                });
                                
                                // Usar stream de solo audio
                                this.localStream = audioOnlyStream;
                                console.log("Usando solo audio en modo fallback");
                                
                                // Mostrar placeholder en el video local (pequeño)
                                const localVideo = document.getElementById('localVideo');
                                if (localVideo) {
                                    localVideo.srcObject = null;
                                    localVideo.style.backgroundColor = "#333";
                                    localVideo.style.display = "flex";
                                    localVideo.style.justifyContent = "center";
                                    localVideo.style.alignItems = "center";
                                    
                                    // Verificar que no estamos mostrando nada en el remote por error
                                    const remoteVideo = document.getElementById('remoteVideo');
                                    if (remoteVideo && remoteVideo.srcObject === this.localStream) {
                                        console.warn('Corrigiendo asignación incorrecta en modo audio: remoteVideo tenía el stream local');
                                        remoteVideo.srcObject = null;
                                    }
                                    
                                    // Texto de "Sin cámara" en el video pequeño
                                    const noVideoEl = document.createElement('div');
                                    noVideoEl.textContent = "Sin cámara";
                                    noVideoEl.style.color = "white";
                                    noVideoEl.style.padding = "20px";
                                    localVideo.parentNode.appendChild(noVideoEl);
                                }
                                
                                // Mostrar mensaje claro sobre el modo audio
                                const videoArea = document.querySelector('.video-area');
                                if (videoArea) {
                                    const alertEl = document.createElement('div');
                                    alertEl.className = 'alert alert-info position-absolute top-0 start-0 m-3';
                                    alertEl.style.zIndex = '100';
                                    alertEl.innerHTML = '<strong>Modo audio:</strong> Videollamada funcionando solo con audio. Su cámara no está disponible.';
                                    videoArea.appendChild(alertEl);
                                }
                                
                                // Ocultar overlay de espera después de un breve retraso
                                setTimeout(() => {
                                    const waitingOverlay = document.getElementById('waitingOverlay');
                                    if (waitingOverlay) {
                                        console.log('Ocultando overlay en modo solo audio');
                                        waitingOverlay.style.display = 'none';
                                    }
                                }, 3000);
                            } catch (audioError) {
                                // Si todo falla, continuar sin streams
                                console.error("Error obteniendo audio:", audioError);
                                throw audioError; // Propagar para ser manejado por el código global
                            }
                        }
                    } else if (isIOS) {
                        // En caso de error en iOS, intentar solo audio
                        try {
                            const audioOnlyStream = await navigator.mediaDevices.getUserMedia({
                                audio: true,
                                video: false
                            });
                            
                            // Usar stream de solo audio
                            this.localStream = audioOnlyStream;
                            console.log("Usando solo audio en modo fallback");
                            
                            // Mostrar placeholder en el video local
                            const localVideo = document.getElementById('localVideo');
                            if (localVideo) {
                                localVideo.srcObject = null;
                                localVideo.style.backgroundColor = "#333";
                                localVideo.style.display = "flex";
                                localVideo.style.justifyContent = "center";
                                localVideo.style.alignItems = "center";
                                
                                // Texto de "Sin cámara"
                                const noVideoEl = document.createElement('div');
                                noVideoEl.textContent = "Sin cámara";
                                noVideoEl.style.color = "white";
                                noVideoEl.style.padding = "20px";
                                localVideo.parentNode.appendChild(noVideoEl);
                            }
                        } catch (audioError) {
                            // Si tampoco funciona el audio, continuar sin streams
                            console.error("Error obteniendo audio:", audioError);
                            
                            // Crear un stream vacío para no romper la conexión
                            this.localStream = new MediaStream();
                            
                            // Mostrar advertencia visible
                            const videoArea = document.querySelector('.video-area');
                            if (videoArea) {
                                const alertEl = document.createElement('div');
                                alertEl.className = 'alert alert-danger position-absolute top-0 start-0 m-3';
                                alertEl.style.zIndex = '100';
                                alertEl.innerHTML = '<strong>Sin acceso a medios:</strong> Solo podrás ver y escuchar, pero no transmitir';
                                videoArea.appendChild(alertEl);
                            }
                        }
                    } else {
                        // Si no es iOS, propagar el error
                        throw mediaError;
                    }
                }
            } catch (finalError) {
                console.error("Error fatal al inicializar los medios:", finalError);
                // Crear un stream vacío para continuar sin medios
                this.localStream = new MediaStream();
                
                // Mostrar advertencia visible
                const videoArea = document.querySelector('.video-area');
                if (videoArea) {
                    const alertEl = document.createElement('div');
                    alertEl.className = 'alert alert-danger position-absolute top-0 start-0 m-3';
                    alertEl.style.zIndex = '100';
                    alertEl.innerHTML = '<strong>Sin acceso a medios:</strong> No se detectó cámara ni micrófono';
                    videoArea.appendChild(alertEl);
                }
            }
            
            // Inicializar la conexión peer
            this.initPeerConnection();
            
            // Inicializar el canal de señalización
            this.initSignaling();
            
            // Ocultar overlay de espera después de iniciar todo
            if (waitingOverlay) {
                setTimeout(() => {
                    waitingOverlay.style.display = 'none';
                }, 1000);
            }
            
        } catch (error) {
            console.error('Error al solicitar permisos:', error);
            
            // Mensaje detallado según el tipo de error
            let mensajeError = 'No se pudo acceder a la cámara y/o micrófono.';
            let instrucciones = '';
            
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                mensajeError = 'Permiso denegado para acceder a la cámara y/o micrófono.';
                instrucciones = `
                    <p>Para permitir el acceso:</p>
                    <ol>
                        <li>Haga clic en el icono de cámara/micrófono en la barra de direcciones</li>
                        <li>Seleccione "Permitir" para ambos permisos</li>
                        <li>Recargue la página usando el botón de abajo</li>
                    </ol>
                `;
            } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                mensajeError = 'No se encontró cámara y/o micrófono en su dispositivo.';
                instrucciones = `
                    <div class="alert alert-warning">
                        <p><strong>No se detectaron dispositivos de cámara o micrófono en su ordenador.</strong></p>
                        <p>Esto puede ocurrir si:</p>
                        <ul>
                            <li>No tiene webcam o micrófono conectados</li>
                            <li>Sus dispositivos están desactivados a nivel de hardware (compruebe si hay un botón físico de apagado)</li>
                            <li>Sus controladores no están instalados correctamente</li>
                        </ul>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-primary" onclick="VideoCall.continueWithoutMedia()">
                            <i class="material-icons align-middle me-1">visibility</i> Continuar sin cámara/micrófono
                        </button>
                        <button class="btn btn-outline-secondary mt-2" onclick="location.reload()">
                            <i class="material-icons align-middle me-1">refresh</i> Reintentar
                        </button>
                    </div>
                `;
            } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                mensajeError = 'Su cámara o micrófono está siendo utilizado por otra aplicación.';
                instrucciones = 'Cierre otras aplicaciones que puedan estar usando la cámara o micrófono, y recargue la página.';
            } else if (error.message && error.message.includes('no soporta')) {
                mensajeError = error.message;
                instrucciones = 'Pruebe con Google Chrome, Mozilla Firefox, Microsoft Edge o Safari en su versión más reciente.';
            }
            
            // Actualizar overlay con información del error
            const waitingOverlay = document.getElementById('waitingOverlay');
            if (waitingOverlay) {
                const heading = waitingOverlay.querySelector('h3');
                const errorContainer = waitingOverlay.querySelector('#error-container');
                
                if (heading) {
                    heading.textContent = 'Error de conexión';
                }
                
                if (errorContainer) {
                    // Usar el contenedor de error dedicado
                    const errorMessage = errorContainer.querySelector('#error-message');
                    const errorInstructions = errorContainer.querySelector('#error-instructions');
                    
                    // Verificar si es el error específico "The object can not be found here"
                    if (error.message && (
                        error.message.includes("The object can not be found here") || 
                        error.message.includes("No se puede encontrar el objeto")
                    )) {
                        errorMessage.innerHTML = '<strong>No se encontraron los dispositivos de cámara o micrófono</strong>';
                        errorInstructions.innerHTML = `
                            <p>Este error puede ocurrir por las siguientes razones:</p>
                            <ol>
                                <li>No tiene cámara o micrófono conectados al ordenador</li>
                                <li>Otro programa está usando exclusivamente estos dispositivos</li>
                                <li>Los controladores de su cámara/micrófono necesitan actualizarse</li>
                            </ol>
                            <p>Sugerencias:</p>
                            <ul>
                                <li>Cierre otros programas que puedan estar usando la cámara (Zoom, Teams, etc.)</li>
                                <li>Verifique que su cámara funciona en otras aplicaciones</li>
                                <li>Reinicie su navegador</li>
                            </ul>
                            <button class="btn btn-warning mt-3" onclick="VideoCall.continueWithoutMedia()">
                                Continuar sin cámara/micrófono
                            </button>
                        `;
                    } else if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                        errorMessage.innerHTML = '<strong>Permiso denegado para acceder a la cámara y/o micrófono</strong>';
                        errorInstructions.innerHTML = `
                            <p>Para permitir el acceso:</p>
                            <ol>
                                <li>Haga clic en el icono de cámara/micrófono en la barra de direcciones</li>
                                <li>Seleccione "Permitir" para ambos permisos</li>
                                <li>Recargue la página usando el botón de abajo</li>
                            </ol>
                            <button class="btn btn-warning mt-3" onclick="VideoCall.continueWithoutMedia()">
                                Continuar sin cámara/micrófono
                            </button>
                        `;
                    } else {
                        if (errorMessage) errorMessage.innerHTML = `<strong>${mensajeError}</strong>`;
                        if (errorInstructions) {
                            errorInstructions.innerHTML = instrucciones;
                            // Añadir botón para continuar sin media para cualquier error
                            if (!errorInstructions.innerHTML.includes('Continuar sin')) {
                                errorInstructions.innerHTML += `
                                    <button class="btn btn-warning mt-3" onclick="VideoCall.continueWithoutMedia()">
                                        Continuar sin cámara/micrófono
                                    </button>
                                `;
                            }
                        }
                    }
                    
                    // Manejo especial para Firefox en Mac
                    if (isFirefox && isMac && errorContainer) {
                        errorMessage.innerHTML = '<strong>Firefox en Mac requiere configuración adicional</strong>';
                        errorInstructions.innerHTML = `
                            <div class="alert alert-info">
                                <p>Firefox en Mac puede requerir permisos adicionales:</p>
                                <ol>
                                    <li>Ve a Preferencias del Sistema > Seguridad y Privacidad > Privacidad > Cámara</li>
                                    <li>Asegúrate de que Firefox tiene permiso para acceder a la cámara</li>
                                    <li>Haz lo mismo para Micrófono</li>
                                    <li>Reinicia Firefox completamente</li>
                                </ol>
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <button class="btn btn-primary" onclick="VideoCall.continueWithoutMedia()">
                                    <i class="material-symbols-rounded align-middle me-1">visibility</i> Continuar de todos modos
                                </button>
                                <button class="btn btn-outline-secondary mt-2" onclick="location.reload()">
                                    <i class="material-symbols-rounded align-middle me-1">refresh</i> Intentar de nuevo
                                </button>
                            </div>
                        `;
                        errorContainer.classList.remove('d-none');
                    }
                    
                    errorContainer.classList.remove('d-none');
                } else {
                    // Fallback si no encontramos el contenedor de error
                    const message = waitingOverlay.querySelector('p');
                    if (message) {
                        message.innerHTML = `<strong>${mensajeError}</strong><br><br>${instrucciones}`;
                    }
                }
            } else {
                // Si no hay overlay, mostrar alerta
                alert(mensajeError + '\n\n' + instrucciones.replace(/<[^>]*>/g, ''));
                console.log('Elemento waitingOverlay no encontrado en el DOM');
            }
        }
    },
    
    /**
     * Continúa la videollamada sin acceso a cámara/micrófono
     */
    continueWithoutMedia: function() {
        console.log("Continuando sin cámara ni micrófono");
        
        // Crear un stream vacío para mantener la compatibilidad
        this.localStream = new MediaStream();
        
        // Mostrar advertencia permanente
        const videoArea = document.querySelector('.video-area');
        if (videoArea) {
            const alertEl = document.createElement('div');
            alertEl.className = 'alert alert-warning position-absolute top-0 start-0 m-3';
            alertEl.style.zIndex = '100';
            alertEl.innerHTML = '<strong>Modo limitado:</strong> No se pudo acceder a su cámara/micrófono. Podrá ver y escuchar, pero no transmitir.';
            videoArea.appendChild(alertEl);
        }
        
        // Mostrar mensaje en la vista local
        const localVideo = document.getElementById('localVideo');
        if (localVideo) {
            localVideo.style.backgroundColor = "#333";
            
            // Añadir texto explicativo
            const msgEl = document.createElement('div');
            msgEl.style.position = 'absolute';
            msgEl.style.top = '50%';
            msgEl.style.left = '50%';
            msgEl.style.transform = 'translate(-50%, -50%)';
            msgEl.style.color = 'white';
            msgEl.style.textAlign = 'center';
            msgEl.style.padding = '10px';
            msgEl.textContent = 'Cámara no disponible';
            localVideo.parentNode.appendChild(msgEl);
        }
        
        // Ocultar overlay y continuar con la conexión
        const waitingOverlay = document.getElementById('waitingOverlay');
        if (waitingOverlay) {
            waitingOverlay.style.display = 'none';
        }
        
        // Inicializar la conexión peer y señalización
        this.initPeerConnection();
        this.initSignaling();
    },
    
    /**
     * Maneja cuando se reciben pistas remotas
     */
    initPeerConnection: function() {
        // Crear la conexión peer
        this.peerConnection = new RTCPeerConnection(this.config);
        
        console.log('⚠️ Inicializando conexión peer con configuración:', JSON.stringify(this.config));
        
        // Asegurar que el video local se muestra siempre en la miniatura
        const localVideo = document.getElementById('localVideo');
        if (localVideo && this.localStream) {
            console.log('🎥 Asignando stream local al video pequeño (miniatura)');
            localVideo.srcObject = this.localStream;
            localVideo.play().catch(e => console.error('Error reproduciendo video local:', e));
        }
        
        // Agregar el stream local a la conexión peer si existe
        if (this.localStream && this.localStream.getTracks().length > 0) {
            console.log(`📤 Añadiendo ${this.localStream.getTracks().length} pistas locales a la conexión peer`);
            this.localStream.getTracks().forEach(track => {
                console.log(`📤 Añadiendo pista: ${track.kind} - enabled: ${track.enabled}`);
                const sender = this.peerConnection.addTrack(track, this.localStream);
                console.log(`📤 Sender creado:`, sender);
            });
        } else {
            console.warn("⚠️ No hay tracks en el stream local, funcionando en modo de solo recepción");
        }
        
        // Manejar eventos de negociación necesaria
        this.peerConnection.onnegotiationneeded = async () => {
            console.log('🔄 Negociación necesaria detectada');
            // Solo el médico inicia las ofertas para evitar conflictos
            if (this.usuarioTipo === 'medico') {
                try {
                    console.log('🔄 Iniciando negociación como médico');
                    await this.createOffer();
                } catch (error) {
                    console.error('🔄 Error en negociación:', error);
                }
            }
        };
        
        // Manejar eventos ICE
        this.peerConnection.onicecandidate = event => {
            if (event.candidate) {
                console.log(`❄️ Candidato ICE generado: ${event.candidate.candidate.substr(0, 50)}...`);
                // Enviar candidato ICE a través del canal de señalización
                this.sendSignal({
                    type: 'ice-candidate',
                    candidate: event.candidate
                });
            } else {
                console.log('❄️ Recolección de candidatos ICE completada');
            }
        };
        
        // Manejar cambios en el estado de conexión ICE
        this.peerConnection.oniceconnectionstatechange = () => {
            console.log('❄️ Estado de conexión ICE:', this.peerConnection.iceConnectionState);
            
            // Mostrar overlay de espera si aún no hay conexión
            const waitingOverlay = document.getElementById('waitingOverlay');
            
            if (this.peerConnection.iceConnectionState === 'connected' || 
                this.peerConnection.iceConnectionState === 'completed') {
                if (waitingOverlay) {
                    console.log('✅ Ocultando overlay por estado ICE connected/completed');
                    waitingOverlay.style.display = 'none';
                    
                    // Inicio del temporizador de llamada cuando la conexión ICE está completa
                    this.startCallTimer();
                }
                
                // Verificar si se están recibiendo pistas remotas
                setTimeout(() => {
                    const receivers = this.peerConnection.getReceivers();
                    console.log(`📥 Verificando pistas remotas: ${receivers.length} receptores`);
                    
                    if (receivers.length > 0) {
                        receivers.forEach(receiver => {
                            console.log(`📥 Receptor: ${receiver.track ? receiver.track.kind : 'sin track'} - ${receiver.track ? (receiver.track.enabled ? 'activo' : 'inactivo') : 'N/A'}`);
                        });
                    }
                    
                    // Si no tenemos remoteStream pero la conexión está establecida, forzar refresco
                    if (!this.remoteStream && (this.peerConnection.connectionState === 'connected' || 
                                              this.peerConnection.iceConnectionState === 'connected' ||
                                              this.peerConnection.iceConnectionState === 'completed')) {
                        console.log('⚠️ Conexión establecida pero sin stream remoto, intentando recrear oferta...');
                        if (this.usuarioTipo === 'medico') {
                            this.createOffer();
                        }
                    }
                }, 3000);
            } else if (this.peerConnection.iceConnectionState === 'disconnected' || 
                       this.peerConnection.iceConnectionState === 'failed' ||
                       this.peerConnection.iceConnectionState === 'closed') {
                if (waitingOverlay) {
                    waitingOverlay.style.display = 'flex';
                    const heading = waitingOverlay.querySelector('h3');
                    const message = waitingOverlay.querySelector('p');
                    if (heading) heading.textContent = 'Conexión perdida';
                    if (message) message.textContent = 'Intentando reconectar...';
                }
            }
        };
        
        // Monitorear todos los cambios de estado
        this.peerConnection.onsignalingstatechange = () => {
            console.log('📣 Estado de señalización:', this.peerConnection.signalingState);
        };
        
        this.peerConnection.onconnectionstatechange = () => {
            console.log('🔌 Estado de conexión:', this.peerConnection.connectionState);
        };
        
        // Manejar cuando se reciben pistas remotas
        this.peerConnection.ontrack = event => {
            console.log('📥 Pista remota recibida:', event.track.kind);
            
            // Asegurarnos de que tenemos un stream remoto válido
            if (event.streams && event.streams[0]) {
                // Guardar el stream remoto
                this.remoteStream = event.streams[0];
                console.log(`📥 Stream remoto recibido con ${this.remoteStream.getTracks().length} pistas`);
                
                // Mostrar el stream remoto en el video grande (siempre será el otro participante)
                const remoteVideo = document.getElementById('remoteVideo');
                if (remoteVideo) {
                    console.log('📥 Asignando stream remoto al video grande');
                    
                    // Limpiar cualquier asignación previa
                    if (remoteVideo.srcObject !== this.remoteStream) {
                        remoteVideo.srcObject = this.remoteStream;
                        
                        // Log de las pistas remotas
                        this.remoteStream.getTracks().forEach(track => {
                            console.log(`📥 Pista remota: ${track.kind} - enabled: ${track.enabled}`);
                        });
                        
                        // Forzar reproducción
                        remoteVideo.play().catch(e => console.error('Error reproduciendo video remoto:', e));
                    }
                
                    // Ocultar overlay de espera
                    const waitingOverlay = document.getElementById('waitingOverlay');
                    if (waitingOverlay) {
                        console.log('✅ Ocultando overlay por pista remota recibida');
                        waitingOverlay.style.display = 'none';
                    }
                    
                    // Volver a verificar que el vídeo local esté en la miniatura
                    const localVideo = document.getElementById('localVideo');
                    if (localVideo && this.localStream && localVideo.srcObject !== this.localStream) {
                        console.log('🔄 Re-asignando stream local al video pequeño (miniatura)');
                        localVideo.srcObject = this.localStream;
                        localVideo.play().catch(e => console.error('Error reproduciendo video local:', e));
                    }
                    
                    // Iniciar contador de duración
                    this.startCallTimer();
                }
            }
        };
        
        // Crear canal de datos para mensajes en tiempo real
        this.dataChannel = this.peerConnection.createDataChannel('chat');
        this.dataChannel.onmessage = this.handleDataChannelMessage.bind(this);
        this.dataChannel.onopen = () => console.log('💬 Canal de datos abierto');
        this.dataChannel.onclose = () => console.log('💬 Canal de datos cerrado');
        
        // Escuchar si el otro lado crea el canal de datos
        this.peerConnection.ondatachannel = event => {
            this.dataChannel = event.channel;
            this.dataChannel.onmessage = this.handleDataChannelMessage.bind(this);
            this.dataChannel.onopen = () => console.log('💬 Canal de datos abierto (remoto)');
            this.dataChannel.onclose = () => console.log('💬 Canal de datos cerrado (remoto)');
        };
    },
    
    /**
     * Inicia el polling para recibir señales
     */
    initSignaling: function() {
        // Detener cualquier polling anterior
        if (this.signaling) {
            clearInterval(this.signaling);
        }
        
        console.log('Iniciando canal de señalización para la sala:', this.salaId);
        
        // Configuración de URL
        let pollUrl = `${baseUrl}/modules/videoconsulta/api/get_signals.php?sala_id=${this.salaId}`;
        
        // Cambiar a HTTPS si estamos en HTTP y el navegador lo soporta
        if (pollUrl.startsWith('http:') && window.location.protocol === 'https:') {
            pollUrl = pollUrl.replace('http:', 'https:');
            console.log('URL de señalización actualizada a HTTPS:', pollUrl);
        }
        
        // Asegurarse de que tenemos un lastId
        this.lastSignalId = this.lastSignalId || 0;
        
        // Si es médico, debe iniciar la llamada
        if (this.usuarioTipo === 'medico') {
            console.log('Usuario es médico: iniciando oferta');
            this.createOffer();
        }
        
        // Iniciar el polling
        const self = this;
        this.pollingFailed = 0; // Contador de fallos consecutivos
        
        this.signaling = setInterval(function() {
            // Añadir timestamp y lastId para evitar cacheo
            const fullUrl = `${pollUrl}&last_id=${self.lastSignalId}&t=${Date.now()}`;
            
            // Usar el método preferido basado en éxitos previos
            if (self.preferXHR) {
                self.pollSignalsXHR(fullUrl);
            } else {
                self.pollSignalsFetch(fullUrl).catch(function() {
                    // Si falla fetch, intentar con XHR
                    self.preferXHR = true;
                    self.pollSignalsXHR(fullUrl);
                });
            }
        }, 1000);
    },
    
    /**
     * Realiza polling usando Fetch API
     */
    pollSignalsFetch: function(url) {
        const self = this;
        
        return fetch(url, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(function(data) {
            self.pollingFailed = 0; // Resetear contador de fallos
            
            if (data.success && data.signals && data.signals.length > 0) {
                data.signals.forEach(function(signal) {
                    if (signal.id > self.lastSignalId) {
                        self.lastSignalId = parseInt(signal.id);
                        self.handleSignalingMessage(signal);
                    }
                });
            }
        })
        .catch(function(error) {
            console.error('Error al recibir señales (fetch):', error);
            self.pollingFailed++;
            
            // Si fallan 5 veces seguidas, cambiar a XHR
            if (self.pollingFailed >= 5) {
                console.log('Cambiando a método XHR para polling después de fallos consecutivos');
                self.preferXHR = true;
            }
            
            throw error; // Re-lanzar para manejo externo
        });
    },
    
    /**
     * Realiza polling usando XMLHttpRequest (como fallback)
     */
    pollSignalsXHR: function(url) {
        const self = this;
        
        try {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);
            xhr.timeout = 5000; // 5 segundos de timeout
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            self.pollingFailed = 0; // Resetear contador de fallos
                            
                            if (data.success && data.signals && data.signals.length > 0) {
                                data.signals.forEach(function(signal) {
                                    if (signal.id > self.lastSignalId) {
                                        self.lastSignalId = parseInt(signal.id);
                                        self.handleSignalingMessage(signal);
                                    }
                                });
                            }
                        } catch (e) {
                            console.error('Error al parsear respuesta XHR:', e);
                            self.pollingFailed++;
                        }
                    } else {
                        console.error('Error XHR HTTP:', xhr.status);
                        self.pollingFailed++;
                    }
                }
            };
            
            xhr.onerror = function() {
                console.error('Error en solicitud XHR');
                self.pollingFailed++;
            };
            
            xhr.ontimeout = function() {
                console.error('Timeout en solicitud XHR');
                self.pollingFailed++;
            };
            
            xhr.send();
        } catch (error) {
            console.error('Error fatal en solicitud XHR:', error);
            self.pollingFailed++;
        }
    },
    
    /**
     * Maneja los mensajes de señalización recibidos
     */
    handleSignalingMessage: function(signal) {
        const signalData = JSON.parse(signal.data);
        
        switch (signalData.type) {
            case 'offer':
                this.handleOffer(signalData);
                break;
            case 'answer':
                this.handleAnswer(signalData);
                // Cuando se recibe una respuesta, la conexión está establecida
                // Ocultar el overlay después de un breve retraso
                setTimeout(() => {
                    const waitingOverlay = document.getElementById('waitingOverlay');
                    if (waitingOverlay && waitingOverlay.style.display !== 'none') {
                        console.log('Ocultando overlay por respuesta SDP recibida');
                        waitingOverlay.style.display = 'none';
                    }
                }, 2000);
                break;
            case 'ice-candidate':
                this.handleIceCandidate(signalData);
                break;
            case 'user-left':
                this.handleUserLeft();
                break;
            default:
                console.log('Mensaje de señalización desconocido:', signalData);
        }
    },
    
    /**
     * Maneja una oferta SDP recibida
     */
    handleOffer: async function(offer) {
        if (this.peerConnection.signalingState !== 'stable') {
            console.log('⚠️ Conexión no estable, ignorando oferta');
            return;
        }
        
        try {
            console.log('📣 Recibida oferta SDP, configurando respuesta...');
            
            // Verificar si la oferta incluye video
            const hasVideo = offer.sdp.sdp && offer.sdp.sdp.includes('m=video');
            console.log(`📣 La oferta ${hasVideo ? 'incluye' : 'no incluye'} video`);
            
            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(offer.sdp));
            
            // Crear respuesta con restricciones para garantizar audio y video
            const answerOptions = {
                offerToReceiveAudio: true,
                offerToReceiveVideo: true
            };
            
            // Crear respuesta
            const answer = await this.peerConnection.createAnswer(answerOptions);
            
            // Modificar SDP para forzar la transmisión de video
            let modifiedSdp = answer.sdp;
            if (!modifiedSdp.includes('a=mid:video')) {
                console.log('⚠️ Forzando inclusión de video en SDP de respuesta');
                // Asegurar que se incluye video en SDP aunque no haya cámara
                modifiedSdp = modifiedSdp.replace(/(m=audio.*?)(m=video|$)/s, '$1m=video 9 UDP/TLS/RTP/SAVPF 96\r\na=mid:video\r\n$2');
            }
            
            // Modificar la respuesta con nuestro SDP personalizado si es necesario
            if (modifiedSdp !== answer.sdp) {
                answer.sdp = modifiedSdp;
                console.log('📣 SDP de respuesta modificado para mejorar compatibilidad');
            }
            
            await this.peerConnection.setLocalDescription(answer);
            
            // Enviar respuesta
            this.sendSignal({
                type: 'answer',
                sdp: this.peerConnection.localDescription
            });
            
            console.log('📣 Oferta aceptada y respuesta enviada');
        } catch (error) {
            console.error('❌ Error al manejar oferta:', error);
        }
    },
    
    /**
     * Maneja una respuesta SDP recibida
     */
    handleAnswer: async function(answer) {
        try {
            await this.peerConnection.setRemoteDescription(new RTCSessionDescription(answer.sdp));
            console.log('Respuesta recibida y aplicada');
            
            // Ocultar overlay de espera si sigue visible después de
            // recibir una respuesta SDP (ya la conexión está establecida)
            setTimeout(() => {
                const waitingOverlay = document.getElementById('waitingOverlay');
                if (waitingOverlay && waitingOverlay.style.display !== 'none') {
                    console.log('Ocultando overlay por respuesta SDP recibida');
                    waitingOverlay.style.display = 'none';
                    
                    // Mostrar mensaje de "solo audio" si es necesario
                    if (this.localStream && this.localStream.getVideoTracks().length === 0) {
                        const videoArea = document.querySelector('.video-area');
                        if (videoArea) {
                            const infoEl = document.createElement('div');
                            infoEl.className = 'alert alert-info position-absolute top-0 start-0 m-3';
                            infoEl.style.zIndex = '100';
                            infoEl.innerHTML = '<strong>Modo audio:</strong> Videollamada funcionando solo con audio';
                            videoArea.appendChild(infoEl);
                        }
                    }
                }
            }, 3000);
        } catch (error) {
            console.error('Error al manejar respuesta:', error);
        }
    },
    
    /**
     * Maneja un candidato ICE recibido
     */
    handleIceCandidate: async function(data) {
        try {
            if (data.candidate) {
                await this.peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
                console.log('Candidato ICE añadido');
            }
        } catch (error) {
            console.error('Error al añadir candidato ICE:', error);
        }
    },
    
    /**
     * Maneja la notificación de que el otro usuario ha abandonado la llamada
     */
    handleUserLeft: function() {
        // Mostrar mensaje
        const waitingOverlay = document.getElementById('waitingOverlay');
        waitingOverlay.style.display = 'flex';
        waitingOverlay.querySelector('h3').textContent = 'El otro participante ha salido de la videollamada';
        waitingOverlay.querySelector('p').textContent = 'Puede cerrar esta ventana o esperar a que se vuelva a conectar';
        
        // Cerrar la conexión peer existente
        if (this.peerConnection) {
            this.peerConnection.close();
        }
        
        // Si es el médico, reiniciar la conexión para permitir que el paciente vuelva a unirse
        if (this.usuarioTipo === 'medico') {
            this.initPeerConnection();
            this.createOffer();
        }
    },
    
    /**
     * Envía un mensaje a través del canal de señalización
     */
    sendSignal: function(data) {
        try {
            // Convertir URL http a https si es necesario
            let signalUrl = `${baseUrl}/modules/videoconsulta/api/send_signal.php`;
            
            // Cambiar a HTTPS si estamos en HTTP y el navegador lo soporta
            if (signalUrl.startsWith('http:') && window.location.protocol === 'https:') {
                signalUrl = signalUrl.replace('http:', 'https:');
                console.log('URL de señalización actualizada a HTTPS:', signalUrl);
            }
            
            // Agregar timestamp para evitar cacheo
            signalUrl += `?t=${Date.now()}`;
            
            console.log('Enviando señal a:', signalUrl);
            
            fetch(signalUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache'
                },
                body: JSON.stringify({
                    sala_id: this.salaId,
                    data: JSON.stringify(data)
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (!result.success) {
                    console.error('Error al enviar señal:', result.message);
                }
            })
            .catch(error => {
                console.error('Error al enviar señal:', error);
                
                // Intentar fallback con XMLHttpRequest si fetch falla
                console.log('Intentando fallback con XMLHttpRequest...');
                this.sendSignalFallback(signalUrl, data);
            });
        } catch (error) {
            console.error('Error general al enviar señal:', error);
            
            // Intentar con fallback
            this.sendSignalFallback(`${baseUrl}/modules/videoconsulta/api/send_signal.php`, data);
        }
    },
    
    /**
     * Método fallback para enviar señales cuando fetch falla
     */
    sendSignalFallback: function(url, data) {
        try {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const result = JSON.parse(xhr.responseText);
                            if (!result.success) {
                                console.error('Error fallback al enviar señal:', result.message);
                            } else {
                                console.log('Señal enviada con éxito mediante fallback');
                            }
                        } catch (e) {
                            console.error('Error al parsear respuesta fallback:', e);
                        }
                    } else {
                        console.error('Error fallback HTTP:', xhr.status);
                    }
                }
            };
            xhr.send(JSON.stringify({
                sala_id: this.salaId,
                data: JSON.stringify(data)
            }));
        } catch (error) {
            console.error('Error fatal en fallback:', error);
            // No podemos hacer más, la señalización falló completamente
        }
    },
    
    /**
     * Maneja los mensajes recibidos a través del canal de datos
     */
    handleDataChannelMessage: function(event) {
        try {
            const data = JSON.parse(event.data);
            
            if (data.type === 'chat-notification') {
                // Si el chat está cerrado, mostrar notificación
                const chatContainer = document.getElementById('chatContainer');
                if (!chatContainer.classList.contains('open')) {
                    // Aquí se podría mostrar una notificación visual o sonora
                    console.log('Nuevo mensaje de chat recibido');
                    
                    // Animar el botón de chat
                    const chatBtn = document.getElementById('toggleChatBtn');
                    chatBtn.classList.add('pulse');
                    setTimeout(() => {
                        chatBtn.classList.remove('pulse');
                    }, 2000);
                }
            }
        } catch (error) {
            console.error('Error al procesar mensaje de canal de datos:', error);
        }
    },
    
    /**
     * Inicia el temporizador de la duración de la llamada
     */
    startCallTimer: function() {
        if (this.callTimerInterval) {
            clearInterval(this.callTimerInterval);
        }
        
        const startTime = Date.now();
        const duracionElement = document.getElementById('duracionLlamada');
        
        if (duracionElement) {
            this.callTimerInterval = setInterval(() => {
                const elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);
                const minutes = Math.floor(elapsedSeconds / 60);
                const seconds = elapsedSeconds % 60;
                duracionElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        }
    },
    
    /**
     * Alterna el estado del micrófono (silenciar/activar)
     * @returns {boolean} true si el micrófono está silenciado, false en caso contrario
     */
    toggleAudio: function() {
        if (!this.localStream) {
            console.warn('No hay stream local para silenciar/activar audio');
            return true; // Consideramos que está silenciado si no hay stream
        }
        
        const audioTracks = this.localStream.getAudioTracks();
        
        if (audioTracks.length === 0) {
            console.warn('No hay pistas de audio en el stream local');
            return true; // Consideramos que está silenciado si no hay pistas de audio
        }
        
        this.isAudioMuted = !this.isAudioMuted;
        
        audioTracks.forEach(track => {
            track.enabled = !this.isAudioMuted;
        });
        
        return this.isAudioMuted;
    },
    
    /**
     * Alterna el estado de la cámara (encender/apagar)
     * @returns {boolean} true si la cámara está apagada, false en caso contrario
     */
    toggleVideo: function() {
        if (!this.localStream) {
            console.warn('No hay stream local para encender/apagar video');
            return true; // Consideramos que está apagado si no hay stream
        }
        
        const videoTracks = this.localStream.getVideoTracks();
        
        if (videoTracks.length === 0) {
            console.warn('No hay pistas de video en el stream local');
            return true; // Consideramos que está apagado si no hay pistas de video
        }
        
        this.isVideoOff = !this.isVideoOff;
        
        videoTracks.forEach(track => {
            track.enabled = !this.isVideoOff;
        });
        
        return this.isVideoOff;
    },
    
    /**
     * Finaliza la videollamada
     */
    endCall: function() {
        // Enviar señal de salida
        this.sendSignal({
            type: 'user-left'
        });
        
        // Detener intervalo de verificación
        if (this.signalCheckInterval) {
            clearInterval(this.signalCheckInterval);
        }
        
        // Detener streams
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
        }
        
        // Cerrar conexión
        if (this.peerConnection) {
            this.peerConnection.close();
        }
    },
    
    /**
     * Crea y envía una oferta SDP
     */
    createOffer: async function() {
        try {
            console.log('📣 Creando oferta SDP...');
            
            // Asegurar que la conexión está en estado estable
            if (this.peerConnection.signalingState !== 'stable') {
                console.log('⚠️ La conexión no está en estado estable, esperando...');
                return;
            }
            
            // Crear oferta con restricciones para garantizar audio y video
            const offerOptions = {
                offerToReceiveAudio: true,
                offerToReceiveVideo: true
            };
            
            // Crear oferta
            const offer = await this.peerConnection.createOffer(offerOptions);
            
            // Modificar SDP para forzar la transmisión de video
            let modifiedSdp = offer.sdp;
            if (!modifiedSdp.includes('a=mid:video')) {
                console.log('⚠️ Forzando inclusión de video en SDP');
                // Asegurar que se incluye video en SDP aunque no haya cámara
                modifiedSdp = modifiedSdp.replace(/(m=audio.*?)(m=video|$)/s, '$1m=video 9 UDP/TLS/RTP/SAVPF 96\r\na=mid:video\r\n$2');
            }
            
            // Modificar la oferta con nuestro SDP personalizado si es necesario
            if (modifiedSdp !== offer.sdp) {
                offer.sdp = modifiedSdp;
                console.log('📣 SDP modificado para mejorar compatibilidad');
            }
            
            await this.peerConnection.setLocalDescription(offer);
            
            console.log('📣 Oferta SDP creada, enviando a través del canal de señalización');
            
            // Enviar oferta al otro peer a través del servidor de señalización
            this.sendSignal({
                type: 'offer',
                sdp: this.peerConnection.localDescription
            });
        } catch (error) {
            console.error('❌ Error al crear oferta:', error);
            
            // Reintentar después de un tiempo si hay error
            setTimeout(() => {
                console.log('🔄 Reintentando crear oferta...');
                this.createOffer();
            }, 3000);
        }
    }
}; 