// Variables globales
let localStream; // Stream de la cámara local
let peerConnection; // Conexión WebRTC
let remoteStream = new MediaStream(); // Stream remoto
let localVideo; // Elemento de video local (pequeño)
let remoteVideo; // Elemento de video remoto (grande)
let salaId = null;
let lastSignalId = 0;
let isPollingActive = true;
let pollingInterval;
let heartbeatInterval;
let reconnectAttempts = 0;
let isInitiator = false;
let isAdmin = false; // Variable para saber si es administrador
let noMedia = false; // Variable para saber si está en modo sin medios
let initialized = false; // Para evitar inicialización múltiple
let iceCandidatesBuffer = []; // Buffer para candidatos ICE recibidos antes de la descripción remota
let processedSignals = new Set(); // Conjunto para almacenar IDs de señales ya procesadas
let lastHeartbeatSent = 0; // Último heartbeat enviado
let lastHeartbeatReceived = 0; // Último heartbeat recibido
let participantReady = false; // Indica si el otro participante está listo
// Detectar Firefox para compatibilidad
// Usa window.isFirefox si está definido, si no, lo define globalmente.
if (typeof window.isFirefox === 'undefined') {
    window.isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
}
// Asegurarse de que isFirefox sea accesible localmente también
const isFirefox = window.isFirefox;

// Configuración de servidores ICE (STUN/TURN)
const iceServers = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' },
        { urls: 'stun:stun3.l.google.com:19302' },
        { urls: 'stun:stun4.l.google.com:19302' },
        // Servidor TURN de OpenRelay
        {
            urls: 'turn:openrelay.metered.ca:443',
            username: 'openrelayproject',
            credential: 'openrelayproject'
        },
        // Servidor TURN de Twilio (gratuito para pruebas)
        {
            urls: 'turn:global.turn.twilio.com:3478?transport=udp',
            username: 'YOUR_TWILIO_ACCOUNT_SID', // Reemplazar con SID real si se usa
            credential: 'YOUR_TWILIO_AUTH_TOKEN'  // Reemplazar con Token real si se usa
        },
        // Añadir otro STUN como fallback
        { urls: 'stun:stun.services.mozilla.com' },
        
        // Añadir opción para deshabilitar negociación automática
        // Esto nos dará más control sobre el proceso de señalización
        enableDtlsSrtp: true
    ],
    iceCandidatePoolSize: 10,
    sdpSemantics: 'unified-plan',
    rtcpMuxPolicy: 'require'
};

// Inicializar la aplicación cuando el DOM está cargado
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Evitar inicialización múltiple
        if (initialized) return;
        initialized = true;

        console.log("Inicializando aplicación de videollamada...");
        
        // Asignar elementos del DOM
        localVideo = document.getElementById('selfVideo');
        remoteVideo = document.getElementById('remoteVideo');
        
        // Verificar si tenemos un ID de sala válido
        if (!salaId) {
            // Intentar obtener de la URL como fallback
            const urlParams = new URLSearchParams(window.location.search);
            salaId = urlParams.get('id');
            
            if (!salaId) {
                showError("Error: No se ha especificado un ID de sala válido");
                return;
            }
        }
        
        console.log(`ID de sala: ${salaId}`);
        console.log(`Iniciador: ${isInitiator}`);
        console.log(`Administrador: ${isAdmin}`);
        console.log(`Sin medios: ${noMedia}`);
        
        // Configurar eventos de botones
        setupButtonListeners();
        
        // Iniciar la cámara, a menos que estemos en modo sin medios
        if (!noMedia) {
            await setupLocalStream();
        } else {
            console.log("Modo sin medios activado, creando stream vacío");
            // Crear un stream vacío para mantener la compatibilidad
            localStream = new MediaStream();
            updateMediaStatus();
        }
        
        // Crear la conexión peer y configurar polling
        await createPeerConnection();
        startSignalPolling();
        
        // Mostrar indicador de estado inicial
        updateConnectionStatusUI();
        
        if (isInitiator) {
            console.log("Soy el profesional/administrador. Iniciando la llamada...");
            // Dar un pequeño tiempo para que se establezca todo antes de crear la oferta
            setTimeout(() => {
                // Si somos el iniciador, creamos la oferta
                createOffer();
            }, 1000);
        } else {
            console.log("Soy el paciente. Esperando a que el profesional inicie la llamada...");
        }
    } catch (error) {
        console.error("Error al inicializar la aplicación:", error);
        showError(`Error al inicializar: ${error.message}`);
    }
});

// Configurar los listeners de botones
function setupButtonListeners() {
    // Botón de colgar
    const hangupButton = document.getElementById('hangupBtn');
    if (hangupButton) {
        hangupButton.addEventListener('click', hangUp);
    }
    
    // Botón de silenciar micrófono
    const muteButton = document.getElementById('muteBtn');
    if (muteButton) {
        muteButton.addEventListener('click', toggleMute);
    }
    
    // Botón de apagar cámara
    const videoButton = document.getElementById('videoBtn');
    if (videoButton) {
        videoButton.addEventListener('click', toggleVideo);
    }
}

