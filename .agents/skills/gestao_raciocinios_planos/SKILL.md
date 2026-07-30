---
name: gestao_raciocinios_planos
description: Orienta e padroniza o salvamento de etapas de raciocínio (thinking), planos de implementação, walkthroughs/taklins e workflows dentro da estrutura local .agents/ do projeto.
---

# Gestão de Raciocínios, Planos e Workflows Localmente

Esta skill define as diretrizes para que todo raciocínio analítico, planejamento de implementação e resumo de entrega (walkthrough/taklins) seja documentado e versionado localmente na estrutura `.agents/` do repositório.

## Estrutura de Pastas no Projeto

Todo projeto em que trabalharmos deve conter a seguinte estrutura dentro de `.agents/`:

```
.agents/
├── AGENTS.md                  # Diretrizes e regras locais do agente
├── raciocinios/              # Histórico das etapas de pensamento (thinking logs) para estudo futuro
│   └── YYYY-MM-DD_[topico_ou_task].md
├── planos_e_workflows/        # Documentação técnica do projeto
│   ├── planos/               # Planos de implementação detalhados
│   ├── walkthroughs/          # Resumos de entregas, modificações e taklins
│   └── workflows/             # Fluxogramas Mermaid e processos operacionais
└── skills/                   # Skills do projeto
    └── gestao_raciocinios_planos/
        └── SKILL.md
```

## Regras de Execução para o Agente

### 1. Etapas de Raciocínio (`.agents/raciocinios/`)
- **Quando gerar**: Ao final de cada sessão/tarefa relevante ou durante diagnósticos complexos.
- **Nome do arquivo**: `.agents/raciocinios/YYYY-MM-DD_[nome_da_tarefa].md`
- **Conteúdo**: O fluxo de pensamento do agente, hipóteses investigadas, causa raiz encontrada e justificativa da solução.
- **Idioma**: O agente pode salvar no idioma em que o raciocínio foi processado (Português ou Inglês).

### 2. Planos de Implementação (`.agents/planos_e_workflows/planos/`)
- **Quando gerar**: Sempre que um plano for elaborado em Planning Mode ou para tarefas de grande porte.
- **Nome do arquivo**: `.agents/planos_e_workflows/planos/YYYY-MM-DD_[funcionalidade].md`
- **Conteúdo**: Objetivos, dependências, arquivos modificados/criados, plano de teste e perguntas abertas.

### 3. Walkthroughs / Taklins (`.agents/planos_e_workflows/walkthroughs/`)
- **Quando gerar**: Após a conclusão e verificação de uma funcionalidade ou correção de bug.
- **Nome do arquivo**: `.agents/planos_e_workflows/walkthroughs/YYYY-MM-DD_[funcionalidade].md`
- **Conteúdo**: Alterações efetuadas, evidências de testes e instrução de uso/validação.

### 4. Workflows (`.agents/planos_e_workflows/workflows/`)
- **Quando gerar**: Ao desenhar integrações, arquiteturas de sistemas ou fluxos de dados/API.
- **Nome do arquivo**: `.agents/planos_e_workflows/workflows/YYYY-MM-DD_[nome_do_fluxo].md`
- **Conteúdo**: Diagramas Mermaid e etapas sequenciais de execução.

## Impacto de Armazenamento

Os arquivos gerados são texto puro em Markdown (`.md`). O tamanho típico de cada arquivo varia entre **5 KB e 50 KB**. Mesmo dezenas de sessões acumuladas ocuparão apenas **poucos Megabytes (MB)**, tornando o impacto de armazenamento praticamente nulo e viabilizando o versionamento via Git.
