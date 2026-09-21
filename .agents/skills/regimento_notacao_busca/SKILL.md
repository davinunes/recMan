---
name: regimento_notacao_busca
description: Especificação técnica da estrutura JSON do Regimento Interno, gramática de notação hierárquica e compacta (ex: 58.[7,9]), mecânica de busca híbrida (por número ou palavra-chave) e endpoint PHP de consulta/formatação (trecho.php).
---

# Skill: Estrutura, Notação e Mecânica de Busca do Regimento Interno

Esta skill documenta a arquitetura, convenções de notação, mecanismos de busca e o interpretador backend (`trecho.php`) do módulo do Regimento Interno em `regimento/`.

---

## 1. Visão Geral da Arquitetura

O sistema de Regimento Interno é composto por 4 pilares:
1. **Banco de Dados JSON (`database.json`)**: Armazena a estrutura hierárquica do Regimento (Capítulos, Artigos, Parágrafos, Incisos e Alíneas).
2. **Gramática de Notação e Notação Compacta**: Sistema de identificação única de qualquer nó do documento (ex: `4.p2.i1` ou notação compacta `58.[7,9]`).
3. **Mecânica de Busca Frontend (`script.js` / `index.html`)**: Motor híbrido de busca rápida no navegador por número de artigo (`^\d+$`) ou busca textual de termos (`>= 3` caracteres).
4. **Endpoint Backend e Formatação (`trecho.php`)**: Serviço em PHP que decodifica notações simples/lote/compactas, extrai o contexto do artigo pai e renderiza a resposta em JSON estruturado ou texto formatado para pareceres jurídicos e relatórios.

---

## 2. Estrutura de Dados (`database.json`)

O arquivo `database.json` possui a seguinte estrutura hierárquica:

```json
{
  "titulo": "CONDOMÍNIO RESIDENCIAL EXEMPLO - REGIMENTO INTERNO",
  "capitulos": {
    "1": "CAPÍTULO I - DO OBJETO",
    "14": "CAPÍTULO XIV – DO USO DAS PISCINAS"
  },
  "artigos": {
    "4": {
      "capitulo": 2,
      "titulo_artigo": "TÍTULO OPCIONAL DO ARTIGO",
      "texto": "Texto principal do Artigo 4...",
      "paragrafos": {
        "1": {
          "texto": "Texto do § 1º..."
        },
        "2": {
          "texto": "Texto do § 2º...",
          "incisos": {
            "1": { "texto": "Texto do inciso I..." },
            "2": { "texto": "Texto do inciso II..." }
          }
        },
        "unico": {
          "texto": "Texto do Parágrafo Único..."
        }
      },
      "incisos": {
        "1": {
          "texto": "Texto do inciso direto do artigo...",
          "alineas": {
            "a": { "texto": "Texto da alínea a..." }
          }
        }
      }
    }
  }
}
```

### Regras de Aninhamento e Tipos:
- **`capitulos`**: Dicionário de `[id_capitulo] => "Título do Capítulo"`.
- **`artigos`**: Dicionário de `[numero_artigo] => ObjetoArtigo`.
- Cada nó pode conter recursivamente:
  - `texto`: String com o conteúdo textual do item.
  - `paragrafos`: Objeto com chaves numéricas (`"1"`, `"2"`) ou `"unico"`.
  - `incisos`: Objeto com chaves numéricas ou romanas (`"1"`, `"2"`, `"I"`).
  - `alineas`: Objeto com chaves alfabéticas (`"a"`, `"b"`).

---

## 3. Gramática de Notação e Notação Compacta

A notação permite referenciar de maneira única qualquer elemento ou grupo de elementos do Regimento.

### A. Notação Simples Hierárquica
Usa o ponto `.` como separador de níveis. Pode utilizar prefixos explícitos (`p` para parágrafo, `i` para inciso, `a` para alínea) ou o número/chave direto:
- `4` $\rightarrow$ Refere-se ao Artigo 4 (texto completo e título).
- `4.1` ou `4.p1` $\rightarrow$ Refere-se ao Parágrafo 1º do Artigo 4.
- `4.p2.1` ou `4.p2.i1` $\rightarrow$ Refere-se ao Inciso 1 (ou I) do Parágrafo 2º do Artigo 4.
- `14.i1.a` $\rightarrow$ Refere-se à Alínea `a` do Inciso 1 do Artigo 14.

### B. Notação Compacta com Colchetes (`.[...]`)
Para evitar a repetição do nó pai quando múltiplos filhos são selecionados sob a mesma raiz:
- **Sintaxe**: `<no_pai>.[<filho_1>,<filho_2>,...]`
- **Exemplo de Notação**: `58.[7,9]` representa a seleção conjunta de `58.7` e `58.9`.
- **Suporte a Ranges (Backend PHP)**: `14.[1-3,5]` é expandido automaticamente no PHP para `14.1`, `14.2`, `14.3` e `14.5`.

