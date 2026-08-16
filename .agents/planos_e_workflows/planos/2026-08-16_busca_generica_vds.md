# Plano de Implementação: Busca Genérica VDS (`GET /registros`)

**Data:** 2026-08-16  
**Status:** Em Planejamento  

---

## 1. Objetivos

1. Implementar o método PHP `vds_busca_generica($busca, $tipo = 'ALL', $usuarioIdConselho = null)` na classe/serviço [`classes/vds_acesso_service.php`](file:///e:/DEV/recMan/classes/vds_acesso_service.php).
2. Criar o endpoint de API HTTP [`api/vds_busca_generica.php`](file:///e:/DEV/recMan/api/vds_busca_generica.php) para consumo assíncrono via AJAX.
3. Atualizar a skill [`.agents/skills/vds_api_v8/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/vds_api_v8/SKILL.md) registrando o novo endpoint `GET /registros?tipo={tipo}&busca={busca}` com todos os seus detalhes técnicos.
4. Integrar um botão **"BUSCA RÁPIDA VDS"** e modal interativo no painel do histórico de unidades ([`palco/historico.php`](file:///e:/DEV/recMan/palco/historico.php)) permitindo consultas e filtragens dinâmicas por tipo (`ALL`, `APARTAMENTO`, `MORADOR`, `AUTOMOVEL`, `GARAGEM`, `SINDICO`, `RECURSO`).

---

## 2. Arquivos Envolvidos

- `[MODIFY]` [`classes/vds_acesso_service.php`](file:///e:/DEV/recMan/classes/vds_acesso_service.php) - Adição da função `vds_busca_generica`.
- `[NEW]` [`api/vds_busca_generica.php`](file:///e:/DEV/recMan/api/vds_busca_generica.php) - Novo endpoint AJAX para a busca genérica.
- `[MODIFY]` [`.agents/skills/vds_api_v8/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/vds_api_v8/SKILL.md) - Atualização da especificação da API v8 da VDS.
- `[MODIFY]` [`palco/historico.php`](file:///e:/DEV/recMan/palco/historico.php) - Adição do botão "BUSCA RÁPIDA VDS", modal `#modalBuscaVDS` e lógica JS de busca.

---

## 3. Detalhamento dos Componentes

### Step 1: Adicionar `vds_busca_generica` em `classes/vds_acesso_service.php`
- Função aceita `$busca`, `$tipo` (default `'ALL'`), `$usuarioIdConselho`.
- Realiza requisição cURL para `VDS_BASE_URL . '/registros?tipo=' . urlencode($tipo) . '&busca=' . urlencode($busca)`.
- Adiciona headers `Authorization: Bearer <token>` e `Origin: <VDS_ORIGIN_HEADER>`.
- Trata respostas HTTP e converte JSON para array PHP.

### Step 2: Criar Endpoint `api/vds_busca_generica.php`
- Verifica autenticação de sessão PHP (`$_SESSION['user_id']`).
- Lê parâmetros `busca` e `tipo`.
- Executa a função `vds_busca_generica`.
- Retorna JSON padronizado com `success`, `data`, `count` e `tipo`.

### Step 3: Atualizar Skill `.agents/skills/vds_api_v8/SKILL.md`
- Incluir subseção em Endpoints Mapeados:
  - **Busca Genérica / Registros Globais:** `GET /registros?tipo={tipo}&busca={busca}`
  - Tipos suportados: `ALL`, `APARTAMENTO`, `AUTOMOVEL`, `MORADOR`, `SINDICO`, `GARAGEM`, `RECURSO`.
  - Formato da resposta JSON.

### Step 4: Criar UI e Modal no Toolset Operacional (`palco/historico.php`)
- Botão "BUSCA RÁPIDA VDS" com ícone Material `search` / `manage_search`.
- Modal `#modalBuscaVDS`:
  - Campo de pesquisa com botão de busca.
  - Filtros rápidos (Chips/Buttons) por tipo (`ALL`, `APARTAMENTO`, `MORADOR`, etc.).
  - Tabela/Cards de resultados com foto, título, subtítulo, tipo com cor distinta e botão de copiar UUID.

---

## 4. Plano de Verificação Manual

1. Abrir a tela de Histórico ([`palco/historico.php`](file:///e:/DEV/recMan/palco/historico.php)).
2. Clicar no botão **"BUSCA RÁPIDA VDS"** para verificar se o modal abre corretamente.
3. Testar buscas por termos conhecidos (ex: placa "PBG-4587", nome de morador, número de apartamento).
4. Alternar entre os filtros de Tipo (`ALL`, `APARTAMENTO`, `AUTOMOVEL`, `MORADOR`, etc.) e verificar o comportamento da busca.
5. Clicar no botão de copiar UUID e verificar a notificação Toast.
