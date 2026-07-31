# Plano de Implementação: Visão Prática Direto da API VDS (limit=10 & Injeção AJAX Progressiva)

- **Data**: 2026-07-31
- **Objetivo**: Restaurar a consulta direta à API VDS na Visão Prática (`Lida=0`), utilizando requisições leves de 10 em 10 itens (`limit=10`) e carregando as páginas subsequentes de forma progressiva via AJAX com injeção suave no DOM.

## Componentes a Serem Modificados

### 1. Serviço de Ocorrências VDS (`classes/vds_ocorrencia_service.php`)
- **Refatorar `vds_get_ocorrencias_pratico($usuarioIdConselho, $limit = 10, $page = 1)`**:
  - Fazer chamada cURL síncrona/direta à API da VDS: `GET /ocorrencia?page={$page}&limit={$limit}&sortBy=dtExibicao&order=asc&Lida=0&Caixa=0`.
  - Persistir localmente os 10 itens recebidos.
  - Retornar array contendo `items`, `page`, `limit`, `totalRegs` e a flag `hasMore = ($page * $limit) < $totalRegs`.

### 2. Endpoint AJAX de Paginação Progressiva (`vds_sync_async.php`)
- Receber parâmetros `page` e `limit`.
- Invocar `vds_get_ocorrencias_pratico($usuarioIdConselho, $limit, $page)`.
- Retornar o JSON com os itens daquela página específica e o estado de paginação (`hasMore`).

### 3. Interface e Injeção Dinâmica no DOM (`livroDeOcorrencias.php`)
- O PHP renderiza de imediato a Página 1 (10 itens) direto da VDS.
- O JavaScript monitora `vdsHasMore`:
  - Dispara requisição AJAX para a página 2, 3, etc.
  - Constrói o HTML das novas ocorrências e as insere suavemente na lista da sidebar (`.sidebar-feed`).
  - Atualiza a contagem de visíveis no topo sem recarregar a tela ou exibir esqueleto.

## Plano de Verificação Manual
1. Acessar `index.php?pag=livroDeOcorrencias&visao=pratico`.
2. Confirmar o carregamento da Página 1 (10 itens) direto da VDS em menos de 1 segundo.
3. Observar no console do navegador e na tela a injeção suave das páginas subsequentes conforme os retornos AJAX chegam.
