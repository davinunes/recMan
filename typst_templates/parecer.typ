#let parecer-doc(data) = {
  let get(key, default) = {
    if key in data and data.at(key) != none and str(data.at(key)) != "" {
      data.at(key)
    } else {
      default
    }
  }

  let formatar-data-br(d-str) = {
    let s = str(d-str).trim()
    if s.contains("-") {
      let parts = s.split("-")
      if parts.len() == 3 and parts.at(0).len() == 4 {
        return parts.at(2) + "/" + parts.at(1) + "/" + parts.at(0)
      }
    } else if s.contains("/") {
      let parts = s.split("/")
      if parts.len() == 3 and parts.at(0).len() == 4 {
        return parts.at(2) + "/" + parts.at(1) + "/" + parts.at(0)
      }
    }
    return s
  }

  let notificacao = get("notificacao", "186/2023")
  let unidade = get("unidade", "A1305")
  let assunto = get("assunto", "ESTACIONAMENTO INDEVIDO")
  let fato = get("fato", "Veículo posicionado temporariamente para descarregamento.")
  let analise = get("analise", "")
  let resultado = get("resultado", "")
  let parecer = get("parecer", get("conclusao", "Favorável ao Recurso"))
  let data_emissao_raw = get("data_emissao", "21/09/2026")
  let data_emissao = formatar-data-br(data_emissao_raw)
  let variant = get("variant", "modern")
  let banner_path = get("banner_path", "LayoutMiami.jpg")
  let modelo = get("modelo", "estatico")
  let corpo = get("corpo", "")
  let exibir_banner_raw = get("exibir_banner", get("exibir_banner_topo", "1"))
  let exibir_banner = str(exibir_banner_raw) != "0" and str(exibir_banner_raw) != "false"

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

  set document(title: [Parecer do Conselho - Notificação #notificacao], author: "Conselho Consultivo e Fiscal")

  set page(
    paper: "a4",
    margin: (x: 2cm, top: 2.5cm, bottom: 2.5cm),
    fill: if variant == "dark" { rgb("0f172a") } else { white },
    background: context {
      let current_page = counter(page).get().first()
      if variant == "watermark_a4" [
        #place(top + left, dx: -2cm, dy: -2.5cm)[
          #image("lay-body-all-pages-1.png", width: 210mm, height: 297mm)
        ]
      ] else if variant == "top_header" and current_page == 1 [
        #place(top + left, dx: -2cm, dy: -2.5cm)[
          #image("lay-top-fist-page-1.png", width: 210mm)
        ]
      ]
    },
    header: context {
      let current_page = counter(page).get().first()
      if (variant != "modern" or not exibir_banner) and (variant != "top_header" or current_page > 1) or current_page > 1 [
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
          Parecer Notificação nº #notificacao • Página #page_number de #total_pages
        ]
      ]
    }
  )

  set text(lang: "pt", size: 10pt, fill: text_primary)
  set par(justify: true, leading: 0.65em)

  // BANNER DE TOPO (Apenas para variante 'modern' se exibir_banner estiver ativo)
  if variant == "modern" and exibir_banner [
    #v(-1cm)
    #align(center)[
      #image(banner_path, width: 100%)
    ]
    #v(0.3cm)
  ] else if variant == "top_header" [
    #v(2.5cm)
  ]

  // Data alinhada à direita
  align(right)[
    #text(size: 9pt, style: "italic", fill: text_secondary)[Taguatinga, #data_emissao]
  ]

  // Título Principal
  align(center)[
    #v(0.2cm)
    #text(size: 15pt, weight: "bold", fill: title_color)[PARECER DO CONSELHO]
    #v(2pt)
    #text(size: 10pt, weight: "medium", fill: text_secondary)[Notificação nº #notificacao]
    #v(0.4cm)
  ]

  // Tabela de Resumo dos Dados por Variante (Unidade e Assunto apenas)
  if variant == "compact" [
    #rect(width: 100%, inset: 8pt, radius: 2pt, fill: bg_card, stroke: 0.5pt + stroke_card)[
      #grid(
        columns: (auto, 1fr),
        gutter: 12pt,
        [ *Unidade:* #unidade ],
        [ *Assunto:* #assunto ]
      )
    ]
  ] else if variant == "minimal" [
    #pad(x: 10pt)[
      #grid(
        columns: (auto, 1fr),
        gutter: 12pt,
        [ *Unidade:* #unidade ],
        [ *Assunto:* #assunto ]
      )
    ]
  ] else if variant == "juridico" [
    #rect(width: 100%, inset: 10pt, radius: 0pt, stroke: 2pt + rgb("1e3a8a"), fill: bg_card)[
      #grid(
        columns: (auto, 1fr),
        gutter: 12pt,
        [ *UNIDADE:* #unidade ],
        [ *ASSUNTO:* #assunto ]
      )
    ]
  ] else [
    #rect(width: 100%, inset: 10pt, radius: 6pt, fill: bg_card, stroke: 0.5pt + stroke_card)[
      #grid(
        columns: (auto, 1fr),
        gutter: 16pt,
        [ *Unidade Recorrente:* #unidade ],
        [ *Assunto:* #assunto ]
      )
    ]
  ]

  v(0.5cm)

  // CORPO DO PARECER (Modelo Dinâmico vs Estático Padrão)
  if modelo == "full_dinamico" [
    #if analise != "" [
      #analise
    ] else if corpo != "" [
      #corpo
    ] else [
      #fato
    ]
  ] else [
    #text(style: "italic", fill: text_secondary)[
      Prezados,
      
      O Conselho Consultivo e Fiscal foi convocado para prestar parecer concernente a recurso contra a notificação supracitada referente à infração prevista no Regimento Interno.
    ]

    #v(0.4cm)

    #heading(level: 2, numbering: none)[1. Notificação]
    #v(0.1cm)
    #rect(width: 100%, inset: 8pt, fill: if variant == "dark" { rgb("1e293b") } else { white }, stroke: 0.5pt + stroke_card, radius: 3pt)[
      O Condomínio, no uso de suas atribuições administrativas, buscando o cumprimento regimental, notificou a unidade em decorrência de: #fato
    ]

    #if analise != "" [
      #v(0.4cm)
      #heading(level: 2, numbering: none)[2. Análise]
      #v(0.1cm)
      #analise
    ]

    #if resultado != "" [
      #v(0.4cm)
      #heading(level: 2, numbering: none)[3. Concluímos]
      #v(0.1cm)
      #resultado
    ]

    #v(0.5cm)
    #rect(width: 100%, inset: 10pt, radius: 4pt, fill: bg_card, stroke: 0.5pt + stroke_card)[
      #text(weight: "bold", fill: title_color)[
        Somos favoráveis, portanto, em #parecer a aplicação da penalidade.
      ]
    ]
  ]

  v(1.2cm)

  // LAVRADO DIGITALMENTE (Substitui campo de assinatura)
  align(center)[
    #rect(width: 85%, inset: 10pt, radius: 4pt, fill: if variant == "dark" { rgb("1e293b") } else { rgb("f1f5f9") }, stroke: 0.5pt + stroke_card)[
      #grid(
        columns: (auto, 1fr),
        gutter: 10pt,
        align: (center + horizon, left + horizon),
        [ #text(size: 16pt)[✒️] ],
        [
          #text(size: 8.5pt, weight: "medium", fill: text_secondary)[
            Documento lavrado digitalmente em Taguatinga, #data_emissao pelo *Conselho Consultivo e Fiscal*.
          ]
        ]
      )
    ]
  ]
}
