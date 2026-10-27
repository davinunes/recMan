# Walkthrough de Entrega: Modo Debug, Timer e Validação de Unidade VDS no Portal do Morador

- **Data**: 2026-10-27
- **Autor**: Antigravity
- **Repositório**: `recMan`

---

## 1. Visão Geral dos Ajustes Realizados

Atendendo à solicitação do usuário, implementamos 3 grandes melhorias no assistente de recursos (`portal/index.php` e `portal/api.php`):

1. **Modo Debug / Simulação via URL**:
   - Pode ser ativado via query string na URL: `?modo-debug`, `?modo-debug=1` ou `?debug=1`.
   - Permite que o testador/desenvolvedor percorra todo o fluxo do assistente.
   - **Sem gravação de dados**: nenhuma inserção na tabela `recurso`, nem movimentação de anexos definitivos.
   - **Sem envio de e-mails**: a API do Gmail não é disparada nem para o morador nem para o síndico/diretoria.
   - **Sem envio de Push Notification**.
   - **Aceite irrestrito de token**: aceita o token simulado gerado (`123456`) ou qualquer outro código digitado na tela de verificação.
   - **Feedback visual explícito**: exibe um banner pontilhado elegante no topo do assistente informando que o modo simulação está ativo e badge na tela de sucesso.

2. **Timer de Leitura para os Inputs da Notificação (Etapa 1)**:
   - Reorganizamos a Etapa 1: o card informativo de ampla defesa e solicitação de CFTV (Art. 181 do Regimento Interno) agora fica **no topo** com visual de alta prioridade.
   - Enquanto o morador está na tela, um timer decrescente de 6 segundos é acionado com aviso amigável: *"Por favor, leia atentamente as orientações acima... Liberando o preenchimento da notificação em X segundo(s)"*.
   - Os inputs de Número e Ano e o botão "Continuar" só aparecem suavemente (`x-transition`) após o término do timer, garantindo tempo para leitura.
   - Em Modo Debug, um atalho rápido `[⚡ Pular Timer de Leitura (Debug)]` fica disponível para acelerar testes repetidos.

3. **Validação Preventiva de Unidade via Integração VDS**:
   - Criamos o serviço `vds_buscar_unidades_por_email($email)` em `classes/vds_acesso_service.php` utilizando os endpoints de busca de morador da API v8 da VDS (`/registros?tipo=MORADOR`).
   - Criamos o endpoint `verificar_unidade_vds` em `portal/api.php`.
   - Assim que o e-mail do morador é validado, o sistema consulta a VDS para saber em qual(is) unidade(s) e bloco(s) o morador está registrado.
   - **Privacidade e Proteção de Dados (LGPD)**: Se houver divergência (ex: morador digitou número de notificação de outro imóvel ou se equivocou), o sistema **NUNCA** revela para qual unidade aquela notificação foi emitida nem no texto da tela nem no JSON retornado. O sistema informa apenas para qual unidade o **e-mail do morador** comprovado está vinculado na VDS e que ela não corresponde à notificação digitada.
   - Se a unidade informada na notificação divergir da unidade cadastrada na VDS (ou caso o e-mail não conste na base da portaria), a Etapa 5 exibe o card de alerta âmbar em destaque:
     - Informa a divergência e sugere o preenchimento com a unidade comprovada do morador.
     - **Não impeditivo**: orienta o morador a revisar os campos de Bloco e Unidade nos campos abaixo, mas permite que ele conclua e protocole o recurso normalmente caso os dados estejam corretos no documento físico.


---

## 2. Arquivos Alterados

| Arquivo | Descrição das Modificações |
|---|---|
| [`classes/vds_acesso_service.php`](file:///e:/DEV/recMan/classes/vds_acesso_service.php) | Adicionada a função `vds_buscar_unidades_por_email($email)` com parser inteligente de bloco, unidade e dados da API VDS. |
| [`portal/api.php`](file:///e:/DEV/recMan/portal/api.php) | Adicionada detecção e persistência de sessão para `$isModoDebug`. Adaptados `send_token`, `verify_token` e `submit` para simulação segura. Adicionada action `verificar_unidade_vds`. |
| [`portal/index.php`](file:///e:/DEV/recMan/portal/index.php) | Adicionado banner visual de modo simulação, reposicionado card do Art. 181, incluído timer de leitura decrescente na Etapa 1, card informativo de divergência de unidade na Etapa 5 e métodos reativos no Alpine.js. |
