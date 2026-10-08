# Raciocínio: Nova Página de Normativos e Política de Tratamento de Dados no Portal

**Data**: 2026-10-08  
**Contexto**: Inclusão de página com visualização inline dos documentos normativos na pasta `portal/docs/normativos/`, link chamativo na Central de Recursos com foco em Política de Tratamento de Dados (LGPD).

---

## 1. Compreensão da Solicitação

O usuário solicitou:
1. **Link chamativo no portal**:
   - Descrição que chame atenção para a política de tratamento de dados.
   - Texto atraente/persuasivo.
2. **Nova página no portal**:
   - Disponibilizar a visualização dos 3 documentos da pasta `portal/docs/normativos`:
     1. PTD (`politica-tratamento-dados-conselho-miami-2025-2027.pdf`)
     2. Convenção (`cc-miami-pesquisavel-interativo.pdf`)
     3. Regimento (`ri-miami-pesquisavel-interativo.pdf`)
   - Ordem de prioridade estrita: **PTD, Convenção, Regimento**.
   - Visualização inline para evitar download forçado e abrir diretamente nos navegadores.
   - Cards com breve explicação do tipo "Entenda..." apontando para cada documento.

## 2. Análise Técnica e Arquitetural

1. **Localização dos Documentos**:
   - Pasta física: `portal/docs/normativos/`
   - Arquivos existentes confirmados:
     - `politica-tratamento-dados-conselho-miami-2025-2027.pdf`
     - `cc-miami-pesquisavel-interativo.pdf`
     - `ri-miami-pesquisavel-interativo.pdf`
   - No servidor web Apache/Nginx, servir o PDF com link direto ou embutido em `iframe` com `#toolbar=1&navpanes=0` garante que o navegador renderize inline com os leitores integrados nativos (Chrome PDF Viewer, Firefox PDF.js, Safari PDF Viewer, Edge).

2. **Página Nova (`portal/normativos.php`)**:
   - Utilizar a mesma identidade visual de `portal/index.php` (Tailwind CSS via CDN, Material Icons, Alpine.js).
   - Componentes da página:
     - Cabeçalho com logo do Conselho, título "Documentos Normativos & Política de Privacidade", botão de retorno à Central de Recursos.
     - Alerta/Banner superior de segurança e conformidade LGPD (Lei nº 13.709/2018).
     - 3 Cards destacados na ordem de prioridade (PTD em destaque principal com badge de prioridade, Convenção e Regimento), com chamadas do tipo "Entenda como tratamos seus dados...", "Entenda as normas fundamentais...", "Entenda as regras de convivência...".
     - Botões em cada card: "Visualizar no Painel (Inline)" e "Abrir no Navegador (Nova Aba)".
     - Leitor inline integrado com alternância instantânea de abas, tela cheia e barra de status do documento em exibição.

3. **Chamada no Portal (`portal/index.php`)**:
   - Inserir na Etapa 0 do assistente um card chamativo com gradiente elegante, ícone de escudo/segurança, badge "LGPD & Transparência" e texto persuasivo.
   - Inserir também no rodapé um botão/pill persistente para que o visitante possa acessar a política e normativos a qualquer momento sem perder o contexto do recurso.
