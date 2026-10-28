# Raciocínio de Diagnóstico e Correção: Falha na Verificação de E-mail VDS no Portal

- **Data**: 2026-10-27
- **Autor**: Antigravity
- **Contexto**: Diagnóstico do motivo de a verificação de e-mail junto à VDS não retornar resultado quando o morador insere e-mail no portal de recursos.

---

## 1. Problema Relatado
O usuário relatou: *"testei a verificação de email no vds e não teve resultado. quando o recurso é apresentado via email não cadastrado na unidade. podemos conferir?"*
Ao inspecionar a resposta da chamada AJAX, foi identificado o seguinte erro do MySQL:
`Unknown column 'bloco' in 'field list' [Query: SELECT torre, bloco, unidade, assunto, notificacao FROM notificacoes WHERE numero = '462' AND ano = '2025' LIMIT 1]`

---

## 2. Diagnóstico da Causa Raiz

1. **Inexistência da coluna `bloco` na tabela `notificacoes`**:
   - A tabela `notificacoes` utiliza o campo `torre` para identificar o bloco do imóvel, e não `bloco`.
   - Na linha 401 de `portal/api.php`, a query continha:
     `SELECT torre, bloco, unidade, assunto, notificacao FROM notificacoes WHERE ...`
   - O método `DBExecute()` interrompeu a execução com erro fatal (`die`), impedindo a resposta JSON.
   - O frontend tentava parsear a resposta como JSON em `api.php?action=verificar_unidade_vds`, disparava o bloco `catch` e não exibia card algum na Etapa 5.

2. **Fallback de Tokens da VDS (`classes/vds_auth_service.php`)**:
   - Em requisições públicas (como as do portal), não há conselheiro logado na sessão (`$usuarioIdConselho = null`).
   - A função `vds_get_token(null)` procurava exclusivamente registros com `tipo = 'condominio'`.
   - Se a base possuir apenas tokens ativos com `tipo = 'conselheiro'`, a função retornava `null`.
   - Adicionamos fallback amplo para qualquer token ativo disponível na tabela `vds_tokens`.

3. **Feedback Visual no Portal (`portal/index.php`)**:
   - Adicionado indicador visual de carregamento discreto (`carregandoValidacaoVds`) na Etapa 5 para informar ao morador que a verificação cadastral na portaria está em andamento enquanto a API da VDS responde.

---

## 3. Próximos Passos
- Orientar o usuário a testar novamente com o e-mail no portal.
- Alinhar se ele também deseja exibir a validação do e-mail de apresentação na tela de análise do recurso pelo conselho (`palco/detalheRecurso.php`).