// Inicializa el stream local (cámara y micrófono)
async function setupLocalStream() {
    try {
        console.log("Solicitando acceso a cámara y micrófono...");
        
        // Limpiar cualquier stream local existente
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }
        
        // Solicitar acceso a cámara y micrófono con constraints más flexibles
        const constraints = {
            audio: true,
            video: {
                width: { ideal: 1280, min: 640 },
                height: { ideal: 720, min: 480 },
                frameRate: { ideal: 30, min: 15 }
            }
        };
        
        console.log("Solicitando medios con constraints:", constraints);
        
        try {
            localStream = await navigator.mediaDevices.getUserMedia(constraints);
            console.log("Acceso concedido a cámara y micrófono con calidad alta");
        } catch (highQualityError) {
            console.warn("No se pudo acceder con alta calidad, intentando con calidad menor:", highQualityError);
            
            // Intentar con constraints más básicos
            const basicConstraints = {
                audio: true,
                video: true
            };
            
            try {
                localStream = await navigator.mediaDevices.getUserMedia(basicConstraints);
                console.log("Acceso concedido a cámara y micrófono con calidad básica");
            } catch (basicError) {
                console.error("Error al acceder a medios con calidad básica:", basicError);
                
                // Último intento: solo audio
                try {
                    const audioOnlyConstraints = {
                        audio: true,
                        video: false
                    };
                    localStream = await navigator.mediaDevices.getUserMedia(audioOnlyConstraints);
                    console.log("Acceso concedido solo a micrófono");
                    showMessage("No se pudo acceder a la cámara. Videollamada solo con audio.", "warning");
                } catch (audioError) {
                    throw audioError; // Si esto falla, realmente no podemos continuar
                }
            }
        }
        
        // Mostrar el video local en el elemento de video pequeño
        if (localVideo) {
            // Verificar si el elemento ya tenía un stream asignado
            if (localVideo.srcObject) {
                console.log("Reemplazando stream local anterior");
            }
            
            localVideo.srcObject = localStream;
            localVideo.muted = true; // Silenciar el audio local para evitar eco
            
            // Forzar reproducción
            try {
                await localVideo.play();
                console.log("Video local reproduciendo");
            } catch (playError) {
                console.warn("Error al reproducir video local:", playError);
                // Intentar nuevamente después de un clic
                document.addEventListener('click', function playOnClick() {
                    localVideo.play();
                    document.removeEventListener('click', playOnClick);
                });
            }
            
            console.log("Stream local conectado al elemento de video");
        } else {
            console.error("No se encontró el elemento de video local (selfVideo)");
        }
        
        // Verificar pistas obtenidas
        const audioTracks = localStream.getAudioTracks();
        const videoTracks = localStream.getVideoTracks();
        
        console.log(`Pistas de audio: ${audioTracks.length}, Pistas de video: ${videoTracks.length}`);
        if (audioTracks.length > 0) {
            console.log(`Usando dispositivo de audio: ${audioTracks[0].label}`);
        }
        if (videoTracks.length > 0) {
            console.log(`Usando dispositivo de video: ${videoTracks[0].label}`);
        }
        
        // Mostrar indicador de que la cámara y micrófono están funcionando
        updateMediaStatus();
        
        // Si tenemos una conexión peer activa, añadir las nuevas pistas
        if (peerConnection) {
            console.log("Reemplazando pistas en la conexión peer existente");
            
            // Obtener los senders actuales
            const senders = peerConnection.getSenders();
            
            // Reemplazar las pistas de audio
            if (audioTracks.length > 0) {
                const audioSender = senders.find(sender => 
                    sender.track && sender.track.kind === 'audio');
                
                if (audioSender) {
                    console.log("Reemplazando pista de audio");
                    audioSender.replaceTrack(audioTracks[0]);
                } else if (peerConnection.signalingState !== 'closed') {
                    console.log("Añadiendo nueva pista de audio");
                    peerConnection.addTrack(audioTracks[0], localStream);
                }
            }
            
            // Reemplazar las pistas de video
            if (videoTracks.length > 0) {
                const videoSender = senders.find(sender => 
                    sender.track && sender.track.kind === 'video');
                
                if (videoSender) {
                    console.log("Reemplazando pista de video");
                    videoSender.replaceTrack(videoTracks[0]);
                } else if (peerConnection.signalingState !== 'closed') {
                    console.log("Añadiendo nueva pista de video");
                    peerConnection.addTrack(videoTracks[0], localStream);
                }
            }
        }
    } catch (error) {
        console.error("Error al acceder a la cámara o micrófono:", error);
        showError(`No se pudo acceder a la cámara o micrófono: ${error.message}`);
        
        // Si falla y es administrador, crear un stream vacío para continuar
        if (isAdmin) {
            console.log("Creando stream vacío para administrador");
            localStream = new MediaStream();
            
            // Mostrar indicación visual
            const noVideoElement = document.createElement('div');
            noVideoElement.textContent = 'Sin cámara/micrófono';
            noVideoElement.style.position = 'absolute';
            noVideoElement.style.top = '50%';
            noVideoElement.style.left = '50%';
            noVideoElement.style.transform = 'translate(-50%, -50%)';
            noVideoElement.style.color = 'white';
            noVideoElement.style.fontWeight = 'bold';
            
            if (localVideo && localVideo.parentNode) {
                localVideo.parentNode.appendChild(noVideoElement);
            }
            
            updateMediaStatus();
            
            // No lanzar error para continuar con la aplicación
            return;
        }
        throw error;
    }
}

// Función utilitaria para normalizar todos los SDP y evitar conflictos de extensiones RTP
function normalizeSdp(sdp) {
    if (!sdp) return sdp;
    
    // Convertimos las extmap problemáticas a IDs altos para evitar conflictos
    let normalizedSdp = sdp;
    
    // ID 2 -> ID 14 para abs-send-time
    normalizedSdp = normalizedSdp.replace(
        /a=extmap:2 http:\/\/www.webrtc.org\/experiments\/rtp-hdrext\/abs-send-time/g,
        'a=extmap:14 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time'
    );
    
    // ID 2 -> ID 15 para csrc-audio-level
    normalizedSdp = normalizedSdp.replace(
        /a=extmap:2 urn:ietf:params:rtp-hdrext:csrc-audio-level/g,
        'a=extmap:15 urn:ietf:params:rtp-hdrext:csrc-audio-level'
    );
    
    // Otros mapeos que podrían causar problemas
    normalizedSdp = normalizedSdp.replace(
        /a=extmap:3 urn:ietf:params:rtp-hdrext:sdes:mid/g,
        'a=extmap:10 urn:ietf:params:rtp-hdrext:sdes:mid'
    );
    
    normalizedSdp = normalizedSdp.replace(
        /a=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid/g,
        'a=extmap:10 urn:ietf:params:rtp-hdrext:sdes:mid'
    );
    
    normalizedSdp = normalizedSdp.replace(
        /a=extmap:5 urn:ietf:params:rtp-hdrext:sdes:mid/g,
        'a=extmap:10 urn:ietf:params:rtp-hdrext:sdes:mid'
    );
    
    return normalizedSdp;
}

