# Walkthrough - Redesign dos Cards de Veículos

## Alterações Realizadas

### 🚗 Layout Centralizado & Cores de Placa por Tipo
1. **Centralização Geral**: O card passa a utilizar `center-align` com a placa em destaque central no topo, seguida pela Descrição (Marca Modelo Cor) e Nome do Proprietário/Morador na linha de baixo.
2. **Cores da Placa por Categoria**:
   - 🚘 **Automóveis**: Placa em tom Grafite / Azul-Escuro (`blue-grey darken-3`) com ícone `directions_car`.
   - 🏍️ **Motocicletas**: Placa em tom Laranja / Âmbar-Escuro (`deep-orange darken-2`) com ícone `two_wheeler`.
   - 🚲 **Bicicletas**: Placa em tom Verde (`green darken-2`) com ícone `pedal_bike`.
3. **PCD Badge**: Posicionamento centralizado do badge de vaga acessível `<i class="material-icons">accessible</i> Vaga PCD`.
4. **Arquivos Atualizados**: [meu.js](file:///e:/DEV/recMan/meu.js) e [palco/detalheRecurso.php](file:///e:/DEV/recMan/palco/detalheRecurso.php).
