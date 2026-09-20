#let to-roman(num) = {
  let val = str(num)
  let roman_map = (
    "1": "I", "2": "II", "3": "III", "4": "IV", "5": "V",
    "6": "VI", "7": "VII", "8": "VIII", "9": "IX", "10": "X",
    "11": "XI", "12": "XII", "13": "XIII", "14": "XIV", "15": "XV",
    "16": "XVI", "17": "XVII", "18": "XVIII", "19": "XIX", "20": "XX",
    "21": "XXI", "22": "XXII", "23": "XXIII", "24": "XXIV", "25": "XXV",
    "26": "XXVI", "27": "XXVII", "28": "XXVIII", "29": "XXIX", "30": "XXX"
  )
  if val in roman_map {
    roman_map.at(val)
  } else {
    val
  }
}

#let render-alineas(alineas-dict, indent-left: 24pt) = {
  if alineas-dict != none and type(alineas-dict) == dictionary {
    for (a_key, a_val) in alineas-dict {
      let a_txt = if type(a_val) == dictionary and "texto" in a_val { a_val.texto } else { str(a_val) }
      v(2pt)
      pad(left: indent-left)[
        #text(fill: rgb("334155"))[
          #text(weight: "bold")[#a_key\)] #a_txt
        ]
      ]
    }
  }
}

#let render-incisos(incisos-dict, indent-left: 12pt) = {
  if incisos-dict != none and type(incisos-dict) == dictionary {
    for (i_key, i_val) in incisos-dict {
      let i_txt = if type(i_val) == dictionary and "texto" in i_val { i_val.texto } else { str(i_val) }
      let i_num = to-roman(i_key)
      v(3pt)
      pad(left: indent-left)[
        #text(fill: rgb("1e293b"))[
          #text(weight: "bold")[#i_num -] #i_txt
        ]
      ]
      if type(i_val) == dictionary and "alineas" in i_val and i_val.alineas != none {
        render-alineas(i_val.alineas, indent-left: indent-left + 12pt)
      }
    }
  }
}

#let regimento-doc(data) = {
  let titulo = if "titulo" in data { data.titulo } else { "REGIMENTO INTERNO - CONDOMÍNIO TOP LIFE MIAMI BEACH" }
  let capitulos = if "capitulos" in data { data.capitulos } else { (:) }
  let artigos = if "artigos" in data { data.artigos } else { (:) }

  set document(title: titulo, author: "Condomínio Residencial Top Life Miami Beach")

  set page(
    paper: "a4",
    margin: (x: 2cm, top: 2.5cm, bottom: 2.5cm),
    header: context {
      if counter(page).get().first() > 1 [
        #grid(
          columns: (1fr, auto),
          align(left)[#text(size: 8pt, fill: rgb("475569"), weight: "bold")[REGIMENTO INTERNO - TOP LIFE MIAMI BEACH]],
          align(right)[#text(size: 8pt, fill: rgb("64748b"))[Documento Oficial]]
        )
        #line(length: 100%, stroke: 0.5pt + rgb("cbd5e1"))
      ]
    },
    footer: context {
      let page_number = counter(page).get().first()
      let total_pages = counter(page).final().first()
      align(center)[
        #text(size: 8.5pt, fill: rgb("64748b"))[
          Página #page_number de #total_pages
        ]
      ]
    }
  )

  set text(lang: "pt", size: 10pt)
  set par(justify: true, leading: 0.65em)

  // Capa / Título principal
  align(center + horizon)[
    #v(-2cm)
    #rect(width: 100%, inset: 20pt, radius: 4pt, fill: rgb("0f172a"))[
      #text(fill: white, weight: "bold", size: 16pt)[#titulo]
    ]
    #v(1.5cm)
    #text(size: 12pt, style: "italic", fill: rgb("334155"))[
      Normas de Funcionamento, Convivência e Administração
    ]
    #v(2cm)
    #text(size: 10pt, fill: rgb("64748b"))[
      Taguatinga / DF
    ]
  ]

  pagebreak()

  // Sumário dos Capítulos
  heading(level: 1, numbering: none)[Sumário dos Capítulos]
  v(0.5cm)

  for (cap_id, cap_nome) in capitulos {
    box(width: 100%, inset: (y: 3pt))[
      #text(weight: "medium", fill: rgb("1e293b"))[#cap_nome]
    ]
  }

  pagebreak()

  // Exibição dos Capítulos e Artigos
  let current_cap = ""

  for (art_id, art_data) in artigos {
    let cap_num = str(art_data.capitulo)
    if cap_num in capitulos and capitulos.at(cap_num) != current_cap {
      current_cap = capitulos.at(cap_num)
      v(0.8cm)
      rect(width: 100%, fill: rgb("f1f5f9"), inset: 8pt, radius: 3pt, stroke: 0.5pt + rgb("cbd5e1"))[
        #text(weight: "bold", fill: rgb("0f172a"), size: 11.5pt)[#current_cap]
      ]
      v(0.3cm)
    }

    let art_texto = if "texto" in art_data { art_data.texto } else { "" }

    block(width: 100%, below: 8pt)[
      #text(weight: "bold", fill: rgb("1e3a8a"))[Art. #art_id º] #art_texto

      #if "incisos" in art_data and art_data.incisos != none {
        render-incisos(art_data.incisos, indent-left: 12pt)
      }

      #if "alineas" in art_data and art_data.alineas != none {
        render-alineas(art_data.alineas, indent-left: 12pt)
      }

      #if "paragrafos" in art_data and art_data.paragrafos != none and type(art_data.paragrafos) == dictionary [
        #for (p_key, p_val) in art_data.paragrafos {
          let p_txt = if type(p_val) == dictionary and "texto" in p_val { p_val.texto } else { str(p_val) }
          v(3pt)
          pad(left: 12pt)[
            #text(style: "italic", fill: rgb("334155"))[
              #if p_key == "unico" [
                #text(weight: "bold")[Parágrafo único.] #p_txt
              ] else [
                #text(weight: "bold")[§ #p_key º] #p_txt
              ]
            ]
          ]
          if type(p_val) == dictionary and "incisos" in p_val and p_val.incisos != none {
            render-incisos(p_val.incisos, indent-left: 24pt)
          }
          if type(p_val) == dictionary and "alineas" in p_val and p_val.alineas != none {
            render-alineas(p_val.alineas, indent-left: 24pt)
          }
        }
      ]
    ]
  }
}