// Crear la conexión peer de WebRTC
async function createPeerConnection() {
    try {
        console.log("Creando conexión peer con configuración:", iceServers);
        
        // Limpiar conexión peer anterior si existe
        if (peerConnection) {
            console.log("Cerrando conexión peer anterior");
            peerConnection.close();
            peerConnection = null;
        }
        
        // Configuración extendida para prevenir conflictos de extensiones RTP
        const peerConfig = {
            ...iceServers,
            sdpSemantics: 'unified-plan',
            rtcpMuxPolicy: 'require',
            // Prevenir negociación automática para tener más control
            enableRtpDataChannels: false,
            // Usar extensiones específicas con IDs fijos
            extmapAllowMixed: true
        };
        
        // Crear la conexión peer con la configuración extendida
        peerConnection = new RTCPeerConnection(peerConfig);
        console.log("Conexión peer creada con configuración extendida");
        
        // Configurar transceivers explícitamente para audio y video
        // Esto configura adecuadamente las direcciones de flujo desde el inicio
        if (RTCRtpTransceiver.prototype.setDirection) { // Verificar soporte para API
            const audioTransceiver = peerConnection.addTransceiver('audio', {
                direction: 'sendrecv'
            });
            console.log("Transceiver de audio creado con dirección:", audioTransceiver.direction);
            
            const videoTransceiver = peerConnection.addTransceiver('video', {
                direction: 'sendrecv'
            });
            console.log("Transceiver de video creado con dirección:", videoTransceiver.direction);
        } else {
            console.log("API de transceivers no soportada completamente, usando configuración tradicional");
            // Configuración de fallback para navegadores que no soportan completamente transceivers
            if (localStream && localStream.getTracks().length > 0) {
                localStream.getTracks().forEach(track => {
                    console.log("Añadiendo pista local a la conexión peer (fallback):", track.kind);
                    peerConnection.addTrack(track, localStream);
                });
            }
        }
        
        // Reiniciar el stream remoto
        remoteStream = new MediaStream();
        if (remoteVideo) {
            remoteVideo.srcObject = remoteStream;
        }
        
        // Configurar remoteStream para recibir las pistas del par remoto
        peerConnection.ontrack = (event) => {
            console.log("*************************************");
            console.log("** ¡EVENTO ONTRACK RECIBIDO! **");
            console.log("** Pista recibida:", event.track.kind);
            console.log("** ID de la pista:", event.track.id);
            console.log("** Estado de la pista:", event.track.readyState);
            console.log("** Streams asociados:", event.streams.length > 0 ? event.streams[0].id : "Ninguno");
            console.log("*************************************");
            
            // Verificar si la pista ya existe en el stream remoto
            const existingTrack = remoteStream.getTracks().find(t => t.id === event.track.id);
            if (!existingTrack) {
                // Agregar la pista al stream remoto solo si no existe
                remoteStream.addTrack(event.track);
                console.log(`✅ Nueva pista ${event.track.kind} (${event.track.id}) añadida al stream remoto.`);
                
                // Escuchar eventos de la pista (mute, unmute, ended)
                event.track.onmute = () => console.log(`🔇 Pista remota ${event.track.kind} silenciada.`);
                event.track.onunmute = () => console.log(`🔊 Pista remota ${event.track.kind} activada.`);
                event.track.onended = () => console.log(`❌ Pista remota ${event.track.kind} finalizada.`);
                
            } else {
                console.log(`🔵 La pista ${event.track.kind} (${event.track.id}) ya existe en el stream remoto.`);
            }
            
            // Mostrar información sobre todas las pistas en el stream remoto
            console.log(`📊 Stream remoto ahora tiene ${remoteStream.getTracks().length} pistas:`);
            remoteStream.getTracks().forEach((track, i) => {
                console.log(`  [${i+1}] Tipo: ${track.kind}, ID: ${track.id}, Estado: ${track.readyState}, Habilitado: ${track.enabled}`);
            });
            
            // Asegurarse que el stream remoto está asignado al elemento de video
            if (remoteVideo) {
                if (remoteVideo.srcObject !== remoteStream) {
                    console.log("🔗 Asignando stream remoto al elemento <video id='remoteVideo'>");
                    remoteVideo.srcObject = remoteStream;
                } else {
                    console.log("ℹ️ El stream remoto ya estaba asignado a remoteVideo.");
                }
                
                // Forzar una actualización visual del elemento (ya estaba)
                remoteVideo.style.display = 'none';
                setTimeout(() => {
                    remoteVideo.style.display = 'block';
                }, 100);
                
                // Forzar reproducción del video remoto
                if (remoteVideo.paused) {
                    console.log("▶️ El video remoto estaba pausado, intentando reproducir...");
                    const playPromise = remoteVideo.play();
                    if (playPromise !== undefined) {
                        playPromise
                            .then(() => console.log("✅ Video remoto reproduciendo correctamente."))
                            .catch(err => {
                                console.error("❌ Error al reproducir video remoto automáticamente:", err);
                                showMessage("Necesita hacer clic en la pantalla para iniciar el video/audio.", "warning");
                                // Intentar reproducir después de interacción del usuario
                                document.body.addEventListener('click', function playOnClick() {
                                    console.log("🖱️ Intentando reproducir video remoto después de clic...");
                                    remoteVideo.play()
                                        .then(() => console.log("✅ Video remoto reproduciendo después de clic."))
                                        .catch(e => console.error("❌ Fallo incluso después de clic:", e));
                                    document.body.removeEventListener('click', playOnClick); // Remover listener después del primer clic
                                }, { once: true }); // Asegura que el listener se ejecute solo una vez
                            });
                    }
                } else {
                    console.log("ℹ️ El video remoto ya se estaba reproduciendo.");
                }
            } else {
                console.error("❌ No se encontró el elemento <video id='remoteVideo'>.");
            }
            
            // Si recibimos una pista, considerar que la conexión está funcionando
            showMessage("Recibiendo video/audio del otro participante", "success");
        };
        
        // Cuando se genera un candidato ICE, enviarlo al otro par
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                console.log("Candidato ICE generado:", event.candidate.sdpMLineIndex, event.candidate.candidate.split(" ")[7]);
                sendSignal({
                    type: 'ice-candidate',
                    candidate: event.candidate
                });
            } else {
                console.log("Fin de candidatos ICE");
            }
        };
        
        // Monitorear el estado de reunión de candidatos ICE
        peerConnection.onicegatheringstatechange = () => {
            console.log("Estado de reunión ICE:", peerConnection.iceGatheringState);
        };
        
        // Monitorear el estado de la conexión ICE
        peerConnection.oniceconnectionstatechange = () => {
            console.log("Estado de conexión ICE:", peerConnection.iceConnectionState);
            
            switch (peerConnection.iceConnectionState) {
                case 'checking':
                    console.log("Verificando candidatos ICE...");
                    break;
                case 'connected':
                    console.log("Conexión ICE establecida con éxito");
                    // Mostrar mensaje para confirmar conexión exitosa
                    showMessage("Conexión establecida con éxito", "success");
                    // Restablecer el contador de intentos de reconexión
                    reconnectAttempts = 0;
                    
                    // Verificar si tenemos pistas en el stream remoto después de 2 segundos
                    setTimeout(() => {
                        if (remoteStream.getTracks().length === 0) {
                            console.log("Conexión ICE establecida pero no hay pistas remotas, forzando reconexión");
                            showMessage("Conexión establecida pero sin video. Reconectando...", "warning");
                            resetConnection();
                        }
                    }, 2000);
                    break;
                case 'completed':
                    console.log("Negociación ICE completada");
                    break;
                case 'disconnected':
                    console.log("Conexión interrumpida, intentando reconectar...");
                    showMessage("La conexión se ha interrumpido, intentando reconectar...", "warning");
                    // Intento de reconexión
                    handleDisconnection();
                    break;
                case 'failed':
                    console.error("Conexión fallida");
                    showError("La conexión ha fallado. Intentando reconectar automáticamente...");
                    // Intento de reconexión más agresiva
                    resetConnection();
                    break;
                case 'closed':
                    console.log("Conexión cerrada");
                    break;
            }
            
            // Actualizar indicador de estado
            updateConnectionStatusUI();
        };
        
        // Monitorear cambios en el estado de la conexión
        peerConnection.onsignalingstatechange = () => {
            console.log("Estado de señalización:", peerConnection.signalingState);
            
            // Si la conexión se completa pero no tenemos pistas remotas, reiniciar
            if (peerConnection.signalingState === 'stable' && remoteStream.getTracks().length === 0) {
                console.log("Señalización estable pero sin pistas remotas");
                
                // Esperar un poco para dar tiempo a que lleguen las pistas
                setTimeout(() => {
                    if (remoteStream.getTracks().length === 0 && 
                        peerConnection.iceConnectionState !== 'checking' && 
                        participantReady) {
                        console.log("Todavía sin pistas remotas después de espera, considerando reconexión");
                        // Si el otro participante está listo pero no tenemos pistas, reiniciar
                        if (isInitiator) {
                            console.log("Soy iniciador, creando nueva oferta para reconexión");
                            createOffer();
                        }
                    }
                }, 3000);
            }
        };
        
        // Monitorear la negociación necesaria
        peerConnection.onnegotiationneeded = async () => {
            console.log("Negociación necesaria detectada");
            if (isInitiator) {
                console.log("Soy iniciador, creando nueva oferta debido a negociación");
                await createOffer();
            }
        };
        
        // Añadir pistas locales a la conexión peer si existen
        if (localStream && localStream.getTracks().length > 0) {
            console.log(`Añadiendo ${localStream.getTracks().length} pistas locales a la conexión`);
            
            localStream.getTracks().forEach(track => {
                console.log("Añadiendo pista local a la conexión peer:", track.kind, track.id);
                try {
                    const sender = peerConnection.addTrack(track, localStream);
                    console.log("Pista añadida con sender:", sender.track?.kind || "desconocido");
                } catch (e) {
                    console.error("Error al añadir pista a la conexión:", e);
                }
            });
            
            // Verificar los senders después de añadir pistas
            const senders = peerConnection.getSenders();
            console.log(`Después de añadir pistas, hay ${senders.length} senders:`);
            senders.forEach((sender, i) => {
                console.log(`  [${i+1}] Sender: tipo=${sender.track?.kind || "vacío"}, id=${sender.track?.id || "N/A"}`);
            });
        } else {
            console.log("No hay pistas locales para añadir (posiblemente en modo sin medios)");
        }
        
        // Función para monitorear y corregir conflictos de extensiones RTP
        peerConnection.addEventListener('negotiationneeded', async (event) => {
            console.log("Evento negotiationneeded detectado");
            // Solo manejar si somos el iniciador
            if (!isInitiator) return;
            
            try {
                // Crear oferta con opciones específicas
                const offer = await peerConnection.createOffer({
                    offerToReceiveAudio: true,
                    offerToReceiveVideo: true,
                    voiceActivityDetection: true
                });
                
                // Modificar SDP para evitar remapeo de extensiones RTP
                if (offer.sdp) {
                    offer.sdp = normalizeSdp(offer.sdp);
                    console.log("SDP modificado para evitar conflictos de extensiones RTP durante negotiationneeded");
                }
                
                // Establecer descripción local con la oferta modificada
                await peerConnection.setLocalDescription(offer);
                
                // Si estamos en estado stable, enviamos la oferta
                if (peerConnection.signalingState === 'stable') {
                    sendSignal({
                        type: 'offer',
                        sdp: peerConnection.localDescription
                    });
                    console.log("Oferta enviada desde evento negotiationneeded");
                }
            } catch (error) {
                console.error("Error durante la negociación:", error);
            }
        });
        
        // Interceptar createOffer y createAnswer nativos para modificar SDP
        const nativeCreateOffer = peerConnection.createOffer.bind(peerConnection);
        peerConnection.createOffer = async (options) => {
            const offer = await nativeCreateOffer(options);
            if (offer.sdp) {
                offer.sdp = normalizeSdp(offer.sdp);
                console.log("SDP de oferta normalizado automáticamente");
            }
            return offer;
        };
        
        const nativeCreateAnswer = peerConnection.createAnswer.bind(peerConnection);
        peerConnection.createAnswer = async (options) => {
            const answer = await nativeCreateAnswer(options);
            if (answer.sdp) {
                answer.sdp = normalizeSdp(answer.sdp);
                console.log("SDP de respuesta normalizado automáticamente");
            }
            return answer;
        };
        
        console.log("Conexión peer creada exitosamente");
    } catch (error) {
        console.error("Error al crear la conexión peer:", error);
        showError(`Error al crear la conexión: ${error.message}`);
        throw error;
    }
}

