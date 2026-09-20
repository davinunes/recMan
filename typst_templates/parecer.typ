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

  set document(title: [Parecer Notificação #notificacao], author: "Conselho Consultivo")

  set page(
    paper: "a4",
    margin: (x: 2cm, top: 2.5cm, bottom: 2.5cm),
    header: [
      #grid(
        columns: (1fr, auto),
        align(left)[#text(size: 8.5pt, fill: rgb("475569"), weight: "bold")[CONDOMÍNIO RESIDENCIAL TOP LIFE MIAMI BEACH]],
        align(right)[#text(size: 8.5pt, fill: rgb("475569"))[COMISSÃO DE RECURSOS]]
      )
      #line(length: 100%, stroke: 0.5pt + rgb("cbd5e1"))
    ],
    footer: context {
      let page_number = counter(page).get().first()
      let total_pages = counter(page).final().first()
      align(center)[
        #text(size: 8pt, fill: rgb("64748b"))[
          Parecer Notificação nº #notificacao • Página #page_number de #total_pages
        ]
      ]
    }
  )

  set text(lang: "pt", size: 10pt)
  set par(justify: true, leading: 0.65em)

  // Título Principal
  align(center)[
    #v(0.3cm)
    #text(size: 14pt, weight: "bold", fill: rgb("0f172a"))[PARECER DA COMISSÃO DE RECURSOS]
    #v(2pt)
    #text(size: 10pt, weight: "medium", fill: rgb("475569"))[Referente à Notificação nº #notificacao]
    #v(0.4cm)
  ]

  // Tabela de Resumo do Parecer
  rect(width: 100%, inset: 12pt, radius: 4pt, fill: rgb("f8fafc"), stroke: 0.5pt + rgb("cbd5e1"))[
    #grid(
      columns: (1fr, 1fr),
      row-gutter: 9pt,
      [ *Unidade Recorrente:* #unidade ],
      [ *Notificação:* #notificacao ],
      [ *Assunto:* #assunto ],
      [ *Data de Emissão:* #data_emissao ],
      grid.cell(colspan: 2)[
        #v(3pt)
        #line(length: 100%, stroke: 0.5pt + rgb("cbd5e1"))
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

  v(0.6cm)

  // Seção 1: Relato do Fato
  heading(level: 2, numbering: none)[1. Relato dos Fatos]
  v(0.2cm)
  rect(width: 100%, inset: 10pt, fill: white, stroke: 0.5pt + rgb("e2e8f0"), radius: 3pt)[
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
      #line(length: 100%, stroke: 0.5pt + rgb("475569"))
      #v(4pt)
      #text(weight: "bold", fill: rgb("0f172a"))[#relator] \
      #text(size: 8.5pt, fill: rgb("64748b"))[Conselho Consultivo e Fiscal]
    ]
  ]
}
