# Raciocínio: Ajuste de Usabilidade - Abertura Direta no Navegador e Remoção do Termo Técnico

**Data**: 2026-10-08  
**Contexto**: Refinamento da tela de normativos do portal com base no feedback do usuário.

---

## 1. Diagnóstico do Feedback

O usuário apontou dois pontos fundamentais de usabilidade e experiência do morador:
1. **Linguagem acessível para o usuário final**:
   - A palavra "inline" é um termo técnico de desenvolvimento web que condôminos leigos não compreendem.
   - Deve ser substituída por termos naturais como "Visualizar no Navegador" ou "Abrir Documento".
2. **Compatibilidade móvel (Mobile UX)**:
   - Visualizadores de PDF embutidos (como `<iframe>` ou `<object>`) são notórios por causarem engasgos, barras de rolagem duplicadas, perda de zoom responsivo e falhas de renderização em telas de celulares (Android e iOS).
   - O comportamento desejado é idêntico ao modelo de arquivo exemplificado pelo usuário (`https://arq.vidadesindico.com.br/.../documento.pdf`): uma URL direta para o PDF aberta em nova aba (`target="_blank"`), permitindo que o leitor nativo do dispositivo do usuário (Chrome PDF Viewer, Safari, etc.) assuma a exibição em tela cheia com zoom touch fluido, busca e nitidez, sem exigir download forçado.

---

## 2. Decisões Implementadas

1. **Eliminação do Iframe Embutido**:
   - A página `portal/normativos.php` agora é 100% responsiva, leve e direta. Não carrega iframes pesados.
2. **Remoção de qualquer referência ao termo "inline"**:
   - Textos ajustados tanto em `portal/normativos.php` quanto no card da tela inicial em `portal/index.php`.
3. **Cartões com Destaque de Ação**:
   - Cada cartão apresenta claramente o selo do documento, seu formato, a explicação "Entenda...", e um botão de ação com destaque:
     `Visualizar no Navegador` apontando diretamente para o PDF em nova aba.
4. **Hierarquia Mantida Rigorosamente**:
   - 1º PTD (Política de Tratamento de Dados)
   - 2º Convenção de Condomínio
   - 3º Regimento Interno (RI)
