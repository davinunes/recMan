# Walkthrough - Implementação do Módulo de Documentos Oficiais do Conselho (Typst Multi-Editor)

## Data: 2026-09-26

## Resumo das Modificações

Foi implementado o módulo para cadastro, numeração automática e emissão em PDF de **Documentos Oficiais do Conselho** (Orientações Técnicas, Pareceres Opinativos, Entendimentos e Instruções Normativas).

### Principais Recursos Entregues

1. **Editor Duplo com Alternador de Modo (WYSIWYG ↔ Split Code Live Preview)**:
   - **Modo Visual (WYSIWYG)**: Permite que conselheiros redijam textos ricos (negrito, itálico, listas, títulos) que são traduzidos automaticamente para a sintaxe Typst via conversor PHP `TypstPdfService::htmlToTypst`.
   - **Modo Split (Code & Live PDF)**: Painel duplo em tempo real. Na esquerda, o editor de código Typst; na direita, a pré-visualização instantânea do PDF renderizado pela API do Typst (porta 5050).

2. **Modo `Clear` (Raw Typst sem Template)**:
   - Opção de seleção no formulário `modo_template = 'clear'`.
   - Quando ativado, o sistema envia o código Typst nativo diretamente para compilação sem aplicar cabeçalhos, rodapés ou margens pré-definidas do sistema.

3. **Numeração Sequencial Automática**:
   - O sistema calcula e incrementa a numeração automaticamente por tipo e ano (ex: *Orientação Técnica nº 001/2026*, *Parecer Opinativo nº 002/2026*).

---

## Arquivos Criados e Modificados

- [`migrates/migrate_documento_oficial.php`](file:///e:/DEV/recMan/migrates/migrate_documento_oficial.php): Script SQL de migração da tabela `conselho.documento_oficial`.
- [`typst_templates/documento_oficial.typ`](file:///e:/DEV/recMan/typst_templates/documento_oficial.typ): Template modelo Typst para documentos oficiais.
- [`palco/documentosOficiais.php`](file:///e:/DEV/recMan/palco/documentosOficiais.php): Interface web com listagem, filtros, editor alternável e modal de preview PDF.
- [`classes/repositorio.php`](file:///e:/DEV/recMan/classes/repositorio.php): Funções de banco `getProximoNumeroDocumentoOficial`, `upsertDocumentoOficial`, `getDocumentosOficiais`, `deleteDocumentoOficial`.
- [`classes/typstPdfService.php`](file:///e:/DEV/recMan/classes/typstPdfService.php): Adicionadas funções `htmlToTypst` e `gerarDocumentoOficial`.
- [`py/typst_server.py`](file:///e:/DEV/recMan/py/typst_server.py): Adicionado endpoint `/gerar_documento_oficial` e suporte ao `modo_template == 'clear'`.
- [`index.php`](file:///e:/DEV/recMan/index.php): Adicionada a rota `documentosOficiais`.
- [`palco/tools.php`](file:///e:/DEV/recMan/palco/tools.php): Incluído o card botãozão **Documentos Oficiais**.

---

## Como Validar no Sistema
1. Execute a migração do banco acessando `migrates/migrate_documento_oficial.php` no navegador.
2. Acesse `index.php?pag=tools` e clique no card **Documentos Oficiais** (ou acesse `index.php?pag=documentosOficiais`).
3. Clique em **"Novo Documento Oficial"**.
4. Teste os dois modos de edição:
   - **Modo Visual**: Escreva um texto formatado e clique em Salvar / Visualizar.
   - **Modo Split**: Clique em "Modo Split", selecione o template **Clear**, digite marcação Typst pura (`#set page(paper: "a4")\n = Teste Raw`) e clique em **"Atualizar PDF Preview"**.
