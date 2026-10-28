# Walkthrough: Verificação de E-mail VDS no Portal e no Painel de Julgamento

- **Data**: 2026-10-27
- **Autor**: Antigravity
- **Repositório**: `recMan`

---

## 1. Resumo da Implementação

Implementamos duas melhorias fundamentais para a verificação de e-mails de moradores junto à portaria (API VDS v8):

1. **Correção do Endpoint no Portal (`portal/api.php`)**:
   - Correção do erro fatal de banco `Unknown column 'bloco' in 'field list'` que ocorria na tabela `notificacoes` (que utiliza a coluna `torre`).
   - Adição de fallback amplo para tokens da VDS em requisições anônimas/públicas em `classes/vds_auth_service.php`.
   - Adição de indicador de progresso (*spinner*) na Etapa 5 do assistente enquanto a conferência VDS é processada.

2. **Integração no Painel do Julgamento do Conselho (`palco/detalheRecurso.php` e `palco/ajax_aceleradores.php`)**:
   - Nova action assíncrona `verificar_email_requerente` via AJAX sem travar a renderização inicial.
   - **No Header do Recurso**: Exibição de chip colorido com ícone e tooltip dinâmico:
     - 🟢 **Verde (`verified_user`)**: *E-mail Morador VDS* (e-mail cadastrado na portaria para a unidade do recurso).
     - 🔴 **Laranja/Vermelho (`warning`)**: *E-mail Outra Unidade (VDS)* (e-mail pertence a outro bloco/unidade na portaria).
     - 🟡 **Âmbar (`person_off`)**: *E-mail Não Localizado (VDS)* (e-mail sem cadastro ativo na portaria).
   - **Na Caixa Informativa de Dados do Recurso**: Exibição do e-mail do recorrente com o status e mensagem detalhada logo abaixo.

---

## 2. Arquivos Modificados

| Arquivo | Descrição |
|---|---|
| [`portal/api.php`](file:///e:/DEV/recMan/portal/api.php) | Correção da query `notificacoes` removendo coluna inexistente `bloco`. |
| [`portal/index.php`](file:///e:/DEV/recMan/portal/index.php) | Adicionado indicador visual de carregamento na Etapa 5. |
| [`classes/vds_auth_service.php`](file:///e:/DEV/recMan/classes/vds_auth_service.php) | Adicionado fallback amplo para recuperar qualquer token ativo se não houver token explícito de condomínio. |
| [`palco/ajax_aceleradores.php`](file:///e:/DEV/recMan/palco/ajax_aceleradores.php) | Nova action `verificar_email_requerente`. |
| [`palco/detalheRecurso.php`](file:///e:/DEV/recMan/palco/detalheRecurso.php) | Chip no cabeçalho do recurso e campo informativo na caixa de datas/recurso com AJAX assíncrono. |

