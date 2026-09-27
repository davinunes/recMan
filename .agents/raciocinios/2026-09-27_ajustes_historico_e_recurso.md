# Raciocínio de Diagnóstico e Correção — Histórico e Detalhe de Recurso

## Data: 2026-09-27
## Tópicos Abordados:
1. Fotos de veículos esticadas no Histórico e Detalhes do Recurso.
2. Botão de Autorizações de Acesso exibindo `[object Object]` e sem ação de ver detalhes.
3. Ocorrências da unidade exibindo `Prot #undefined/undefined`.
4. Adição da lupinha de ver detalhes para Moradores da Unidade na tela `detalheRecurso.php`.

---

## 1. Análise da Causa Raiz

### 1.1 Fotos de Veículos Esticadas
- **Causa Raiz**: O CSS dos cards de veículos em `meu.js` (`renderToolsetVeiculos`) e `palco/detalheRecurso.php` utilizava `width:100%; height:110px; object-fit:cover;`. Como as imagens enviadas pela API da VDS possuem proporção 60x60 (quadradas), a renderização com `width:100%` forçava a foto a esticar na largura do card.
- **Solução**: Ajustar a estilização da tag `<img>` para `width:60px; height:60px; object-fit:cover; aspect-ratio:1/1; margin:0 auto 10px auto; display:block; border-radius:8px; border:1px solid #cfd8dc;`.

### 1.2 Autorizações de Acesso — `[object Object]` e Falta de Ação
- **Causa Raiz**:
  1. Em `meu.js` (`renderToolsetAutorizacoes`) e `detalheRecurso.php`, as propriedades `a.autorizadoPor` e `a.status` por vezes vinham da API como objetos JSON (ex: `{nome: "..."}` ou `{id:1, nome:"Ativo"}`). Ao interpolar em template literal `${a.autorizadoPor}`, o JavaScript convertia o objeto para `[object Object]`.
  2. A função `renderToolsetAutorizacoes` em `meu.js` não continha o botão nem o handler para abrir o modal de inspeção/detalhes.
- **Solução**:
  1. Utilizar sanitizadores de string (`window.vdsExtractStringValue` e `formatObjStr`) para tratar objetos antes de renderizá-los.
  2. Adicionar o botão com ícone de lupa/inspeção `Inspecionar <i class="material-icons right tiny">search</i>`.
  3. Criar a função `window.abrirModalDetalhesAutorizacao(data)` em `meu.js` que renderiza o modal de detalhes completos da autorização de acesso.

### 1.3 Ocorrências com `Prot #undefined/undefined`
- **Causa Raiz**: Em `meu.js` (linhas 2838 e 2873), o código calculava o protocolo assim: `let prot = o.protocolo || (o.numero + '/' + o.ano);`. Quando `o.protocolo` e `o.numero`/`o.ano` eram indefindos, a expressão `(undefined + '/' + undefined)` gerava a string `"undefined/undefined"`.
- **Solução**: Atualizar o fallback de protocolo para: `let prot = o.protocolo || o.protocolo_vds || (o.numero && o.ano ? (o.numero + '/' + o.ano) : (o.id || o.numero || 'N/A'));` garantindo tratamento caso a string resultante contenha `"undefined"`.

### 1.4 Lupinha de Detalhes dos Moradores em `detalheRecurso.php`
- **Causa Raiz**: Na tela `palco/detalheRecurso.php`, a lista de Moradores da Unidade renderizava os cards básicos sem o elemento de overlay `.morador-card-preview-lupa` e `.btn-lupa-preview`, e o modal `#modalDetalhesMorador` não estava incluído no arquivo.
- **Solução**:
  1. Incluir o modal `#modalDetalhesMorador` em `palco/detalheRecurso.php`.
  2. Promover `window.abrirDetalhesMorador` a função global compartilhada em `meu.js`.
  3. Atualizar a renderização de moradores em `detalheRecurso.php` para incluir o botão de lupinha e torna os cards clicáveis.
  4. Adicionar os estilos CSS em `meu.css`.
