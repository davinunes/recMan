# Plano de Implementação: Otimização e Estabilidade da IA Gemini

**Data**: 2026-09-16  
**Status**: Aprovado pelo Usuário  
**Objetivo**: Eliminar o erro intermitente `API Gemini retornou código HTTP 0: Erro desconhecido na API do Gemini` na emissão de pareceres, otimizar tempo de resposta do Gemini 2.5 Flash de ~35s para ~3s, aumentar timeouts e implementar fallback resiliente.

---

## 1. Contexto do Problema
O endpoint `metodo.php?metodo=sugerirParecerIA` passou a utilizar o modelo `gemini-2.5-flash`. Modelos 2.5 possuem raciocínio interno (*thinking*) ativado por padrão. Para esquemas JSON estruturados com 5 campos, o tempo de resposta oscilava acima de 30 segundos, estourando o `CURLOPT_TIMEOUT, 30` do PHP e resultando em HTTP 0 sem captura de erro pelo cURL.

---

## 2. Mudanças Planejadas

### Componente 1: Backend (`metodo.php`)
- Resgatar o modelo de IA configurado via `getConfigSistema('gemini_modelo')`, com padrão `gemini-2.5-flash`.
- Adicionar `'thinkingConfig' => ['thinkingBudget' => 0]` no `generationConfig` para modelos da família 2.5 (elimina tempo ocioso de pensamento).
- Aumentar `CURLOPT_TIMEOUT` para 60 segundos e `CURLOPT_CONNECTTIMEOUT` para 15 segundos.
- Capturar detalhadamente `$curlErrno` e `$curlError` quando `curl_exec` falhar.
- Adicionar fallback automático para `gemini-1.5-flash` se a chamada inicial ao 2.5 falhar por timeout, 429, 503 ou erro de transporte.

### Componente 2: Interface Administrativa (`palco/configuracoes_ia.php`)
- Adicionar seletor visual do modelo Gemini (`gemini-2.5-flash`, `gemini-2.0-flash`, `gemini-1.5-flash`).
- Persistir a chave `gemini_modelo` no salvamento AJAX `upsertMultipleConfigSistema`.

---

## 3. Plano de Verificação
- Revisão estática da sintaxe PHP e JavaScript.
- Conferência da montagem do payload e URLs da API Gemini v1beta.
- Registro das alterações no walkthrough.
