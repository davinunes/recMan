# Raciocínio de Diagnóstico e Implementação: Favicons e Ícones do Portal

**Data**: 22/09/2026
**Tópico**: Vinculação dos favicons e inclusão do ícone/logo no Portal de Interposição de Recursos.

---

## 1. Contexto & Diagnóstico
O usuário adicionou uma pasta com favicons e ícones (`portal/fav_portal_recurso_conselho/`) contendo os arquivos:
- `android-chrome-192x192.png`
- `android-chrome-512x512.png`
- `apple-touch-icon.png`
- `favicon-16x16.png`
- `favicon-32x32.png`
- `site.webmanifest`
Além de ter `portal/favicon.ico` na raiz do portal.

Anteriormente, em `portal/index.php`, as tags `<link rel="*">` apontavam para a raiz do domínio (`/apple-touch-icon.png`, `/site.webmanifest`, etc.), o que causava erro de 404 quando o portal rodava em subdiretórios ou com estruturas relativas.

---

## 2. Decisão Técnica
1. **Atualização das tags em `portal/index.php`**:
   - Ajustar os caminhos das tags `<link>` para apontar para a pasta `fav_portal_recurso_conselho/` usando caminhos relativos.
   - Manter a referência a `favicon.ico`.
2. **Atualização do `site.webmanifest`**:
   - Definir os caminhos de ícone como relativos ao próprio arquivo manifest (`android-chrome-192x192.png` e `android-chrome-512x512.png`).
   - Definir os atributos `name` ("Central de Recursos - Conselho"), `short_name` ("Recursos") e `theme_color` ("#1e3a8a").
3. **Melhoria de UI no Header do Portal (`portal/index.php`)**:
   - Adicionar o logo/ícone (`fav_portal_recurso_conselho/android-chrome-192x192.png`) centralizado logo acima do título principal no cabeçalho do portal, trazendo identidade visual ao assistente de recursos.
