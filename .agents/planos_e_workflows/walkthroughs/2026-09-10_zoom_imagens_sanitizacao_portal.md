# Walkthrough: Ajustes no Visualizador de Imagens e Sanitização no Portal

## 1. Visualizador de Imagens (`meu.js`, `palco/detalheRecurso.php`, `livroDeOcorrencias.php`)
- **Causa do "não acontece nada ao clicar"**:
  - Havia um ouvinte delegado `$(document).on('click', '.materialboxed.active')` que, ao receber o borbulhamento do mesmo clique que abriu a imagem, disparava `close()` de imediato.
- **Correção Aplicada**:
  - Removido o ouvinte em `document`.
  - O ouvinte de fechamento agora é anexado apenas após a animação de abertura (`onOpenEnd`) com `$img.off('click.mbClose').one('click.mbClose', ...)`, atuando exclusivamente no próximo clique.
  - O clique de fundo no overlay escuro e tecla ESC continuam fechando nativamente.
  - Ao abrir, a imagem redimensiona proporcionalmente até tocar a largura ou altura máxima (`95vw × 92vh`). Se o tamanho original da imagem for menor que a tela, suas dimensões originais são preservadas (sem esticar).
  - Chamadas em `palco/detalheRecurso.php` e `livroDeOcorrencias.php` unificadas para usar `initMaterialboxed()`.

## 2. Sanitização no Portal de Recursos (`portal/index.php` e `portal/api.php`)
- **Digitação Livre no Campo**:
  - Removido qualquer bloqueio de teclado (`@keydown`) ou alteração de texto em tempo real (`@input`) que apagava a barra enquanto o usuário digitava. O usuário pode digitar `322/26` naturalmente.
- **Remoção Apenas no Envio**:
  - No momento da submissão/consulta (`checarNotificacao()`, `enviarCodigo()`, `enviarRecursoFinal()`, `loginExisting()`), o método `sanitizarCamposEnvio()` remove a barra `/` e tudo o que estiver à direita dela no primeiro campo (`322/26` -> `322`).
  - No backend (`portal/api.php`), a função `sanitizarNumeroAnoNotificacao()` higieniza globalmente `$_POST['numero']` e `$_POST['ano']`, descartando qualquer separador excedente e garantindo o envio e armazenamento estrito de `322/2026`.
