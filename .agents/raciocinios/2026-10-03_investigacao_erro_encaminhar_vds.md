# Investigação e Diagnóstico: Erro ao Encaminhar Ocorrência na VDS (httpCode: 0)

**Data**: 2026-10-03  
**Contexto**: Ao submeter o formulário de encaminhamento de ocorrência na VDS pela interface (`livroDeOcorrencias.php`), o sistema retornou:
```json
{"success":false,"httpCode":0,"message":"Erro ao encaminhar ocorrência na VDS."}
```
Contudo, ao consultar a ocorrência (`55397320`), constatou-se que o encaminhamento foi **efetivado com sucesso no servidor da VDS**, gerando múltiplos eventos tipo 79 ("Encaminhado para Gregório Rabelo", por Davi Nunes) às 11:56:11, 11:56:24 e 11:57:43.

---

## 1. Causa Raiz Identificada

No arquivo [`classes/vds_ocorrencia_service.php`](file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php#L1570-L1631) na função `vds_encaminhar_ocorrencia`:

1. **Timeout cURL excessivamente baixo (`CURLOPT_TIMEOUT => 10`)**:
   - A operação de encaminhamento na API da VDS (`POST https://apiv8.vds.app.br/ocorrencia/encaminhar`) é custosa no servidor remoto: altera status de fila, gera notificações push para o aplicativo do funcionário, dispara e-mails e registra logs.
   - O processamento na VDS frequentemente leva entre 11 e 15 segundos para responder HTTP 200.
   - O cURL do recMan estava configurado com apenas 10 segundos de timeout total (`CURLOPT_TIMEOUT => 10`) e 5 segundos de conexão (`CURLOPT_CONNECTTIMEOUT => 5`).

2. **Aborto prematuro da conexão e `httpCode: 0`**:
   - Quando o cURL atingiu os 10 segundos sem receber os headers de resposta HTTP, encerrou a conexão com o erro cURL `CURLE_OPERATION_TIMEDOUT` (28).
   - Com o cancelamento por timeout, `curl_getinfo($ch, CURLINFO_HTTP_CODE)` retornou `0` e `$response` retornou `false`.

3. **Falso-negativo e Duplicidade de Encaminhamentos**:
   - O servidor da VDS já havia recebido o payload completo e concluiu a gravação no banco de dados deles.
   - O recMan avaliou `$httpCode !== 200` e devolveu o JSON de falha genérico para o frontend.
   - A UI reativou o botão de envio exibindo um Toast vermelho, induzindo o usuário a clicar novamente, resultando em 3 eventos de encaminhamento idênticos no histórico da VDS (às 11:56:11, 11:56:24 e 11:57:43).

4. **Falta de tratamento detalhado de erros cURL e mensagens da API**:
   - Não havia captura de `curl_errno($ch)` nem `curl_error($ch)`.
   - Caso a VDS responda com status HTTP de erro (400, 422, 500), o corpo JSON com o motivo real da VDS era descartado e substituído pela string estática `"Erro ao encaminhar ocorrência na VDS."`.

---

## 2. Ações Corretivas Necessárias

1. **Ajuste de Timeout em `vds_encaminhar_ocorrencia`**:
   - Aumentar `CURLOPT_TIMEOUT` de **10s** para **35s** (e `CURLOPT_CONNECTTIMEOUT` para 10s), garantindo margem para a VDS concluir o envio de notificações e responder.
   - Adicionar `CURLOPT_USERAGENT` padronizado.

2. **Diagnóstico Detalhado no Retorno de Erro**:
   - Capturar `curl_errno($ch)` e `curl_error($ch)`.
   - Se ocorrer timeout (`$httpCode === 0` ou `$curlErrno === 28`), informar explicitamente:
     > *"Tempo limite esgotado ao aguardar resposta da VDS (Timeout). O servidor da VDS pode ter processado o encaminhamento em segundo plano. Por favor, atualize o chamado antes de tentar novamente para evitar duplicidade."*
   - Se a VDS retornar HTTP diferente de 200/201, decodificar o JSON remoto para extrair a mensagem oficial (`$json['message']` ou `$json['msg']`).

3. **Revisão Preventiva em outros métodos correlatos**:
   - `vds_alterar_status_classificacao`: aumentar timeout de 10s para 25s e melhorar mensagens de erro da API.
   - `vds_get_encaminhar_funcionarios` e `vds_get_encaminhar_grupos`: aumentar timeout de 10s para 20s.
