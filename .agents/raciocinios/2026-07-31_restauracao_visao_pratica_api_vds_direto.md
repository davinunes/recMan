# Raciocínio de Arquitetura: Restauração da Visão Prática Direto da API VDS (limit=10 & Injeção AJAX Progressiva)

- **Data**: 2026-07-31
- **Tópico**: Restauração da consulta direta à API VDS na Visão Prática com `limit=10` e carregamento progressivo de páginas subsequentes via AJAX no frontend.

## 1. Contexto & Diretriz do Usuário
O usuário solicitou retornar o carregamento da Visão Prática para consultar **diretamente a API da VDS** (`GET /ocorrencia?Lida=0`), utilizando a paginação com `limit=10` (que responde em < 1 segundo), e injetando as páginas subsequentes via AJAX conforme os retornos chegarem, evitando recarregar a tela ou causar o efeito de esqueleto.

## 2. Nova Arquitetura de Carregamento Progressivo

1. **Página 1 (Direto da API VDS em < 1s)**:
   - Na abertura da tela, o PHP invoca `vds_get_ocorrencias_pratico($usuarioIdConselho, 10, 1)`.
   - Dispara cURL direto na VDS API: `GET /ocorrencia?page=1&limit=10&sortBy=dtExibicao&order=asc&Lida=0&Caixa=0` utilizando o token Ultra-Login do conselheiro.
   - Como o limite é de apenas 10 itens, a API responde em **menos de 1 segundo**, sem estouro de timeout no SQL Server da VDS.
   - O PHP renderiza a Página 1 na tela.

2. **Carregamento Progressivo das Páginas Subsequentes via AJAX**:
   - Um script JavaScript detecta a flag `hasMore` (se `totalRegs > 10`).
   - 1,5 segundos após o carregamento inicial, dispara requisição assíncrona para `vds_sync_async.php?page=2&limit=10`.
   - Quando os novos itens retornarem, o JS os **injeta dinamicamente na lista (DOM)** sem recarregar a página ou piscar refletores de skeleton.
   - Se ainda houverem mais páginas (`page=3`, etc.), o loop continua suavemente até carregar todas as páginas pendentes.

3. **Persistência Local Automática**:
   - Cada lote de 10 ocorrências recebido da VDS é persistido na tabela local `ocorrencias` para manter o histórico do Conselho atualizado.
