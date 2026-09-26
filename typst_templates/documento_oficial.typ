#let documento-oficial-doc(data) = {
  let get(key, default) = {
    if key in data and data.at(key) != none and str(data.at(key)) != "" {
      data.at(key)
    } else {
      default
    }
  }

  let numero = get("numero", "001")
  let ano = get("ano", "2026")
  let tipo_rotulo = get("tipo_rotulo", "ORIENTAÇÃO TÉCNICA")
  let titulo = get("titulo", "Título do Documento Oficial")
  let ementa = get("ementa", "")
  let conteudo_typst = get("conteudo_typst", "")
  let relator = get("relator", "Conselho Consultivo e Fiscal")
  let data_emissao = get("data_emissao", "26/09/2026")
  let variant = get("variante_global", "top_header")
  let top_image_path = get("top_image_path", "lay-top-fist-page-1.png")
  let watermark_image_path = get("watermark_image_path", "lay-body-all-pages-1.png")

  // Cores dinâmicas por variante
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
  } else if variant == "editorial" or variant == "top_header" or variant == "watermark_a4" {
    title_color = rgb("065f46")
    bg_card = rgb("ecfdf5")
    stroke_card = rgb("059669")
  }

  set document(title: [#tipo_rotulo nº #numero/#ano - #titulo], author: relator)

  set page(
    paper: "a4",
    margin: (x: 2cm, top: 2.5cm, bottom: 2.5cm),
    fill: if variant == "dark" { rgb("0f172a") } else { white },
    background: context {
      let current_page = counter(page).get().first()
      if variant == "watermark_a4" [
        #place(top + left)[
          #image(watermark_image_path, width: 100%, height: 100%)
        ]
      ] else if variant == "top_header" and current_page == 1 [
        #place(top + left)[
          #image(top_image_path, width: 100%)
        ]
      ]
    },
    header: context {
      let current_page = counter(page).get().first()
      if (variant != "top_header" or current_page > 1) or current_page > 1 [
        #grid(
          columns: (1fr, auto),
          align(left)[#text(size: 8.5pt, fill: text_secondary, weight: "bold")[CONDOMÍNIO RESIDENCIAL TOP LIFE MIAMI BEACH]],
          align(right)[#text(size: 8.5pt, fill: text_secondary)[CONSELHO CONSULTIVO E FISCAL]]
        )
        #line(length: 100%, stroke: 0.5pt + stroke_card)
      ]
    },
    footer: context {
      let page_number = counter(page).get().first()
      let total_pages = counter(page).final().first()
      align(center)[
        #text(size: 8pt, fill: text_secondary)[
          #tipo_rotulo nº #numero/#ano • Página #page_number de #total_pages
        ]
      ]
    }
  )

  set text(lang: "pt", size: 10pt, fill: text_primary)
  set par(justify: true, leading: 0.65em)

  if variant == "top_header" [
    #v(2.5cm)
  ]

  // Data alinhada à direita
  align(right)[
    #text(size: 9pt, style: "italic", fill: text_secondary)[Taguatinga, #data_emissao]
  ]

  // Título do Documento
  align(center)[
    #v(0.2cm)
    #text(size: 15pt, weight: "bold", fill: title_color)[#tipo_rotulo Nº #numero/#ano]
    #v(4pt)
    #text(size: 12pt, weight: "bold", fill: text_primary)[#titulo]
    #v(0.4cm)
  ]

  // Ementa (se houver)
  if ementa != "" [
    #pad(left: 3cm)[
      #rect(width: 100%, inset: 10pt, radius: 4pt, fill: bg_card, stroke: 0.5pt + stroke_card)[
        #text(size: 9pt, style: "italic", fill: text_secondary)[
          *EMENTA:* #ementa
        ]
      ]
    ]
    #v(0.5cm)
  ]

  // Conteúdo do Documento (em formato Typst)
  #eval(conteudo_typst, mode: "markup")

  #v(1.2cm)

  // Assinatura / Lavrado Digitalmente
  align(center)[
    #rect(width: 85%, inset: 10pt, radius: 4pt, fill: if variant == "dark" { rgb("1e293b") } else { rgb("f1f5f9") }, stroke: 0.5pt + stroke_card)[
      #grid(
        columns: (auto, 1fr),
        gutter: 10pt,
        align: (center + horizon, left + horizon),
        [ #text(size: 16pt)[✒️] ],
        [
          #text(size: 8.5pt, weight: "medium", fill: text_secondary)[
            #tipo_rotulo lavrado digitalmente em Taguatinga, #data_emissao por *#relator*.
          ]
        ]
      )
    ]
  ]
}
