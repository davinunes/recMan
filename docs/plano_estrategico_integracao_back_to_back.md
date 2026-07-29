# Plano Estratégico de Integração Back-to-Back (API v8 Vida de Síndico) - Versão Final

Este documento estabelece o plano arquitetural e de implementação reestruturado para integrar o Sistema do Conselho diretamente com a API REST v8 da Vida de Síndico (`apiv8.vds.app.br`), contemplando todas as regras de segurança, mocks de teste, sinalização visual e anotação local das 10 categorias conhecidas de ocorrências (`ocoTipo`).

---

## 1. Arquitetura Geral Reestruturada

```mermaid
graph TD
    subgraph Sistema do Conselho
        CONFIG["Tela Config / Ultra-Login Conselheiros"]
        CRON["Sync Background (15 em 15 min) + Botão Sincronizar"]
        LEGADO["Reaproveitamento ocorrencias (Responsabilidade Síndico/Sub & Resolvido Interno)"]
        MAP["Tabela vds_uuid_mapping (Cache UUIDs + 10 Categorias ocoTipo)"]
        LIVRO["Menu livroDeOcorrencias (Chat WhatsApp + Badges por ocoTipo)"]
        NOTAS["Notas Internas (Padrão) & Postagem em 2 Fatores no Remoto"]
        TRAVA["Trava de Segurança (Escrita APENAS no Protocolo 259564 em dev/teste)"]
        REC["Tela detalheRecurso.php (Aceleradores de Análise)"]
    end

    subgraph API REST v8 (Vida de Síndico)
        AUTH_API["/auth/anon & /login & Refresh Token"]
        OCOR_API["/ocorrencia & /ocorrencia/{uuid} & /upload"]
        ACCESS_API["/evento_acesso"]
        ENT_API["/entrega"]
    end

    CONFIG -->|Boot / Renovação Automática| AUTH_API
    CRON -->|Sync 15min / Manual| OCOR_API
    LIVRO -->|Cria Nota Interna Por Padrão| NOTAS
    NOTAS -->|Sinalização Explícita (2º Fator)| TRAVA
    TRAVA -->|Valida Protocolo = 259564 em teste| OCOR_API
    REC -->|Consulta Acessos/Visitas/Entregas| ACCESS_API
    LEGADO <-->|Preserva Responsabilidades & Status| LIVRO
    MAP <-->|Semeia & Atualiza ocoTipo| LIVRO
```

---

## 2. Regras Específicas de Negócio, Testes e Mapeamento de Categorias

### 2.1. Anotação e Semeadura das Categorias de Ocorrência (`ocoTipo`)
A tabela `vds_uuid_mapping` conterá registros com `entidade_tipo = 'categoria_ocorrencia'`. As 10 categorias conhecidas serão pré-carregadas na instalação do script de migração:

1. **`115`** - `Fale com o Conselho` (Badge Purple `#6f42c1`)
2. **`247`** - `Monitoramento` (Badge Orange `#fd7e14`)
3. **`114`** - `Livro de ocorrência` (Badge Blue `#0d6efd`)
4. **`86`** - `Fale com o Síndico` (Badge Crimson `#dc3545`)
5. **`109`** - `Fale com o Síndico de Bloco` (Badge Dark Red `#b02a37`)
6. **`102`** - `Fale com a Administração` (Badge Teal `#20c997`)
7. **`145`** - `Fale com a Mensageria` (Badge Amber `#ffc107`)
8. **`87`** - `Fale com a portaria` (Badge Brown `#795548`)
9. **`126`** - `Fale com a Supervisão` (Badge Cyan `#0dcaf0`)
10. **`172`** - `Suporte ao Controle de Acesso` (Badge Dark Gray `#495057`)

*Caso a VDS retorne um novo `ocoTipo` ainda não cadastrado, o sistema anotará automaticamente a nova categoria na tabela e utilizará o estilo neutro como fallback.*

### 2.2. Trava de Segurança para Testes de Escrita (Protocolo 259564)
- **Protocolo Exclusivo de Testes:** Durante o desenvolvimento e homologação, **qualquer tentativa de escrita/postagem remota de notas será RESTRITA ao Protocolo `259564`**.
- Se for solicitada a publicação remota de uma nota em outro protocolo durante os testes, a requisição será bloqueada no backend com aviso seguro.

### 2.3. Mocks JSON de Resposta (`docs/mocks/`)
- Amostras completas salvas em `docs/mocks/` para testes de layout offline (`mock_ocorrencia_detalhe.json`, `mock_evento_acesso.json`, `mock_entrega.json`).

