# Raciocínio de Diagnóstico: Correção da Publicação de Anexos em PDF na API VDS

- **Data**: 2026-10-05
- **Tópico**: Correção da publicação remota de notas internas com anexo PDF na API VDS v8 (Livro de Ocorrências).
- **Problema Relatado**: "Anexei um pdf a um chamado, e depois cliquei pra publicar no vds e não deu certo. Acredito que só está funcionando com imagens. Mas pelo VDS dá certo."

## 1. Análise do Fluxo Atual

1. **Seleção e Preview do Arquivo (`livroDeOcorrencias.php`)**:
   - O `<input type="file" name="anexo">` possuía `accept="image/*"`, restringindo nativamente a seleção a imagens.
   - O preview JavaScript tentava injetar o `DataURL` de um PDF diretamente em um elemento `<img src="...">`, que não renderiza PDFs no navegador.
   - Na lista de notas internas do chat (antes de publicar), o anexo era sempre renderizado como `<img>`, quebrando a exibição caso fosse um `.pdf`.

2. **Salvar Nota Interna (`vds_adicionar_nota_interna` em `classes/vds_ocorrencia_service.php`)**:
   - Para uploads via multipart (`$_FILES['anexo']`), o arquivo era salvo localmente com extensão `.pdf` em `storage/comentarios/`.
   - Porém, para dados base64, o código filtrava apenas `data:image/`, não suportando `data:application/pdf;base64,`.

3. **Publicação Remota no VDS (`vds_publicar_nota_remoto`)**:
   - **MIME type forçado**: Ao ler o arquivo salvo localmente, o código fazia:
     `$mime = ($ext === 'png') ? 'image/png' : (($ext === 'gif') ? 'image/gif' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg'));`
     Para um PDF (`$ext === 'pdf'`), o MIME type ficava fixado em `image/jpeg`!
     Isso enviava `data:image/jpeg;base64,JVBERi0xLj...` para a API da VDS no endpoint `POST /upload`. A API VDS rejeita ou trata incorretamente o arquivo como imagem.
   - **Falta do parâmetro `fileName`**: A inspeção de rede capturada no ambiente oficial da VDS (`docs/inspect/analise-anexando-arquivo-ocorrencia.txt`) revelou que o `POST /upload` da VDS recebe:
     `{"base64String": "...", "fileName": "documento.pdf"}`. Sem `fileName`, o arquivo temporário salvo no staging da VDS não retém o nome nem a extensão correta.
   - **`cortarQuadrado` ativado no `POST /anexo`**: A vinculação em `vds_vincular_anexo_remoto` passava fixo `'cortarQuadrado' => true`. Ao tentar cortar e gerar thumbnail quadrada de um arquivo binário PDF, o backend do VDS gera erro. Para PDF e documentos, `cortarQuadrado` deve ser `false`.
   - **Mensagem vazia**: Se o conselheiro anexasse apenas o PDF sem preencher texto, o comentário no `POST /ocorrencia/comentario` enviava `mensagem: ""`, que a VDS rejeita com erro 400.
   - **Timeout curto**: O timeout do upload estava em 8 segundos, o que pode falhar em arquivos PDF maiores.

## 2. Solução Planejada

1. **Ajustar `classes/vds_ocorrencia_service.php`**:
   - `vds_upload_midia`: aceitar `$fileName = null`, incluir no payload JSON da VDS e elevar timeout para 30s.
   - `vds_vincular_anexo_remoto`: aceitar parâmetro `$cortarQuadrado = true`, passando `false` para PDFs.
   - `vds_adicionar_nota_interna`: aceitar `data:application/pdf` em base64.
   - `vds_publicar_nota_remoto`:
     - Mapear MIME types corretos (`pdf` -> `application/pdf`, etc.).
     - Enviar `fileName` no `vds_upload_midia`.
     - Definir `$cortarQuadrado = false` para PDFs.
     - Garantir mensagem padrão caso o texto esteja vazio com anexo presente.
     - Tratar e reportar adequadamente falhas parciais do anexo.

2. **Ajustar `livroDeOcorrencias.php`**:
   - Atualizar `accept` para `image/*,application/pdf,.pdf`.
   - Atualizar preview no frontend para exibir ícone e detalhes específicos de PDF.
   - Ajustar a renderização da nota interna para exibir link/botão dedicado quando for PDF ao invés de tag `<img>`.
   - Atualizar documentação e skill `vds_api_v8`.
