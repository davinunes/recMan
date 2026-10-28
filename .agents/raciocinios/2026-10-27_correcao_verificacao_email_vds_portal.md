# Raciocínio de Diagnóstico e Correção: Falha na Verificação de E-mail VDS no Portal e Detalhe

- **Data**: 2026-10-27
- **Autor**: Antigravity
- **Contexto**: Correção de erros na validação de e-mail junto à API VDS v8.

---

## 1. Problema 1: Erro SQL de Coluna Desconhecida
- Mensagem: `Unknown column 'bloco' in 'field list'` em `notificacoes`.
- Causa: A tabela utiliza o campo `torre` e não `bloco`.
- Correção: Ajustada a query de consulta da notificação em `portal/api.php`.

---

## 2. Problema 2: Bloco "BCF" gerado por regex gulosa
- Mensagem identificada pelo usuário: *"Atenção: E-mail cadastrado na portaria para: Bloco BCF - Unidade 1105. Acho que esse BC ai está atrapalhando a checagem"*.
- Causa: Na API da VDS, o objeto do bloco vem como `"Bloco F"` (ou `"BLOCO F"`). A função utilizava `preg_replace('/[^A-F]/i', '', strtoupper($bloco))` para manter apenas letras entre A e F. Como as letras **B** e **C** da palavra **B**LO**C**O estão no intervalo A-F, a palavra `"BLOCO F"` era convertida em `"BCF"`. Consequentemente, a comparação `"BCF" === "F"` falhava, gerando falso positivo de divergência!
- Correção:
  - Criada a função `vds_normalizar_bloco($bloco)` em `classes/vds_acesso_service.php` que remove explicitamente palavras como `bloco`, `torre`, `edifício` antes de isolar a letra ou dígito do bloco.
  - Aplicada a mesma normalização em `palco/ajax_aceleradores.php` e `portal/api.php`.
