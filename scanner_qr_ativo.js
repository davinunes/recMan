/**
 * QrActiveScanner - Módulo Reutilizável de Detecção Ativa de QR Code (Video Stream Contínuo)
 * 
 * Recursos:
 * - Leitura contínua frame-a-frame (zero cliques do usuário)
 * - Engine Duplo: Native BarcodeDetector (Chrome/Android) + Fallback jsQR (Safari/iOS)
 * - Moldura Ativa com transição para Verde Neon ao identificar o QR Code
 * - Feedback multissensorial: Bip de áudio sintético + Vibração tátil
 * - Congelamento automático e extração de foto nítida (Canvas DataURL/Blob)
 * - Suporte a controle de lanterna/torch e troca de câmera
 */

class QrActiveScanner {
    /**
     * @param {Object} config
     * @param {string|HTMLElement} config.container Elemento container onde o scanner será renderizado
     * @param {Function} config.onSuccess Callback(qrText, photoDataUrl) invocado na leitura bem sucedida
     * @param {Function} [config.onError] Callback(error) para tratamento de erros
     * @param {string} [config.facingMode="environment"] Câmera traseira ("environment") ou frontal ("user")
     * @param {number} [config.fps=15] Taxa de varredura por segundo (frames por segundo)
     */
    constructor(config = {}) {
        this.container = typeof config.container === 'string' 
            ? document.getElementById(config.container) 
            : config.container;

        if (!this.container) {
            throw new Error("[QrActiveScanner] Elemento container inválido ou não encontrado.");
        }

        this.onSuccess = config.onSuccess || function() {};
        this.onError = config.onError || console.error;
        this.facingMode = config.facingMode || 'environment';
        this.fps = config.fps || 15;
        
        this.stream = null;
        this.videoEl = null;
        this.canvasEl = null;
        this.canvasCtx = null;
        this.overlayEl = null;
        this.animFrameId = null;
        this.isScanning = false;
        this.nativeDetector = null;
        this.torchActive = false;
    }

    /**
     * Inicializa a câmera e inicia a varredura contínua
     */
    async start() {
        if (this.isScanning) return;
        this.isScanning = true;

        try {
            // 1. Criar e configurar a interface HTML do scanner
            this._setupUI();

            // 2. Solicitar acesso à câmera com configurações ideais de foco e resolução
            const constraints = {
                video: {
                    facingMode: { ideal: this.facingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    focusMode: { ideal: "continuous" }
                },
                audio: false
            };

            this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            this.videoEl.srcObject = this.stream;
            
            await new Promise((resolve) => {
                this.videoEl.onloadedmetadata = () => {
                    this.videoEl.play();
                    resolve();
                };
            });

            // Ajustar o canvas ao tamanho real do vídeo
            this.canvasEl.width = this.videoEl.videoWidth || 640;
            this.canvasEl.height = this.videoEl.videoHeight || 480;

            // 3. Inicializar Engine de Leitura (Nativo ou Fallback iOS)
            if ('BarcodeDetector' in window) {
                try {
                    this.nativeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
                    console.log("[QrActiveScanner] Engine nativo BarcodeDetector ativo (Android/Chrome).");
                } catch (e) {
                    console.warn("[QrActiveScanner] BarcodeDetector nativo indisponível, usando fallback JS.", e);
                    this.nativeDetector = null;
                }
            }

            if (!this.nativeDetector) {
                await this._loadJsQrFallback();
                console.log("[QrActiveScanner] Engine de fallback JS ativo (iOS/Safari).");
            }

            // 4. Iniciar o loop de escaneamento em tempo real
            this._scanLoop();

        } catch (err) {
            this.isScanning = false;
            this._destroyUI();
            this.onError(err);
        }
    }

    /**
     * Loop contínuo de captura e decodificação frame a frame
     */
    async _scanLoop() {
        if (!this.isScanning) return;

        const intervalMs = 1000 / this.fps;
        const now = performance.now();

        if (!this._lastScanTime || now - this._lastScanTime >= intervalMs) {
            this._lastScanTime = now;

            if (this.videoEl && this.videoEl.readyState === this.videoEl.HAVE_ENOUGH_DATA) {
                // Desenhar o frame atual no canvas interno
                this.canvasCtx.drawImage(this.videoEl, 0, 0, this.canvasEl.width, this.canvasEl.height);

                let decodedData = null;

                // Tentar decodificar via Engine Nativo
                if (this.nativeDetector) {
                    try {
                        const barcodes = await this.nativeDetector.detect(this.canvasEl);
                        if (barcodes && barcodes.length > 0) {
                            decodedData = barcodes[0].rawValue;
                        }
                    } catch (e) {
                        // Ignorar falhas pontuais de detecção em frames intermediários
                    }
                } 
                // Tentar decodificar via Fallback JS (jsQR)
                else if (window.jsQR) {
                    const imageData = this.canvasCtx.getImageData(0, 0, this.canvasEl.width, this.canvasEl.height);
                    const code = window.jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: "dontInvert"
                    });
                    if (code && code.data) {
                        decodedData = code.data;
                    }
                }

                // Se obteve a leitura do QR Code no primeiro frame nítido:
                if (decodedData) {
                    this._triggerSuccess(decodedData);
                    return; // Interrompe o loop
                }
            }
        }

