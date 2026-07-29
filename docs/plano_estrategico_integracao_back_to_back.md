# Plano Estratégico de Integração Back-to-Back (API v8 Vida de Síndico) - Versão Final

Este documento estabelece o plano arquitetural e de implementação reestruturado para integrar o Sistema do Conselho diretamente com a API REST v8 da Vida de Síndico (`apiv8.vds.app.br`), contemplando todas as regras de segurança, mocks de teste e sinalização visual.

---

## 1. Arquitetura Geral Reestruturada

```mermaid
graph TD
    subgraph Sistema do Conselho
        CONFIG["Tela Config / Ultra-Login Conselheiros"]
        CRON["Sync Background (15 em 15 min) + Botão Sincronizar"]
        LEGADO["Reaproveitamento ocorrencias (Responsabilidade Síndico/Sub & Resolvido Interno)"]
        MAP["Tabela vds_uuid_mapping (Cache UUIDs)"]
        LIVRO["Menu livroDeOcorrencias (Chat WhatsApp + Badges por Tipo)"]
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
```

---

## 2. Regras Específicas de Negócio e Testes

### 2.1. Trava de Segurança para Testes de Escrita (Protocolo 259564)
- **Protocolo Exclusivo de Testes:** Durante todo o desenvolvimento e fase de homologação, **qualquer tentativa de escrita/postagem remota de notas será RESTRITA ao Protocolo `259564`**.
- **Trava no Backend (`vds_ocorrencia_service.php`):** Se for solicitada a publicação remota de uma nota em uma ocorrência cujo protocolo seja diferente de `259564` durante o modo de teste, a requisição remota será bloqueada com aviso seguro na interface.

### 2.2. Sinalizadores Visuais por Tipo/Categoria de Chamado
- **Badges Coloridos na Interface:** Na sidebar da lista de ocorrências e no cabeçalho do Chat (`livroDeOcorrencias.php`), cada chamado terá um badge/tag com cor distinta conforme o tipo:
  - 🟣 **`Fale com - Fale com o Conselho`**: Tag Roxa / Destaque Institucional.
  - 🟠 **`Fale com - Monitoramento`**: Tag Laranja / Alerta de Segurança.
  - 🔵 **`Livro de Ocorrências`**: Tag Azul / Registro Geral.
  - 🟢 **Outros Tipos (Sugestões, Manutenção, etc.)**: Tags de categorias mapeadas.

### 2.3. Mocks JSON de Resposta (`docs/mocks/`)
- Antes do desenvolvimento de layout final do Chat WhatsApp e Aceleradores, salvaremos amostras completas das respostas de API em `docs/mocks/`:
  - `mock_ocorrencia_detalhe.json`
  - `mock_evento_acesso.json`
  - `mock_entrega.json`

---

## 3. Decisões Arquiteturais Consolidadas

### 3.1. Periodicidade do Sync & Sincronização Sob Demanda
- **Background Cron Job:** Execução automática a cada **15 minutos**.
- **Botão "Sincronizar Agora":** Presente no cabeçalho da tela `livroDeOcorrencias.php` para acionamento imediato.

### 3.2. Gestão de Tokens, Toast/Banner e Renovação Automática
- **Alertas Visual:** Notificações tipo **Toast/Banner** no topo do painel quando o token precisar de renovação.
- **Refresh Automático:** Validação via `GET /usuario/status` e renovação transparente com interceptador 401.

### 3.3. Notas Internas por Padrão & Resposta em 2 Fatores
- **1º Fator:** Toda nota é gravada **somente no banco do Conselho** (`ocorrencia_notas_internas`).
- **2º Fator:** Ação explícita **"Publicar no Sistema Remoto (VDS)"**, que dispara a postagem usando o **Bearer Token do Conselheiro logado**.

### 3.4. Reaproveitamento do Legado (`ocorrenciasCondominioDigital`)
- Evolução da tabela `ocorrencias` existente no banco de dados do Conselho:
  - Manutenção do campo `responsabilidade` (`sindico`, `sub`, `adm`, `nenhum`).
  - Manutenção do status de `resolvido` interno (0 ou 1).
  - Preservação da compatibilidade com `quantitativos.php` e `relatorio.php`.

---

## 4. Detalhamento dos Componentes

---

### Componente 1: Banco de Dados (`migrate_vds_integration.php`)

#### [NEW] [migrate_vds_integration.php](file:///e:/DEV/recMan/migrate_vds_integration.php)
1. **`ALTER TABLE ocorrencias`**:
   - Adicionar `uuid_remoto`, `protocolo_vds`, `tipo_categoria`, `dados_json`.
