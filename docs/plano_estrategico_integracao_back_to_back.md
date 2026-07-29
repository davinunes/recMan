# Plano Estratégico de Integração Back-to-Back (API v8 Vida de Síndico) no Conselho

Este documento estabelece o plano arquitetural e de implementação para integrar o Sistema do Conselho diretamente com a API REST v8 da Vida de Síndico (`apiv8.vds.app.br`), eliminando dependências de extensões de navegador e capacitando os conselheiros com ferramentas avançadas de decisão e monitoramento.

---

## 1. Arquitetura Geral do Sistema de Integração

```mermaid
graph TD
    subgraph Sistema do Conselho
        CONFIG["Tela Config / Token Condomínio"]
        ULTRA["Tela Ultra-Login Conselheiros"]
        CRON["Worker / Cron Refresh Token"]
        MAP["Tabela vds_uuid_mapping"]
        LIVRO["Menu livroDeOcorrencias (Chat WhatsApp)"]
        REC["Tela detalheRecurso.php (Aceleradores)"]
    end

    subgraph API REST v8 (Vida de Síndico)
        AUTH_API["/auth/anon & /login"]
        OCOR_API["/ocorrencia & /ocorrencia/{uuid}"]
        ACCESS_API["/evento_acesso"]
        ENT_API["/entrega"]
        STRUC_API["/bloco & /unidade"]
    end

    CONFIG -->|Login Central| AUTH_API
    ULTRA -->|Login Conselheiro| AUTH_API
    CRON -->|Valida & Renova 401| AUTH_API
    LIVRO -->|Responde com Token Conselheiro| OCOR_API
    REC -->|Busca Acessos, Visitas, Entregas| ACCESS_API
    REC -->|Busca Chamados Vinc.| OCOR_API
    MAP <-->|Cache de UUIDs| STRUC_API
```

---

## 2. Decisões Arquiteturais e Destaques

### 2.1. Tabela de Anotação/Mapeamento de UUIDs (`vds_uuid_mapping`)
- Para evitar chamadas repetitivas de tradução de estrutura (bloco/unidade -> UUID da VDS) e resolução de usuários/entregas, o Conselho usará a tabela `vds_uuid_mapping`.
- Suporta busca indexada pela chave composta (`bloco:unidade`, ex: `B1:102`) ou pelo ID/Protocolo interno do Conselho.

### 2.2. Gestão de Tokens e "Ultra-Login"
- **Token do Condomínio (Sincronização Central):** Mantido via credencial de administração para rotinas automáticas de fundo (cron).
- **"Ultra-Login" dos Conselheiros:**
  - O conselheiro faz o login na VDS através de uma tela de configuração no Conselho.
  - O sistema envia a requisição de login para a VDS e armazena **apenas o Bearer Token e UUID do conselheiro**.
  - **A senha nunca é salva no banco.**
  - Quando um conselheiro responde a uma ocorrência no Conselho, o sistema envia o payload à VDS autenticado com o **token individual daquele conselheiro**, registrando a autoria com precisão na VDS.

### 2.3. Menu `livroDeOcorrencias` e Chat Estilo WhatsApp
- Interface dedicada no Conselho para gerenciar os chamados da VDS.
- **Visualização Chat:**
  - Mensagens do Autor (Morador/Solicitante) alinhadas à esquerda com avatar (`.../MORADOR/p-{ID}.jpg`), dados da unidade e anexos.
  - Mensagens da Administração/Conselho alinhadas à direita com avatar (`.../PESSOA/f-{ID}.jpg`), identificando o conselheiro respondente e anexos.
- **Suporte a Upload:** Permite anexar arquivos/fotos nas respostas utilizando a rota `/upload` da API v8.

### 2.4. Tags de Unidades e Links com Recursos/Notificações
- **Vínculos / Tags de Unidades (`ocorrencia_unidade_tag`):** Permite vincular uma ocorrência a uma ou mais unidades (além da autora). Exemplo: ocorrência registrada pela portaria a respeito de barulho vindo da Unidade 204.
- **Vínculo com Recursos/Notificações (`ocorrencia_recurso_link`):** Associa um chamado da VDS diretamente a uma Notificação ou Recurso em andamento no Conselho.

### 2.5. Aceleradores na Tela de Análise de Recurso (`palco/detalheRecurso.php`)
Ao julgar um recurso no Conselho, o conselheiro terá um painel contextual que busca em tempo real (com cache local via UUIDs):
1. **Eventos de Acesso na Data do Ocorrido:** Entradas/saídas de moradores, biometrias, portaria e veículos no dia/horário da infração (`GET /evento_acesso`).
2. **Visitas e Prestadores:** Registros de visitantes que acessaram a unidade na data, com foto do visitante.
3. **Histórico de Chamados:** Todos os chamados abertos **pela** unidade ou **contra/citando** a unidade.
4. **Encomendas Recentes:** Entregas e correspondências recebidas/retiradas nas proximidades da ocorrência.

---

## 3. Estrutura de Arquivos a Serem Criados/Modificados

### Banco de Dados
- `migrate_vds_integration.php`: Script SQL/PHP para criação das tabelas (`vds_uuid_mapping`, `vds_tokens`, `ocorrencia_unidade_tag`, `ocorrencia_recurso_link`).

### Backend / Services
- `classes/vds_auth_service.php`: Serviço central de autenticação, renovação de tokens (tratamento de HTTP 401 retry) e execução de requisições.
- `classes/vds_ocorrencia_service.php`: Serviço para consulta, resposta com token do conselheiro, upload de mídias e tagging.
- `classes/vds_acesso_service.php`: Serviço para consulta de acessos, visitas e entregas por unidade/período.

### Frontend / Telas
- `forms/configVds.php`: Tela de configuração de sincronização do Condomínio e Ultra-Login do conselheiro.
- `livroDeOcorrencias.php`: Interface principal do livro de ocorrências com feed e layout Chat WhatsApp.
- `palco/detalheRecurso.php`: Adição dos Aceleradores de Análise de Recurso.

### Skills
- `skills/vds_uuid_mapper/SKILL.md`: Padrões e convenções para resolução e cache de UUIDs remotos.
- `skills/vds_chat_component/SKILL.md`: Padrões de UI/UX para o chat estilo WhatsApp.
- `skills/recurso_accelerators/SKILL.md`: Padrões de agregação contextual de dados em julgamentos do Conselho.

---

## 4. Plano de Validação e Testes

1. **Teste de Autenticação & Retry 401:** Script CLI verificando fluxo `/auth/anon` -> `/login` e renovação sob demanda.
2. **Teste de Ultra-Login:** Validação de login individual de conselheiro sem armazenamento de senha.
3. **Teste de Resposta com Token do Conselheiro:** Envio de mensagem de teste e conferência da autoria na VDS.
4. **Teste de Aceleradores em `detalheRecurso.php`:** Conferência de carregamento de acessos e visitas da unidade na data estipulada.
