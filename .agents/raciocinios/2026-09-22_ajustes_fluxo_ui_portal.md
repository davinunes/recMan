# Thinking Log: Ajustes de Fluxo e UI/UX no Portal de Recursos do Condômino

- **Data**: 2026-09-22
- **Tópico**: Atualização de cards informativos, orientação do Art. 181 (CFTV/Ampla Defesa), remoção de obrigatoriedade da cópia da notificação e melhoria da caixa de ciência.

---

## 1. Contexto e Motivação
A integração de dados do sistema já disponibilizou o acesso completo às notificações registradas, dispensando a necessidade anterior de solicitar que o condômino fizesse upload de cópia digitalizada/foto do documento físico da notificação.

Além disso, observou-se a importância jurídica de esclarecer a dinâmica de ampla defesa referente às fotos/prints estáticos presentes nas notificações em PDF. O Artigo 181 do Regimento Interno concede o direito ao morador de solicitar o agendamento para assistir ao vídeo completo presencialmente na Sala de CFTV acompanhado da Administração antes de protocolar sua defesa.

---

## 2. Modificações Efetuadas em `portal/index.php`

### 2.1 Adição de Card Informativo na ETAPA 1 (Identificação: Número e Ano)
- **Local**: Logo abaixo dos campos de input do número/ano e mensagem de erro, antes dos botões de ação.
- **Formato**: Card em destaque azul (`bg-blue-50 border-l-4 border-blue-500`) com ícone `📌` e título "ATENÇÃO AO ELABORAR SEU RECURSO".
- **Conteúdo**: Texto na íntegra informando sobre a gravação completa na Sala de CFTV nos termos do Art. 181 do Regimento Interno.

### 2.2 Atualização da Seção de Anexos na ETAPA 5 (Redação do Recurso)
- **Ação**: Removida a mensagem amarela anterior que solicitava o anexo obrigatório da "Cópia da Notificação recebida".
- **Substituição**: Inserido card com tom âmbar suave (`bg-amber-50 border-amber-200`) trazendo a dica resumida:
  > 💡 **DICA DE AMPLA DEFESA:** As fotos no PDF são apenas um indicativo de prova. Nos termos do Art. 181 do Regimento Interno, você pode agendar uma visita à Sala de CFTV junto à Administração para assistir à gravação completa antes de protocolar sua defesa. Não baseie seu recurso apenas no quadro impresso.

### 2.3 Aprimoramento da Caixa de Ciência Obrigatória na ETAPA 5
- **Visual**: Envelopada em container estilizado (`p-3.5 bg-gray-50 border border-gray-200 rounded-lg shadow-sm`).
- **Texto**: Redação jurídica e objetiva reafirmando a atuação do Conselho de Administração e a ciência do morador sobre o seu direito de consulta ao CFTV antes da decisão final.

---

## 3. Validação
- Verificado código HTML e classes Tailwind CSS em `portal/index.php`.
- Nenhuma alteração destrutiva ou de backend foi exigida, garantindo compatibilidade total.
