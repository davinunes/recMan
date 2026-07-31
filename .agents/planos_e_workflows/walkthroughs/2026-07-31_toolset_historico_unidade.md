# Walkthrough - Tratamento Visual de Veículos Inativos / Desvinculados

## Alterações Realizadas

### 🚫 Mapeamento & Estilização de Veículos Inativos
1. **Regra de Verificação (`ativo`)**:
   - Analisados os campos `item.ativo`, `auto.ativo`, `item.status` e datas de exclusão (`dtExclusao`, `dtFim`).
   - Se `status !== 0` ou `ativo === false` ou se houver data de exclusão, o veículo é marcado como `'ativo' => false`.
2. **Estilização no Frontend**:
   - **Opacidade e Esmaecimento**: O card do veículo recebe `opacity: 0.65`, `filter: grayscale(30%)` e borda tracejada (`border: 1px dashed #b0bec5`).
   - **Placa Neutra**: A placa do veículo inativo passa a ser renderizada em cor cinza (`grey darken-2`).
   - **Badge de Inatividade**: Exibição da etiqueta `<i class="material-icons">block</i> INATIVO`.
3. **Arquivos Atualizados**:
   - [vds_acesso_service.php](file:///e:/DEV/recMan/classes/vds_acesso_service.php)
   - [meu.js](file:///e:/DEV/recMan/meu.js)
   - [palco/detalheRecurso.php](file:///e:/DEV/recMan/palco/detalheRecurso.php)
