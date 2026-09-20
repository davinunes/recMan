# Raciocínio: API de PDFs com Typst e Teste com Regimento Interno (JSON)

- **Data**: 2026-09-20
- **Tópico**: Implementação em paralelo de API de geração de PDF via Typst utilizando `regimento/database.json` e suporte a Pareceres.

---

## 1. Contexto e Motivação
O usuário aprovou a utilização do **Typst** como alternativa moderna, leve e ultra-rápida ao gerador de PDFs atual (`http://127.0.0.1:5000/gerar_pdf?base64=true` consumido por `classes/pdfParecer.php`).
Ele sugeriu:
1. Subir/criar uma API nova/serviço em paralelo.
2. Criar um teste utilizando o arquivo `regimento/database.json` para testar variantes de layout e desempenho.

## 2. Análise do Dataset (`regimento/database.json`)
O arquivo `regimento/database.json` é um JSON bem estruturado contendo:
- `titulo`: Título oficial do Regimento Interno.
- `capitulos`: Objeto indexado com o nome dos capítulos (`1` a `33`).
- `artigos`: Objeto indexado com cada artigo, contendo:
  - `capitulo`: Número do capítulo correspondente.
  - `texto`: Texto do artigo.
  - `paragrafos`: Objeto opcional com parágrafos (ex: `"unico"`, `"1"`, etc.).

Este dataset é perfeito para validar a capacidade do Typst em:
- Renderizar documentos extensos e estruturados.
- Gerar sumário / índice dinâmico.
- Tratar cabeçalhos, rodapés com numeração de página ("Página X de Y").
- Processar formatações e estilizações tipográficas avançadas.

## 3. Arquitetura Proposta
Para que o desenvolvimento ocorra sem interferir em nada na aplicação atual (desenvolvimento em paralelo):

1. **Templates Typst (`typst_templates/`)**:
   - `regimento.typ`: Template focado no Regimento Interno (capa, cabeçalho condominial, índice, estrutura de capítulos e artigos).
   - `parecer.typ`: Template focado no modelo de Parecer Notificação/Recurso (cabeçalho formal, tabela de resumo, relato do fato, votação e assinatura).

2. **Classe Wrapper / Service PHP (`classes/typstPdfService.php`)**:
   - Recebe dados em array/JSON.
   - Escreve temporariamente ou passa via parâmetros CLI para o `typst compile`.
   - Retorna o binário PDF ou string Base64.
   - Fallback gracioso para a API atual caso o binário Typst não esteja no PATH ou não esteja instalado no ambiente.

3. **Endpoint de Teste em Paralelo (`palco/test_typst.php` / `api/test_typst.php`)**:
   - Permite disparar a compilação do Regimento e de Pareceres de teste via interface web ou API.
   - Retorna o PDF diretamente no navegador (`Content-Type: application/pdf`).

## 4. Próximos Passos
- Registrar o plano oficial no arquivo `.agents/planos_e_workflows/planos/2026-09-20_planejamento_typst_regimento_api.md`.
- Criar o `implementation_plan.md` no artifact da sessão para aprovação do usuário.
