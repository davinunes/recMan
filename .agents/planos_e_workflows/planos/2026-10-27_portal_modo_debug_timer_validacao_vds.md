# Plano de Implementação: Modo Debug, Timer e Validação de Unidade VDS no Portal do Morador

- **Data**: 2026-10-27
- **Status**: Em Execução
- **Arquivos Envolvidos**:
  - `portal/index.php` (Frontend Alpine.js + templates de tela)
  - `portal/api.php` (Endpoints backend: `check_notification`, `send_token`, `verify_token`, `verificar_unidade_vds`, `submit`)
  - `classes/vds_acesso_service.php` (Serviços VDS auxiliares para busca por e-mail)

---

## Fases de Execução

### Fase 1: Suporte a Modo Debug (Simulação)
1. Detectar parâmetro `modo-debug` ou `debug` na URL via PHP (`$_GET`) e persistir em `$_SESSION['portal_modo_debug']`.
2. Adicionar indicador visual no header do portal (`portal/index.php`) com aviso de simulação quando ativo.
3. No backend (`portal/api.php`):
   - `send_token`: Em modo debug, salvar o token simulado na sessão sem chamar a API do Gmail e retornar `debug: true`.
   - `verify_token`: Em modo debug, aceitar qualquer token digitado pelo usuário (ou o simulado).
   - `submit`: Em modo debug, simular sucesso imediato sem inserir no banco de dados (`recurso`), sem mover anexos permanentes, sem enviar push notification e sem enviar e-mails ao condomínio/síndico.
4. Na tela de sucesso (Etapa 6): Exibir nota amigável de que se trata de uma simulação.

### Fase 2: Timer de Leitura na Identificação da Notificação (Etapa 1)
1. Reorganizar os elementos da Etapa 1 em `portal/index.php`:
   - Colocar o card do Art. 181 (orientações de CFTV e ampla defesa) logo no topo da Etapa 1.
2. Inserir estado no Alpine.js:
   - `timerLeitura`: Contador decrescente (ex: 6 segundos).
   - `timerLiberado`: Booleano indicando quando os inputs podem ser revelados.
3. Enquanto `timerLiberado == false`:
   - Ocultar os campos de Número e Ano e o botão Continuar.
   - Exibir badge elegante com barra de progresso / contador: *"Por favor, leia as orientações acima com atenção. Liberando campos em Xs..."*.
   - Se `modoDebug`, permitir botão "Pular Leitura" para agilizar testes.
4. Quando o timer atingir 0:
   - Revelar suavemente com `x-transition` os campos de Número e Ano e o botão Continuar.

### Fase 3: Validação da Unidade do Morador via Integração VDS
1. Implementar helper no backend `vds_buscar_unidades_por_email($email)`:
   - Conectar com `vds_busca_generica($email, 'MORADOR')`.
   - Processar itens e extrair `bloco` e `unidade`.
2. Criar action `verificar_unidade_vds` em `portal/api.php`:
   - Recebe `email`, `numero` e `ano`.
   - Localiza a unidade/bloco da notificação informada.
   - Localiza a(s) unidade(s) cadastradas para o e-mail na VDS.
   - Retorna o status da conferência e eventuais divergências.
3. No frontend (`portal/index.php`):
   - Chamar `verificar_unidade_vds` após validação do token (transição etapa 4 -> 5).
   - Na Etapa 5 (Formulário de Redação), exibir card informativo se:
     - As unidades divergirem (ex: Morador cadastrado no Bloco A Unidade 101, mas notificação informada para Bloco B Unidade 504).
     - O e-mail não for encontrado em nenhuma unidade cadastrada na VDS.
   - Garantir que o card oriente a conferência dos campos de Bloco e Unidade abaixo, deixando explícito que o envio NÃO é bloqueado.

### Fase 4: Validação e Testes
1. Testar modo debug via URL `portal/index.php?modo-debug=1`.
2. Testar timer de leitura da etapa 1.
3. Testar a consistência do fluxo de submissão simulada e avisos de unidade.
4. Gerar walkthrough de entrega.
