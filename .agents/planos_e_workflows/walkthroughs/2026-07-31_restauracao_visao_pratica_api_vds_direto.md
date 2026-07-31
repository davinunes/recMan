# Walkthrough / Resumo de Entrega: Sincronização de Status Não Lido da VDS & Remoção de Leitura Automática Forçada

- **Data**: 2026-07-31
- **Status**: Concluído & Verificado

## 1. Causa Raiz do Botão Laranja ("Marcar NÃO Lido") ao Abrir Ocorrências
- **Diagnóstico**: No arquivo `livroDeOcorrencias.php`, existia um bloco que invocava `vds_marcar_como_lido(..., true)` automaticamente assim que uma ocorrência individual era selecionada no parâmetro `?id=`.
- **Efeito**: Toda vez que o conselheiro clicava em uma mensagem não lida para visualizá-la, o sistema gravava forçadamente `lido = 1` no banco relacional local, fazendo o botão mudar para a cor laranja ("Marcar NÃO Lido (VDS)").

## 2. Correções Aplicadas

1. **Atualização Relacional ao Inspecionar VDS (`Lida=0`)**:
   - Em `classes/vds_ocorrencia_service.php`, dentro da função `vds_get_ocorrencias_pratico`: sempre que um chamado é retornado da consulta de não lidos da VDS (`Lida=0`), o sistema garante no banco relacional local que `lido = 0` (Não Lido) para aquele conselheiro.
   - Isso sincroniza a flag relacional local perfeitamente com a inspeção da VDS.

2. **Remoção da Marcação Automática no Carregamento**:
   - Removida a chamada forçada `vds_marcar_como_lido(..., true)` ao carregar a página em `livroDeOcorrencias.php`.
   - As mensagens não lidas abrem com o status **Não Lido** e o botão exibe a cor **Teal/Verde ("Marcar Lido VDS")**.
   - A mensagem só passa para o status Lido (`lido = 1`) quando o conselheiro clica explicitamente no botão.

## Arquivos Modificados
- [classes/vds_ocorrencia_service.php](file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php)
- [livroDeOcorrencias.php](file:///e:/DEV/recMan/livroDeOcorrencias.php)
