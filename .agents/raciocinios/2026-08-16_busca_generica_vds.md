# Raciocínio Analítico: Implementação de Busca Genérica / Global da API VDS v8

**Data:** 2026-08-16  
**Tópico:** Integração do endpoint de busca global `GET /registros` da API VDS, criação do wrapper PHP, endpoint de API e modal de consulta rápida no Toolset do Histórico (`palco/historico.php`).

---

## 1. Contexto e Descoberta

Foi identificado na documentação de inspeção (`docs/inspect/busca_glocal.md`) um endpoint de busca global/genérica na API v8 do Vida de Síndico (VDS):
- **URL Base:** `GET https://apiv8.vds.app.br/registros?tipo={tipo}&busca={busca}`
- **Headers:** `Authorization: Bearer {token}`, `Origin: https://app1.vidadesindico.com.br`
- **Tipos Suportados Identificados:** `ALL` (padrão), `APARTAMENTO`, `AUTOMOVEL`, `MORADOR`, `SINDICO`, `GARAGEM`, `RECURSO`.
- **Formato do Retorno:** Array JSON de objetos no formato:
  ```json
  [
    {
      "id": "c95b4d96-5309-4912-9300-61865811fb0f",
      "foto": null,
      "titulo": "Bloco A - Unidade NI",
      "subtitulo": "Ocupado",
      "descricao": "",
      "tipo": "APARTAMENTO"
    }
  ]
  ```

---

## 2. Diagnóstico da Arquitetura e Decisões de Projeto

### 2.1 Camada de Serviço (PHP Backend)
- **Arquivo Alvo:** [`classes/vds_acesso_service.php`](file:///e:/DEV/recMan/classes/vds_acesso_service.php) (ou arquivo de serviço VDS apropriado).
- **Nova Função:** `vds_busca_generica($busca, $tipo = 'ALL', $usuarioIdConselho = null)`.
- **Comportamento:**
  1. Valida o parâmetro `$busca`.
  2. Obtém o Bearer Token ativo do conselheiro via `vds_get_token($usuarioIdConselho)`.
  3. Formata os parâmetros `tipo` (em maiúsculas, ex: `ALL`, `MORADOR`, etc.) e `busca` com `urlencode`.
  4. Executa chamada cURL com timeout seguro de 10s e headers corretos de autorização e origem.
  5. Retorna estrutura padronizada contendo `['success' => true, 'data' => [...], 'count' => N, 'tipo' => $tipo]`.

### 2.2 Endpoint HTTP AJAX
- **Novo Arquivo:** [`api/vds_busca_generica.php`](file:///e:/DEV/recMan/api/vds_busca_generica.php).
- **Comportamento:**
  1. Valida sessão do usuário logado (`session_start()` + `$_SESSION['user_id']`).
  2. Captura `$_GET['busca']` ou `$_POST['busca']` e `$_GET['tipo']` ou `$_POST['tipo']`.
  3. Invoca `vds_busca_generica($busca, $tipo, $userId)`.
  4. Retorna resposta JSON limpa para o frontend com `header('Content-Type: application/json')`.

### 2.3 Atualização da Skill de Documentação
- **Arquivo Alvo:** [`.agents/skills/vds_api_v8/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/vds_api_v8/SKILL.md).
- Adicionar documentação completa do endpoint `GET /registros?tipo={tipo}&busca={busca}` na seção 2 ("Endpoints Mapeados por Módulo"), especificando tipos suportados, parâmetros e esquema de resposta.

### 2.4 Interface do Usuário (Modal de Busca no Histórico)
- **Arquivo Alvo:** [`palco/historico.php`](file:///e:/DEV/recMan/palco/historico.php).
- **Componentes UI:**
  - Botão no cabeçalho do Toolset Operacional: **"BUSCA RÁPIDA VDS"** (com ícone `search` / `saved_search`).
  - Modal Materialize CSS `#modalBuscaVDS`:
    - Campo de input de busca rápida com suporte a tecla Enter.
    - Seletor / Filtros tipo "chips" para alternar entre `ALL`, `APARTAMENTO`, `MORADOR`, `AUTOMOVEL`, `GARAGEM`, `SINDICO`, `RECURSO`.
    - Lista/Grid de resultados estilizada com badges de tipo coloridos, avatar/foto (se houver), título, subtítulo, descrição e botão "Copiar UUID".
    - Estados de Feedback: Carregando (spinner), Nenhum resultado encontrado, Estado Inicial e Toast de notificação ao copiar UUID.

---

## 3. Conformidade com Regras Globais
- **Ambiente Remoto:** Código escrito inteiramente no repositório local sem execução de builds ou servidores locais.
- **Idioma:** Documentação e comentários em Português (Brasil).
- **Sem Adivinhações:** Modelo e esquema baseados na captura real documentada em `docs/inspect/busca_glocal.md`.
