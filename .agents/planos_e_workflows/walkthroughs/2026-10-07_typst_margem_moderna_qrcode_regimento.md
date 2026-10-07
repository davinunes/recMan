# Walkthrough: Correção de Layout Margem Moderna e QR Code com Link na Capa do Regimento e Convenção

**Data:** 07/10/2026  
**Status:** Concluído com Sucesso  

---

## 1. Resumo das Alterações Realizadas

### 1.1 Correção da Variante `margem_moderna` em `typst_templates/regimento.typ`
- **Imagem de Fundo:** Adicionada a regra `else if variant == "margem_moderna"` no bloco `background` para carregar a marca d'água `lay-body-all-pages-2.png` em todas as páginas do documento normativo.
- **Capa Oficial:** Implementada a capa visual dedicada para a variante `margem_moderna`, com cabeçalho em Verde Esmeralda (`#065f46`), subtítulo explicativo e dados institucionais de Taguatinga / DF.
- **Cores Dinâmicas:** Alinhadas as cores `main_color`, `accent_color` e `bg_cap` para a paleta esmeralda moderna.

### 1.2 Geração Standalone de QR Code Vetorial
- **Gerador Puro Python (`py/qrcodegen.py`):** Criada biblioteca de zero dependências baseada no padrão ISO/IEC 18004 (Project Nayuki MIT), permitindo que o microserviço gere arquivos SVG vetoriais sem depender de `pip install qrcode` ou compiladores C.
- **Processamento em `py/typst_server.py`:**
  - Função `generate_qr_svg()` que tenta primeiro o gerador puro local e, em caso de necessidade, a biblioteca oficial instalada no sistema.
  - Interceptação de `url_cartorio` / `cartorio_url` no payload JSON, criando dinamicamente o arquivo SVG em `typst_templates/qr_<hash>.svg` e limpando-o no `finally:`.

### 1.3 Link Clicável e QR Code na Capa do Typst
- **Função `render-bloco-cartorio`:** Criado componente visual reutilizável no Typst que exibe:
  - Card estilizado respeitando a variante selecionada (modo claro, escuro ou esmeralda).
  - Link interativo clicável (`#link()`) para o documento original registrado em cartório.
  - QR Code vetorial de alta definição para leitura via smartphone.
  - Legenda instrucional.
- **Suporte em Todas as Variantes:** O bloco é renderizado tanto na nova capa de `margem_moderna` quanto em `modern`, `watermark_a4`, `corporate`, `juridico`, `dark`, `editorial`, etc.

### 1.4 Painel de Testes e Gerenciamento em `palco/test_typst.php`
- **Novos Campos no Card 1:**
  - *URL do Arquivo Original Registrado em Cartório* (`url_cartorio`).
  - *Texto do Link na Capa* (`texto_cartorio`).
- **Pré-preenchimento Inteligente:** Carrega automaticamente os links padrão definidos em `regimento/database.json` e `convencao_coletiva_miami_json.json`.
- **Alternância Dinâmica via JS:** Ao alternar o select entre "Regimento Interno" e "Convenção de Condomínio", os campos atualizam instantaneamente com os dados correspondentes.
- **Persistência Automática:** Ao gerar o PDF com nova URL informada, os arquivos JSON são atualizados automaticamente para manter as preferências salvas no sistema.

---

## 2. Arquivos Modificados / Criados

| Arquivo | Ação | Descrição |
| :--- | :--- | :--- |
| `typst_templates/regimento.typ` | Modificado | Suporte a `lay-body-all-pages-2.png`, capa `margem_moderna` e função `render-bloco-cartorio`. |
| `py/qrcodegen.py` | Criado | Gerador puro em Python de QR Code em SVG (zero deps). |
| `py/typst_server.py` | Modificado | Geração dinâmica de QR Code SVG a partir de `url_cartorio` e limpeza temporária. |
| `palco/test_typst.php` | Modificado | Formulário com novos campos, script de troca dinâmica e persistência nos JSONs. |
| `regimento/database.json` | Modificado | Inclusão dos campos padrão `url_cartorio` e `texto_cartorio`. |
| `convencao_coletiva/convencao_coletiva_miami_json.json` | Modificado | Inclusão dos campos padrão `url_cartorio` e `texto_cartorio`. |
| `.agents/skills/geracao_pdf_typst/SKILL.md` | Modificado | Atualização do catálogo de variantes e documentação de QR code/cartório. |

---

## 3. Próximos Passos para o Usuário no Servidor Remoto
1. Fazer o commit / push e pull no servidor remoto via painel Git (`git.php`).
2. Reiniciar o microserviço Typst caso esteja rodando em segundo plano:
   ```bash
   bash py/restart_typst.sh
   ```
3. Acessar `/palco/test_typst.php?action=test_regimento` para compilar o Regimento ou Convenção na variante **Marca d'Água Margem Moderna**, conferindo o fundo `lay-body-all-pages-2.png`, a capa e o QR Code gerado.
