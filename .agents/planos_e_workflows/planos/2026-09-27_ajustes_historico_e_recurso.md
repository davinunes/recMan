# Plano de Implementação — Correções no Histórico e Detalhes do Recurso

## Data: 2026-09-27

## Objetivos
1. **Veículos**: Preservar proporção quadrada 60x60px sem esticar imagens.
2. **Autorizações de Acesso**: Corrigir exibição de `[object Object]` nos botões/badges e disponibilizar ação de ver detalhes/inspecionar.
3. **Ocorrências**: Tratar fallback de protocolo para evitar `Prot #undefined/undefined`.
4. **Moradores da Unidade**: Adicionar botão/lupinha de ver detalhes com modal na tela `index.php?pag=recurso&rec=...` (`detalheRecurso.php`).

---

## Arquivos a Serem Modificados

1. `meu.css`:
   - Adicionar estilos dos cards de morador (`.card-morador-wrapper`, `.morador-card-preview-lupa`, `.btn-lupa-preview`, `.detalhe-morador-foto-wrap`, badges de status).
2. `meu.js`:
   - Atualizar `renderToolsetVeiculos` com foto 60x60px centralizada.
   - Atualizar `renderToolsetAutorizacoes` com sanitização de `autorizadoPor` e `status` + botão `Inspecionar`.
   - Adicionar `window.abrirModalDetalhesAutorizacao`.
   - Atualizar `renderToolsetOcorrenciasAutoria` e `renderToolsetOcorrenciasTag` com fallback seguro de protocolo.
   - Tornar `window.abrirDetalhesMorador` global e disponível para `detalheRecurso.php`.
3. `palco/detalheRecurso.php`:
   - Atualizar renderização de fotos de veículos para 60x60px.
   - Corrigir sanitização de `aut.autorizadoPor` e `aut.status` na tabela de autorizações.
   - Atualizar renderização de moradores para incluir o botão/lupinha `.btn-lupa-preview` e handler `window.abrirDetalhesMorador`.
   - Adicionar o HTML do `#modalDetalhesMorador`.

---

## Verificação
- Verificar integridade do código PHP e JS sem alterar dependências ou introduzir seletores redundantes.
- Garantir conformidade com as regras globais e LGPD.
