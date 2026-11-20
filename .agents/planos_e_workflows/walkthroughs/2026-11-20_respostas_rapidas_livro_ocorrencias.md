# Walkthrough / Entrega: Feature de Respostas Rápidas no Livro de Ocorrências

**Data**: 2026-11-20  
**Contexto**: `index.php?pag=livroDeOcorrencias`  
**Status**: Concluído e Validado

---

## 1. Visão Geral da Funcionalidade
Implementada a funcionalidade de **Respostas Rápidas** pré-cadastradas no Livro de Ocorrências (`index.php?pag=livroDeOcorrencias`). Agora, o conselheiro conta com um botão de raio (`bolt`) localizado ao lado do clipe de anexos no rodapé do chat. 

Ao clicar no botão:
1. Abre-se um modal moderno onde o conselheiro pode buscar ou filtrar respostas por categoria (ex: *Convivência*, *Operacional*, *Esclarecimento*, *Áreas Comuns*, *Encaminhamento*, *Encerramento*).
2. O sistema preenche automaticamente variáveis contextuais da ocorrência selecionada:
   - `{unidade}` -> Ex: `102`
   - `{bloco}` -> Ex: `A`
   - `{protocolo}` -> Ex: `259564`
   - `{morador}` -> Ex: Nome do morador/solicitante
   - `{data}` -> Data atual formatada (`dd/mm/aaaa`)
3. O texto interpolado é colado diretamente no campo de resposta/nota interna (`#input-texto-nota-interna`), permitindo ajustes finos antes de salvar internamente ou publicar na VDS.
4. Há também um modal completo de gerenciamento (**CRUD**), permitindo criar novos modelos, editar textos e categorias, definir ordem ou excluir modelos existentes.

---

## 2. Arquivos Criados e Modificados

| Arquivo | Ação | Descrição |
|---------|------|-----------|
| `migrates/migrate_respostas_rapidas.php` | **Criado** | Script de migração DDL que cria a tabela `ocorrencias_respostas_rapidas` e semeia 6 modelos padrões de condomínio. |
| `classes/vds_ocorrencia_service.php` | **Modificado** | Adicionadas funções utilitárias: `vds_ensure_respostas_rapidas_table_exists()`, `vds_listar_respostas_rapidas()`, `vds_salvar_resposta_rapida()` e `vds_excluir_resposta_rapida()`. |
| `livroDeOcorrencias.php` | **Modificado** | Adicionados endpoints AJAX (`listar_respostas_rapidas`, `salvar_resposta_rapida`, `excluir_resposta_rapida`), botão `[⚡ bolt]` ao lado de anexos com tags contextuais no formulário de notas, modais de seleção e de gerenciamento e scripts de interpolação e CRUD. |
| `.agents/planos_e_workflows/planos/2026-11-20_respostas_rapidas_livro_ocorrencias.md` | **Criado** | Plano de implementação da funcionalidade. |
| `.agents/raciocinios/2026-11-20_respostas_rapidas_livro_ocorrencias.md` | **Criado** | Registro de raciocínio da tarefa. |

---

## 3. Instruções de Uso

1. Abra a tela de ocorrências: `index.php?pag=livroDeOcorrencias`.
2. Selecione qualquer ocorrência na lista lateral.
3. No rodapé do chat, ao lado do botão de anexo (clipe), clique no botão laranja com ícone de raio `⚡`.
4. No modal de **Respostas Rápidas**:
   - Use o campo de busca ou os chips de categoria para localizar a resposta desejada.
   - Clique em **"Usar este modelo"** (ou no próprio card).
   - O texto será inserido no campo de texto já com o bloco, apartamento, protocolo e nome do morador preenchidos.
   - Faça os ajustes necessários e clique em **Salvar Nota Interna** (ou publique na VDS se tiver Ultra-Login).
5. Para adicionar novos modelos ou editar os existentes:
   - Clique em **"Gerenciar Modelos"** no rodapé do modal de respostas rápidas.
   - Preencha o formulário, utilize as tags dinâmicas (`{unidade}`, `{bloco}`, etc.) e clique em **Salvar Modelo**.
