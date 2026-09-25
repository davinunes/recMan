# Walkthrough: Correção do Vínculo e Exibição de Tags de Ocorrências VDS no Recurso

**Data:** 2026-09-25  
**Recurso Testado / Referência:** `index.php?pag=recurso&rec=352/2026`  

---

## 🎯 Resumo da Correção

A tela do recurso não exibia ocorrências vinculadas via tags (ex: `352/2026`) porque a função de busca legada `getOcorrenciasVinculadas` consultava estritamente a tabela `recurso_ocorrencia` por ID numérico.

Com as correções efetuadas:
1. **Busca Unificada em 3 Fontes de Dados**:
   - `recurso_ocorrencia` (ID numérico do recurso).
   - `ocorrencia_recurso_link` (Número do recurso, ex: `352/2026`).
   - `ocorrencia_unidade_tag` (Tag de notificação/recurso, ex: `NOTIF` / `352/2026`).
2. **Compatibilidade Flexível de Ano**:
   - Trata automaticamente tanto `352/2026` (4 dígitos) quanto `352/26` (2 dígitos).
3. **Auto-Healing de Vínculos**:
   - Se uma ocorrência foi vinculada por tag antes do recurso existir localmente, ela é recuperada e gravada automaticamente em `recurso_ocorrencia`.
4. **Interface Visual Aprimorada**:
   - Exibe os badges/chips coloridos das tags de cada ocorrência (`Notificação 352/2026`, `Bloco/Unidade`, etc.).
   - Oferece o botão **Chat Local** (para abrir diretamente no componente de chat em `index.php?pag=livroDeOcorrencias&id=...`) e o botão **Abrir VDS Remoto**.

---

## 📁 Arquivos Alterados

- [`classes/repositorio.php`](file:///e:/DEV/recMan/classes/repositorio.php)
  - Reescreveu `getOcorrenciasVinculadas($id_recurso, $numero_recurso = null)` com suporte às 3 fontes de vínculo e auto-healing.
  - Adicionou `getTagsOcorrencia($ocorrenciaId)`.
- [`classes/vds_ocorrencia_service.php`](file:///e:/DEV/recMan/classes/vds_ocorrencia_service.php)
  - Aprimorou `vds_vincular_tag_recurso` para resolver o `recurso.id` aceitando variações de 4 dígitos e 2 dígitos de ano.
- [`palco/detalheRecurso.php`](file:///e:/DEV/recMan/palco/detalheRecurso.php)
  - Passou o número do recurso (`$result['numero']`) para `getOcorrenciasVinculadas`.
  - Atualizou o layout da lista de ocorrências vinculadas para renderizar os badges de tags e atalhos de chat.

---

## 🧪 Como Validar no Servidor Remoto

1. Acesse no navegador: `index.php?pag=recurso&rec=352/2026`.
2. Vá até a seção **"Ocorrências Condomínio Digital Vinculadas"**.
3. Verifique que as ocorrências cujas tags apontam para `352/2026` agora aparecem listadas, acompanhadas dos badges de tags e dos botões para abertura no Chat Local ou VDS.
