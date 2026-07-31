# Walkthrough - Acelerador de Moradores & Foto do Objeto Pessoa

## Atualizações Realizadas

### 📷 1. Extração da Foto do Objeto `pessoa` e Fallback por Ícone
- **Estrutura da API VDS v8**: Ajustado o extrator backend em `classes/vds_acesso_service.php` para recuperar a foto exatamente de `$m['pessoa']['foto']` (e nome de `$m['pessoa']['nome']`, tipo de `$m['tipo']['nome']` e inadimplência de `$m['unidade']['inadimplente']`).
- **URL Completa da Imagem**: Se o campo `foto` contiver um caminho relativo (ex: `/app/dados/cond/...`), é prefixado automaticamente por `https://app.vidadesindico.com.br`.
- **Fallback por Ícone**: Caso a foto esteja `null` ou não cadastrada, é renderizado um avatar redondo estilizado com o ícone `<i class="material-icons cyan-text">account_circle</i>` em vez de imagens externas broken/placeholder.

---

### 📷 2. Resumo da Estrutura de Mapeamento

| Campo Extraído | Caminho no JSON da API VDS v8 |
| :--- | :--- |
| **Nome** | `item.pessoa.nome` |
| **Foto** | `item.pessoa.foto` (ou `https://app.vidadesindico.com.br` + path) |
| **Tipo** | `item.tipo.nome` (ex: Proprietário) |
| **Inadimplência** | `item.unidade.inadimplente` (boolean) |
