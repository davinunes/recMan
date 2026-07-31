# Walkthrough - Integração das Vagas de Garagem no Toolset e na Análise de Recurso

## Alterações Realizadas

### 🅿️ Exibição das Vagas de Garagem do Banco Local (`conselho.estacionamento`)
1. **Recuperação das Vagas**: Utilizada a função nativa `getEstacionamento($bloco, $unidade)` para recuperar as vagas e locais atribuídos à unidade (ex: *Vaga 105 (G1)*).
2. **Cabeçalho da Análise de Recurso (`detalheRecurso.php`)**:
   - Inserido o chip de vaga no topo do painel premium: `<i class="material-icons">local_parking</i> Vaga(s): Vaga 105 (G1)`.
3. **Banner na Seção de Veículos**:
   - Adicionado um banner destacado no topo da seção/aba de **Veículos da Unidade** tanto no Toolset (`index.php?pag=historico`) quanto em `detalheRecurso.php`.
4. **Backend (`metodo.php`)**:
   - O endpoint `metodo=toolsetUnidade` agora consulta e retorna a lista `'vagas'` do banco local.
