# Plano de Implementação: Correção da Tag de Tempo na Sidebar do Livro de Ocorrências

**Data:** 20/11/2026  
**Problema:** Tag de tempo não aparece em alguns chamados na sidebar; ao clicar no chamado ela é renderizada, mas ao atualizar a tela (F5) ela volta a sumir.

---

## Etapas de Execução

1. **Ajuste em `vds_sync_async.php`**:
   - Iterar sobre `$items` retornados por `vds_get_ocorrencias_pratico` e calcular `delays` via `vds_calcular_delays_ocorrencia($it)`.
   - Garantir que cada item enviado no JSON possua os cálculos de tempo já formatados e consistentes com o SSR.

2. **Ajuste no SSR de `livroDeOcorrencias.php`**:
   - Garantir que, se houver ocorrência selecionada (`$detalheSel` carregado), e seu ID não estiver em `$ocorrencias` e `$ocorrenciasAgrupadas`, ela seja inserida no início do array geral e no início do seu respectivo grupo (`$ocorrenciasAgrupadas[$tKeySel]`).
   - Dessa forma, na renderização SSR, o card já é desenhado imediatamente com o estado ativo e os delays corretos via `$mapaDelaysPrecalculados`.

3. **Ajuste no Frontend de `livroDeOcorrencias.php` (`injetarNovosItensDOM`)**:
   - Definir a variável `selectedOcorrenciaId` com o ID selecionado inicialmente no PHP.
   - Atualizar `selectedOcorrenciaId = ocorrenciaId` ao executar `selecionarOcorrencia`.
   - Na criação dinâmica do card em `injetarNovosItensDOM`:
     - Renderizar o HTML do badge de delay (`badge-delay-sidebar`) caso `oco.delays` exista, respeitando status de resolução ou dias em aberto / última interação.
     - Aplicar a classe `.active` caso o ID seja igual a `selectedOcorrenciaId`.
