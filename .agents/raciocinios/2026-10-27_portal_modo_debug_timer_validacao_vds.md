# Raciocínio de Diagnóstico e Implementação: Modo Debug, Timer e Validação de Unidade VDS no Portal

- **Data**: 2026-10-27
- **Autor**: Antigravity
- **Contexto**: Ajustes no assistente de recursos do portal do morador (`portal/index.php` e `portal/api.php`).

---

## 1. Necessidades do Usuário

1. **Modo Debug (Simulação via URL)**:
   - Parâmetro na URL (ex: `?modo-debug` ou `?modo-debug=1` ou `?debug=1`).
   - O assistente funcionará como simulação completa:
     - Permite navegar por todas as etapas.
     - Não registra o recurso no banco de dados.
     - Não envia notificações push.
     - Não envia e-mails (código de verificação nem e-mail ao síndico).
     - Aceita qualquer token inserido.
     - Banner/alerta visual indicando que está em modo simulação.

2. **Timer de Leitura para os Inputs da Notificação (Etapa 1)**:
   - Evitar que o morador pule imediatamente para digitar o número sem ler o card de orientações sobre o CFTV / Art. 181 do Regimento Interno.
   - Posicionar o card com destaque e segurar a exibição dos inputs de número/ano por alguns segundos (ex: 6 a 8 segundos), exibindo contagem regressiva amigável.
   - Liberar os campos de forma suave com Alpine.js (`x-transition`).
   - Em modo debug, disponibilizar atalho de pular timer para facilitar testes repetidos.

3. **Validação de Unidade do Morador via Integração VDS**:
   - Assim que o e-mail do usuário é conhecido e validado (Etapa 3 / 4), consultar a API VDS (`vds_busca_generica($email, 'MORADOR')`) para identificar a(s) unidade(s) e bloco(s) onde este e-mail está cadastrado.
   - Comparar a unidade/bloco da notificação com as unidades cadastradas na VDS para aquele e-mail.
   - Se houver divergência (ex: notificação vinculada a outra unidade ou e-mail não encontrado no cadastro da VDS), exibir um card de aviso na Etapa 5 alertando o morador e permitindo que ele confira/corrija Bloco e Unidade.
   - Regra estrita: isso **não deve ser impeditivo**, o morador poderá protocolar normalmente mesmo que haja divergência cadastral.

---

## 2. Análise Técnica e Componentes

### 2.1 Modo Debug
- **Sessão PHP**: Salvar `$_SESSION['portal_modo_debug'] = true` quando detectado parâmetro GET `modo-debug` ou `debug`.
- **Frontend Alpine**:
  - Variável reativa `modoDebug`.
  - Banner no topo do assistente indicando `[Modo Simulação / Debug Ativo]`.
  - Ao enviar código (`send_token`): backend não dispara Gmail e retorna o token simulado (ou aceita qualquer token no `verify_token`).
  - Ao protocolar (`submit`): backend detecta `$_SESSION['portal_modo_debug']` e simula resposta de sucesso sem gravar nas tabelas `recurso`, `recurso_anexos` e sem chamar push/e-mail.
  - Tela final exibe aviso de simulação concluída.

### 2.2 Timer da Etapa 1
- Reposicionar o card do Art. 181 para o topo da Etapa 1.
- Iniciar contador reativo `timerLeitura` (ex: 6 segundos) ao entrar na Etapa 1.
- Exibir badge com contagem decrescente: "Por favor, leia as orientações acima... Liberando campos em Xs".
- Ao zerar, os inputs de Número e Ano e o botão Continuar são revelados com `x-transition`.
- Se `modoDebug`, permitir botão "Pular Leitura (Debug)".

### 2.3 Validação de Unidade via VDS
- Helper PHP `vds_buscar_unidades_por_email($email)`:
  - Invoca `vds_busca_generica($email, 'MORADOR')` e se vazio tenta `'ALL'`.
  - Processa os resultados para extrair bloco e unidade (analisa `subtitulo` e detalhes de morador).
- Endpoint `api.php?action=verificar_unidade_vds`:
  - Recebe `email`, `numero`, `ano`.
  - Obtém unidade/bloco da notificação (banco local ou Supabase).
  - Obtém unidades do e-mail na VDS.
  - Retorna `{ match: bool, divergente: bool, unidades_vds: array, unidade_notificacao: object, aviso: string|null }`.
- No Frontend:
  - Disparado ao validar o token (ou logo após envio do token).
  - Na Etapa 5, renderiza um card amarelo/laranja informativo caso haja divergência ou caso o e-mail não seja encontrado na VDS, orientando a conferência dos dados.
