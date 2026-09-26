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

#let render-lista(lista-dict, indent_left: 24pt, text_color: rgb("334155"), marker: "- ") = {
  if lista-dict != none and type(lista-dict) == dictionary {
    for (l_key, l_val) in lista-dict {
      let l_txt = if type(l_val) == dictionary and "texto" in l_val { l_val.texto } else if type(l_val) == dictionary { "" } else { str(l_val) }
      if l_txt != "" [
        #v(2pt)
        #pad(left: indent_left)[
          #text(fill: text_color)[
            #text(weight: "bold")[#marker] #l_txt
          ]
        ]
      ]
    }
  }
}

#let render-itens(itens-dict, indent_left: 36pt, text_color: rgb("334155")) = {
  if itens-dict != none and type(itens-dict) == dictionary {
    for (it_key, it_val) in itens-dict {
      let it_txt = if type(it_val) == dictionary and "texto" in it_val { it_val.texto } else if type(it_val) == dictionary { "" } else { str(it_val) }
      if it_txt != "" [
        #v(2pt)
        #pad(left: indent_left)[
          #text(fill: text_color)[
            #text(weight: "bold")[#it_key\)] #it_txt
          ]
        ]
      ]
    }
  }
}

#let render-alineas(alineas-dict, indent_left: 24pt, text_color: rgb("334155")) = {
  if alineas-dict != none and type(alineas-dict) == dictionary {
    for (a_key, a_val) in alineas-dict {
      let a_txt = if type(a_val) == dictionary and "texto" in a_val { a_val.texto } else if type(a_val) == dictionary { "" } else { str(a_val) }
      if a_txt != "" [
        #v(2pt)
        #pad(left: indent_left)[
          #text(fill: text_color)[
            #text(weight: "bold")[#a_key\)] #a_txt
          ]
        ]
      ]
      if type(a_val) == dictionary and "itens" in a_val and a_val.itens != none {
        render-itens(a_val.itens, indent_left: indent_left + 12pt, text_color: text_color)
      }
      if type(a_val) == dictionary and "incisos" in a_val and a_val.incisos != none {
        render-incisos(a_val.incisos, indent_left: indent_left + 12pt, text_color: text_color)
      }
      if type(a_val) == dictionary and "lista" in a_val and a_val.lista != none {
        render-lista(a_val.lista, indent_left: indent_left + 12pt, text_color: text_color)
      } else if type(a_val) == dictionary and "tracos" in a_val and a_val.tracos != none {
        render-lista(a_val.tracos, indent_left: indent_left + 12pt, text_color: text_color)
      }
    }
  }
}

#let render-incisos(incisos-dict, indent_left: 12pt, text_color: rgb("1e293b")) = {
  if incisos-dict != none and type(incisos-dict) == dictionary {
    for (i_key, i_val) in incisos-dict {
      let i_txt = if type(i_val) == dictionary and "texto" in i_val { i_val.texto } else if type(i_val) == dictionary { "" } else { str(i_val) }
      let i_num = to-roman(i_key)
      if i_txt != "" [
        #v(3pt)
        #pad(left: indent_left)[
          #text(fill: text_color)[
            #text(weight: "bold")[#i_num -] #i_txt
          ]
        ]
      ]
      if type(i_val) == dictionary and "incisos" in i_val and i_val.incisos != none {
        render-incisos(i_val.incisos, indent_left: indent_left + 12pt, text_color: text_color)
      }
      if type(i_val) == dictionary and "alineas" in i_val and i_val.alineas != none {
        render-alineas(i_val.alineas, indent_left: indent_left + 12pt, text_color: text_color)
      }
      if type(i_val) == dictionary and "itens" in i_val and i_val.itens != none {
        render-itens(i_val.itens, indent_left: indent_left + 12pt, text_color: text_color)
      }
      if type(i_val) == dictionary and "lista" in i_val and i_val.lista != none {
        render-lista(i_val.lista, indent_left: indent_left + 12pt, text_color: text_color)
      } else if type(i_val) == dictionary and "tracos" in i_val and i_val.tracos != none {
        render-lista(i_val.tracos, indent_left: indent_left + 12pt, text_color: text_color)
      }
    }
  }
}

