#let parecer-doc(data) = {
  let get(key, default) = {
    if key in data and data.at(key) != none and str(data.at(key)) != "" {
      data.at(key)
    } else {
      default
    }
  }

  let notificacao = get("notificacao", "186/2023")
  let unidade = get("unidade", "A1305")
  let assunto = get("assunto", "ESTACIONAMENTO INDEVIDO")
  let fato = get("fato", "Veículo posicionado temporariamente para descarregamento.")
  let parecer = get("parecer", "Favorável ao Recurso")
  let relator = get("relator", "Conselho Consultivo e Fiscal")
  let data_emissao = get("data_emissao", "20/09/2026")
  let fundamentacao = get("fundamentacao", "Após análise das alegações e documentos apresentados pelo recorrente, o Conselho deliberou pelo provimento do recurso.")
  let variant = get("variant", "modern")
  let banner_path = get("banner_path", "LayoutMiami.jpg")

  // Cores dinâmicas
  let text_primary = rgb("0f172a")
  let text_secondary = rgb("475569")
  let bg_card = rgb("f8fafc")
  let stroke_card = rgb("cbd5e1")
  let title_color = rgb("0f172a")

  if variant == "corporate" {
    title_color = rgb("1e40af")
    bg_card = rgb("eff6ff")
    stroke_card = rgb("93c5fd")
  } else if variant == "minimal" {
    title_color = rgb("334155")
    bg_card = rgb("f8fafc")
    stroke_card = rgb("e2e8f0")
  } else if variant == "juridico" {
    title_color = rgb("1e3a8a")
    bg_card = rgb("f0fdf4")
    stroke_card = rgb("1e3a8a")
  } else if variant == "dark" {
    text_primary = rgb("f8fafc")
    text_secondary = rgb("cbd5e1")
    title_color = rgb("38bdf8")
    bg_card = rgb("1e293b")
    stroke_card = rgb("0284c7")
  } else if variant == "editorial" {
    title_color = rgb("065f46")
    bg_card = rgb("ecfdf5")
    stroke_card = rgb("059669")
  }

  set document(title: [Parecer Notificação #notificacao], author: "Conselho Consultivo")

  set page(
    paper: "a4",
    margin: (x: 2cm, top: 2.5cm, bottom: 2.5cm),
    fill: if variant == "dark" { rgb("0f172a") } else { white },
    header: context {
      let current_page = counter(page).get().first()
      if variant != "modern" or current_page > 1 [
        #grid(
          columns: (1fr, auto),
          align(left)[#text(size: 8.5pt, fill: text_secondary, weight: "bold")[CONDOMÍNIO RESIDENCIAL TOP LIFE MIAMI BEACH]],
          align(right)[#text(size: 8.5pt, fill: text_secondary)[COMISSÃO DE RECURSOS]]
        )
        #line(length: 100%, stroke: 0.5pt + stroke_card)
      ]
    },
    footer: context {
      let page_number = counter(page).get().first()
      let total_pages = counter(page).final().first()
      align(center)[
        #text(size: 8pt, fill: text_secondary)[
          Parecer Notificação nº #notificacao • Página #page_number de #total_pages
        ]
      ]
    }
  )

  set text(lang: "pt", size: 10pt, fill: text_primary)
  set par(justify: true, leading: 0.65em)

  // BANNER DE TOPO (Apenas para variante 'modern')
  if variant == "modern" [
    #v(-1cm)
    #align(center)[
      #image(banner_path, width: 100%)
    ]
    #v(0.5cm)
  ]

  // Título Principal
  align(center)[
    #v(0.2cm)
    #text(size: 14pt, weight: "bold", fill: title_color)[PARECER DA COMISSÃO DE RECURSOS]
    #v(2pt)
    #text(size: 10pt, weight: "medium", fill: text_secondary)[Referente à Notificação nº #notificacao]
    #v(0.4cm)
  ]

  // Tabela de Resumo do Parecer por Variante
  if variant == "compact" [
    #rect(width: 100%, inset: 8pt, radius: 2pt, fill: bg_card, stroke: 0.5pt + stroke_card)[
      #grid(
        columns: (1fr, 1fr),
        row-gutter: 6pt,
        [ *Unidade:* #unidade ],
        [ *Notificação:* #notificacao ],
        [ *Assunto:* #assunto ],
        [ *Resultado:* *#parecer* ]
      )
    ]
  ] else if variant == "minimal" [
    #pad(x: 10pt)[
      #grid(
        columns: (1fr, 1fr),
        row-gutter: 8pt,
        [ *Unidade:* #unidade ],
        [ *Notificação:* #notificacao ],
        [ *Assunto:* #assunto ],
        [ *Data:* #data_emissao ],
        grid.cell(colspan: 2)[
          #v(2pt)
          #line(length: 100%, stroke: 0.5pt + rgb("cbd5e1"))
          #v(4pt)
          *Resultado:* #parecer
        ]
      )
    ]
  ] else if variant == "juridico" [
    #rect(width: 100%, inset: 12pt, radius: 0pt, stroke: 2pt + rgb("1e3a8a"), fill: bg_card)[
      #grid(
        columns: (1fr, 1fr),
        row-gutter: 8pt,
        [ *UNIDADE:* #unidade ],
        [ *NOTIFICAÇÃO:* #notificacao ],
        [ *ASSUNTO:* #assunto ],
        [ *DATA DE EMISSÃO:* #data_emissao ],
        grid.cell(colspan: 2)[
          #v(4pt)
          #line(length: 100%, stroke: 1pt + rgb("1e3a8a"))
          #v(6pt)
          *DECISÃO DO CONSELHO:* #parecer
        ]
      )
    ]
  ] else [
    #rect(width: 100%, inset: 12pt, radius: 6pt, fill: bg_card, stroke: 0.5pt + stroke_card)[
      #grid(
        columns: (1fr, 1fr),
        row-gutter: 9pt,
        [ *Unidade Recorrente:* #unidade ],
        [ *Notificação:* #notificacao ],
        [ *Assunto:* #assunto ],
        [ *Data de Emissão:* #data_emissao ],
        grid.cell(colspan: 2)[
          #v(3pt)
          #line(length: 100%, stroke: 0.5pt + stroke_card)
          #v(6pt)
          *Conclusão do Parecer:* 
          #if parecer == "Favorável" or parecer == "Favorável ao Recurso" [
            #text(weight: "bold", fill: rgb("15803d"))[#parecer (DEFERIDO)]
          ] else [
            #text(weight: "bold", fill: rgb("b91c1c"))[#parecer (INDEFERIDO)]
          ]
        ]
      )
    ]
  ]

  v(0.6cm)

  // Seção 1: Relato do Fato
  heading(level: 2, numbering: none)[1. Relato dos Fatos]
  v(0.2cm)
  rect(width: 100%, inset: 10pt, fill: bg_card, stroke: 0.5pt + stroke_card, radius: 3pt)[
    #fato
  ]

  v(0.6cm)

  // Seção 2: Análise e Fundamentação
  heading(level: 2, numbering: none)[2. Análise e Fundamentação]
  v(0.2cm)
  fundamentacao

  v(2cm)

  // Bloco de Assinatura
  align(center)[
    #block(width: 65%)[
      #line(length: 100%, stroke: 0.5pt + text_secondary)
      #v(4pt)
      #text(weight: "bold", fill: text_primary)[#relator] \
      #text(size: 8.5pt, fill: text_secondary)[Conselho Consultivo e Fiscal]
    ]
  ]
}
