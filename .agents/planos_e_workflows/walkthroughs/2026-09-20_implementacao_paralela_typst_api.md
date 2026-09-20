# Walkthrough: Implementação da API Typst na Porta 5050 (Regimento e Pareceres em Paralelo)

- **Data**: 2026-09-20
- **Status**: Concluído

---

## 1. O que foi Implementado

Foi desenvolvida a arquitetura completa para geração e teste de PDFs via **Typst** operando em paralelo na **porta 5050**, totalmente isolada da API em produção (que utiliza a porta 5000).

### Arquivos Criados:

1. **Templates Typst (`typst_templates/`)**:
   - [`typst_templates/regimento.typ`](file:///e:/DEV/recMan/typst_templates/regimento.typ): Template que consome o JSON do Regimento (`regimento/database.json`), gera capa condominial, sumário automático e formatação para todos os 33 capítulos e artigos com cabeçalho/rodapé dinâmicos ("Página X de Y").
   - [`typst_templates/parecer.typ`](file:///e:/DEV/recMan/typst_templates/parecer.typ): Template nativo para pareceres formais de recursos e notificações, com tabelas estilizadas, relatos e bloco de assinatura.

2. **Microserviço Python na Porta 5050 (`py/typst_server.py`)**:
   - [`py/typst_server.py`](file:///e:/DEV/recMan/py/typst_server.py): Servidor HTTP em Python (sem dependências externas) escutando na porta **5050**.
   - Endpoints disponibilizados:
     - `GET /health`: Health check da API.
     - `POST /gerar_pdf?base64=true`: Compila o parecer via Typst e retorna em Base64 ou binário.
     - `POST /gerar_regimento?base64=true`: Compila o Regimento Interno via Typst.

3. **Service PHP (`classes/typstPdfService.php`)**:
   - [`classes/typstPdfService.php`](file:///e:/DEV/recMan/classes/typstPdfService.php): Wrapper estático para consumo dos endpoints na porta 5050 e sanitização de caracteres tipográficos.

4. **Painel de Testes Web (`palco/test_typst.php`)**:
   - [`palco/test_typst.php`](file:///e:/DEV/recMan/palco/test_typst.php): Interface de testes com indicador de status da porta 5050, compilação em 1 clique do Regimento em PDF e formulário interativo de parecer com visualizador em tempo real (`<iframe>`).

---

## 2. Como Utilizar no Servidor Remote

1. No servidor remoto, para subir o microserviço na porta 5050:
   ```bash
   python py/typst_server.py
   ```
2. Acesse a tela de testes pelo navegador:
   ```text
   http://[ip-do-servidor]/palco/test_typst.php
   ```
3. Teste a geração em 1 clique tanto do **Regimento Interno em PDF (33 Capítulos)** quanto de **Pareceres de Notificações**.
