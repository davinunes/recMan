# Walkthrough - Acelerador de Visitantes & Prestadores da Portaria

## Alterações Realizadas

### 🚪 Novo Acelerador de Visitantes & Prestadores da Portaria
1. **Integração Backend (`vds_get_visitantes_unidade`)**:
   - Criada a função em [vds_acesso_service.php](file:///e:/DEV/recMan/classes/vds_acesso_service.php) para consultar `GET /portaria/visitante?DestinoUuid={unidadeUuid}&DestinoTipo=UNIDADE` na API v8 da VDS.
   - Extrai foto, nome, tipo do cadastro (ex: *Visitante*, *Prestador de Serviços*) e documento (CPF/RG/UF).
2. **Toolset Operacional (`index.php?pag=historico`)**:
   - Card KPI no cabeçalho (*Visitantes / Prestadores*).
   - Seção colapsável dedicada **Visitantes & Prestadores da Portaria** com badge roxo dinâmico de contagem.
3. **Análise de Recurso (`detalheRecurso.php`)**:
   - Aba dedicada **Visitantes & Prestadores da Portaria (N)** adicionada ao painel de aceleradores.