// Función para reiniciar completamente la conexión
async function resetConnection() {
    try {
        console.log("Reiniciando completamente la conexión WebRTC...");
        showMessage("Reiniciando conexión...", "info");
        
        // Cerrar la conexión existente
        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }
        
        // Limpiar buffer de candidatos ICE
        iceCandidatesBuffer = [];
        
        // Esperar un momento antes de crear una nueva conexión
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Crear nueva conexión peer
        await createPeerConnection();
        
        // Si somos iniciador, crear nueva oferta
        if (isInitiator) {
            console.log("Soy iniciador, creando nueva oferta después de reset");
            await createOffer();
        } else {
            console.log("Soy receptor, esperando nueva oferta después de reset");
        }
        
        console.log("Conexión reiniciada con éxito");
    } catch (error) {
        console.error("Error al reiniciar la conexión:", error);
        showError(`Error al reiniciar: ${error.message}`);
    }
}

// Crear una oferta SDP
async function createOffer() {
    try {
        console.log("🔵 Intentando crear oferta SDP...");
        // Log del estado del iniciador JUSTO ANTES de crear la oferta
        console.log(`🔵 Estado isInitiator al llamar createOffer: ${isInitiator}`);
        
        // Verificar si somos el iniciador
        if (!isInitiator) {
            console.warn("🟡 No soy iniciador, no debería crear oferta.");
            return;
        }
        
        // Asegurar que la conexión peer existe
        if (!peerConnection) {
            console.error("❌ Error: No hay peerConnection para crear oferta.");
            await resetConnection(); // Intentar reiniciar
            return;
        }
        
        console.log("🔵 Creando oferta con opciones: { offerToReceiveAudio: true, offerToReceiveVideo: true }");
        const offer = await peerConnection.createOffer({
            offerToReceiveAudio: true,
            offerToReceiveVideo: true
        });
        
        console.log("🔵 Oferta SDP creada:", offer.sdp.substring(0, 100) + "...");
        
        // Modificar el SDP para prevenir conflictos de extensiones RTP
        if (offer.sdp) {
            offer.sdp = normalizeSdp(offer.sdp);
            console.log("🔵 SDP oferta normalizada para prevenir conflictos de extensiones RTP");
        }
        
        // Verificar estado antes de setLocalDescription
        console.log(`🔵 Estado de señalización ANTES de setLocalDescription(offer): ${peerConnection.signalingState}`);
        if (peerConnection.signalingState !== 'stable' && peerConnection.signalingState !== 'have-remote-offer') {
            console.warn(`🟡 Estado de señalización (${peerConnection.signalingState}) no ideal para setLocalDescription(offer), pero intentando...`);
        }
        
        // Establecer la descripción local (oferta modificada)
        await peerConnection.setLocalDescription(offer);
        console.log("🔵 Descripción local (oferta) establecida.");
        console.log(`🔵 Estado de señalización DESPUÉS de setLocalDescription(offer): ${peerConnection.signalingState}`);
        
        // Enviar la oferta al otro par
        console.log("🔵 Enviando señal tipo 'offer' al servidor...");
        sendSignal({
            type: 'offer',
            sdp: peerConnection.localDescription // Usar la descripción local recién establecida
        });
        
        console.log("🔵 Oferta enviada al otro par.");
    } catch (error) {
        console.error("❌ Error al crear oferta:", error);
        showError(`Error al crear oferta: ${error.message}`);
        // Intentar reiniciar si falla la creación de oferta
        await resetConnection();
    }
}

