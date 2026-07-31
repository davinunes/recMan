# Walkthrough - Toolset Operacional por Unidade & Análise de Recurso

## Resumo das Alterações Realizadas

### 👥 1. Acelerador de Moradores da Unidade
- **Nova Função Backend (`vds_get_moradores_unidade`)**: Adicionada em `classes/vds_acesso_service.php` para realizar a consulta de moradores cadastrados no endpoint `/morador?Unidade.Uuid={uuid}&Combo=true` da API v8 Vida de Síndico.
- **Formato dos Cards**: Exibe cards compactos e responsivos por morador contendo exclusivamente:
  - 📷 Foto / Avatar
  - 👤 Nome do Morador
  - 🏷️ Tipo / Perfil (ex: Proprietário Residente, Inquilino, Morador)
- **Integração no Toolset (`index.php?pag=historico`)**: Adicionada seção colapsável `Moradores da Unidade` e indicador no badge.
- **Integração na Análise de Recurso (`detalheRecurso.php`)**: Adicionada aba de moradores no painel contextual de aceleradores.

### ⚠️ 2. Sinalização de Inadimplência da Unidade
- **Verificação no Endpoint de Moradores**: Mapeia flags de inadimplência no morador e na unidade vinculada.
- **Selo na Dashboard do Toolset (`index.php?pag=historico`)**: Quando a unidade possui pendências financeiras, renderiza um painel de alerta em vermelho no topo da dashboard KPI: `⚠️ UNIDADE INADIMPLENTE`.
- **Selo na Tela de Análise de Recurso (`detalheRecurso.php`)**: Exibe o badge vermelho `UNIDADE INADIMPLENTE` no cabeçalho premium do recurso e uma tag `INADIMPLENTE` com efeito pulse no título do acelerador.

---

### 📦 3. Outras Melhorias do Toolset
- **Rastreio de Encomendas por UUID**: Atualização assíncrona do código de rastreio e fotos em segundo plano.
- **Extração de Multas em Boletos com Modal de Confirmação**: Leitura dinâmica da composição do boleto com botão de confirmação e salvamento em `upsertMultaCobrada`.
