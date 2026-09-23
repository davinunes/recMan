# Walkthrough: Implementação de Favicons e Ícone no Portal de Recursos

**Data**: 22/09/2026
**Módulo**: Portal de Interposição de Recursos (`portal/`)

---

## 🛠️ Alterações Efetuadas

### 1. Atualização do [index.php](file:///e:/DEV/recMan/portal/index.php)
- Configuração correta das tags no `<head>`:
  - `apple-touch-icon` (180x180) -> `fav_portal_recurso_conselho/apple-touch-icon.png`
  - `favicon-32x32.png` -> `fav_portal_recurso_conselho/favicon-32x32.png`
  - `favicon-16x16.png` -> `fav_portal_recurso_conselho/favicon-16x16.png`
  - `shortcut icon` -> `favicon.ico`
  - `manifest` -> `fav_portal_recurso_conselho/site.webmanifest`
- Adição do elemento de imagem no **Header** do portal com o ícone do condomínio/conselho (`android-chrome-192x192.png`), arredondado e com sombra sutil.

### 2. Atualização do [site.webmanifest](file:///e:/DEV/recMan/portal/fav_portal_recurso_conselho/site.webmanifest)
- Ajustados os nomes da aplicação: `Central de Recursos - Conselho`
- Ajustadas as imagens de ícones PWA (`192x192` e `512x512`) para caminhos relativos ao arquivo do manifesto.

---

## 🔍 Como Validar
1. Acesse o portal no navegador (`/portal/index.php`).
2. Verifique a aba do navegador: o favicon personalizado do Conselho deve ser exibido.
3. Observe o cabeçalho do portal: o ícone oficial da Central de Recursos agora é exibido acima do título principal.
4. Abra as ferramentas do desenvolvedor (F12 > Application > Manifest) para confirmar que o `site.webmanifest` é carregado sem erros de 404.
