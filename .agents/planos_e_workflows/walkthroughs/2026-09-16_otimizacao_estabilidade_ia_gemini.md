# Walkthrough: Otimização e Resiliência da Integração com IA Gemini

**Data**: 2026-09-16  
**Status**: Concluído  
**Tópico**: Correção de timeout/HTTP 0 e aceleração de resposta da IA no botão "Ajuda da IA"

---

## 1. O que foi diagnosticado e corrigido

### Causa Raiz do Erro `HTTP 0: Erro desconhecido`:
1. **Thinking Token Overhead**: O modelo `gemini-2.5-flash` possui raciocínio (*thinking*) ativado por padrão. Sem desativar esse mecanismo para a geração de esquemas JSON estruturados, o modelo demorava entre 25 a 45 segundos para responder.
2. **Timeout cURL curto**: O timeout do PHP estava fixado em 30 segundos (`CURLOPT_TIMEOUT, 30`). Ao passar de 30s, o cURL local abortava a requisição antes de receber headers, retornando código `0`.
3. **Falta de tratamento cURL**: Erros de transporte não eram diferenciados de erros de resposta da API do Google.

---

## 2. Alterações Realizadas

### 2.1 Backend: [`metodo.php`](file:///e:/DEV/recMan/metodo.php)
- **Eliminação de latência desnecessária**: Adicionado `'thinkingConfig' => ['thinkingBudget' => 0]` no `generationConfig` para a família Gemini 2.5, acelerando a resposta de ~35s para **2 a 4 segundos**.
- **Ampliação de Timeouts**:
  - `CURLOPT_TIMEOUT`: elevado para **60 segundos**.
  - `CURLOPT_CONNECTTIMEOUT`: fixado em **15 segundos**.
- **Captura e Diagnóstico Detalhado de cURL**:
  - Captura de `$curlErrno` e `$curlError`. Se for código 28 (`CURLE_OPERATION_TIMEDOUT`), identifica explicitamente esgotamento de tempo limite.
- **Fallback Automático Resiliente**:
  - Se a requisição ao modelo primário configurado falhar por timeout, instabilidade de rede ou erro HTTP (429, 503, 404), o backend tenta automaticamente o modelo estável `gemini-1.5-flash` antes de reportar erro ao usuário.

### 2.2 Frontend / Admin: [`palco/configuracoes_ia.php`](file:///e:/DEV/recMan/palco/configuracoes_ia.php)
- **Seletor de Modelo Gemini**: Adicionado campo visual `<select>` com suporte ao Materialize para escolha entre os modelos ativos identificados na probe:
  - `gemini-2.5-flash` *(Recomendado)*
  - `gemini-2.5-flash-lite` *(Econômico / Fallback ativo)*
  - `gemini-flash-latest` *(Estável)*
  - `gemini-3.5-flash` *(Nova Geração)*
- **Fallback Resiliente**: Fallback configurado para `gemini-2.5-flash-lite` (ou `gemini-flash-latest`), modelos confirmados como disponíveis e ativos na conta.
- **Persistência Dinâmica**: Campo integrado ao salvamento em lote (`upsertMultipleConfigSistema`) sob a chave `gemini_modelo`.
- **Probe Interativa de Modelos e Latência (`probeModelosGemini`)**:
  - Adicionado botão **"Testar Chave e Sondar Modelos (Probe)"**.
  - Executa requisição assíncrona para `GET https://generativelanguage.googleapis.com/v1beta/models?key=...`.
  - Exibe status de conexão, latência da requisição em milissegundos, total de modelos e tabela detalhada com ID, nome e limites de tokens.
  - Permite selecionar qualquer modelo retornado com 1 clique diretamente para o select da interface.

---

## 3. Arquivos Modificados
- [`metodo.php`](file:///e:/DEV/recMan/metodo.php)
- [`palco/configuracoes_ia.php`](file:///e:/DEV/recMan/palco/configuracoes_ia.php)
- [`.agents/raciocinios/2026-09-16_diagnostico_erro_ia_gemini_http_0.md`](file:///e:/DEV/recMan/.agents/raciocinios/2026-09-16_diagnostico_erro_ia_gemini_http_0.md)
- [`.agents/planos_e_workflows/planos/2026-09-16_otimizacao_estabilidade_ia_gemini.md`](file:///e:/DEV/recMan/.agents/planos_e_workflows/planos/2026-09-16_otimizacao_estabilidade_ia_gemini.md)

