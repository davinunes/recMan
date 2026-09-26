# Diagnóstico e Raciocínio: Padronização dos Cards em `index.php?pag=tools`

## Data: 2026-09-26

## Contexto do Problema
No painel de ferramentas admin (`index.php?pag=tools` em `palco/tools.php`), o padrão visual da página é uma grade de **cards clicáveis (botões gigantes)** organizados por categorias. 

Com a adição da funcionalidade de configuração da API de PDF (Typst vs Legado, seleção de 8 templates e exibição de banner), o formulário foi embutido diretamente como um card exposto em bloco na página principal do `tools.php`, quebrando o padrão estético e a simetria dos cards.

## Solução Proposta

1. **Criar a nova página de configurações**:
   - Arquivo: `palco/configuracoes_pdf.php`
   - Ela conterá o formulário completo de configuração da emissão de PDF com salva-guarda em `upsertConfigSistema`, suporte às 8 variantes do Typst, toggle de banner e link direto de teste de variantes em paralelo (`palco/test_typst.php`).
   - Botão de navegação "Voltar para Ferramentas" apontando para `index.php?pag=tools`.

2. **Registrar a rota em `index.php`**:
   - Adicionar o `case "configuracoes_pdf": include "palco/configuracoes_pdf.php"; break;` na estrutura `switch ($pag)`.

3. **Restaurar a simetria em `palco/tools.php`**:
   - Remover o card exposto embutido.
   - Adicionar um card botãozão padronizado na categoria `Recursos e Pareceres`:
     - Rótulo: `Configurações da API de PDF`
     - URL: `index.php?pag=configuracoes_pdf`
     - Ícone: `picture_as_pdf`
     - Cor: `blue darken-2`
