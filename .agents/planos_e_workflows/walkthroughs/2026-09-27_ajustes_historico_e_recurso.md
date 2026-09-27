# Resumo de Entrega (Walkthrough / Taklin) — Histórico e Detalhes do Recurso

## Data: 2026-09-27

---

## 1. Modificações Efetuadas

### 1.1 Fotos de Veículos com Proporção Quadrada (60x60px)
- **O que mudou**: Em `meu.js` (`renderToolsetVeiculos`) e `palco/detalheRecurso.php`, a renderização da imagem do veículo foi alterada de `width:100%; height:110px;` para `width:60px; height:60px; object-fit:cover; aspect-ratio:1/1; border-radius:8px; margin:0 auto 10px auto; display:block; border:1px solid #cfd8dc;`.
- **Resultado**: As fotos dos veículos agora mantêm rigorosamente a proporção quadrada de 60x60px centralizada no card, sem serem esticadas ou distorcidas.

---

### 1.2 Autorizações de Acesso — Correção de `[object Object]` e Botão "Inspecionar"
- **O que mudou**: 
  - Em `meu.js` (`renderToolsetAutorizacoes`) e `palco/detalheRecurso.php`, adicionou-se tratamento com `vdsExtractStringValue` / `formatObjStr` nos campos `autorizadoPor` e `status` para evitar a renderização literal da string `[object Object]`.
  - Adicionou-se o botão `Inspecionar <i class="material-icons right tiny">search</i>` em cada card de autorizações de acesso no histórico.
  - Implementou-se a função global `window.abrirModalDetalhesAutorizacao(data)` em `meu.js`, que abre o modal com todos os detalhes da autorização (visitante, documento, período de vigência, morador autorizador, cadastrador e QR code).

---

### 1.3 Ocorrências da Unidade — Solução do `Prot #undefined/undefined`
- **O que mudou**: Em `meu.js` (funções `renderToolsetOcorrenciasAutoria` e `renderToolsetOcorrenciasTag`), atualizou-se o algoritmo de resolução de protocolo com fallbacks encadeados:
  `let prot = o.protocolo || o.protocolo_vds || (o.numero && o.ano ? (o.numero + '/' + o.ano) : (o.id || o.numero || 'N/A'));`
- **Resultado**: As ocorrências registradas pela unidade ou com tag nunca mais exibem `Prot #undefined/undefined`, apresentando corretamente o protocolo remoto da VDS ou o ID local.

---

### 1.4 Moradores da Unidade — Lupinha de Ver Detalhes em `index.php?pag=recurso&rec=...`
- **O que mudou**:
  - Em `palco/detalheRecurso.php`, a seção de Moradores da Unidade foi atualizada para incluir a estrutura `.card-morador-wrapper`, o overlay `.morador-card-preview-lupa` e o botão `.btn-lupa-preview` (`Ver detalhes <i class="material-icons tiny">search</i>`).
  - Adicionou-se a estrutura HTML `#modalDetalhesMorador` em `palco/detalheRecurso.php`.
  - Promoveu-se a função `window.abrirDetalhesMorador` em `meu.js` para ser globalmente acessível, carregando assincronamente os dados completos do morador via `metodo.php?metodo=obterDetalhesMorador`.
  - Adicionaram-se os estilos CSS correspondentes em `meu.css`.

---

## 2. Instruções de Validação

1. **Testar Veículos (`index.php?pag=historico` e `index.php?pag=recurso&rec=...`)**:
   - Abrir o toolset de uma unidade com veículo cadastrado.
   - Confirmar que a foto do veículo aparece centralizada em tamanho quadrado 60x60px sem esticar.

2. **Testar Autorizações de Acesso (`index.php?pag=historico`)**:
   - Expandir o painel "Autorizações de Acesso & Convites".
   - Confirmar que o nome do morador autorizador e o status aparecem em formato de texto limpo (sem `[object Object]`).
   - Clicar no botão **Inspecionar** para abrir os detalhes completos da autorização.

3. **Testar Ocorrências (`index.php?pag=historico`)**:
   - Expandir a aba "Ocorrências Registradas pela Unidade".
   - Confirmar que o número do protocolo exibe valores válidos (ex: `Prot #344/2026` ou `Prot #344`), sem apresentar `undefined/undefined`.

4. **Testar Lupinha de Moradores em `index.php?pag=recurso&rec=344/2026`**:
   - Abrir qualquer tela de detalhe de recurso.
   - Na aba "Moradores da Unidade", passar o mouse sobre o card de um morador.
   - Observar a aparição suave da lupinha **Ver detalhes**.
   - Clicar na lupinha ou no card para abrir o modal de informações completas do morador.
