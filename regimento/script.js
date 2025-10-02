document.addEventListener('DOMContentLoaded', () => {

    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const resultsContainer = document.getElementById('resultsContainer');
    const searchModeRadios = document.querySelectorAll('input[name="searchMode"]');

    let database = null;
    let currentSearchMode = 'notation';

    // --- CARREGAMENTO DO BANCO DE DADOS ---
    async function loadDatabase() {
        try {
            const response = await fetch('database.json');
            if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
            database = await response.json();
            console.log("Banco de dados carregado.");
        } catch (error) {
            resultsContainer.innerHTML = `<pre class="error">Falha ao carregar o database.json. Verifique o console.</pre>`;
            console.error(error);
        }
    }

    // --- FUNÇÕES DE BUSCA (sem alteração) ---

    // Busca por Notação (ex: 6.20.a)
    function buscarPorNotacao(dados, notacao) {
        if (!dados || !dados.artigos || !notacao) return { erro: "Dados ou notação inválida." };
        const partes = notacao.split('.');
        const artigoNumero = partes[0];
        let resultado = dados.artigos[artigoNumero];
        if (!resultado) return { erro: `Artigo "${artigoNumero}" não encontrado.` };
        for (let i = 1; i < partes.length; i++) {
            const chave = partes[i];
            let proximoNivelEncontrado = false;
            const ordemDeBusca = ['incisos', 'paragrafos', 'alineas'];
            for (const subnivel of ordemDeBusca) {
                if (resultado[subnivel] && resultado[subnivel][chave]) {
                    resultado = resultado[subnivel][chave];
                    proximoNivelEncontrado = true;
                    break;
                }
            }
            if (!proximoNivelEncontrado) return { erro: `Hierarquia "${notacao}" inválida.` };
        }
        return resultado;
    }

    // Busca por Texto
    function pesquisarPorTexto(dados, termo) {
        const resultados = [];
        const termoBusca = termo.toLowerCase();
        if (termoBusca.length < 3) return { erro: "O termo de busca deve ter pelo menos 3 caracteres." };
        
        function explorar(objeto, caminho, capituloInfo) {
            if (objeto.texto && objeto.texto.toLowerCase().includes(termoBusca)) {
                resultados.push({ notacao: caminho, texto: objeto.texto, capitulo: capituloInfo });
            }
            for (const chave of ['paragrafos', 'incisos', 'alineas']) {
                if (objeto[chave]) {
                    for (const subChave in objeto[chave]) {
                        explorar(objeto[chave][subChave], `${caminho}.${subChave}`, capituloInfo);
                    }
                }
            }
        }

        for (const artigoNum in dados.artigos) {
            const artigo = dados.artigos[artigoNum];
            const capituloInfo = { numero: artigo.capitulo, titulo: dados.capitulos[artigo.capitulo] };
            explorar(artigo, artigoNum, capituloInfo);
        }
        return resultados;
    }

    // --- FUNÇÃO DE EXIBIÇÃO (ATUALIZADA) ---

    function exibirResultados(resultado, termoBusca) {
        resultsContainer.innerHTML = ''; // Limpa resultados anteriores

        if (resultado.erro) {
            resultsContainer.innerHTML = `<pre class="error">${resultado.erro}</pre>`;
            return;
        }

        // Se a busca for por notação, use o novo layout
        if (currentSearchMode === 'notation') {
            const artigoNum = termoBusca.split('.')[0];
            const artigoBase = database.artigos[artigoNum];
            const capituloInfo = {
                numero: artigoBase.capitulo,
                titulo: database.capitulos[artigoBase.capitulo]
            };
            
            // Função recursiva para construir o HTML do conteúdo
            function renderizarConteudo(obj) {
                let html = '';
                if (obj.texto) {
                    html += `<p class="text">${obj.texto}</p>`;
                }

                if (obj.paragrafos || obj.incisos || obj.alineas) {
                    html += '<div class="content-block">';
                    
                    if (obj.paragrafos) {
                        for (const pNum in obj.paragrafos) {
                            const pLabel = pNum === 'unico' ? 'Parágrafo único' : `§ ${pNum}°`;
                            html += `<span class="item-label">${pLabel}:</span>`;
                            html += renderizarConteudo(obj.paragrafos[pNum]);
                        }
                    }
                    if (obj.incisos) {
                         for (const iNum in obj.incisos) {
                             html += `<span class="item-label">Inciso ${iNum}:</span>`;
                             html += renderizarConteudo(obj.incisos[iNum]);
                        }
                    }
                    if (obj.alineas) {
                        for (const aLetra in obj.alineas) {
                            html += `<span class="item-label">Alínea ${aLetra}):</span>`;
                            html += renderizarConteudo(obj.alineas[aLetra]);
                        }
                    }
                    html += '</div>';
                }
                return html;
            }

            const itemDiv = document.createElement('div');
            itemDiv.className = 'result-item';
            itemDiv.innerHTML = `
                <div class="notation">Resultado para: ${termoBusca}</div>
                <span class="chapter">Capítulo ${capituloInfo.numero}: ${capituloInfo.titulo}</span>
                ${renderizarConteudo(resultado)}
            `;
            resultsContainer.appendChild(itemDiv);

        } else { // Lógica para busca por texto (permanece a mesma)
            if (resultado.length === 0) {
                resultsContainer.innerHTML = '<p class="placeholder">Nenhum resultado encontrado.</p>';
                return;
            }
            const regex = new RegExp(termoBusca, 'gi');
            resultado.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'result-item';
                const textoDestacado = item.texto.replace(regex, '<mark>$&</mark>');
                itemDiv.innerHTML = `
                    <div class="notation">Referência: ${item.notacao}</div>
                    <span class="chapter">Capítulo ${item.capitulo.numero}: ${item.capitulo.titulo}</span>
                    <p class="text">${textoDestacado}</p>
                `;
                resultsContainer.appendChild(itemDiv);
            });
        }
    }

    // --- LÓGICA PRINCIPAL E EVENTOS (sem alteração) ---
    function performSearch() {
        if (!database) {
            resultsContainer.innerHTML = `<pre class="error">O banco de dados ainda não foi carregado.</pre>`;
            return;
        }
        const query = searchInput.value.trim();
        if (!query) {
            resultsContainer.innerHTML = '<p class="placeholder">Digite algo para buscar.</p>';
            return;
        }
        let result;
        if (currentSearchMode === 'notation') {
            result = buscarPorNotacao(database, query);
        } else {
            result = pesquisarPorTexto(database, query);
        }
        exibirResultados(result, query);
    }

    searchModeRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            currentSearchMode = e.target.value;
            searchInput.placeholder = currentSearchMode === 'notation' ? "Digite a notação aqui..." : "Digite um termo para pesquisar...";
            searchInput.focus();
        });
    });

    searchButton.addEventListener('click', performSearch);
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') performSearch();
    });

    loadDatabase();
});