// Procesar oferta recibida
async function processOffer(offer) {
    try {
        console.log("🔵 Procesando oferta SDP RECIBIDA...");
        // Log del estado del iniciador al procesar oferta
        console.log(`🔵 Estado isInitiator al llamar processOffer: ${isInitiator}`);
        
        // Verificar si somos iniciador (no deberíamos recibir ofertas si lo somos, salvo glare)
        if (isInitiator && peerConnection && peerConnection.signalingState !== 'stable') {
             console.warn("🟡 Soy iniciador y recibí una oferta (posible glare). Estado actual:", peerConnection.signalingState);
             // Implementar lógica de resolución de glare si es necesario, por ahora ignoramos.
             // return;
        }
        
        // Verificar si la conexión peer aún no está inicializada
        if (!peerConnection) {
            console.log("🔵 PeerConnection no inicializado, creándolo para procesar oferta...");
            await createPeerConnection();
        }
        
        // Verificar el estado de señalización antes de setRemoteDescription
        console.log(`🔵 Estado de señalización ANTES de setRemoteDescription(offer): ${peerConnection.signalingState}`);

        // Modificar el SDP para resolver el conflicto de extensiones RTP
        if (offer.sdp && offer.sdp.sdp) {
            offer.sdp.sdp = normalizeSdp(offer.sdp.sdp);
            console.log("🔵 SDP oferta normalizada para resolver conflictos de extensiones RTP");
        }
        
        // Establecer la descripción remota con la oferta recibida original
        console.log("🔵 Estableciendo descripción remota con la oferta recibida...");
        console.log("🔵 SDP de la oferta recibida:", offer.sdp.sdp.substring(0, 100) + "...");
        await peerConnection.setRemoteDescription(new RTCSessionDescription(offer.sdp));
        console.log("🔵 Descripción remota (oferta) establecida.");
        console.log(`🔵 Estado de señalización DESPUÉS de setRemoteDescription(offer): ${peerConnection.signalingState}`);
        
        // Procesar candidatos ICE almacenados en buffer
        if (iceCandidatesBuffer.length > 0) {
            console.log(`Procesando ${iceCandidatesBuffer.length} candidatos ICE almacenados en buffer`);
            for (const candidate of iceCandidatesBuffer) {
                try {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidate.candidate));
                } catch (err) {
                    console.warn("Error al procesar candidato ICE del buffer:", err);
                }
            }
            // Limpiar buffer
            iceCandidatesBuffer = [];
        }
        
        // Verificar que seguimos en el estado correcto para crear una respuesta
        if (peerConnection.signalingState !== 'have-remote-offer') {
            console.warn(`🟡 Estado de señalización (${peerConnection.signalingState}) no es 'have-remote-offer'. No se creará respuesta.`);
            // Quizás necesitemos reiniciar si estamos en un estado inesperado
            if (peerConnection.signalingState === 'stable') {
                console.log("🟡 Estado estable, posible oferta duplicada o tardía. Ignorando.");
            }
            return;
        }
        
        // Crear una respuesta
        console.log("🔵 Creando respuesta SDP...");
        const answer = await peerConnection.createAnswer();
        console.log("🔵 Respuesta SDP creada:", answer.sdp.substring(0, 100) + "...");
        
        // Verificar estado antes de setLocalDescription(answer)
        console.log(`🔵 Estado de señalización ANTES de setLocalDescription(answer): ${peerConnection.signalingState}`);
        
        // Establecer la descripción local con la respuesta original sin modificar
        await peerConnection.setLocalDescription(answer);
        console.log("🔵 Descripción local (respuesta) establecida.");
        console.log(`🔵 Estado de señalización DESPUÉS de setLocalDescription(answer): ${peerConnection.signalingState}`);
        
        // Enviar la respuesta al otro par
        console.log("🔵 Enviando señal tipo 'answer' al servidor...");
        sendSignal({
            type: 'answer',
            sdp: peerConnection.localDescription // Usar la descripción local recién establecida
        });
        
        console.log("🔵 Respuesta enviada al otro par.");
    } catch (error) {
        console.error("❌ Error al procesar oferta:", error);
        showError(`Error al procesar oferta: ${error.message}`);
        await resetConnection();
    }
}

