# Raciocínio de Arquitetura: Módulo de Documentos Oficiais com Typst Multi-Editor

## Data: 2026-09-26

## Conceito do Módulo
Atualmente, pareceres opinativos, orientações técnicas e normativas do Conselho são lavrados manualmente em ferramentas externas (Google Docs), sem histórico unificado ou numeração sequencial no `recMan`.

A solução desenhada agrega um editor híbrido poderoso no sistema, integrando o motor ultra-rápido Typst CLI (Porta 5050).

## Principais Escolhas de Design

1. **Modo Duplo no Frontend (WYSIWYG ↔ Split Code)**:
   - Permite que qualquer conselheiro redija facilmente no modo gráfico sem conhecer sintaxe.
   - Permite que operadores avançados alternem para o código-fonte Typst no modo Split com pré-visualização ao vivo.

2. **Suporte à Opção `clear` (Modo Raw Typst)**:
   - Atende à necessidade de emitir documentos com formatação totalmente livre ou não padronizada.
   - O compilador compila o arquivo `.typ` direto, sem envolver nenhum wrapper do sistema.

3. **Modo `global` (Com Templates Inteligentes)**:
   - Injeta automaticamente a logomarca/banner, marca d'água A4, numeração de páginas `Página X de Y` e ementa notarial, poupando trabalho repetitivo.
