# Walkthrough: Substituição de Ícones e Favicon (Logo Conselho)

**Data**: 2026-10-07  
**Status**: Concluído  

---

## 🛠️ Alterações Efetuadas

Os favicons e ícones globais do sistema recMan foram atualizados a partir dos novos arquivos da marca do Conselho localizados em `favicon/logo-conselho/`.

### 1. Arquivos Substituídos em `favicon/` e na Raiz (`/`)
- `favicon.ico` (substituído pelo `convertico-b2462da2-4833-45ba-8151-c2e17b509380.ico`)
- `favicon-16x16.png` (substituído pelo `16-16.png`)
- `favicon-32x32.png` (substituído pelo `32-32.png`)
- `apple-touch-icon.png` (substituído pelo `256-256.png`)
- `android-chrome-192x192.png` (substituído pelo `256-256.png`)
- `android-chrome-512x512.png` (substituído pelo `256-256.png`)

### 2. Módulos Preservados (Conforme Solicitado)
- Todos os ícones do módulo **Portal de Interposição de Recursos** (`portal/` e `portal/fav_portal_recurso_conselho/`) foram integralmente mantidos sem qualquer alteração.

---

## 🔍 Como Validar no Navegador
1. Abra a aplicação principal do recMan (ex: `/index.php` ou `/forms/login.php`).
2. Recarreague forçando a limpeza do cache (`Ctrl + F5` ou `Cmd + Shift + R`).
3. Verifique se o ícone exibido na aba do navegador reflete o novo logo do Conselho.
4. Para confirmar a preservação do Portal, acesse `/portal/index.php` e certifique-se de que o favicon exclusivo do portal permanece inalterado.
