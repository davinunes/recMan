# Plano de Implementação: Substituição de Favicons (Logo Conselho)

**Data**: 2026-10-07  
**Status**: Em Execução  

## 🎯 Objetivo
Atualizar todos os favicons e ícones globais da aplicação recMan com os novos ativos da pasta `favicon/logo-conselho/`, preservando os favicons do Portal de Recursos (`portal/`).

---

## 📂 Arquivos Envolvidos

### Origem:
- `favicon/logo-conselho/16-16.png`
- `favicon/logo-conselho/32-32.png`
- `favicon/logo-conselho/256-256.png`
- `favicon/logo-conselho/convertico-b2462da2-4833-45ba-8151-c2e17b509380.ico`

### Destinos (Pasta `favicon/` & Raiz `/`):
- `favicon.ico`
- `favicon-16x16.png`
- `favicon-32x32.png`
- `apple-touch-icon.png`
- `android-chrome-192x192.png`
- `android-chrome-512x512.png`

---

## 🛠️ Passos de Execução
1. Copiar `favicon/logo-conselho/convertico-b2462da2-4833-45ba-8151-c2e17b509380.ico` -> `favicon/favicon.ico` e `favicon.ico`.
2. Copiar `favicon/logo-conselho/16-16.png` -> `favicon/favicon-16x16.png` e `favicon-16x16.png`.
3. Copiar `favicon/logo-conselho/32-32.png` -> `favicon/favicon-32x32.png` e `favicon-32x32.png`.
4. Copiar `favicon/logo-conselho/256-256.png` -> `favicon/apple-touch-icon.png`, `favicon/android-chrome-192x192.png`, `favicon/android-chrome-512x512.png` e réplicas na raiz.
5. Garantir que **nenhum** arquivo na pasta `portal/` seja modificado.
6. Gerar o walkthrough de confirmação em `.agents/planos_e_workflows/walkthroughs/2026-10-07_substituicao_favicons_logo_conselho.md`.