#let render-anexo1-itens(itens-dict, prefix: "", indent_left: 6pt, text_color: rgb("334155")) = {
  if itens-dict != none and type(itens-dict) == dictionary {
    for (it_key, it_val) in itens-dict {
      let code_num = if prefix == "" { str(it_key) } else { prefix + "." + str(it_key) }
      let it_txt = if type(it_val) == dictionary and "texto" in it_val { it_val.texto } else if type(it_val) == dictionary { "" } else { str(it_val) }

      if it_txt != "" [
        #v(3pt)
        #pad(left: indent_left)[
          #text(fill: text_color)[
            #text(weight: "bold")[#code_num] #it_txt
          ]
        ]
      ]

      if type(it_val) == dictionary and "itens" in it_val and it_val.itens != none {
        render-anexo1-itens(it_val.itens, prefix: code_num, indent_left: indent_left + 10pt, text_color: text_color)
      }

      if type(it_val) == dictionary and "alineas" in it_val and it_val.alineas != none {
        render-alineas(it_val.alineas, indent_left: indent_left + 12pt, text_color: text_color)
      }

      if type(it_val) == dictionary and "lista" in it_val and it_val.lista != none {
        render-lista(it_val.lista, indent_left: indent_left + 12pt, text_color: text_color)
      } else if type(it_val) == dictionary and "tracos" in it_val and it_val.tracos != none {
        render-lista(it_val.tracos, indent_left: indent_left + 12pt, text_color: text_color)
      }
    }
  }
}

