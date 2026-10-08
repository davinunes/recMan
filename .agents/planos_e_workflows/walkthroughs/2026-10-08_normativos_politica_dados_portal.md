# Walkthrough / Taklin: Nova Página de Normativos e Chamada de Política de Dados (Portal)

**Data**: 2026-10-08  
**Autor**: Antigravity  
**Status**: Concluído com Sucesso  

---

## 1. Resumo da Entrega

Foi implementada a nova página de documentos normativos no portal (`portal/normativos.php`) e integrada a chamada de destaque sobre a **Política de Tratamento de Dados (LGPD)** e transparência no portal de recursos (`portal/index.php`).

---

## 2. Modificações Efetuadas

### 2.1 Nova Página: `portal/normativos.php`
- **Ordem rigorosa de prioridade**:
  1. **PTD - Política de Tratamento de Dados (2025–2027)**: `docs/normativos/politica-tratamento-dados-conselho-miami-2025-2027.pdf`
  2. **Convenção de Condomínio**: `docs/normativos/cc-miami-pesquisavel-interativo.pdf`
  3. **Regimento Interno (RI)**: `docs/normativos/ri-miami-pesquisavel-interativo.pdf`
- **Cards com estrutura "Entenda..."**:
  - **PTD**: *"Entenda como tratamos seus dados pessoais e garantimos o sigilo de seus recursos."*
  - **Convenção**: *"Entenda as normas fundamentais, direitos e obrigações estruturais de todos os condôminos."*
  - **Regimento**: *"Entenda as regras de convivência, uso das áreas comuns e procedimentos de ampla defesa."*
- **Visualização Inline Sem Download Obrigatório**:
  - Visualizador integrado embutido na página com `iframe` responsivo e abas dinâmicas.
  - Botão *"Visualizar Inline"* em cada card que atualiza o documento ativo e desliza com scroll suave até o leitor.
  - Botão secundário *"Abrir em Nova Aba"* (`target="_blank"`) para abertura direta no leitor nativo do navegador.
  - Dica de atalho `Ctrl + F` para busca instantânea nos PDFs pesquisáveis.
  - Botão de retorno imediato à Central de Recursos (`index.php`).

### 2.2 Portal Central: `portal/index.php`
- **Card Chamativo na Etapa 0 (Boas-vindas)**:
  - Posicionado abaixo dos botões de ação com gradiente suave esmeralda/azul e bordas destacadas.
  - Ícone de escudo/verificação, tag *"LGPD & Privacidade"*, título chamativo: *"Seus dados e recursos estão protegidos?"* e chamada convidativa para consultar os normativos.
- **Link Persistente no Rodapé**:
  - Selo/pill discreto e elegante: *"Política de Tratamento de Dados (LGPD) & Normativos"*, visível a qualquer momento.

---

## 3. Arquivos Modificados / Criados

- `portal/normativos.php` *(criado)*
- `portal/index.php` *(modificado)*
- `.agents/raciocinios/2026-10-08_normativos_politica_dados_portal.md` *(criado)*
- `.agents/planos_e_workflows/planos/2026-10-08_normativos_politica_dados_portal.md` *(criado)*
- `.agents/planos_e_workflows/walkthroughs/2026-10-08_normativos_politica_dados_portal.md` *(criado)*

---

## 4. Instruções de Verificação

1. Acesse o portal em `portal/index.php`.
2. Verifique na tela inicial o card chamativo de privacidade e conformidade LGPD.
3. Clique em **"Consultar"** ou no link do rodapé para abrir `portal/normativos.php`.
4. Na página de normativos:
   - Observe a ordem dos 3 cards: **PTD (Prioridade 1)**, **Convenção (Prioridade 2)** e **Regimento (Prioridade 3)**.
   - Teste o clique em *"Visualizar Inline"* nos cards e a alternância rápida pelas abas do visualizador integrado.
   - Teste o botão de *"Tela Cheia / Nova Guia"* para abrir o PDF diretamente na janela do navegador.