2. **`vds_uuid_mapping`**: Cache de UUIDs (`bloco:unidade`, pessoas, condomínio).
3. **`vds_tokens`**: Bearer Tokens do Condomínio e dos Conselheiros (Ultra-Login).
4. **`ocorrencia_notas_internas`**: Armazenamento local das notas com flag `enviado_remoto`.
5. **`ocorrencia_unidade_tag`** & **`ocorrencia_recurso_link`**: Tags de unidades e vínculos com recursos/notificações.

---

### Componente 2: Serviços Backend (`classes/`)

#### [NEW] [vds_auth_service.php](file:///e:/DEV/recMan/classes/vds_auth_service.php)
Gestão de sessão, refresh automático e emissão de alertas em Toast/Banner.

#### [NEW] [vds_ocorrencia_service.php](file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php)
Consulta de ocorrências, busca por protocolo, postagem de notas com **trava para o protocolo `259564` em modo de teste** e upload de mídias (`POST /upload`).

---

### Componente 3: Menu `livroDeOcorrencias` & Chat com Badges e 2 Fatores

#### [NEW] [livroDeOcorrencias.php](file:///e:/DEV/recMan/livroDeOcorrencias.php)
- **Cabeçalho:** Botão **"Sincronizar Agora"**, seletores de **Responsabilidade** (`Síndico`, `Subsíndico`, `ADM`).
- **Sidebar & Feed:** Ocorrências acompanhadas de **Badges Visuais por Categoria** (`Fale com o Conselho`, `Monitoramento`, `Livro de Ocorrências`, etc.).
- **Chat WhatsApp Reestruturado:**
  - Balões à esquerda para o Autor (morador) com foto de perfil (`.../MORADOR/p-{ID}.jpg`).
  - Notas Internas (Padrão) alinhadas ao centro/direita com destaque de conselheiro autor.
  - Botão **"Publicar no Sistema Remoto (VDS)" (2º Fator)** com validação do protocolo `259564` na fase de homologação.

---

### Componente 4: Aceleradores na Tela de Análise de Recurso (`palco/detalheRecurso.php`)

#### [MODIFY] [detalheRecurso.php](file:///e:/DEV/recMan/palco/detalheRecurso.php)
Widget **"Aceleradores de Análise (Condomínio Digital)"**:
1. **Acessos da Unidade:** Entradas/saídas na data/hora do fato gerador.
2. **Visitas & Prestadores:** Fotos e nomes de visitantes no dia do ocorrido.
3. **Histórico de Chamados:** Chamados onde a unidade é autora, reclamada ou citada (`ocorrencia_unidade_tag`).
4. **Entregas:** Correspondências recentes.

---

### Componente 5: Mocks e Skills

#### [NEW] [docs/mocks/](file:///e:/DEV/recMan/docs/mocks/)
Arquivos JSON de retorno de amostra das chamadas REST.

#### [NEW] [skills/vds_uuid_mapper/SKILL.md](file:///e:/DEV/recMan/skills/vds_uuid_mapper/SKILL.md)
Cache local e resolução de UUIDs VDS.

#### [NEW] [skills/vds_chat_component/SKILL.md](file:///e:/DEV/recMan/skills/vds_chat_component/SKILL.md)
Layout Chat WhatsApp, badges por categoria e fluxo de publicação em 2 fatores.

#### [NEW] [skills/recurso_accelerators/SKILL.md](file:///e:/DEV/recMan/skills/recurso_accelerators/SKILL.md)
Consultas aceleradas de prova na análise de recursos.

---

## 5. Plano de Validação e Testes

1. **Captura de Mocks:** Gerar JSONs em `docs/mocks/` para validação offline dos componentes.
2. **Teste da Trava de Escrita (Protocolo 259564):**
   - Tentar publicar nota remota no protocolo `259564` -> **Permitido com sucesso**.
   - Tentar publicar nota remota em qualquer outro protocolo durante a fase de teste -> **Bloqueado com aviso visual seguro**.
3. **Teste de Badges por Categoria:** Verificar renderização correta das tags coloridas por tipo de chamado na lista.
4. **Teste de Sync Fundo (15 min) & Manual:** Cron automático e botão "Sincronizar Agora".
5. **Teste de Toast/Banner e Token:** Expiração forçada e refresh automático.
6. **Teste de Compatibilidade Legada:** Validação contínua de `quantitativos.php` e `relatorio.php`.
