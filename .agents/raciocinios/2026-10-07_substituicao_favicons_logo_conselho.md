# Raciocínio: Substituição de Ícones e Favicon pelos do Logo Conselho

**Data**: 2026-10-07  
**Solicitante**: Usuário  
**Objetivo**: Substituir todos os ícones e favicons do sistema recMan (exceto os do diretório `portal/`) pelos novos ícones presentes na pasta `favicon/logo-conselho/`.

---

## 1. Mapeamento de Arquivos Fonte e Destino

### Origem (`favicon/logo-conselho/`):
- `16-16.png` (16x16)
- `32-32.png` (32x32)
- `128-128.png` (128x128)
- `256-256.png` (256x256)
- `convertico-b2462da2-4833-45ba-8151-c2e17b509380.ico` (multi-res .ico)

### Destinos a Substituir (em `favicon/` e na raiz `/`):
1. `favicon-16x16.png` <- `favicon/logo-conselho/16-16.png`
2. `favicon-32x32.png` <- `favicon/logo-conselho/32-32.png`
3. `apple-touch-icon.png` <- `favicon/logo-conselho/256-256.png`
4. `android-chrome-192x192.png` <- `favicon/logo-conselho/256-256.png`
5. `android-chrome-512x512.png` <- `favicon/logo-conselho/256-256.png`
6. `favicon.ico` <- `favicon/logo-conselho/convertico-b2462da2-4833-45ba-8151-c2e17b509380.ico`

---

## 2. Restrições e Preservação
- **NÃO alterar** os favicons de `portal/` (`portal/fav_portal_recurso_conselho/`), conforme instrução explícita do usuário (`exceto os da portal`).
- Manter o padrão do repositório onde os favicons ficam guardados em `favicon/` e copiados para a raiz do repositório para servir via webserver `/favicon.ico`, `/favicon-32x32.png`, etc.

---

## 3. Passos da Execução
1. Copiar e sobrescrever os arquivos correspondentes na pasta `favicon/`.
2. Copiar e sobrescrever os mesmos arquivos na raiz do projeto (`d:\dev\github\recMan\`).
3. Registrar o plano e o walkthrough conforme a skill `gestao_raciocinios_planos`.