#let regimento-doc(data) = {
  let titulo = if "titulo" in data { data.titulo } else { "REGIMENTO INTERNO - CONDOMÍNIO TOP LIFE MIAMI BEACH" }
  let capitulos = if "capitulos" in data { data.capitulos } else { (:) }
  let artigos = if "artigos" in data { data.artigos } else { (:) }
  let variant = if "variant" in data { data.variant } else { "modern" }
  let banner_path = if "banner_path" in data and data.banner_path != "" { data.banner_path } else { "LayoutMiami.jpg" }

  // Cores dinâmicas por variante
  let main_color = rgb("0f172a")
  let accent_color = rgb("1e3a8a")
  let bg_cap = rgb("f1f5f9")
  let text_primary = rgb("0f172a")
  let text_secondary = rgb("334155")

  if variant == "corporate" {
    main_color = rgb("1e40af")
    accent_color = rgb("1d4ed8")
    bg_cap = rgb("eff6ff")
  } else if variant == "minimal" {
    main_color = rgb("475569")
    accent_color = rgb("64748b")
    bg_cap = rgb("f8fafc")
  } else if variant == "juridico" {
    main_color = rgb("1e3a8a")
    accent_color = rgb("1e3a8a")
    bg_cap = rgb("f0fdf4")
  } else if variant == "dark" {
    main_color = rgb("0284c7")
    accent_color = rgb("38bdf8")
    bg_cap = rgb("1e293b")
    text_primary = rgb("f8fafc")
    text_secondary = rgb("cbd5e1")
  } else if variant == "editorial" {
    main_color = rgb("065f46")
    accent_color = rgb("047857")
    bg_cap = rgb("ecfdf5")
  }

  set document(title: titulo, author: "Condomínio Residencial Top Life Miami Beach")

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
      if current_page > 1 [
        #grid(
          columns: (1fr, auto),
          align(left)[#text(size: 8pt, fill: if variant == "dark" { rgb("94a3b8") } else { rgb("475569") }, weight: "bold")[REGIMENTO INTERNO - TOP LIFE MIAMI BEACH]],
          align(right)[#text(size: 8pt, fill: if variant == "dark" { rgb("64748b") } else { rgb("64748b") })[Documento Oficial]]
        )
        #line(length: 100%, stroke: 0.5pt + (if variant == "dark" { rgb("334155") } else { rgb("cbd5e1") }))
      ]
    },
    footer: context {
      let page_number = counter(page).get().first()
      let total_pages = counter(page).final().first()
      align(center)[
        #text(size: 8.5pt, fill: if variant == "dark" { rgb("94a3b8") } else { rgb("64748b") })[
          Página #page_number de #total_pages
        ]
      ]
    }
  )

  set text(lang: "pt", size: 10pt, fill: text_primary)
  set par(justify: true, leading: 0.65em)

  // CAPAS DAS VARIANTES
  if variant == "top_header" [
    #align(center)[
      #v(3cm)
      #rect(width: 100%, inset: 20pt, radius: 6pt, fill: rgb("0f172a"))[
        #text(fill: white, weight: "bold", size: 16pt)[#titulo]
      ]
      #v(1.5cm)
      #text(size: 12pt, style: "italic", fill: rgb("334155"))[
        Normas de Funcionamento, Convivência e Administração
      ]
      #v(3cm)
      #text(size: 10pt, fill: rgb("64748b"))[
        Taguatinga / DF • Versão Digital Interativa
      ]
    ]
    #pagebreak()
  ] else if variant == "watermark_a4" [
    #align(center)[
      #v(2cm)
      #rect(width: 100%, inset: 20pt, radius: 6pt, fill: rgb("0f172a"))[
        #text(fill: white, weight: "bold", size: 16pt)[#titulo]
      ]
      #v(1.5cm)
      #text(size: 12pt, style: "italic", fill: rgb("334155"))[
        Normas de Funcionamento, Convivência e Administração
      ]
      #v(3cm)
      #text(size: 10pt, fill: rgb("64748b"))[
        Taguatinga / DF • Versão Digital Interativa
      ]
    ]
    #pagebreak()
  ] else if variant == "modern" [
    #align(center)[
      #v(-1cm)
      #image(banner_path, width: 100%)
      #v(2cm)
      #rect(width: 100%, inset: 20pt, radius: 6pt, fill: rgb("0f172a"))[
        #text(fill: white, weight: "bold", size: 16pt)[#titulo]
      ]
      #v(1.5cm)
      #text(size: 12pt, style: "italic", fill: rgb("334155"))[
        Normas de Funcionamento, Convivência e Administração
      ]
      #v(3cm)
      #text(size: 10pt, fill: rgb("64748b"))[
        Taguatinga / DF • Versão Digital Interativa
      ]
    ]
    #pagebreak()
  ] else if variant == "corporate" [
    #align(center + horizon)[
      #v(-2cm)
      #rect(width: 100%, inset: 24pt, radius: 4pt, fill: rgb("1e40af"))[
        #text(fill: white, weight: "bold", size: 18pt)[#titulo]
      ]
      #v(1cm)
      #line(length: 40%, stroke: 2pt + rgb("1d4ed8"))
      #v(1cm)
      #text(size: 11pt, weight: "medium", fill: rgb("334155"))[
        REGULAMENTO CORPORATIVO E NORMAS CONDOMINIAIS
      ]
      #v(3cm)
      #text(size: 9.5pt, fill: rgb("64748b"))[
        Conselho Consultivo e Fiscal • Taguatinga / DF
      ]
    ]
    #pagebreak()
  ] else if variant == "minimal" [
    #align(center + horizon)[
      #v(-2cm)
      #text(size: 20pt, weight: "light", fill: rgb("334155"))[#titulo]
      #v(1cm)
      #line(length: 20%, stroke: 0.5pt + rgb("94a3b8"))
      #v(1cm)
      #text(size: 10pt, style: "italic", fill: rgb("64748b"))[
        Normas de Convivência
      ]
    ]
    #pagebreak()
  ] else if variant == "juridico" [
    #align(center + horizon)[
      #v(-2cm)
      #rect(width: 100%, inset: 20pt, radius: 0pt, stroke: (rest: 3pt + rgb("1e3a8a")))[
        #text(fill: rgb("1e3a8a"), weight: "bold", size: 16pt)[#titulo]
      ]
      #v(1.5cm)
      #text(size: 11pt, weight: "bold", fill: rgb("1e3a8a"))[
        TÍTULO NORMATIVO E COMPENDIO REGIMENTAL
      ]
      #v(0.5cm)
      #text(size: 10pt, style: "italic", fill: rgb("334155"))[
        Aprovado em Assembleia Geral Extraordinária
      ]
    ]
    #pagebreak()
  ] else if variant == "dark" [
    #align(center + horizon)[
      #v(-2cm)
      #rect(width: 100%, inset: 24pt, radius: 8pt, fill: rgb("1e293b"), stroke: 1pt + rgb("0284c7"))[
        #text(fill: rgb("38bdf8"), weight: "bold", size: 18pt)[#titulo]
      ]
      #v(1.5cm)
      #text(size: 11pt, fill: rgb("94a3b8"))[
        Edição Digital Noturna • Top Life Miami Beach
      ]
    ]
    #pagebreak()
  ] else if variant == "editorial" [
    #align(center)[
      #v(1cm)
      #rect(width: 100%, fill: rgb("065f46"), inset: 15pt)[
        #text(fill: white, weight: "bold", size: 16pt)[BOLETIM REGIMENTAL]
      ]
      #v(1cm)
      #text(size: 15pt, weight: "bold", fill: rgb("065f46"))[#titulo]
      #v(1.5cm)
      #rect(width: 80%, fill: rgb("ecfdf5"), inset: 12pt, radius: 4pt, stroke: 0.5pt + rgb("047857"))[
        #text(size: 10pt, fill: rgb("065f46"))[
          Documento consolidado contendo as seções do Regimento Interno.
        ]
      ]
    ]
    #pagebreak()
  ] else if variant == "classic" [
    #align(center + horizon)[
      #v(-2cm)
      #rect(width: 100%, inset: 24pt, radius: 0pt, stroke: 2pt + rgb("1e3a8a"))[
        #text(fill: rgb("1e3a8a"), weight: "bold", size: 16pt)[#titulo]
      ]
      #v(1.5cm)
      #text(size: 12pt, style: "italic", fill: rgb("334155"))[
        Documento Normativo do Condomínio
      ]
    ]
    #pagebreak()
  ]

  // SUMÁRIO INTERATIVO (COM LINKS CLICÁVEIS PARA AS PÁGINAS)
  if variant != "compact" [
    #heading(level: 1, numbering: none, outlined: false)[Sumário dos Capítulos]
    #v(0.3cm)

    #outline(
      title: none,
      indent: 1.2em,
      target: heading.where(level: 1)
    )

    #pagebreak()
  ]

  // EXIBIÇÃO DO PRÓLOGO / PREÂMBULO (SE HOUVER)
  if "prologo" in data and data.prologo != none and str(data.prologo).trim() != "" [
    #v(0.5cm)
    #rect(width: 100%, fill: bg_cap, inset: 12pt, radius: 4pt, stroke: 0.5pt + accent_color)[
      #text(weight: "bold", fill: main_color, size: 9.5pt)[PREÂMBULO]
      #v(4pt)
      #text(style: "italic", fill: text_secondary, size: 9.5pt)[#data.prologo]
    ]
    #v(0.5cm)
  ]

  // EXIBIÇÃO DOS CAPÍTULOS E ARTIGOS DA CONVENÇÃO
  let current_cap = ""

  for (art_id, art_data) in artigos {
    let cap_num = if type(art_data) == dictionary and "capitulo" in art_data { str(art_data.capitulo) } else { "" }
    if cap_num != "" and cap_num in capitulos and capitulos.at(cap_num) != current_cap {
      current_cap = capitulos.at(cap_num)
      v(0.8cm)
      if variant == "dark" {
        rect(width: 100%, fill: bg_cap, inset: 8pt, radius: 4pt, stroke: 0.5pt + rgb("0284c7"))[
          #heading(level: 1, numbering: none, outlined: true)[
            #text(weight: "bold", fill: rgb("38bdf8"), size: 11.5pt)[#current_cap]
          ]
        ]
      } else {
        rect(width: 100%, fill: bg_cap, inset: 8pt, radius: 3pt, stroke: 0.5pt + main_color)[
          #heading(level: 1, numbering: none, outlined: true)[
            #text(weight: "bold", fill: main_color, size: 11.5pt)[#current_cap]
          ]
        ]
      }
      v(0.3cm)
    }

    let art_texto = if type(art_data) == dictionary and "texto" in art_data { art_data.texto } else { "" }

    block(width: 100%, below: 8pt)[
      #text(weight: "bold", fill: accent_color)[Art. #art_id º] #art_texto

      #if type(art_data) == dictionary and "incisos" in art_data and art_data.incisos != none {
        render-incisos(art_data.incisos, indent_left: 12pt, text_color: text_secondary)
      }

      #if type(art_data) == dictionary and "alineas" in art_data and art_data.alineas != none {
        render-alineas(art_data.alineas, indent_left: 12pt, text_color: text_secondary)
      }

      #if type(art_data) == dictionary and "lista" in art_data and art_data.lista != none {
        render-lista(art_data.lista, indent_left: 12pt, text_color: text_secondary)
      } else if type(art_data) == dictionary and "tracos" in art_data and art_data.tracos != none {
        render-lista(art_data.tracos, indent_left: 12pt, text_color: text_secondary)
      }

      #if type(art_data) == dictionary and "paragrafos" in art_data and art_data.paragrafos != none and type(art_data.paragrafos) == dictionary [
        #for (p_key, p_val) in art_data.paragrafos [
          #let p_txt = if type(p_val) == dictionary and "texto" in p_val { p_val.texto } else if type(p_val) == dictionary { "" } else { str(p_val) }
          #v(3pt)
          #pad(left: 12pt)[
            #text(style: "italic", fill: text_secondary)[
              #if p_key == "unico" [
                #text(weight: "bold")[Parágrafo único.] #p_txt
              ] else [
                #text(weight: "bold")[§ #p_key º] #p_txt
              ]
            ]
          ]
          #if type(p_val) == dictionary and "incisos" in p_val and p_val.incisos != none {
            render-incisos(p_val.incisos, indent_left: 24pt, text_color: text_secondary)
          }
          #if type(p_val) == dictionary and "alineas" in p_val and p_val.alineas != none {
            render-alineas(p_val.alineas, indent_left: 24pt, text_color: text_secondary)
          }
          #if type(p_val) == dictionary and "lista" in p_val and p_val.lista != none {
            render-lista(p_val.lista, indent_left: 24pt, text_color: text_secondary)
          } else if type(p_val) == dictionary and "tracos" in p_val and p_val.tracos != none {
            render-lista(p_val.tracos, indent_left: 24pt, text_color: text_secondary)
          }
        ]
      ]
    ]
  }

  // EXIBIÇÃO DO ANEXO I (REGIMENTO INTERNO VINCULADO À CONVENÇÃO)
  if "anexo1" in data and data.anexo1 != none and type(data.anexo1) == dictionary [
    #v(1cm)
    #pagebreak()
    #let anexo_cabecalho = if "cabecalho" in data.anexo1 { data.anexo1.cabecalho } else { "" }
    #let anexo_titulo = if "titulo" in data.anexo1 { data.anexo1.titulo } else { "ANEXO I - REGIMENTO INTERNO DO CONDOMÍNIO" }
    #rect(width: 100%, fill: bg_cap, inset: 12pt, radius: 4pt, stroke: 1pt + main_color)[
      #align(center)[
        #if anexo_cabecalho != "" [
          #text(weight: "bold", fill: accent_color, size: 9.5pt)[#anexo_cabecalho]
          #v(4pt)
        ]
        #heading(level: 1, numbering: none, outlined: true)[
          #text(weight: "bold", fill: main_color, size: 13pt)[#anexo_titulo]
        ]
      ]
    ]
    #v(0.5cm)

    #let anexo_secoes = if "secoes" in data.anexo1 { data.anexo1.secoes } else { (:) }
    #let anexo_itens = if "itens" in data.anexo1 { data.anexo1.itens } else { (:) }

    #for (sec_id, sec_nome) in anexo_secoes [
      #v(0.6cm)
      #rect(width: 100%, fill: bg_cap, inset: 6pt, radius: 3pt, stroke: 0.5pt + main_color)[
        #heading(level: 2, numbering: none, outlined: true)[
          #text(weight: "bold", fill: main_color, size: 11pt)[#sec_nome]
        ]
      ]
      #v(0.3cm)

      #if sec_id in anexo_itens and type(anexo_itens.at(sec_id)) == dictionary and "itens" in anexo_itens.at(sec_id) [
        #render-anexo1-itens(anexo_itens.at(sec_id).itens, prefix: sec_id, indent_left: 6pt, text_color: text_secondary)
      ]
    ]
  ]
}