// Procesar respuesta recibida
async function processAnswer(answer) {
    try {
        console.log("🔵 Procesando respuesta SDP RECIBIDA...");
        // Log del estado del iniciador al procesar respuesta
        console.log(`🔵 Estado isInitiator al llamar processAnswer: ${isInitiator}`);
        
        // Verificar si la conexión peer está inicializada
        if (!peerConnection) {
            console.error("❌ Error: No hay peerConnection para procesar la respuesta.");
            return;
        }
        
        // Verificar el estado de señalización antes de setRemoteDescription
        console.log(`🔵 Estado de señalización ANTES de setRemoteDescription(answer): ${peerConnection.signalingState}`);
        
        // Modificar el SDP para resolver el conflicto de extensiones RTP
        if (answer.sdp && answer.sdp.sdp) {
            answer.sdp.sdp = normalizeSdp(answer.sdp.sdp);
            console.log("🔵 SDP respuesta normalizada para resolver conflictos de extensiones RTP");
        }
        
        // Solo establecer respuesta si estamos esperando una (have-local-offer)
        if (peerConnection.signalingState !== 'have-local-offer') {
            console.warn(`🟡 Estado de señalización (${peerConnection.signalingState}) no es 'have-local-offer'. Ignorando respuesta.`);
            // Podría ser una respuesta duplicada o tardía
            return;
        }
        
        // Establecer la descripción remota con la respuesta recibida
        console.log("🔵 Estableciendo descripción remota con la respuesta recibida...");
        console.log("🔵 SDP de la respuesta recibida:", answer.sdp.sdp.substring(0, 100) + "...");
        await peerConnection.setRemoteDescription(new RTCSessionDescription(answer.sdp));
        console.log("🔵 Descripción remota (respuesta) establecida.");
        console.log(`🔵 Estado de señalización DESPUÉS de setRemoteDescription(answer): ${peerConnection.signalingState}`);
        
        // Procesar candidatos ICE almacenados en buffer
        if (iceCandidatesBuffer.length > 0) {
            console.log(`Procesando ${iceCandidatesBuffer.length} candidatos ICE almacenados en buffer`);
            for (const candidate of iceCandidatesBuffer) {
                try {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidate.candidate));
                } catch (err) {
                    console.warn("Error al procesar candidato ICE del buffer:", err);
                }
            }
            // Limpiar buffer
            iceCandidatesBuffer = [];
        }
    } catch (error) {
        console.error("❌ Error al procesar respuesta:", error);
        showError(`Error al procesar respuesta: ${error.message}`);
        await resetConnection();
    }
}

// Procesar candidato ICE recibido
async function processIceCandidate(iceCandidate) {
    try {
        console.log("Procesando candidato ICE recibido...");
        
        // Verificar si la conexión peer está inicializada
        if (!peerConnection) {
            console.error("Error: No hay conexión peer inicializada para procesar el candidato ICE");
            return;
        }
        
        // Verificar si tenemos remoteDescription antes de añadir candidatos ICE
        if (!peerConnection.remoteDescription) {
            console.log("No hay remoteDescription, almacenando candidato ICE en buffer");
            iceCandidatesBuffer.push(iceCandidate);
            return;
        }
        
        // Añadir el candidato ICE recibido a la conexión peer
        await peerConnection.addIceCandidate(new RTCIceCandidate(iceCandidate.candidate));
        console.log("Candidato ICE añadido a la conexión peer");
    } catch (error) {
        console.error("Error al procesar candidato ICE:", error);
        // No mostrar error al usuario, ya que algunos candidatos pueden fallar normalmente
    }
}

// Enviar una señal al otro par
async function sendSignal(data) {
    try {
        console.log("Enviando señal:", data.type);
        
        // Añadir identificador de sala a la señal
        const signalData = {
            sala_id: salaId,
            data: JSON.stringify(data)
        };
        
        // Enviar la señal al servidor
        const response = await fetch('/ruiz/modules/api/send_signal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(signalData)
        });
        
        // Verificar respuesta
        const result = await response.json();
        
        if (!result.success) {
            console.error("Error al enviar señal:", result.message);
        } else {
            console.log("Señal enviada correctamente");
        }
    } catch (error) {
        console.error("Error al enviar señal:", error);
    }
}

// Función para enviar heartbeat y verificar conexión
function startHeartbeat() {
    console.log("Iniciando sistema de heartbeat...");
    
    // Cancelar intervalo existente
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
    }
    
    // Enviar heartbeat inicial
    sendHeartbeat();
    
    // Configurar intervalo de heartbeat (cada 5 segundos)
    heartbeatInterval = setInterval(() => {
        sendHeartbeat();
        checkParticipantStatus();
    }, 5000);
}

// Enviar señal de heartbeat
async function sendHeartbeat() {
    lastHeartbeatSent = Date.now();
    
    try {
        // Enviar heartbeat con información de rol
        sendSignal({
            type: 'heartbeat',
            timestamp: lastHeartbeatSent,
            isAdmin: isAdmin,
            isInitiator: isInitiator
        });
    } catch (error) {
        console.error("Error al enviar heartbeat:", error);
    }
}

