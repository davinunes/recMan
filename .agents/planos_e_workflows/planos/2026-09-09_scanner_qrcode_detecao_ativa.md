# Plano de Implementação: Scanner de QR Code com Detecção Ativa (Live Video Stream) e Fallback Cross-Platform

**Data**: 2026-09-09
**Tópico**: Registro de Odômetros / Leitura de Notas Fiscais via QR Code Ativo

---

## 1. Objetivo
Substituir o fluxo atual de captura manual de fotos para leitura de QR Code por um **Scanner de Detecção Ativa (Live Stream)**. O scanner lê continuamente os frames da câmera até obter a primeira decodificação legível, fornecendo feedback visual com moldura verde, resposta tátil/sonora e extração automática de uma foto nítida.

---

## 2. Arquitetura da Solução Híbrida (Android + iOS)

1. **Camada de Câmera (`getUserMedia`)**:
   - Resolução HD (1280x720 ou 1920x1080).
   - `facingMode: "environment"` (câmera traseira).
   - Suporte a iluminação/torch (lanterna) se o hardware permitir.

2. **Engine de Decodificação Duplo**:
   - **Android (Chrome/Edge)**: API Nativa `window.BarcodeDetector` (Aceleração por hardware C++, ultrarrápido).
   - **iOS (Safari/WKWebView)**: Engine em JavaScript (`jsQR` ou `html5-qrcode`) executado via `<canvas>` oculto a 15-20 FPS.

3. **Overlay & UI Feedback**:
   - Mira com cantos destacados (estilo visual scanner industrial).
   - Animação de transição para **Verde Neon** ao decodificar.
   - Bip de confirmação via `Web Audio API` (independente de arquivos de som externos).
   - Vibração via `navigator.vibrate([100, 50, 100])`.

4. **Captura Nítida Automática**:
   - No momento do disparo de sucesso, converte o frame do canvas em `Blob`/`dataURL`.
   - Congela o feed para evitar múltiplos disparos.

---

## 3. Estrutura de Arquivos

- `.agents/planos_e_workflows/planos/2026-09-09_scanner_qrcode_detecao_ativa.md` (Este documento)
- `C:\Users\Davi\.gemini\antigravity-ide\brain\dc484ef1-16e4-4f56-9f7c-7dfd3e69e892\implementation_plan.md` (Artefato de planejamento)
