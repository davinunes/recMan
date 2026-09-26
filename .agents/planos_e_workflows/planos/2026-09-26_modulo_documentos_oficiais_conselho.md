# Plano de Implementação - Módulo de Documentos Oficiais do Conselho (Typst Multi-Editor)

## Data: 2026-09-26

## 1. Visão Geral
Criação do módulo para redação, numeração automática e emissão de **Documentos Oficiais do Conselho** (Orientações Técnicas, Pareceres Opinativos, Entendimentos do Conselho e Instruções Normativas), substituindo arquivos avulsos do Google Docs por um fluxo integrado de alta performance com o Typst CLI (Porta 5050).

---

## 2. Arquitetura do Editor Duplo (WYSIWYG ↔ Split Live Preview)

O editor possuirá um botão alternador de modo visual instantâneo:

1. **Modo Visual (WYSIWYG - Rich Text)**:
   - Editor visual amigável (Summernote / Trumbowyg) estilo Google Docs / Word.
   - Converte automaticamente tags HTML (`<b>`, `<i>`, `<h1>`, `<ul>`, `<table>`) para a marcação do Typst no momento da compilação.
   
2. **Modo Avançado (Split Code / Live Preview)**:
   - Tela dividida com editor de código na esquerda e visualizador de PDF em tempo real na direita (renderizado via AJAX em ~30ms).
   - Suporte à opção **`clear` (Sem Template / Raw Typst)**, onde o operador tem total liberdade para escrever código Typst puro (`#set page(...)`, etc.) sem nenhuma interferência de cabeçalhos/rodapés do sistema.

---

## 3. Estrutura do Banco de Dados (Migration `migrates/migrate_documento_oficial.php`)

```sql
CREATE TABLE IF NOT EXISTS `conselho`.`documento_oficial` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero` INT NOT NULL,                        -- Número sequencial (ex: 1)
  `ano` INT NOT NULL,                           -- Ano de emissão (ex: 2026)
  `tipo` VARCHAR(50) NOT NULL,                  -- 'orientacao_tecnica', 'parecer_opinativo', 'entendimento', 'instrucao_normativa'
  `titulo` VARCHAR(255) NOT NULL,               -- Título principal
  `ementa` TEXT NULL,                           -- Resumo/Ementa
  `conteudo` LONGTEXT NOT NULL,                 -- Texto/Código do documento
  `modo_editor` VARCHAR(20) DEFAULT 'visual',   -- 'visual' ou 'split_code'
  `modo_template` VARCHAR(20) DEFAULT 'global', -- 'global', 'custom' ou 'clear'
  `template_especifico` LONGTEXT NULL,          -- Código Typst customizado quando modo_template = 'custom'
  `variante_global` VARCHAR(50) DEFAULT 'top_header', -- Variante global ('top_header', 'watermark_a4', 'editorial', 'modern', etc.)
  `id_usuario` INT NOT NULL,                    -- Autor/Relator
  `data_emissao` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tipo_ano` (`tipo`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Etapas de Desenvolvedor

### Passo 1: Migration e Métodos de Banco
- Criar o script `migrates/migrate_documento_oficial.php`.
- Adicionar no `classes/repositorio.php` as funções:
  - `getProximoNumeroDocumentoOficial($tipo, $ano)`
  - `upsertDocumentoOficial($dados)`
  - `getDocumentosOficiais($filtros)`
  - `getDocumentoOficialById($id)`

### Passo 2: Wrapper e Endpoint Typst (`classes/typstPdfService.php` e `py/typst_server.py`)
- Adicionar o endpoint `/gerar_documento_oficial` no Python.
- Tratar a opção `modo_template == 'clear'` para compilar o código Typst diretamente sem invocar wrapper externo.
- Adicionar conversor PHP `TypstPdfService::htmlToTypst($html)` para traduzir conteúdo do modo visual.

### Passo 3: Interface Web (`palco/documentosOficiais.php`)
- Tabela de listagem com busca por ano, tipo e palavra-chave.
- Modal / Tela de Cadastro & Edição com o alternador **WYSIWYG ↔ Split Code**.
- Visualização de PDF em tempo real.

### Passo 4: Rota e Card do Painel de Ferramentas
- Adicionar a rota em `index.php?pag=documentosOficiais`.
- Adicionar o card botãozão no `palco/tools.php` na categoria "Recursos e Pareceres".

---

## 5. Plano de Validação
1. Executar a migration e criar um documento de teste do tipo `Orientação Técnica`.
2. Testar o gerador no **Modo Visual (WYSIWYG)** aplicando o template global `top_header`.
3. Alternar para o **Modo Split** com a opção `clear` e digitar comandos nativos do Typst para verificar o PDF gerado.
4. Validar a numeração automática incremental por ano e tipo.
