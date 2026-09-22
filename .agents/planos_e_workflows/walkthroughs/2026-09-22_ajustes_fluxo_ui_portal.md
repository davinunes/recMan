# Walkthrough: Ajustes de Fluxo e UI/UX no Portal de Recursos

- **Data**: 2026-09-22
- **Arquivo Modificado**: [`portal/index.php`](file:///e:/DEV/recMan/portal/index.php)

---

## Alterações Realizadas

### 1. Correção do Aninhamento e Posição do Card Informativo (ETAPA 1 - Número e Ano)
- **Correção de Layout**: Fechada a div flexível dos inputs de Número/Ano (`div.flex.gap-4.mb-6`) antes do card informativo.
- **Estruturação de Tela**: O card informativo ocupa agora 100% da largura em sua própria linha, posicionado diretamente abaixo dos campos de entrada de número/ano e acima da barra de botões (`Voltar` / `Continuar`).
- **Card**: Destaque em tom azul com borda esquerda reforçada (`bg-blue-50 border-l-4 border-blue-500 shadow-sm`), ícone `📌` e título **"ATENÇÃO AO ELABORAR SEU RECURSO"**.

### 2. Dica de Ampla Defesa Desvinculada do Card de Upload (ETAPA 5 - Redação do Recurso)
- **Remoção**: Eliminado o aviso amarelo interno que obrigava o condômino a anexar foto/scan da "Cópia da Notificação recebida".
- **Nova Disposição**: O card de **Dica de Ampla Defesa** (`bg-amber-50 border-amber-200 shadow-sm`) foi posicionado **fora do container pontilhado de upload de anexos**, figurando agora como um elemento independente imediatamente anterior à caixa de ciência:
  > 💡 **DICA DE AMPLA DEFESA:** As fotos no PDF são apenas um indicativo de prova. Nos termos do Art. 181 do Regimento Interno, você pode agendar uma visita à Sala de CFTV junto à Administração para assistir à gravação completa antes de protocolar sua defesa. Não baseie seu recurso apenas no quadro impresso.

### 3. Aprimoramento da Caixa de Ciência Obrigatória (ETAPA 5)
- **Visual**: Envelopada em caixa de destaque visual com fundo cinza suave (`p-3.5 bg-gray-50 border border-gray-200 rounded-lg shadow-sm`).
- **Texto**: Atualizada a redação com ressalva sobre a atribuição do Conselho e a presunção do direito de visualização prévia das gravações do CFTV.

---

## Verificação
- Verificado o fechamento correto das tags HTML na ETAPA 1 e na ETAPA 5.
- O card de upload fica exclusivamente dedicado aos anexos e o card de Ampla Defesa ganha destaque autônomo na página.