### C. Notação em Lote Separada por Vírgulas
Permite enviar seleções heterogêneas em uma única string:
- `14.5,47,49` $\rightarrow$ Busca o item `14.5`, o Artigo `47` e o Artigo `49`.
- `14.[1,3],58.7` $\rightarrow$ Busca os itens `14.1`, `14.3` e `58.7`.

---

## 4. Algoritmo de Compactação (`script.js`)

A função `compactarNotacoes(notationsArray)` no frontend recebe uma lista de notações individuais (ex: `['58.7', '58.9', '6']`) e as agrupa por nó pai:

1. Extrai o pai (`parent`) e a chave filha (`child`) de cada item via `lastIndexOf('.')`.
2. Se um pai possui apenas **1 filho**, gera notação padrão: `parent.child`.
3. Se um pai possui **múltiplos filhos**, gera notação compacta: `parent.[child1,child2]`.
4. Se o item não possui filho (artigo inteiro), gera apenas `parent`.
5. Retorna as partes ordenadas e separadas por vírgulas.

---

## 5. Mecânica de Busca Híbrida (`script.js`)

No campo `#searchInput`:
1. **Busca Numérica Direta (`/^\d+$/`)**:
   - Se o usuário digitar apenas números (ex: `14`), dispara `buscarEstruturaDoArtigo("14")`.
   - Localiza o Artigo 14 em `database.json` e constrói uma lista contendo o próprio Artigo e todos os seus filhos (parágrafos `14.p1`, incisos `14.i1`), facilitando a seleção individual na UI.
2. **Busca Textual por Palavra-Chave (`termo.length >= 3`)**:
   - Dispara `pesquisarPorTexto(termo)`.
   - Executa uma varredura recursiva (`explorar(objeto, caminho)`) navegando por `artigos`, `paragrafos`, `incisos` e `alineas`.
   - Compara o termo (case-insensitive) contra a propriedade `texto`.
   - Retorna um array de resultados contendo `notacao`, `texto` e dados do `capitulo`.

---

## 6. Interpretador Backend e Endpoint (`trecho.php`)

O arquivo `trecho.php` atua como a API REST de consulta do Regimento.

### Parâmetros da Requisição GET:
- `notacao` (obrigatório): String com notação simples (`4.p1`), lote (`14.5,47`), ou compacta (`58.[7,9]`).
- `formato` (opcional):
  - `estruturado` (padrão): Retorna JSON com os objetos parseados, dados de capítulo e o artigo pai.
  - `texto`: Retorna um objeto JSON contendo a propriedade `texto_formatado`, pronto para inclusão direta em pareceres.

### Fluxo de Execução do Backend:
1. **Roteamento & Parser de Notação**:
   - Se contiver `[...]`, isola o `caminhoPai` e o `seletor`, chamando `parsearSelecaoComplexa($seletor)`. O parser aceita listas (`7,9`) e intervalos (`1-5`).
   - Navega pela árvore JSON via `encontrarNoCaminho($dados, $notacao)`. Suporta notações tipadas (`p`, `i`, `a`) via Regex `^([pia])(.+)$` ou fallback por ordem de subníveis.
2. **Inclusão do Contexto do Artigo Pai**:
   - Ao buscar um item específico (ex: um parágrafo ou inciso), o PHP embute as propriedades `texto_artigo_pai` e `titulo_artigo_pai` no retorno para manter a inteligibilidade jurídica do parecer.
3. **Formatação para Texto Legível (`formatarItemComoTexto()`)**:
   - Formata a saída organizando por Capítulo (`Capítulo XIV: CAPÍTULO XIV – DO USO DAS PISCINAS`).
   - Adiciona marcadores legais padronizados:
     - Parágrafos: `§ 1°:` ou `Parágrafo único:`.
     - Incisos: `Inciso I:`.
     - Alíneas: `Alínea a):`.
   - Aplica recuo/indentação recursiva por nível hierárquico.

---

## 7. Exemplos Práticos de Uso

### A. Consulta via HTTP GET (Formato Texto)
```http
GET /regimento/trecho.php?notacao=58.[7,9]&formato=texto
```

### B. Exemplo de Resposta (Formato Texto Formatado)
```
Capítulo XXVIII: CAPÍTULO XXVIII – OUTROS DEVERES E PROIBIÇÕES
Referência: 58.7
ARTIGO 58
São proibições dos condôminos, moradores e frequentadores:

§ 7°:
É proibido colocar ou estender roupas, tapetes ou quaisquer objetos nas janelas, sacadas ou áreas externas visíveis do condomínio.
```

### C. Consulta de Lote Heterogênea (Formato Estruturado)
```http
GET /regimento/trecho.php?notacao=14.5,47&formato=estruturado
```
