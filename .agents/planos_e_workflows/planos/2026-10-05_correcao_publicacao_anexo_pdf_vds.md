# Plano de Implementação: Suporte Completo a Anexos em PDF na Publicação para a VDS

- **Data**: 2026-10-05
- **Tópico**: Correção da publicação remota de notas internas com anexo PDF na API VDS v8 (Livro de Ocorrências).

## 1. Objetivos

1. Permitir que arquivos PDF sejam anexados com clareza visual no formulário de notas internas e renderizados adequadamente no chat.
2. Corrigir o backend PHP para enviar MIME type real (`application/pdf`), nome do arquivo (`fileName`), sem thumbnail forçada (`cortarQuadrado: false`) no fluxo de publicação remota na API VDS v8.
3. Garantir resiliência com mensagem padrão quando o anexo for enviado sem texto digitado.

## 2. Etapas de Execução

- [ ] **Etapa 1: Atualização em `classes/vds_ocorrencia_service.php`**
  - Ajustar `vds_upload_midia` para aceitar `$fileName = null` e aumentar timeout para 30s.
  - Ajustar `vds_vincular_anexo_remoto` para receber `$cortarQuadrado = true`.
  - Ajustar `vds_adicionar_nota_interna` para decodificar base64 genérico (`data:application/pdf;base64,`).
  - Ajustar `vds_publicar_nota_remoto` com mapa completo de MIME types, `$cortarQuadrado = false` para PDF, envio de `fileName` e fallback de mensagem não-vazia.

- [ ] **Etapa 2: Atualização da UI e JS em `livroDeOcorrencias.php`**
  - Adicionar `accept="image/*,application/pdf,.pdf"` no input file.
  - Adicionar suporte a preview de PDF com ícone `picture_as_pdf` no container de preview.
  - Ajustar a renderização das notas internas no chat para exibir cartão com link para download/visualização de PDF em vez de tag `<img>`.

- [ ] **Etapa 3: Atualização da Documentação Técnica**
  - Atualizar a skill `.agents/skills/vds_api_v8/SKILL.md` documentando o suporte a PDFs e os parâmetros `fileName` em `POST /upload` e `cortarQuadrado: false` em `POST /anexo`.
  - Criar walkthrough da entrega em `.agents/planos_e_workflows/walkthroughs/2026-10-05_correcao_publicacao_anexo_pdf_vds.md`.
