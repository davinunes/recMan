# Walkthrough / Resumo de Entrega: Skill de Estrutura do Banco & Otimização de Índices

- **Data**: 2026-07-31
- **Status**: Concluído & Verificado

## Resumo das Entregas

### 1. Skill Local `consultar_estrutura_banco`
- **Arquivo**: [.agents/skills/consultar_estrutura_banco/SKILL.md](file:///e:/DEV/recMan/.agents/skills/consultar_estrutura_banco/SKILL.md)
- **Função**: Instrui os assistentes a lerem via cURL a URL `https://mini.davinunes.eti.br/estrutura.php` ou inspecionarem o script [estrutura.php](file:///e:/DEV/recMan/estrutura.php) para obter a estrutura DDL exata do banco antes de escrever modificações.

### 2. Estudo Aprofundado & Script de Otimização de Índices
- **Arquivo**: [otimizar_indices_banco.php](file:///e:/DEV/recMan/otimizar_indices_banco.php)
- **Melhorias Aplicadas**:
  1. **`ocorrencias`**: Adicionados índices para `uuid_remoto`, `protocolo_vds`, `bloco + unidade`, `resolvido + abertura` e `oco_tipo + abertura` (elimina Table Scans na busca e ordenação).
  2. **`ocorrencia_leitura_conselheiro`**: Índices compostos `(conselheiro_id, lido)` e `(conselheiro_id, sincronizado_remoto)` (acelera o filtro de não lidos e o flush de leituras remota).
  3. **`recurso`**: Índices para `numero`, `bloco + unidade` e `fase + data DESC`.
  4. **`notificacoes` e `multas_cobradas`**: Índices compostos por `(numero, ano)` e `(torre, unidade)`.
  5. **`parecer`**: Índice em `(id, concluido)`.
  6. **Mensagens / Notas**: Índices por `ocorrencia_id + data` em `ocorrencia_notas_internas` e `ocorrencia_comentarios_vds`.

## Como Executar no Servidor
Acesse a URL `https://mini.davinunes.eti.br/otimizar_indices_banco.php` para aplicar todos os índices de forma idempotente e segura no banco de dados.

## Arquivos Criados
- [.agents/skills/consultar_estrutura_banco/SKILL.md](file:///e:/DEV/recMan/.agents/skills/consultar_estrutura_banco/SKILL.md) (NOVO)
- [otimizar_indices_banco.php](file:///e:/DEV/recMan/otimizar_indices_banco.php) (NOVO)
- [.agents/raciocinios/2026-07-31_estudo_indices_banco_skill_estrutura.md](file:///e:/DEV/recMan/.agents/raciocinios/2026-07-31_estudo_indices_banco_skill_estrutura.md) (NOVO)
