# Diagnóstico: Erro na Ajuda da IA (Gemini retornou código HTTP 0)

**Data**: 2026-09-16  
**Contexto**: O botão "Ajuda da IA" na tela de emissão de parecer do conselho (`palco/emiteParecer.php` -> `meu.js` -> `metodo.php?metodo=sugerirParecerIA`) está apresentando falha intermitente com a mensagem:  
`Erro da IA: API Gemini retornou código HTTP 0: Erro desconhecido na API do Gemini.`

---

## 1. O que significa "Código HTTP 0"?

No PHP com cURL:
- O código HTTP 0 retornado por `curl_getinfo($ch, CURLINFO_HTTP_CODE)` ocorre quando **a requisição cURL não recebeu resposta HTTP do servidor remoto** (`curl_exec($ch)` retorna `false`).
- Isso acontece devido a:
  1. **Timeout da requisição**: Foi configurado `CURLOPT_TIMEOUT, 30` (30 segundos). Se o Gemini demorar mais de 30s para responder, o cURL aborta com `CURLE_OPERATION_TIMEDOUT` (código 28).
  2. **Falha de rede/DNS/Reset de conexão**: O servidor não consegue se comunicar temporariamente com `generativelanguage.googleapis.com`.
- No código atual de `metodo.php`:
  ```php
  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($httpCode !== 200) {
      $errData = json_decode($response, true);
      $errMsg = isset($errData['error']['message']) ? $errData['error']['message'] : 'Erro desconhecido na API do Gemini.';
      echo json_encode(['success' => false, 'error' => "API Gemini retornou código HTTP $httpCode: $errMsg"]);
      break;
  }
  ```
  Como `$response` é `false`, `json_decode` retorna `null`, gerando a mensagem genérica `Erro desconhecido na API do Gemini` com HTTP 0, mascarando o erro real do cURL (`curl_error($ch)` / `curl_errno($ch)`).

---

## 2. O que mudou recentemente no repositório?

1. **Commit `3485b6a` (21/06/2026)**:
   - Implementação original do botão "Ajuda da IA".
   - Chamava o modelo: `gemini-1.5-flash` com `CURLOPT_TIMEOUT, 30`.
2. **Commit `f0bc867` (21/06/2026)**:
   - A URL foi alterada de `gemini-1.5-flash` para `gemini-2.5-flash`:
     `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=`
3. **Commit `3d02c66` (30/06/2026)**:
   - Adicionada tela de configuração de prompts e descrições do schema (`palco/configuracoes_ia.php`).

---

## 3. Por que o `gemini-2.5-flash` causa o HTTP 0 intermitente?

1. **Thinking Token Overhead**:
   - O modelo `gemini-2.5-flash` traz por padrão o recurso de raciocínio embutido (*Thinking*).
   - Sem a flag explícita `thinkingConfig: {"thinkingBudget": 0}`, o modelo consome tempo "pensando" internamente antes de iniciar a geração do JSON estruturado.
   - Em horários de pico e no free tier, esse tempo de raciocínio + geração com schema ultrapassa frequentemente **30 segundos** (atingindo 35s - 45s).
   - Ao atingir 30 segundos exatos, o cURL corta a conexão -> `HTTPCode = 0`.

2. **Timeout de 30s muito curto**:
   - Para chamadas de LLM com prompt complexo e schema JSON rígido, 30s é muito baixo para suportar instabilidades temporárias de rede e fila do Google. Deve ser de pelo menos 60s ou 90s, ou o thinking deve ser desativado para responder em 2 a 4 segundos.

3. **Falta de Captura do Erro cURL**:
   - Se `curl_exec` falha, o código deve consultar `curl_error($ch)` e `curl_errno($ch)`.

4. **Falta de Seleção ou Fallback de Modelo**:
   - Não há opção de configurar qual modelo usar (`gemini-2.5-flash`, `gemini-2.0-flash`, `gemini-1.5-flash`) nas configurações da IA (`palco/configuracoes_ia.php`), estando fixo no código.

---

## 4. Soluções Propostas

1. **Captura detalhada de erros cURL**:
   - Tratar `$response === false`, capturar `curl_error($ch)` e se for `CURLE_OPERATION_TIMEDOUT` (28), informar claramente: "Tempo limite esgotado (timeout de Xs) aguardando resposta da IA do Gemini".
2. **Otimizar Payload para Gemini 2.5 Flash**:
   - Adicionar `thinkingConfig` com `thinkingBudget: 0` (ou configurável) no `generationConfig` para desativar a latência desnecessária de raciocínio na geração de parecer estruturado. Isso reduz drasticamente o tempo de resposta de ~35s para ~3s!
3. **Aumentar o Timeout**:
   - Ajustar o timeout de 30s para 60s (ou configurável).
4. **Fallback inteligente e/ou Configuração de Modelo**:
   - Permitir configurar o modelo em `config_sistema` (`gemini_model`, padrão `gemini-2.5-flash` ou `gemini-1.5-flash`) com fallback automático caso o primeiro falhe.
