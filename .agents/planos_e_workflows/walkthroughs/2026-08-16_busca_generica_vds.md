# Walkthrough: Implementação da Busca Genérica VDS (`GET /registros`)

**Data:** 2026-08-16  
**Status:** Concluído com Sucesso  

---

## 1. Resumo das Alterações

Foi realizada a integração completa do novo endpoint de **Busca Genérica / Global** da API v8 da VDS (`GET /registros?tipo={tipo}&busca={busca}`). A implementação compreende a camada de serviço PHP, o endpoint AJAX HTTP, a documentação técnica nas skills do projeto e a interface do usuário no painel de Histórico ([`palco/historico.php`](file:///e:/DEV/recMan/palco/historico.php)).

### 1.1 Serviço Backend (`vds_acesso_service.php`)
- Criada a função [`vds_busca_generica($busca, $tipo = 'ALL', $usuarioIdConselho = null)`](file:///e:/DEV/recMan/classes/vds_acesso_service.php#L1374-L1432).
- Realiza requisição HTTP GET para `https://apiv8.vds.app.br/registros?tipo={tipo}&busca={busca}` utilizando o Bearer Token do conselheiro autenticado.
- Suporta todos os tipos mapeados (`ALL`, `APARTAMENTO`, `AUTOMOVEL`, `MORADOR`, `SINDICO`, `GARAGEM`, `RECURSO`).

### 1.2 Endpoint AJAX (`api/vds_busca_generica.php`)
- Criado o arquivo [`api/vds_busca_generica.php`](file:///e:/DEV/recMan/api/vds_busca_generica.php) para servir requisições assíncronas do frontend.
- Valida a sessão do usuário (`$_SESSION['user_id']`), sanitiza entradas e retorna JSON estruturado.

### 1.3 Atualização da Skill (`vds_api_v8`)
- Atualizado o arquivo [`.agents/skills/vds_api_v8/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/vds_api_v8/SKILL.md) registrando a **Seção H (Busca Global & Registros Genéricos)** com a especificação técnica completa do endpoint `GET /registros`.

### 1.4 Interface e Modal (`palco/historico.php`)
- Inserido o botão **"BUSCA RÁPIDA VDS"** no cabeçalho da página ([`palco/historico.php`](file:///e:/DEV/recMan/palco/historico.php#L95-L99)).
- Criado o modal Materialize CSS `#modalBuscaVDS` contendo:
  - Campo de entrada com pesquisa por tecla Enter.
  - Filtros rápidos estilo *chips* para alternar entre os tipos (`TODOS`, `APARTAMENTO`, `MORADOR`, `AUTOMOVEL`, `GARAGEM`, `SINDICO`, `RECURSO`).
  - Bar de contagem de resultados e indicação de filtro ativo.
  - Cards de resultados com foto (ou ícone tipado), badge com cor customizada por tipo, título, subtítulo e descrição.
  - **Detecção de Bloco e Unidade:** Parser inteligente via Expressão Regular que detecta bloco e unidade no título/subtítulo (ex: `Bloco C - 904`) e renderiza o botão **"Carregar Unid. C-904"**. Ao clicar, o modal fecha, o seletor de bloco e a caixa de unidade do Histórico são preenchidos e a consulta do Toolset é disparada automaticamente.
  - **Ações Rápidas:** Botão **"Detalhes"** para moradores (abre modal existente) e botão **"Copiar UUID"** (copia para o clipboard).

---

## 2. Estrutura de Arquivos

- `[MODIFY]` [`classes/vds_acesso_service.php`](file:///e:/DEV/recMan/classes/vds_acesso_service.php)
- `[NEW]` [`api/vds_busca_generica.php`](file:///e:/DEV/recMan/api/vds_busca_generica.php)
- `[MODIFY]` [`.agents/skills/vds_api_v8/SKILL.md`](file:///e:/DEV/recMan/.agents/skills/vds_api_v8/SKILL.md)
- `[MODIFY]` [`palco/historico.php`](file:///e:/DEV/recMan/palco/historico.php)

---

## 3. Instrução de Validação Manual

1. Acessar a tela de Histórico ([`palco/historico.php`](file:///e:/DEV/recMan/palco/historico.php)).
2. Clicar no botão **"BUSCA RÁPIDA VDS"** no canto superior direito.
3. Digitar um termo no campo de busca (ex: placa `PBG-4587` ou nome/apartamento) e pressionar Enter.
4. Testar a filtragem por tipo clicando nos chips (`AUTOMÓVEL`, `MORADOR`, `APARTAMENTO`, etc.).
5. Em resultados contendo unidade (ex: `Bloco C - 904`), clicar no botão **"Carregar Unid. C-904"** e verificar se o modal fecha e o Toolset da unidade é carregado.
6. Testar o botão **"Copiar UUID"** em qualquer resultado para verificar o Toast de confirmação.
