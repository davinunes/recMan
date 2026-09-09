# Raciocínio de Diagnóstico e Design: Scanner de QR Code Ativo com Fallback

**Data**: 2026-09-09
**Problema Relatado**: No registro de odômetros/notas fiscais, técnicos tiravam foto manual que frequentemente saía borrada por falta de foco ou trepidação, falhando a leitura pós-foto.
**Solução Proposta**: Substituição do modelo estático "Tirar foto -> Processar" por "Live Video Stream -> Decodificação em Tempo Real -> Disparo Ativo em Moldura Verde -> Autocaptura Nítida".

---

## Análise Técnica de Compatibilidade

### 1. Desafio Android vs. iOS
- `BarcodeDetector` é nativo do Chromium/Android, operando em C++ diretamente no hardware da câmera.
- Safari/iOS (Webkit) não habilita `BarcodeDetector` nativamente por padrão.
- **Decisão de Design**: Implementar a detecção de recurso com fallback (`feature detection` + `jsQR`).

### 2. Fluxo de Execução
1. `navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })`
2. Testar `'BarcodeDetector' in window`.
3. Se `true`: Usar loop assíncrono com `barcodeDetector.detect(videoElement)`.
4. Se `false`: Usar `<canvas>` em memória + `jsQR(imageData.data, width, height)`.
5. Em ambos os casos, ao obter `code.data`:
   - Ativar classe CSS `.qr-success-green` no container da câmera.
   - Disparar `navigator.vibrate([100, 50, 100])` e sintetizar um bip audível em `AudioContext`.
   - Congelar o vídeo e gerar `canvas.toDataURL('image/jpeg', 0.9)` para garantir que a foto anexada à nota esteja perfeitamente legível.
