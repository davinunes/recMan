# Walkthrough: Correção do Vínculo de Ocorrências por Tags de Unidade (ex: B1803) e Notificação (ex: 352/2026)

**Data:** 2026-09-25  
**Recurso Testado / Referência:** `index.php?pag=recurso&rec=352/2026`  

---

## 🎯 Resumo da Solução

Expandimos o motor de busca de ocorrências vinculadas (`getOcorrenciasVinculadas`) para que ele localize e exiba automaticamente a ocorrência na página do recurso em **TODOS os cenários de tagging**:

1. **Tag por Número/Ano do Recurso** (ex: `352/2026`, `352/26`):
   - Localiza por `ocorrencia_recurso_link` ou `ocorrencia_unidade_tag` (tipo `notificacao`/`NOTIF`).
2. **Tag por Unidade** (ex: `B1803`, `1803`, `B/1803`):
   - Localiza por `ocorrencia_unidade_tag` onde `bloco` e `unidade` da tag batem com a unidade do recurso (ex: Bloco B, Apt 1803).
3. **Autoria de Chamado pela Unidade**:
   - Localiza ocorrências abertas diretamente para/pela unidade do recurso (`bloco` e `unidade`).
4. **Vínculo Direto por ID**:
   - Tabela `recurso_ocorrencia`.

---

## 📁 Arquivos Atualizados

- [`classes/repositorio.php`](file:///e:/DEV/recMan/classes/repositorio.php):
  - `getOcorrenciasVinculadas($id_recurso, $numero_recurso, $bloco, $unidade)`: inclui cruzamento de `bloco` e `unidade` da tag (`b1803`, `B1803`, `B/1803`) com auto-healing.
- [`palco/detalheRecurso.php`](file:///e:/DEV/recMan/palco/detalheRecurso.php):
  - Passa `$result['bloco']` e `$result['unidade']` para a consulta de ocorrências vinculadas.

---

## 🧪 Teste de Validação

1. Abra qualquer ocorrência no Livro de Ocorrências e insira a tag `b1803`.
2. Acesse o recurso correspondente ao **Bloco B Apt 1803** (`index.php?pag=recurso&rec=...`).
3. A ocorrência aparecerá listada na área **"Ocorrências Condomínio Digital Vinculadas"** com o badge da tag de unidade e o botão de acesso rápido ao Chat Local.
