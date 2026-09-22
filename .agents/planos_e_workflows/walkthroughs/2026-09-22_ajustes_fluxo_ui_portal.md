# Walkthrough: Ajustes de Fluxo e UI/UX no Portal de Recursos

- **Data**: 2026-09-22
- **Arquivo Modificado**: [`portal/index.php`](file:///e:/DEV/recMan/portal/index.php)

---

## Alterações Realizadas

### 1. Card Informativo Completo na Segunda Tela (ETAPA 1 - Número e Ano)
- **Localização**: Adicionado imediatamente antes dos botões de navegação na Etapa 1.
- **Card**: Destaque em tom azul com borda esquerda reforçada (`bg-blue-50 border-l-4 border-blue-500 shadow-sm`), ícone `📌` e o título **"ATENÇÃO AO ELABORAR SEU RECURSO"**.
- **Texto**:
  > As imagens (prints) anexadas à notificação servem apenas para comprovar a existência do registro do fato. Para garantir o seu direito à ampla defesa, o morador tem o direito de agendar a visualização do vídeo completo presencialmente na Sala de Operações de CFTV, acompanhado pela Administração, nos termos do Artigo 181 do Regimento Interno. Evite fundamentar sua defesa apenas na imagem estática impressa: conheça o contexto integral das filmagens antes de enviar seu recurso.

### 2. Substituição do Aviso de Anexo da Notificação (ETAPA 5 - Redação do Recurso)
- **Remoção**: Eliminado o aviso amarelo que obrigava o condômino a anexar foto/scan da "Cópia da Notificação recebida".
- **Nova Mensagem Resumida**: Inserido card em tom âmbar (`bg-amber-50 border-amber-200 shadow-sm`) com a dica de ampla defesa:
  > 💡 **DICA DE AMPLA DEFESA:** As fotos no PDF são apenas um indicativo de prova. Nos termos do Art. 181 do Regimento Interno, você pode agendar uma visita à Sala de CFTV junto à Administração para assistir à gravação completa antes de protocolar sua defesa. Não baseie seu recurso apenas no quadro impresso.

### 3. Aprimoramento da Caixa de Ciência Obrigatória (ETAPA 5)
- **Visual**: Envelopada em caixa de destaque visual com fundo cinza suave (`p-3.5 bg-gray-50 border border-gray-200 rounded-lg shadow-sm`).
- **Novo Texto**:
  > Declaro ter ciência de que o Conselho de Administração atua na análise e julgamento do recurso. Confirmo estar ciente do meu direito de agendar a visualização prévia da gravação completa na Sala de CFTV junto à Administração (nos termos do Art. 181 do Regimento Interno) para a plena elaboração da minha defesa antes da decisão final.

---

## Verificação
- O arquivo `portal/index.php` foi validado quanto à sintaxe HTML e estruturação dos componentes Alpine.js/Tailwind CSS.
- O fluxo de etapas (Etapas 0 a 8) permanece 100% funcional sem alterar a lógica de submissão do formulário ou backend.
