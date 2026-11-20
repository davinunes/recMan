# Raciocínio: Feature de Respostas Rápidas no Livro de Ocorrências

**Data**: 2026-11-20  
**Contexto**: `index.php?pag=livroDeOcorrencias`  
**Objetivo**: Implementar respostas rápidas pré-cadastradas para agilizar o atendimento de ocorrências e chamados semelhantes.

---

## 1. Análise da Situação Atual
- Na tela `livroDeOcorrencias.php`, o conselheiro atende chamados moradores e redige comentários/notas internas no campo `<textarea name="texto" id="input-texto-nota-interna">`.
- Muitos chamados possuem naturezas recorrentes:
  - Notificações de barulho fora do horário regulamentar;
  - Regras de mudanças e recebimento de entregas;
  - Solicitação de mais dados/fotos/vídeos para apuração de infração;
  - Encerramento/orientação padrão sobre uso de vagas ou áreas comuns;
  - Comunicação de encaminhamento à administração/síndico.
- Atualmente, os membros do conselho precisam redigir o mesmo texto repetidamente ou copiar e colar de bloco de notas externo.

---

## 2. Requisitos da Feature
1. **Banco de Dados**:
   - Tabela `ocorrencias_respostas_rapidas` (id, titulo, categoria, texto, atalho, conselheiro_id, ativo, ordem, timestamps).
   - Migração `migrates/migrate_respostas_rapidas.php` com sementes padrão de condomínio.
2. **Interface do Usuário (UI/UX)**:
   - Botão de acesso rápido `[⚡ Respostas Rápidas]` acoplado na barra de ferramentas do textarea de nota interna (`#form-adicionar-nota-interna`).
   - Dropdown ou popover rápido de seleção rápida, com filtro por categoria (Barulho, Obras, Pets, Mudanças, Convivência, Geral).
   - Ao clicar, o texto é injetado no textarea com interpolação opcional de variáveis (`{unidade}`, `{morador}`, `{protocolo}`).
   - Modal de gerenciamento CRUD (Cadastrar nova resposta, Editar, Excluir) acessível diretamente da tela sem reload de página.
3. **Backend / AJAX**:
   - Endpoints em `livroDeOcorrencias.php` para carregar, cadastrar, editar e excluir respostas rápidas.

---

## 3. Próximos Passos
- Alinhar com o usuário os detalhes visuais e comportamentais (escopo compartilhado, tags automáticas).
- Aplicar o plano após aprovação.
