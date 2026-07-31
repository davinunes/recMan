# Plano de Implementação: Ações via AJAX sem Reload e Ícones de Responsabilidade

- **Data**: 2026-07-31
- **Objetivo**: Substituir o `<select>` de responsabilidades por botões ícone interativos (`sindico`, `sub`, `não atribuído`) e transformar as ações de "Marcar Resolvido", "Marcar Lido" e "Responsabilidade" em requisições AJAX puras com animação restrita ao botão clicado.

## Componentes a Serem Modificados

### 1. Processamento de Ações AJAX no Backend (`livroDeOcorrencias.php`)
- Detectar se a requisição é AJAX (`$_REQUEST['is_ajax']` ou `HTTP_X_REQUESTED_WITH`).
- Nos handlers `atualizar_responsabilidade`, `marcar_resolvido` e `marcar_como_lido`:
  - Executar as queries SQL / chamadas VDS normalmente.
  - Retornar o JSON com os novos valores e encerrar com `exit;`.

### 2. Nova Interface de Responsabilidade por Ícones (`livroDeOcorrencias.php`)
- Substituir o `<select>` por um grupo de 3 botões ícone:
  - `Não Atribuído` (valor `''`, ícone `person_off`)
  - `Síndico` (valor `'sindico'`, ícone `gavel`)
  - `Subsíndico` (valor `'sub'`, ícone `badge`)
- Destacar visualmente o botão correspondente ao valor atualmente gravado no banco.

### 3. Animações e Atualização Dinâmica no DOM (`livroDeOcorrencias.php`)
- Adicionar função JS `executarAcaoAjaxOcorrencia(action, params)`:
  - Anima apenas o botão específico clicado (exibe spinner no ícone e ajusta opacidade).
  - Mantém o container do chat 100% visível, sem ativar o esqueleto (skeleton).
  - Ao receber a resposta JSON:
    - Atualiza os botões do header do chat.
    - Atualiza os selos de status ("✓ Resolvido", "Resp: SÍNDICO") no card correspondente na lista da barra lateral.

## Plano de Verificação Manual
1. Clicar nos ícones de Responsabilidade (Síndico, Subsíndico, Não Atribuído) e confirmar que o destaque muda imediatamente e a barra lateral reflete o novo responsável sem recarregar a tela.
2. Clicar em "Marcar Resolvido (Local)" e "Marcar Lido (VDS)" e verificar que apenas o botão clicado se anima e alterna de estado sem esqueleto na conversa.
