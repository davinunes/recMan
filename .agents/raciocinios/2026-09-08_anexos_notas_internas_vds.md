# Raciocínio Diagnóstico: Anexar Imagens em Notas Internas e Sincronização Remota VDS

- **Data:** 2026-09-08
- **Objetivo:** Adicionar suporte a anexos (botão de anexo + colar da área de transferência - paste clipboard) no chat de notas internas do Livro de Ocorrencias, salvando localmente primeiro e enviando em 3 etapas para a VDS ao publicar/sincronizar remoto.

## Contexto & Situação Atual
1. No `livroDeOcorrencias.php`, o conselheiro digita uma Nota Interna (1º Fator) que é salva localmente na tabela `ocorrencia_notas_internas`.
2. A tabela `ocorrencia_notas_internas` já possui a coluna `anexo_caminho TEXT DEFAULT NULL`.
3. Quando o conselheiro clica em "Publicar Remoto" (2º Fator), o backend chama `vds_publicar_nota_remoto()` que faz o POST para `/ocorrencia/comentario`.
4. A API VDS exige um fluxo de 3 passos para anexar imagens em ocorrências:
   - **Etapa 1:** `POST /upload` com `base64String` -> retorna `url` temporária.
   - **Etapa 2:** `POST /ocorrencia/comentario` -> retorna `ocorrenciaId` (ID numérico do comentário).
   - **Etapa 3:** `POST /anexo` -> vincula `{nome_temp}*{nome_original}` ao `destinoUuid` (`ocorrenciaId`).

## Decisão de Arquitetura & UX
1. **Frontend (`livroDeOcorrencias.php` + JavaScript):**
   - Adicionar botão de anexo `<button type="button" id="btn-anexo-clip">` com `<input type="file" id="input-file-anexo" accept="image/*">`.
   - Adicionar ouvinte do evento `paste` no `textarea` para capturar imagens da área de transferência (`event.clipboardData.items`).
   - Adicionar container de preview da imagem selecionada/colada com botão de remover.
   - Alterar `submeterNotaInternaAjax` para enviar o formulário usando `FormData`, permitindo o envio do arquivo `anexo` junto com o texto da nota.

2. **Backend Local (`classes/vds_ocorrencia_service.php` & `livroDeOcorrencias.php`):**
   - Atualizar `vds_adicionar_nota_interna()` para receber o upload `$_FILES['anexo']` ou base64 de colar.
   - Salvar o arquivo na pasta local `storage/comentarios/`.
   - Gravar o caminho em `anexo_caminho` na tabela `ocorrencia_notas_internas`.
   - Renderizar o anexo/imagem na bolha de nota interna no chat.

3. **Sincronização Remota VDS (`vds_publicar_nota_remoto`):**
   - Se `anexo_caminho` estiver preenchido:
     - Ler a imagem local em `storage/comentarios/`, converter para Base64.
     - Executar `vds_upload_midia($base64Data)` (`POST /upload`).
     - Executar `POST /ocorrencia/comentario` para criar a mensagem e obter `$vdsEventoId`.
     - Executar `POST /anexo` para vincular a imagem remota ao `$vdsEventoId`.
     - Remover o arquivo físico local de `storage/comentarios/` conforme solicitado ("removemos do nosso") ou atualizar status.

## Próximos Passos
Elaborar o plano de implementação completo e solicitar aprovação do usuário.