// Procesar heartbeat recibido
function processHeartbeat(heartbeatData) {
    console.log("Heartbeat recibido del otro participante:", heartbeatData);
    
    // Actualizar timestamp del último heartbeat recibido
    lastHeartbeatReceived = Date.now();
    
    // Marcar que el otro participante está conectado
    if (!participantReady) {
        participantReady = true;
        showMessage("El otro participante está conectado", "success");
        console.log("El otro participante está conectado y listo");
        
        // Si somos el iniciador y tenemos una conexión pero no hay comunicación establecida,
        // intentar reiniciar la negociación
        if (isInitiator && peerConnection && 
            peerConnection.iceConnectionState !== 'connected' && 
            peerConnection.iceConnectionState !== 'completed') {
            
            console.log("Detectado participante pero sin conexión establecida, reiniciando negociación...");
            setTimeout(() => {
                if (peerConnection && 
                    peerConnection.iceConnectionState !== 'connected' && 
                    peerConnection.iceConnectionState !== 'completed') {
                    
                    console.log("Reiniciando negociación WebRTC...");
                    createOffer();
                }
            }, 1000);
        }
    }
    
    // Actualizar UI para mostrar el estado del otro participante
    updateConnectionStatusUI();
}

// Verificar el estado del otro participante
function checkParticipantStatus() {
    const now = Date.now();
    
    // Si han pasado más de 15 segundos desde el último heartbeat recibido,
    // considerar al otro participante como desconectado
    if (participantReady && (now - lastHeartbeatReceived > 15000)) {
        participantReady = false;
        console.log("El otro participante parece haberse desconectado");
        showMessage("Se ha perdido conexión con el otro participante", "warning");
        
        // Actualizar UI
        updateConnectionStatusUI();
    }
}

// Actualizar UI según el estado de conexión
function updateConnectionStatusUI() {
    // Crear o actualizar el indicador de estado del participante
    let statusIndicator = document.getElementById('participantStatus');
    
    if (!statusIndicator) {
        statusIndicator = document.createElement('div');
        statusIndicator.id = 'participantStatus';
        statusIndicator.style.position = 'absolute';
        statusIndicator.style.top = '10px';
        statusIndicator.style.left = '10px';
        statusIndicator.style.padding = '5px 10px';
        statusIndicator.style.borderRadius = '4px';
        statusIndicator.style.color = 'white';
        statusIndicator.style.fontWeight = 'bold';
        statusIndicator.style.zIndex = '1000';
        
        const videoContainer = document.querySelector('.video-container');
        if (videoContainer) {
            videoContainer.appendChild(statusIndicator);
        } else {
            document.body.appendChild(statusIndicator);
        }
    }
    
    // Combinar estado del heartbeat y estado de la conexión ICE
    let statusText = '';
    let statusColor = '';
    
    if (!participantReady) {
        statusText = 'Esperando al otro participante...';
        statusColor = 'rgba(255, 193, 7, 0.7)'; // Amarillo semi-transparente
    } else {
        if (peerConnection) {
            switch (peerConnection.iceConnectionState) {
                case 'checking':
                    statusText = 'Participante conectado - Estableciendo conexión...';
                    statusColor = 'rgba(255, 193, 7, 0.7)'; // Amarillo
                    break;
                case 'connected':
                case 'completed':
                    if (remoteStream.getTracks().length > 0) {
                        statusText = 'Conexión establecida';
                        statusColor = 'rgba(40, 167, 69, 0.7)'; // Verde
                    } else {
                        statusText = 'Conexión establecida - Esperando video...';
                        statusColor = 'rgba(255, 193, 7, 0.7)'; // Amarillo
                    }
                    break;
                case 'disconnected':
                case 'failed':
                    statusText = 'Problemas de conexión - Reconectando...';
                    statusColor = 'rgba(220, 53, 69, 0.7)'; // Rojo
                    break;
                default:
                    statusText = 'Participante conectado';
                    statusColor = 'rgba(0, 123, 255, 0.7)'; // Azul
            }
        } else {
            statusText = 'Participante conectado - Preparando conexión';
            statusColor = 'rgba(0, 123, 255, 0.7)'; // Azul
        }
    }
    
    // Actualizar el indicador
    statusIndicator.textContent = statusText;
    statusIndicator.style.backgroundColor = statusColor;
}

// Iniciar el polling para recibir señales
function startSignalPolling() {
    console.log("Iniciando polling de señales...");
    
    // Cancelar cualquier intervalo existente
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    
    // Función para realizar el polling
    const pollSignals = async () => {
        if (!isPollingActive) return;
        
        try {
            // Solicitar nuevas señales
            const url = `/ruiz/modules/api/get_signals.php?sala_id=${salaId}&last_id=${lastSignalId}`;
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success && result.signals && result.signals.length > 0) {
                console.log(`Recibidas ${result.signals.length} nuevas señales`);
                
                // Procesar cada señal recibida
                for (const signal of result.signals) {
                    // Actualizar el último ID de señal procesado
                    if (signal.id > lastSignalId) {
                        lastSignalId = signal.id;
                    }
                    
                    // Verificar si ya procesamos esta señal
                    const signalUniqueId = `${signal.id}`;
                    if (processedSignals.has(signalUniqueId)) {
                        console.log(`Señal ${signalUniqueId} ya procesada, ignorando...`);
                        continue;
                    }
                    
                    // Marcar como procesada
                    processedSignals.add(signalUniqueId);
                    
                    // Mantener el tamaño del conjunto de señales procesadas
                    if (processedSignals.size > 100) {
                        // Eliminar elementos antiguos si el conjunto crece demasiado
                        const iterator = processedSignals.values();
                        processedSignals.delete(iterator.next().value);
                    }
                    
                    // Parsear los datos de la señal
                    const signalData = JSON.parse(signal.data);
                    
                    // Procesar según el tipo de señal
                    switch (signalData.type) {
                        case 'offer':
                            console.log("Oferta recibida");
                            await processOffer(signalData);
                            break;
                        case 'answer':
                            console.log("Respuesta recibida");
                            await processAnswer(signalData);
                            break;
                        case 'ice-candidate':
                            console.log("Candidato ICE recibido");
                            await processIceCandidate(signalData);
                            break;
                        case 'heartbeat':
                            console.log("Heartbeat recibido");
                            processHeartbeat(signalData);
                            break;
                        default:
                            console.log("Señal desconocida recibida:", signalData.type);
                    }
                }
            }
        } catch (error) {
            console.error("Error al realizar polling de señales:", error);
        }
    };
    
    // Realizar polling inmediatamente y luego cada 2 segundos
    pollSignals();
    pollingInterval = setInterval(pollSignals, 2000);
    
    // Iniciar sistema de heartbeat
    startHeartbeat();
}

