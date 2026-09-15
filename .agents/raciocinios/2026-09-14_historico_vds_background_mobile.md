# Raciocínio Analítico: Otimização da Tela de Histórico da Unidade (Segundo Plano VDS e Responsividade Mobile)

**Data**: 14/09/2026  
**Contexto**: `index.php?pag=historico` (Toolset Operacional por Unidade)  
**Problemas Identificados**:
1. **Latência de Carregamento**: Ao buscar uma unidade, a tela faz uma única requisição síncrona ao backend (`metodo.php?metodo=toolsetUnidade`), que executa em sequência 10 consultas remotas HTTP/cURL à API v8 do Vida de Síndico (VDS). O usuário espera vários segundos (ou dezenas de segundos em caso de lentidão da VDS) com um loader de tela inteira, impedindo a visualização dos dados que já estão no nosso banco local MySQL (Notificações, Multas, Recursos e Vagas de Garagem).
2. **Layout Mobile "Embolado"**: O primeiro card de filtros possui a classe Materialize `valign-wrapper` em sua `<div class="row">`. No Materialize CSS, `valign-wrapper` aplica `display: flex; align-items: center;` sem `flex-wrap: wrap`. Em telas de smartphones (360px–480px), todas as 5 colunas (Unidade, Bloco, Vaga, Mês de Abrangência e Botão Carregar) são forçadas na mesma linha horizontal ou sofrem quebras anômalas, gerando sobreposição de textos, campos espremidos e botões ilegíveis.

---

## 1. Análise da Arquitetura do Backend (`metodo.php`)

Atualmente em `case "toolsetUnidade":`:
- Dados locais:
  - `getNotificacoes($unidade, $torre)` -> consulta local ao MySQL (tempo < 20ms).
  - `getEstacionamento($torre, $unidade)` -> consulta local ao MySQL (tempo < 5ms).
- Dados remotos VDS (10 chamadas cURL sequenciais):
  - `vds_get_entregas_unidade`
  - `vds_get_autorizacoes_acesso`
  - `vds_get_reservas_unidade`
  - `vds_get_chamados_unidade`
  - `vds_get_boletos_unidade`
  - `vds_get_moradores_unidade`
  - `vds_get_veiculos_unidade`
  - `vds_get_visitantes_unidade`
  - `vds_get_liberacoes_portaria_unidade`
  - `vds_get_eventos_acesso`

Além disso, `session_start()` é chamado sem `session_write_close()`, o que trava o arquivo de sessão do PHP. Se o frontend disparasse requisições paralelas, a segunda ficaria bloqueada na fila até a primeira terminar.

### Solução Proposta no Backend:
1. Adicionar `session_write_close()` imediatamente após coletar `$_SESSION['user_id']`.
2. Suportar o parâmetro `$modo`:
   - `modo=local`: processa apenas `getNotificacoes` e `getEstacionamento`, calcula os KPIs locais e responde em ~20ms.
   - `modo=vds`: processa as consultas remotas da VDS e seus KPIs correspondentes.
   - `modo=todos` (padrão): mantém compatibilidade total com qualquer chamada legada.
3. Adicionar aliases de rota se conveniente (`toolsetUnidadeLocal` e `toolsetUnidadeVds`).

---

## 2. Análise do Frontend (`meu.js` e `palco/historico.php`)

### Fluxo Assíncrono Desacoplado:
1. Ao clicar em **CARREGAR** (ou via busca rápida):
   - Aborta qualquer requisição local/VDS pendente.
   - Dispara imediatamente `metodo=toolsetUnidade&modo=local`.
   - Assim que o retorno local chega (~20ms):
     - Remove o loader global e o estado vazio.
     - Exibe imediatamente o painel de acordeão e a área de KPIs (`unitBrief`).
     - Renderiza a seção 1 (Notificações & Recursos da Unidade), que já abre expandida por padrão com os dados reais do banco local.
     - Renderiza o banner de vagas de garagem locais na seção 0.1 (Veículos).
     - Renderiza os KPIs locais no topo e placeholders elegantes nos KPIs da VDS.
     - Coloca skeletons/spinners de carregamento em segundo plano nas demais seções da VDS (Moradores, Encomendas, Autorizações, Eventos, Reservas, Ocorrências, Boletos, Liberações, Visitantes).
2. Em paralelo (ou imediatamente após disparar a busca):
   - Dispara a requisição em segundo plano: `metodo=toolsetUnidade&modo=vds`.
   - Quando a VDS responder:
     - Renderiza os dados das seções remotas.
     - Atualiza os KPIs gerais (incluindo o banner de inadimplência, se houver).
     - Remove os estados de carregamento das seções VDS.
   - Se a VDS falhar ou der timeout:
     - As notificações e vagas locais continuam 100% acessíveis e operacionais.
     - As seções VDS exibem um aviso discreto de "VDS temporariamente indisponível" com botão de tentar novamente.

---

## 3. Análise da Responsividade do Card de Filtros

### Diagnóstico do Problema no Mobile:
- `<div class="row valign-wrapper flex-responsive">` possui `valign-wrapper`, impedindo a quebra de linha das colunas no flexbox.
- No mobile, 5 colunas com labels e botões ficam comprimidas.
- Os botões do mês (`<`, `input[type=month]`, `>`, `Hoje`) ficam apertados.
- O botão `CARREGAR` não possui largura total ou margem adequada ao quebrar.

### Solução de Layout Responsivo:
- Remover `valign-wrapper` inline do container pai.
- Reestruturar o card em uma grade CSS / Materialize limpa:
  - **Mobile (< 768px)**:
    - Linha 1: Unidade (50%) + Bloco (50%) lado a lado.
    - Linha 2: Vaga (100%) com rótulo claro de busca alternativa.
    - Linha 3: Mês de abrangência (100%) com controles de navegação espaçados e touch-friendly.
    - Linha 4: Botão CARREGAR (100% de largura, altura 42px, destaque visual).
  - **Desktop (>= 993px)**:
    - Distribuição horizontal equilibrada com alinhamento vertical pela base (`align-items: flex-end`).
