# Walkthrough: Detecção e Autoformatação de Placas de Veículo na Busca Rápida VDS

## Resumo das Alterações
Atendendo à necessidade da API VDS (que exige a presença do hífen `-` para correspondência de placas de veículos, inclusive no padrão Mercosul), implementamos um mecanismo inteligente e robusto de detecção de padrões de placas no campo de Busca Rápida VDS da tela de Histórico (`index.php?pag=historico#!`).

Caso o usuário digite a placa sem hífen (seja padrão antigo cinza, Mercosul carro ou Mercosul moto), o sistema adiciona automaticamente o hífen antes do envio da requisição para a API VDS, atualizando também o campo em tela e fornecendo feedback ao usuário.

---

## Detalhes Técnicos da Implementação

### 1. Padrões de Placa Cobertos
- **Padrão Tradicional (Cinza)**: 3 letras + 4 números
  - Ex: `ABC1234` ou `abc1234` $\rightarrow$ `ABC-1234`
- **Padrão Mercosul (Automóveis/Comerciais)**: 3 letras + 1 número + 1 letra + 2 números
  - Ex: `BRA2E19` ou `bra2e19` $\rightarrow$ `BRA-2E19`
- **Padrão Mercosul (Motocicletas)**: 3 letras + 2 números + 1 letra + 1 número
  - Ex: `BRA22E9` ou `bra22e9` $\rightarrow$ `BRA-22E9`
- **Variações com espaço ou separador**:
  - Ex: `BRA 2E19` $\rightarrow$ `BRA-2E19`
  - Ex: `abc 1234` $\rightarrow$ `ABC-1234`
- **Proteção contra Falsos Positivos**:
  - Lista de siglas e prefixos comuns em condomínios (`APT`, `APTO`, `BLC`, `GAR`, `VAG`, `TOR`, `LOT`, etc.) que impede termos como `apt 1001` de serem formatados indevidamente.

---

### 2. Arquivos Modificados

1. **[`palco/historico.php`](file:///e:/DEV/recMan/palco/historico.php)**:
   - Adicionada a função JavaScript `normalizarPlacaVeiculo(termo)`.
   - Modificado `window.executarBuscaVDS` para validar e normalizar o termo digitado em `#vdsBuscaQuery`.
   - Adicionado auto-update visual no input `#vdsBuscaQuery` com a placa formatada e maiúscula.
   - Adicionado listener de eventos `change` e `blur` no input `#vdsBuscaQuery` para formatação imediata.
   - Atualizado a barra de status de busca (`vdsBuscaFilterText`) para exibir o aviso indicando a placa detectada e formatada com hífen.

2. **[`classes/vds_acesso_service.php`](file:///e:/DEV/recMan/classes/vds_acesso_service.php)**:
   - Implementada a função PHP `vds_normalizar_termo_placa($busca)`.
   - Integrada em `vds_busca_generica($busca, $tipo, $usuarioIdConselho)` antes do envio da URL cURL para a VDS (`/registros?tipo={tipo}&busca={busca}`).
   - Incluídos os campos `'termo_original'` e `'termo_buscado'` na resposta para rastreabilidade completa.

---

## Roteiro de Validação

1. Abra a tela de Histórico: `index.php?pag=historico#!`.
2. Clique no botão roxo **BUSCA RÁPIDA VDS** no topo da página.
3. No campo de busca:
   - Digite uma placa Mercosul sem hífen (ex: `bra2e19`) e pressione **Enter** ou clique em **PESQUISAR**.
   - Observe que o campo é imediatamente formatado para `BRA-2E19`.
   - Na barra de resultados, confira o texto: `Filtro: ALL • Placa formatada com hífen (BRA-2E19)`.
   - A requisição para a API VDS é enviada com o hífen, retornando os veículos correspondentes cadastrados.
4. Teste com placa padrão antigo (ex: `abc1234`), observando a conversão para `ABC-1234`.
5. Teste com termos condominiais (ex: `101`, `apt 101`, `bloco B`) e verifique que não sofrem alteração indevida.