        this.animFrameId = requestAnimationFrame(() => this._scanLoop());
    }

    /**
     * Trata o evento de leitura bem sucedida (Feedback + Autocaptura + Parada)
     */
    _triggerSuccess(qrText) {
        this.isScanning = false;
        if (this.animFrameId) cancelAnimationFrame(this.animFrameId);

        // 1. Congela o vídeo para travar a imagem nítida
        if (this.videoEl) this.videoEl.pause();

        // 2. Extrai a foto nítida do frame do canvas
        const photoDataUrl = this.canvasEl.toDataURL('image/jpeg', 0.92);

        // 3. Feedback Visual (Moldura fica verde neon)
        if (this.overlayEl) {
            this.overlayEl.classList.add('qr-success-green');
        }

        // 4. Feedback Tátil (Vibração no Celular)
        if (navigator.vibrate) {
            try { navigator.vibrate([100, 50, 100]); } catch (e) {}
        }

        // 5. Feedback Sonoro (Bip de Confirmação Sintético)
        this._playBeepSound();

        // 6. Invoca o Callback de Sucesso após breve animação visual (250ms)
        setTimeout(() => {
            this.stop();
            this.onSuccess(qrText, photoDataUrl);
        }, 250);
    }

    /**
     * Sintetiza um som de Bip agradável usando a Web Audio API (sem carregar arquivo MP3)
     */
    _playBeepSound() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime); // Nota A5 (agudo e claro)
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start();
            osc.stop(ctx.currentTime + 0.15);
        } catch (e) {}
    }

    /**
     * Alterna a lanterna/torch caso o hardware permita
     */
    async toggleTorch() {
        if (!this.stream) return false;
        const track = this.stream.getVideoTracks()[0];
        if (!track) return false;

        const capabilities = track.getCapabilities ? track.getCapabilities() : {};
        if (!capabilities.torch) {
            console.warn("[QrActiveScanner] Lanterna/Torch não suportada neste dispositivo.");
            return false;
        }

        this.torchActive = !this.torchActive;
        await track.applyConstraints({
            advanced: [{ torch: this.torchActive }]
        });
        return this.torchActive;
    }

    /**
     * Encerra a câmera e limpa a memória
     */
    stop() {
        this.isScanning = false;
        if (this.animFrameId) {
            cancelAnimationFrame(this.animFrameId);
            this.animFrameId = null;
        }

        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }

        this._destroyUI();
    }

    /**
     * Carrega dinamicamente a biblioteca jsQR para iOS/Safari como Fallback
     */
    _loadJsQrFallback() {
        return new Promise((resolve, reject) => {
            if (window.jsQR) {
                resolve();
                return;
            }
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error("Falha ao carregar biblioteca jsQR de fallback."));
            document.head.appendChild(script);
        });
    }

    /**
     * Constrói a estrutura DOM e os estilos do Scanner com moldura
     */
    _setupUI() {
        this.container.innerHTML = '';
        this.container.style.position = 'relative';
        this.container.style.overflow = 'hidden';
        this.container.style.backgroundColor = '#000';
        this.container.style.borderRadius = '12px';
        this.container.style.minHeight = '300px';

        // Elemento Video (Feed da câmera)
        this.videoEl = document.createElement('video');
        this.videoEl.style.width = '100%';
        this.videoEl.style.height = '100%';
        this.videoEl.style.objectFit = 'cover';
        this.videoEl.style.display = 'block';
        this.videoEl.setAttribute('playsinline', 'true');
        this.videoEl.setAttribute('autoplay', 'true');
        this.videoEl.muted = true;

        // Canvas Oculto (Processamento frame a frame)
        this.canvasEl = document.createElement('canvas');
        this.canvasEl.style.display = 'none';
        this.canvasCtx = this.canvasEl.getContext('2d', { willReadFrequently: true });

        // Overlay da Moldura Ativa (Mira + Animação de sucesso)
        this.overlayEl = document.createElement('div');
        this.overlayEl.className = 'qr-scanner-overlay';
        this.overlayEl.innerHTML = `
            <style>
                .qr-scanner-overlay {
                    position: absolute;
                    top: 0; left: 0; right: 0; bottom: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    pointer-events: none;
                }
                .qr-target-box {
                    width: 230px;
                    height: 230px;
                    position: relative;
                    border: 2px dashed rgba(255, 255, 255, 0.6);
                    border-radius: 16px;
                    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.45);
                    transition: all 0.25s ease-in-out;
                }
                .qr-target-box::before, .qr-target-box::after {
                    content: '';
                    position: absolute;
                    width: 24px;
                    height: 24px;
                    border-color: #ffffff;
                    border-style: solid;
                    transition: all 0.25s ease-in-out;
                }
                .qr-target-box::before {
                    top: -2px; left: -2px;
                    border-width: 4px 0 0 4px;
                    border-top-left-radius: 14px;
                }
                .qr-target-box::after {
                    bottom: -2px; right: -2px;
                    border-width: 0 4px 4px 0;
                    border-bottom-right-radius: 14px;
                }
                /* Estado de Sucesso: Moldura Verde Neon */
                .qr-scanner-overlay.qr-success-green .qr-target-box {
                    border-color: #00E676 !important;
                    border-style: solid !important;
                    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.65), 0 0 25px #00E676 !important;
                    transform: scale(1.04);
                }
                .qr-scanner-overlay.qr-success-green .qr-target-box::before,
                .qr-scanner-overlay.qr-success-green .qr-target-box::after {
                    border-color: #00E676 !important;
                }
                .qr-scan-line {
                    position: absolute;
                    width: 100%;
                    height: 2px;
                    background: linear-gradient(90deg, transparent, #00E676, transparent);
                    top: 0;
                    animation: scanAnimation 2s infinite ease-in-out;
                }
                @keyframes scanAnimation {
                    0% { top: 5%; opacity: 0.8; }
                    50% { top: 90%; opacity: 1; }
                    100% { top: 5%; opacity: 0.8; }
                }
                .qr-scanner-overlay.qr-success-green .qr-scan-line {
                    display: none;
                }
            </style>
            <div class="qr-target-box">
                <div class="qr-scan-line"></div>
            </div>
        `;

        this.container.appendChild(this.videoEl);
        this.container.appendChild(this.canvasEl);
        this.container.appendChild(this.overlayEl);
    }

    /**
     * Limpa o DOM do container
     */
    _destroyUI() {
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

// Exportação global para uso em formulários e scripts PHP
window.QrActiveScanner = QrActiveScanner;
