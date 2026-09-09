# Walkthrough / Resumo de Entrega: Anexos de Imagens em Notas Internas e Sincronização Remota VDS

- **Data:** 2026-09-08
- **Módulo:** Livro de Ocorrências (`livroDeOcorrencias.php`) / Serviço VDS (`classes/vds_ocorrencia_service.php`)

## Alterações Efetuadas

### 1. Backend (`classes/vds_ocorrencia_service.php`)
- **`vds_adicionar_nota_interna()`**: Atualizada para receber arquivos via `$_FILES['anexo']` ou imagens em Base64 vindas da área de transferência (Ctrl+V). Garante a criação do diretório `storage/comentarios/`, grava o arquivo localmente com nome único e salva o caminho em `anexo_caminho`.
- **`vds_publicar_nota_remoto()`**: Atualizada para executar o fluxo de 3 etapas da VDS quando a nota interna contiver anexo:
  1. Converte a imagem local para Base64 e envia para `POST /upload` (obtendo o nome temporário na VDS).
  2. Publica o comentário em `POST /ocorrencia/comentario` (obtendo o `vdsEventoId`).
  3. Executa a vinculação do anexo via `vds_vincular_anexo_remoto()` (`POST /anexo`), associando `{nome_temp}*{nome_original}` ao `destinoUuid`.
  4. Remove o arquivo local de `storage/comentarios/` (`@unlink`) após confirmação do envio remoto.
- **`vds_vincular_anexo_remoto()`**: Nova função helper criada para realizar a requisição `POST /anexo` na VDS.

### 2. Frontend & Chat UI (`livroDeOcorrencias.php`)
- **Botão de Anexo:** Adicionado botão com ícone `<i class="material-icons">attach_file</i>` e input escondido `<input type="file" name="anexo" accept="image/*">`.
- **Intercepção de Paste (Ctrl+V):** Adicionado listener JS que captura eventos de colar no campo de texto (`#input-texto-nota-interna`), identifica itens do tipo `image/*`, converte para DataURL e gera o preview instantâneo.
- **Preview de Anexo:** Container `#preview-anexo-nota-container` exibe thumbnail da imagem selecionada ou colada e botão de exclusão `(X)`.
- **Envio AJAX:** Atualizada a função `submeterNotaInternaAjax` para usar `FormData` com `contentType: false` e `processData: false`.
- **Renderização da Bolha de Chat:** Bolhas de notas internas no chat renderizam thumbnail da imagem do anexo com link para visualização em tamanho real.

---

## Verificação e Validação

1. **Upload via Botão de Clipe:** Clicar no clipe abre o seletor de arquivos, gera o preview com o nome e tamanho do arquivo e envia a nota via AJAX.
2. **Colar Imagem (Ctrl+V):** Copiar qualquer imagem e colar no campo de texto exibe instantaneamente a miniatura no preview.
3. **Publicação no VDS (2º Fator):** Ao clicar em "Publicar no Remoto (VDS)", o backend envia a mensagem, uploader de mídias e vincula em 3 etapas na API VDS, limpando a cópia local do anexo.
