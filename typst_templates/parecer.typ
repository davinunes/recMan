#let data = if "data" in sys.inputs {
  json.decode(sys.inputs.data)
} else {
  (
    notificacao: "186/2023",
    unidade: "A1305",
    assunto: "ESTACIONAMENTO INDEVIDO",
    fato: "Notificação emitida por estacionar veículo fora dos limites da vaga demarcada na garagem do subsolo.",
    parecer: "Favorável ao Recurso",
    relator: "Conselho Consultivo e Fiscal",
    data_emissao: "20/09/2026",
    fundamentacao: "Após análise das alegações e documentos apresentados pelo recorrente, o Conselho verificou atenuantes relevantes e deliberou pelo provimento do recurso."
  )
}

#set document(title: [Parecer Notificação #data.notificacao], author: "Conselho Consultivo")

#set page(
  paper: "a4",
  margin: (x: 2cm, top: 2.5cm, bottom: 2.5cm),
  header: [
    #grid(
      columns: (1fr, auto),
      align(left)[#text(size: 8.5pt, fill: rgb("#475569"), weight: "bold")[CONDOMÍNIO RESIDENCIAL TOP LIFE MIAMI BEACH]],
      align(right)[#text(size: 8.5pt, fill: rgb("#475569"))[COMISSÃO DE RECURSOS]]
    )
    #line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))
  ],
  footer: locate(loc => {
    let page_number = counter(page).at(loc).first()
    let total_pages = counter(page).final(loc).first()
    align(center)[
      #text(size: 8pt, fill: rgb("#64748b"))[
        Parecer Notificação nº #data.notificacao • Página #page_number de #total_pages
      ]
    ]
  })
)

#set text(font: ("Liberation Sans", "DejaVu Sans", "Arial"), lang: "pt", size: 10pt)
#set par(justify: true, leading: 0.65em)

// Título Principal
#align(center)[
  #v(0.3cm)
  #text(size: 14pt, weight: "bold", fill: rgb("#0f172a"))[PARECER DA COMISSÃO DE RECURSOS]
  #v(2pt)
  #text(size: 10pt, weight: "medium", fill: rgb("#475569"))[Referente à Notificação nº #data.notificacao]
  #v(0.4cm)
]

// Tabela de Resumo do Parecer
#rect(width: 100%, inset: 12pt, radius: 4pt, fill: rgb("#f8fafc"), stroke: 0.5pt + rgb("#cbd5e1"))[
  #grid(
    columns: (1fr, 1fr),
    row-gutter: 9pt,
    [ *Unidade Recorrente:* #data.unidade ],
    [ *Notificação:* #data.notificacao ],
    [ *Assunto:* #data.assunto ],
    [ *Data de Emissão:* #data.data_emissao ],
    grid.cell(colspan: 2)[
      #v(3pt)
      #line(length: 100%, stroke: 0.5pt + rgb("#cbd5e1"))
      #v(6pt)
      *Conclusão do Parecer:* 
      #if data.parecer == "Favorável" or data.parecer == "Favorável ao Recurso" [
        #text(weight: "bold", fill: rgb("#15803d"))[#data.parecer (DEFERIDO)]
      ] else [
        #text(weight: "bold", fill: rgb("#b91c1c"))[#data.parecer (INDEFERIDO)]
      ]
    ]
  )
]

#v(0.6cm)

// Seção 1: Relato do Fato
#heading(level: 2, numbering: none)[1. Relato dos Fatos]
#v(0.2cm)
#rect(width: 100%, inset: 10pt, fill: white, stroke: 0.5pt + rgb("#e2e8f0"), radius: 3pt)[
  #data.fato
]

#v(0.6cm)

// Seção 2: Análise e Fundamentação
#heading(level: 2, numbering: none)[2. Análise e Fundamentação]
#v(0.2cm)
#if "fundamentacao" in data and data.fundamentacao != "" [
  #data.fundamentacao
] else [
  O Conselho Consultivo e Fiscal analisou o recurso impetrado e os elementos fáticos anexados ao processo, fundamentando a decisão nas diretrizes do Regimento Interno vigente.
]

#v(2cm)

// Bloco de Assinatura
#align(center)[
  #block(width: 65%)[
    #line(length: 100%, stroke: 0.5pt + rgb("#475569"))
    #v(4pt)
    #text(weight: "bold", fill: rgb("#0f172a"))[#data.relator] \
    #text(size: 8.5pt, fill: rgb("#64748b"))[Conselho Consultivo e Fiscal]
  ]
]