---

## 3. Decisões Arquiteturais Consolidadas

### 3.1. Periodicidade do Sync & Sincronização Sob Demanda
- **Background Cron Job:** A cada **15 minutos**.
- **Botão "Sincronizar Agora":** Na barra do `livroDeOcorrencias.php` para busca imediata.

### 3.2. Gestão de Tokens, Toast/Banner e Renovação Automática
- **Toast/Banner:** Avisos de expiração na UI.
- **Refresh Automático:** Validação via `GET /usuario/status` e renovação transparente com interceptador 401.

### 3.3. Notas Internas por Padrão & Resposta em 2 Fatores
- **1º Fator:** Nota salva **somente no banco do Conselho** (`ocorrencia_notas_internas`).
- **2º Fator:** Ação explícita **"Publicar no Sistema Remoto (VDS)"** autenticada com o token do conselheiro logado.

### 3.4. Reaproveitamento do Legado (`ocorrenciasCondominioDigital`)
- Manutenção da tabela `ocorrencias` existente:
  - Campo `responsabilidade` (`sindico`, `sub`, `adm`, `nenhum`).
  - Campo `resolvido` interno (0 ou 1).
  - Compatibilidade garantida com `quantitativos.php` e `relatorio.php`.

---

## 4. Detalhamento dos Componentes

---

### Componente 1: Banco de Dados (`migrate_vds_integration.php`)

#### [NEW] [migrate_vds_integration.php](file:///e:/DEV/recMan/migrate_vds_integration.php)
1. **`ALTER TABLE ocorrencias`**: Adicionar `uuid_remoto`, `protocolo_vds`, `oco_tipo`, `dados_json`.
2. **`vds_uuid_mapping`**: Cache de UUIDs (`bloco:unidade`, pessoas, condomínio e categorias `ocoTipo`).
3. **`vds_tokens`**: Bearer Tokens do Condomínio e dos Conselheiros (Ultra-Login).
4. **`ocorrencia_notas_internas`**: Notas locais com status `enviado_remoto`.
5. **`ocorrencia_unidade_tag`** & **`ocorrencia_recurso_link`**: Tags de unidades e vínculos com recursos/notificações.

---

### Componente 2: Serviços Backend (`classes/`)

#### [NEW] [vds_auth_service.php](file:///e:/DEV/recMan/classes/vds_auth_service.php)
Sessão, refresh automático de token e avisos Toast/Banner.

#### [NEW] [vds_ocorrencia_service.php](file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php)
Busca por protocolo, postagem com **trava no protocolo 259564** e upload de mídias (`POST /upload`).

---

### Componente 3: Menu `livroDeOcorrencias` & Chat com Badges e 2 Fatores

#### [NEW] [livroDeOcorrencias.php](file:///e:/DEV/recMan/livroDeOcorrencias.php)
- **Cabeçalho:** Botão **"Sincronizar Agora"** e seletores de **Responsabilidade** (`Síndico`, `Subsíndico`, `ADM`).
- **Sidebar & Feed:** Badges visuais coloridos conforme os 10 tipos de `ocoTipo`.
- **Chat WhatsApp:** Balões de moradores (esquerda), Notas Internas (centro/direita) e botão de **Publicação em 2 Fatores**.

---

### Componente 4: Aceleradores na Tela de Análise de Recurso (`palco/detalheRecurso.php`)

#### [MODIFY] [detalheRecurso.php](file:///e:/DEV/recMan/palco/detalheRecurso.php)
Widget **"Aceleradores de Análise"**: Acessos da unidade, visitas, chamados vinculados por tag e entregas.

---

### Componente 5: Mocks e Skills

#### [NEW] [docs/mocks/](file:///e:/DEV/recMan/docs/mocks/)
Mocks JSON para validação visual.

#### [NEW] [skills/vds_uuid_mapper/SKILL.md](file:///e:/DEV/recMan/skills/vds_uuid_mapper/SKILL.md)
Cache local e resolução de UUIDs e categorias `ocoTipo`.

#### [NEW] [skills/vds_chat_component/SKILL.md](file:///e:/DEV/recMan/skills/vds_chat_component/SKILL.md)
Layout Chat WhatsApp, badges coloridos por `ocoTipo` e publicação em 2 fatores.

#### [NEW] [skills/recurso_accelerators/SKILL.md](file:///e:/DEV/recMan/skills/recurso_accelerators/SKILL.md)
Consultas aceleradas na análise de recursos.
