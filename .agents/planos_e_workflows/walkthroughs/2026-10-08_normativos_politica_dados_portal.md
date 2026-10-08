# Walkthrough / Taklin: Página de Normativos com Abertura Direta no Navegador

**Data**: 2026-10-08  
**Autor**: Antigravity  
**Status**: Concluído e Validado  

---

## 1. O que foi ajustado

1. **Remoção total de termos técnicos**:
   - Eliminado o termo "inline" de todos os textos, botões e descrições do portal (`portal/index.php` e `portal/normativos.php`).
   - Linguagem clara e amigável: *"Visualizar no Navegador"*, *"Abre diretamente na tela do seu aparelho sem precisar baixar arquivos"*.

2. **Remoção do visualizador embutido (iframe)**:
   - Evita problemas de scroll duplo, engasgos e falhas de compatibilidade em telas touch de smartphones Android e iPhones.
   - Cada documento conta com botão dedicado que abre o PDF diretamente em nova guia (`target="_blank"`), exatamente como no modelo da VDS (`https://arq.vidadesindico.com.br/.../documento.pdf`), permitindo zoom nativo e tela cheia limpa.

3. **Ordem e Apresentação dos Documentos**:
   - **1. Política de Tratamento de Dados (PTD)**: Destaque de prioridade 1 com badges de LGPD e segurança.
   - **2. Convenção de Condomínio**: Estatuto jurídico fundamental.
   - **3. Regimento Interno (RI)**: Regras de convivência e procedimentos de ampla defesa (Art. 181).

---

## 2. Arquivos Atualizados

- [`portal/normativos.php`](file:///d:/dev/github/recMan/portal/normativos.php): Interface redesenhada, leve, rápida e mobile-first.
- [`portal/index.php`](file:///d:/dev/github/recMan/portal/index.php): Texto amigável no card inicial e link no rodapé.