// Colgar la llamada
function hangUp() {
    console.log("Colgando llamada...");
    
    // Detener el polling de señales
    isPollingActive = false;
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    
    // Detener todos los tracks del stream local
    if (localStream) {
        localStream.getTracks().forEach(track => {
            track.stop();
        });
    }
    
    // Cerrar la conexión peer
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    
    // Redirigir a la página anterior o mostrar mensaje de fin de llamada
    document.getElementById('callEnded').style.display = 'block';
    
    // Opcional: redirigir después de un tiempo
    setTimeout(() => {
        window.location.href = '../';
    }, 3000);
}

// Silenciar/activar micrófono
function toggleMute() {
    if (localStream) {
        const audioTracks = localStream.getAudioTracks();
        
        if (audioTracks.length > 0) {
            const isEnabled = !audioTracks[0].enabled;
            audioTracks[0].enabled = isEnabled;
            
            // Actualizar el icono del botón
            const muteBtn = document.getElementById('muteBtn');
            if (muteBtn) {
                if (isEnabled) {
                    muteBtn.innerHTML = '<i class="fas fa-microphone"></i>';
                    muteBtn.classList.remove('btn-danger');
                    muteBtn.classList.add('btn-secondary');
                } else {
                    muteBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
                    muteBtn.classList.remove('btn-secondary');
                    muteBtn.classList.add('btn-danger');
                }
            }
            
            console.log(`Micrófono ${isEnabled ? 'activado' : 'silenciado'}`);
        }
    }
}

// Activar/desactivar cámara
function toggleVideo() {
    if (localStream) {
        const videoTracks = localStream.getVideoTracks();
        
        if (videoTracks.length > 0) {
            const isEnabled = !videoTracks[0].enabled;
            videoTracks[0].enabled = isEnabled;
            
            // Actualizar el icono del botón
            const videoBtn = document.getElementById('videoBtn');
            if (videoBtn) {
                if (isEnabled) {
                    videoBtn.innerHTML = '<i class="fas fa-video"></i>';
                    videoBtn.classList.remove('btn-danger');
                    videoBtn.classList.add('btn-secondary');
                } else {
                    videoBtn.innerHTML = '<i class="fas fa-video-slash"></i>';
                    videoBtn.classList.remove('btn-secondary');
                    videoBtn.classList.add('btn-danger');
                }
            }
            
            console.log(`Cámara ${isEnabled ? 'activada' : 'desactivada'}`);
        }
    }
}

// Actualizar indicadores de estado de medios
function updateMediaStatus() {
    if (localStream) {
        const audioEnabled = localStream.getAudioTracks().length > 0 && localStream.getAudioTracks()[0].enabled;
        const videoEnabled = localStream.getVideoTracks().length > 0 && localStream.getVideoTracks()[0].enabled;
        
        // Actualizar botones según el estado actual
        const muteBtn = document.getElementById('muteBtn');
        if (muteBtn) {
            if (audioEnabled) {
                muteBtn.innerHTML = '<i class="fas fa-microphone"></i>';
                muteBtn.classList.remove('btn-danger');
                muteBtn.classList.add('btn-secondary');
            } else {
                muteBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
                muteBtn.classList.remove('btn-secondary');
                muteBtn.classList.add('btn-danger');
            }
        }
        
        const videoBtn = document.getElementById('videoBtn');
        if (videoBtn) {
            if (videoEnabled) {
                videoBtn.innerHTML = '<i class="fas fa-video"></i>';
                videoBtn.classList.remove('btn-danger');
                videoBtn.classList.add('btn-secondary');
            } else {
                videoBtn.innerHTML = '<i class="fas fa-video-slash"></i>';
                videoBtn.classList.remove('btn-secondary');
                videoBtn.classList.add('btn-danger');
            }
        }
    }
}

// Mostrar mensaje de error
function showError(message) {
    console.error(message);
    
    const errorElem = document.getElementById('errorMessage');
    if (errorElem) {
        errorElem.textContent = message;
        errorElem.style.display = 'block';
        
        // Ocultar después de 10 segundos
        setTimeout(() => {
            errorElem.style.display = 'none';
        }, 10000);
    }
    
    // También mostrar en la consola para debugging
    console.error(message);
}

// Mostrar mensaje con diferentes estilos
function showMessage(message, type = "info") {
    console.log(message);
    
    const messageElement = document.getElementById('messageOverlay');
    if (!messageElement) {
        // Crear elemento si no existe
        const overlay = document.createElement('div');
        overlay.id = 'messageOverlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '20px';
        overlay.style.left = '50%';
        overlay.style.transform = 'translateX(-50%)';
        overlay.style.padding = '10px 20px';
        overlay.style.borderRadius = '5px';
        overlay.style.zIndex = '1000';
        overlay.style.transition = 'opacity 0.5s';
        document.body.appendChild(overlay);
    }
    
    const msgElement = document.getElementById('messageOverlay');
    
    // Establecer color según tipo
    switch (type) {
        case 'success':
            msgElement.style.backgroundColor = 'rgba(40, 167, 69, 0.9)';
            break;
        case 'warning':
            msgElement.style.backgroundColor = 'rgba(255, 193, 7, 0.9)';
            msgElement.style.color = 'black';
            break;
        case 'error':
            msgElement.style.backgroundColor = 'rgba(220, 53, 69, 0.9)';
            break;
        default:
            msgElement.style.backgroundColor = 'rgba(0, 123, 255, 0.9)';
    }
    
    msgElement.style.color = type === 'warning' ? 'black' : 'white';
    msgElement.textContent = message;
    msgElement.style.display = 'block';
    msgElement.style.opacity = '1';
    
    // Ocultar después de 5 segundos
    setTimeout(() => {
        msgElement.style.opacity = '0';
        setTimeout(() => {
            msgElement.style.display = 'none';
        }, 500);
    }, 5000);
}

// Manejar desconexiones
function handleDisconnection() {
    reconnectAttempts++;
    console.log(`Intento de reconexión ${reconnectAttempts}/3`);
    
    if (reconnectAttempts <= 3) {
        setTimeout(() => {
            console.log("Intentando reconectar mediante handleDisconnection...");
            if (isInitiator) {
                createOffer();
            } else {
                // Si no somos el iniciador pero la conexión está fallida por mucho tiempo, 
                // intentamos reiniciar completamente
                if (reconnectAttempts >= 2) {
                    resetConnection();
                }
            }
        }, 2000 * reconnectAttempts);
    } else {
        console.log("Máximo de intentos de reconexión alcanzado, reiniciando conexión");
        resetConnection();
    }
} 