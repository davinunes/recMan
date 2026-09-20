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
  let analise = get("analise", "")
  let resultado = get("resultado", "")
  let parecer = get("parecer", get("conclusao", "Favorável ao Recurso"))
  let data_emissao = get("data_emissao", "20 de Setembro de 2026")
  let variant = get("variant", "modern")
  let banner_path = get("banner_path", "LayoutMiami.jpg")
  let modelo = get("modelo", "estatico")
  let corpo = get("corpo", "")

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
  } else if variant == "editorial" {
    title_color = rgb("065f46")
    bg_card = rgb("ecfdf5")
    stroke_card = rgb("059669")
  }

  set document(title: [Parecer do Conselho - Notificação #notificacao], author: "Conselho Consultivo e Fiscal")

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

  // BANNER DE TOPO (Apenas para variante 'modern')
  if variant == "modern" [
    #v(-1cm)
    #align(center)[
      #image(banner_path, width: 100%)
    ]
    #v(0.3cm)
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

  // Tabela de Resumo dos Dados por Variante
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
          *Conclusão:* #parecer
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
        [ *DATA:* #data_emissao ],
        grid.cell(colspan: 2)[
          #v(4pt)
          #line(length: 100%, stroke: 1pt + rgb("1e3a8a"))
          #v(6pt)
          *DECISÃO DO CONSELHO CONSULTIVO E FISCAL:* #parecer
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
        [ *Data:* #data_emissao ],
        grid.cell(colspan: 2)[
          #v(3pt)
          #line(length: 100%, stroke: 0.5pt + stroke_card)
          #v(6pt)
          *Conclusão do Parecer:* 
          #if parecer == "REVOGAR" or parecer == "REVOGAR A PENALIDADE" or parecer == "Favorável" or parecer == "Favorável ao Recurso" [
            #text(weight: "bold", fill: rgb("15803d"))[#parecer (RECURSO DEFERIDO)]
          ] else if parecer == "CONVERTER" or parecer == "CONVERTER EM ADVERTÊNCIA" [
            #text(weight: "bold", fill: rgb("d97706"))[#parecer]
          ] else [
            #text(weight: "bold", fill: rgb("b91c1c"))[#parecer (RECURSO INDEFERIDO)]
          ]
        ]
      )
    ]
  ]

  v(0.5cm)

  // CORPO DO PARECER (Modelo Dinâmico vs Estático Padrão)
  if modelo == "full_dinamico" and corpo != "" [
    #corpo
  ] else [
    #text(style: "italic", fill: text_secondary)[
      Prezados,
      
      O Conselho Consultivo e Fiscal foi convocado para prestar parecer concernente a recurso contra a notificação supracitada referente à infração prevista no Regimento Interno, pautada sobre os artigos acima relacionados.
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
