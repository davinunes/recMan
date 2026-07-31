# Raciocínio de Implementação - Toolset de Unidade (Histórico)

## Contexto e Objetivo
O menu "Histórico por Unidade" (`index.php?pag=historico`) atualmente apenas lista as notificações de uma unidade e indica se houve recurso cadastrado.
O objetivo é transformar essa tela em um **Toolset Completo da Unidade**, agregando dados contextuais e aceleradores extraídos da plataforma Vida de Síndico (VDS v8) e do banco de dados local.

## Aceleradores Solicitados
1. **Notificações e Recursos**: Notificações/advertências/multas da unidade com indicação de recurso e ações rápidas (Acessar recurso, Registrar data de ciência/retirada, Confirmar lançamento de cobrança).
2. **Encomendas**: Entregas recentes com foto do pacote, código de rastreio/identificador, destinatário, status e data de chegada.
3. **Autorizações de Acesso**: Visitantes e prestadores cadastrados/autorizados no período (com foto, documento, período e QR code/chave).
4. **Reservas de Área Comum**: Agendamentos de espaços do condomínio no período.
5. **Ocorrências de Própria Autoria**: Ocorrências registradas pelo morador/unidade.
6. **Ocorrências nas quais Sofreu Tag**: Chamados onde a unidade foi marcada/citada ou figura como ré.
7. **Lista de Boletos**: Boletos do ano/mês, status (Liquidado/Aberto), 2ª via Superlógica e análise rápida de lançamento de multas.

## Abrangência Temporal & Dashboard
- O filtro temporal padrão será o **mês atual** (ex: 01/07/2026 a 31/07/2026).
- Adicionar seletores dinâmicos de mês (`input[type=month]`), botões "Mês Anterior" e "Próximo Mês".
- Criar **Dashboard Estatístico de KPI da Unidade** com resumo de totais por categoria.

## Estrutura em Accordion Colapsado
- Todos os aceleradores permanecerão colapsados inicialmente.
- Cada cabeçalho exibirá o número de registros encontrados e ícone temático.
- Ações contextuais de inspeção e edição estarão disponíveis diretamente dentro de cada card do acelerador.
