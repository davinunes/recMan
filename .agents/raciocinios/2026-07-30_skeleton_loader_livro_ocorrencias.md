# Raciocínio & Diagnóstico: Skeleton Screen Loader Restrito à Área de Conteúdo (<main>)

**Data:** 30/07/2026  
**Projeto:** recMan  
**Componente:** `meu.js` / `livroDeOcorrencias.php` / Navegação Global do Menu

---

## 1. Ajuste de Experiência Solicitado pelo Usuário
- O usuário apontou que congelar/bloquear a tela inteira (incluindo a barra de navegação superior e a barra lateral de menu `.sidenav`) era excessivo.
- Se o carregamento da API VDS demorasse, o usuário ficava impossibilitado de desistir da ação e mudar de menu.

---

## 2. Solução Implementada

### A. Escopo Restrito do Overlay (`#vds-content-skeleton-overlay`)
- Alterado o container do Skeleton Overlay em `meu.js`.
- Em vez de usar `position: fixed` cobrindo o `body` inteiro, o elemento é injetado com `position: absolute` diretamente dentro da tag `<main>` (`div` principal de conteúdo).
- **Resultado:** A barra lateral de menu (`.sidenav`), o avatar do perfil e a barra azul do topo continuam **100% visíveis, ativos e interativos**. Caso o usuário decida clicar em "Dashboard", "Recursos", "Historico" ou qualquer outro menu enquanto a VDS carrega, a navegação ocorre imediatamente sem impedimento.

### B. Manutenção do Feedback Visual
- A barra de progresso no topo (`#vds-top-loader`) continua avançando no topo da janela como um elegante indicador visual.
- Assim que o conteúdo de `livroDeOcorrencias.php` chega do servidor, o `#vds-content-skeleton-overlay` sofre um `fadeOut(250)` dentro do `<main>`.

---

## 3. Conclusão
A interface atingiu o equilíbrio ideal de UX: feedback instantâneo de carregamento na área de conteúdo sem comprometer a liberdade de navegação do usuário no menu lateral.
