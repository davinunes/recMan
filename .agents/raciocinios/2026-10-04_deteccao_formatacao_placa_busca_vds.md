# Raciocínio: Detecção e Autoformatação de Placas de Carro na Busca Rápida VDS

## Contexto do Pedido
O usuário solicitou que na funcionalidade "Busca Rápida VDS" (`index.php?pag=historico#!`) seja detectado o padrão de placa de veículo brasileiro. A API da VDS exige o hífen (`-`) mesmo no modelo Mercosul (ex: `BRA-2E19` e `ABC-1234`). Quando o usuário digita a placa sem o hífen (ex: `bra2e19` ou `abc1234`), a busca não encontra correspondência porque a VDS armazena com hífen. O sistema deve detectar o padrão de placa e enviar obrigatoriamente com hífen na requisição para a VDS.

## Análise Técnica dos Padrões de Placa Brasileiros
1. **Padrão Tradicional (Cinza)**:
   - Formato: LLL-NNNN (3 letras, 4 números).
   - Sem hífen: `ABC1234`.
   - Com hífen: `ABC-1234`.

2. **Padrão Mercosul (Automóveis/Comerciais)**:
   - Formato: LLL-NLNN (3 letras, 1 número, 1 letra, 2 números).
   - Sem hífen: `BRA2E19`.
   - Com hífen: `BRA-2E19`.

3. **Padrão Mercosul (Motocicletas)**:
   - Formato: LLL-NNLN (3 letras, 2 números, 1 letra, 1 número).
   - Sem hífen: `BRA22E9`.
   - Com hífen: `BRA-22E9`.

4. **Variações comuns de digitação**:
   - Sem hífen: `abc1234`, `bra2e19`
   - Com espaço: `abc 1234`, `bra 2e19`
   - Já com hífen mas minúscula: `abc-1234`, `bra-2e19`
   - Em frase: `carro bra2e19`

## Prevenção de Falsos Positivos
Para evitar que termos comuns em buscas condominiais sejam interpretados incorretamente como placas (ex: `apt 1001`), estabelecemos uma lista de exclusão de siglas condominiais de 3 letras:
`['APT', 'APTO', 'BLC', 'GAR', 'VAG', 'TOR', 'LOT', 'RES', 'CON', 'DOC', 'REF', 'NUM', 'TEL']`.

## Arquitetura da Solução
A normalização é implementada em duas camadas complementares:

1. **Frontend (`palco/historico.php`)**:
   - Criação da função JS `normalizarPlacaVeiculo(termo)`.
   - Interceptação em `window.executarBuscaVDS()` para formatar a query antes do envio via AJAX para `api/vds_busca_generica.php`.
   - Atualização visual no campo `#vdsBuscaQuery` com o valor corrigido/formatado.
   - Event listener `blur` e `change` no `#vdsBuscaQuery` para formatar a placa logo após a digitação.
   - Feedback na barra de status da busca indicando a detecção e formatação da placa.

2. **Backend (`classes/vds_acesso_service.php` & `api/vds_busca_generica.php`)**:
   - Criação da função PHP `vds_normalizar_termo_placa($busca)`.
   - Chamada interna em `vds_busca_generica($busca, $tipo, $usuarioIdConselho)` antes de montar a URL cURL `/registros?tipo=...&busca=...`.
   - Retorno dos campos `termo_original` e `termo_buscado` na resposta JSON para rastreabilidade e auditoria.
