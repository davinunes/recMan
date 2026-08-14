# Registro de Raciocínio: Análise do Arquivo `eventos-portaria.md` e Novo Acelerador de Liberações de Portaria

- **Data**: 14/08/2026
- **Tópico**: Análise da API v8 VDS para liberações de portaria (Caixa 9 e tipos de ocorrência/eventos) e criação do 8º acelerador nas telas `detalheRecurso.php` e `historico.php`.

---

## 1. Contexto e Investigação

O usuário solicitou a análise do arquivo [`docs/inspect/eventos-portaria.md`](file:///e:/DEV/recMan/docs/inspect/eventos-portaria.md), que contém requisições cURL e respostas JSON da API v8 do Vida de Síndico (VDS) relativas a liberações de visitantes na portaria.

As duas principais dúvidas/considerações levantadas pelo usuário no arquivo são:
1. **Papel da `Caixa=9`**: Entender se essa caixa é especificamente usada para agrupar ocorrências geradas pela portaria/administração.
2. **Filtragem por Tipo de Evento / Ocorrência**: Como identificar quais tipos de ocorrência correspondem a Controle de Acesso / Liberações da Portaria (`tipoId`, `tipo=49`, `ocoNivelUm=48, 63`).

---

## 2. Diagnóstico Técnico

1. **`Caixa=9` (Caixa da Administração/Portaria)**:
   - Na API v8 do VDS (`GET /ocorrencia`), a `Caixa=9` contém todas as notificações e liberações cadastradas pelos funcionários/portaria e direcionadas aos moradores da unidade.
   - Portanto, a busca por liberações de visitantes feitas na portaria para uma unidade deve obrigatoriamente enviar `Caixa=9&Lida=9`.

2. **Tipos de Ocorrência vs Eventos Internos**:
   - **Filtragem Nativa na API VDS (`Tipo`)**: Ao invés de listar tudo e filtrar no PHP, a API aceita `Tipo=48` (Controle de Acesso) e `Tipo=63` (Portaria) diretamente no endpoint `GET /ocorrencia`.
   - **Nos Eventos (`GET /ocorrencia/{uuid}`)**: Cada ocorrência possui um array `eventos`, onde o primeiro evento (entrada/liberação) tem `"tipo": 49` e o evento de saída tem `"tipo": 0`.
   - **Estratégia**: Executar as queries com `Tipo=48` e `Tipo=63` (ou combinadas), concatenar e ordenar no PHP por `dtExibicao` decrescente, reduzindo overhead e dispensando a necessidade de filtragem secundária por ID de tipo.

3. **Arquitetura da Solução**:
   - Criar a função `vds_get_liberacoes_portaria_unidade` em `classes/vds_acesso_service.php`.
   - Adicionar a action `liberacoes_portaria` em `palco/ajax_aceleradores.php`.
   - Incluir o novo componente visual de acelerador em `palco/detalheRecurso.php` e `palco/historico.php`.
