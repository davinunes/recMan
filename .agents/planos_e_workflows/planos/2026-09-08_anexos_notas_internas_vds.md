# Plano de Implementação: Anexos de Imagens em Notas Internas e Sincronização Remota VDS

Adicionar funcionalidade de anexar imagens (botão de upload + colar via Ctrl+V / Paste Clipboard) na criação de Notas Internas no Livro de Ocorrências (`livroDeOcorrencias.php`). O arquivo é armazenado localmente em `storage/comentarios/` (1º Fator) e, ao clicar em "Publicar Remoto" (2º Fator), é postado na VDS via fluxo de 3 etapas (`/upload` -> `/ocorrencia/comentario` -> `/anexo`) e limpo localmente.

## Mudanças Propostas

### 1. Backend (`classes/vds_ocorrencia_service.php`)
- **`vds_adicionar_nota_interna($ocorrenciaId, $conselheiroId, $conselheiroNome, $texto, $anexoFile = null)`**:
  - Aceitar parâmetro de arquivo de anexo (`$_FILES['anexo']` ou array de upload).
  - Validar tipo (imagem JPEG, PNG, WEBP, etc.) e tamanho.
  - Salvar no diretório `storage/comentarios/anexo_{timestamp}_{uniqid}.ext`.
  - Salvar o caminho relativo na coluna `anexo_caminho` de `ocorrencia_notas_internas`.
- **`vds_publicar_nota_remoto($notaId, $usuarioIdConselho = null)`**:
  - Verificar se `$nota['anexo_caminho']` está preenchido e se o arquivo local existe em `storage/comentarios/`.
  - Se houver anexo:
    1. Ler o arquivo e converter em Base64.
    2. Chamar `vds_upload_midia($base64)` -> envia para `POST /upload` da VDS e obtém o nome do arquivo temporário VDS (`{nome_temp}`).
    3. Enviar a mensagem para `POST /ocorrencia/comentario` -> obtém o ID do comentário VDS (`$vdsEventoId`).
    4. Executar chamada curl `POST /anexo` na VDS com payload `{"anexoCaminho": "{nome_temp}*{nome_original}", "tipoId": "35", "destinoUuid": "{$vdsEventoId}", "cortarQuadrado": true}`.
    5. Após confirmação do sucesso na VDS, remover o arquivo local de `storage/comentarios/` e atualizar o banco.

### 2. Frontend & Chat UI (`livroDeOcorrencias.php`)
- **Formulário de Envio (`#form-adicionar-nota-interna`)**:
  - Incluir botão de anexo `<label for="input-anexo-nota" class="btn-flat waves-effect" title="Anexar Imagem"><i class="material-icons">attach_file</i></label>`.
  - Incluir `<input type="file" id="input-anexo-nota" name="anexo" accept="image/*" style="display:none;">`.
  - Incluir div `#preview-anexo-nota` para exibir thumbnail da imagem selecionada/colada com botão de exclusão `(X)`.
- **Captura do Evento Paste (Ctrl+V)**:
  - Adicionar listener no `textarea` (`#input-texto-nota-interna`) para tratar `paste`.
  - Se `e.originalEvent.clipboardData.items` contiver um item de tipo imagem (`image/*`), ler como `Blob`/`File`, atribuir ao preview e ao envio via `DataTransfer` ou `FormData`.
- **Envio AJAX (`submeterNotaInternaAjax`)**:
  - Alterar a requisição de `$.ajax` simples para `FormData` permitindo envio multipart (`enctype="multipart/form-data"`).
- **Renderização da Bolha de Chat (`vds_render_chat_detalhe_conteudo`)**:
  - Se a nota interna possuir `anexo_caminho`, renderizar a tag `<img>` com lightbox/link para o arquivo.

---

## Plano de Verificação

### Testes Manuais
1. **Teste de Seleção por Arquivo:**
   - Abrir uma ocorrência em `livroDeOcorrencias.php`.
   - Clicar no botão de clipe/anexo, selecionar uma foto JPG/PNG.
   - Verificar se o preview da imagem aparece acima da caixa de texto.
   - Enviar a nota interna.
   - Verificar se a nota interna é salva localmente com a foto renderizada na bolha de chat.

2. **Teste de Colar Imagem (Ctrl+V / Clipboard):**
   - Tirar um print/copiar uma imagem para a área de transferência.
   - Clicar no campo de texto da nota e pressionar `Ctrl+V`.
   - Confirmar que o preview da imagem é gerado instantaneamente.
   - Enviar a nota interna e verificar gravação no banco local.

3. **Teste de Sincronização / Publicação no VDS (2º Fator):**
   - Clicar em "Publicar Remoto" na nota interna que possui anexo.
   - Confirmar a execução da sequência `POST /upload` -> `POST /ocorrencia/comentario` -> `POST /anexo`.
   - Confirmar que a mensagem e a imagem aparecem publicadas na VDS.
   - Confirmar a remoção limpa do arquivo local de `storage/comentarios/`.
