# Walkthrough: Expansão Inline via AJAX das Ocorrências Vinculadas no Recurso

Data: 09/10/2026  
Arquivo alterado: [`palco/detalheRecurso.php`](file:///e:/DEV/recMan/palco/detalheRecurso.php)

## 1. Objetivo Realizado
Modificado o comportamento da seção **Ocorrências Condomínio Digital Vinculadas** na tela de detalhes do recurso (`index.php?pag=recurso&rec=370/2026`).  
Em vez de redirecionar ou abrir o Livro de Ocorrências em outra guia ao clicar, a ocorrência agora realiza uma requisição AJAX para carregar o histórico completo e o expande suavemente logo abaixo do próprio item da collection (`collection-item`).

---

## 2. Alterações Implementadas

### 2.1 Interface & Toggle Dinâmico
- Cada item vinculado exibe:
  - **Cabeçalho clicável**: ID da ocorrência, Bloco / Unidade, Data de Abertura e Tags associadas.
  - **Botão Principal ("Ver Chat" / "Recolher")**: Dispara `toggleOcorrenciaInline(id, event)`.
  - **Botão Secundário ("Abrir em Nova Guia")**: Ícone discreto `open_in_new` mantendo a opção de acessar o Livro de Ocorrências completo em nova aba se o conselheiro desejar.
  - **Botão VDS Remoto**: Mantido se a ocorrência possuir link remoto cadastrado (`launch`).

### 2.2 Container Expansível Inline
- Adicionado container `<div class="oco-inline-expand-container" id="oco-inline-expand-{id}">` abaixo de cada item.
- Exibição de pré-carregamento suave (spinner + texto explicativo).
- Cache em DOM: após o primeiro carregamento, o clique subsequente recolhe (`slideUp`) ou reabre (`slideDown`) instantaneamente sem requisições desnecessárias.

### 2.3 Estilos Visuais Compatíveis
- Adicionado bloco de CSS scoped `.oco-inline-chat-wrapper`:
  - Fundo característico `#efeae2` (estilo WhatsApp).
  - Balões de mensagens: `.msg-bubble`, `.msg-left`, `.msg-right`, `.msg-internal` (notas internas com destaque âmbar).
  - Altura máxima com scroll confortável (`max-height: 480px; overflow-y: auto;`).
  - Ocultação de botões de navegação mobile que não se aplicam à visualização inline.
  - Suporte ao zoom de imagens estilo feed (`materialboxed`).

### 2.4 Funções e Ações no Chat Inline
- Suporte a:
  - `submeterNotaInternaAjax`: salva notas internas do conselho diretamente a partir do chat inline e recarrega os dados.
  - Anexos e preview de imagens / PDF (`tratarAnexoNotaArquivo`, `removerAnexoNotaPreview`).
  - Ações rápidas de conferência (`executarAcaoAjaxLido`, `executarAcaoAjaxResolvido`, `executarAcaoAjaxAuditoria`, `executarAcaoAjaxResponsabilidade`, `publicarNotaRemotoAjax`).

---

## 3. Validação
- Estrutura validada em [`palco/detalheRecurso.php`](file:///e:/DEV/recMan/palco/detalheRecurso.php#L585-L1078).
- Sem dependência de reload da página ou conflito com manipuladores de histórico.
