# Plano de Implementação: Página de Normativos & Política de Tratamento de Dados (Portal)

**Data**: 2026-10-08  
**Autor**: Antigravity  
**Status**: Em Execução  

---

## 1. Objetivos

1. Criar a página `portal/normativos.php` com visual moderno e responsivo.
2. Disponibilizar os 3 documentos normativos em ordem rigorosa de prioridade:
   - **1º: PTD** (`politica-tratamento-dados-conselho-miami-2025-2027.pdf`)
   - **2º: Convenção Condominial** (`cc-miami-pesquisavel-interativo.pdf`)
   - **3º: Regimento Interno** (`ri-miami-pesquisavel-interativo.pdf`)
3. Cada documento terá um card com a estrutura explicativa "Entenda..." destacando o propósito e relevância para o condômino.
4. Implementar visualização **inline** (leitor embutido responsivo + links de abertura direta no navegador sem forçar download).
5. Adicionar no `portal/index.php` um card e link chamativo convidando o usuário a conhecer a Política de Tratamento de Dados (LGPD) e os normativos.

---

## 2. Arquivos Envolvidos

- `portal/normativos.php` (novo)
- `portal/index.php` (modificado)
- `.agents/planos_e_workflows/walkthroughs/2026-10-08_normativos_politica_dados_portal.md` (novo)

---

## 3. Etapas de Execução

1. **Construção de `portal/normativos.php`**:
   - Layout compatível com Tailwind CSS, Material Icons e Alpine.js.
   - Header com logo, títulos institucionais e navegação de retorno.
   - Banner de Privacidade & Governança (LGPD).
   - Grade com 3 Cards interativos ("Entenda...") na ordem: PTD, Convenção, Regimento.
   - Visualizador de PDF inline embutido com altura confortável, seletor de documento ativo e suporte a tela cheia.
2. **Atualização de `portal/index.php`**:
   - Inserir banner chamativo na Etapa 0 do assistente.
   - Inserir link permanente no rodapé.
3. **Validação**:
   - Conferir caminhos dos PDFs, classes CSS, funcionamento do leitor inline e responsividade.
4. **Walkthrough e Documentação**:
   - Gerar o arquivo de walkthrough em `.agents/planos_e_workflows/walkthroughs/`.
