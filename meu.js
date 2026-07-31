$(document).ready(function () {

    // Auto-login check when remember_device_token is present in browser Local Storage
    if ($('#loginForm').length > 0) {
        const token = localStorage.getItem('remember_device_token');
        if (token) {
            $('body').append('<div id="autoLoginOverlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.9); z-index:9999; display:flex; justify-content:center; align-items:center; flex-direction:column;">' +
                             '<div class="preloader-wrapper big active">' +
                             '<div class="spinner-layer spinner-blue-only">' +
                             '<div class="circle-clipper left"><div class="circle"></div></div>' +
                             '<div class="gap-patch"><div class="circle"></div></div>' +
                             '<div class="circle-clipper right"><div class="circle"></div></div>' +
                             '</div></div>' +
                             '<p style="margin-top:20px; font-weight:bold; color:#1565c0; font-family:sans-serif;">Entrando automaticamente...</p></div>');
            
            $.ajax({
                url: 'metodo.php?metodo=loginComToken',
                method: 'POST',
                data: { token: token },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        localStorage.removeItem('remember_device_token');
                        $('#autoLoginOverlay').remove();
                    }
                },
                error: function() {
                    $('#autoLoginOverlay').remove();
                }
            });
        }
    }

    if (window.screen.orientation.type.includes("portrait")) {
        console.log("Monitor na orientação vertical");
        // Remove a classe "container" e adiciona o atributo "data-custom" ao elemento
        $(".container").removeClass("container").addClass("exContainer");

    } else {
        console.log("Monitor na orientação horizontal");
        $(".exContainer").removeClass("exContainer").addClass("container");
    }

    if (window.innerHeight > window.innerWidth) {
        console.log("Monitor na orientação vertical");
        $(".container").removeClass("container").addClass("exContainer");
    } else {
        console.log("Monitor na orientação horizontal");
        $(".exContainer").removeClass("exContainer").addClass("container");
    }


    $('select').formSelect();
    $('.modal').modal();
    initMaterialboxed();
    $('.chips').chips();
    $('.sidenav').sidenav();

    // Feedback Visual Instantâneo: Top Progress Bar & Full Skeleton Overlay para links do Menu / Sidenav
    $(document).on('click', 'a[href*="livroDeOcorrencias"], .sidenav a, nav a', function (e) {
        const href = $(this).attr('href');
        if (!href || href === '#' || href.startsWith('javascript:')) return;

        // 1. Barra de Progresso Topo (Vercel Style)
        let $topLoader = $('#vds-top-loader');
        if (!$topLoader.length) {
            $('body').append('<div id="vds-top-loader" style="position:fixed; top:0; left:0; height:4px; width:0%; background:linear-gradient(90deg, #0d6efd, #0dcaf0, #6f42c1, #0d6efd); background-size:200% 100%; z-index:999999; box-shadow:0 0 14px rgba(13,110,253,0.9); transition:width 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease; pointer-events:none;"></div>');
            $topLoader = $('#vds-top-loader');
        }
        $topLoader.css({ width: '25%', opacity: 1 });
        setTimeout(() => $topLoader.css('width', '65%'), 150);
        setTimeout(() => $topLoader.css('width', '90%'), 400);

        // 2. Se o link for para Ocorrências VDS, exibe o Esqueleto exclusivamente na área de Conteúdo (<main>)
        if (href.indexOf('livroDeOcorrencias') !== -1) {
            const $main = $('main');
            if ($main.length > 0) {
                $main.css('position', 'relative');
            }

            if ($('#vds-content-skeleton-overlay').length === 0) {
                const skeletonHtml = `
                    <div id="vds-content-skeleton-overlay" style="position:absolute; top:0; left:0; width:100%; min-height:calc(100vh - 70px); background:#f8f9fa; z-index:99; padding:20px; display:flex; flex-direction:column; gap:15px; box-sizing:border-box;">
                        <style>
                            @keyframes vds-shimmer-pulse-g { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
                            .vds-sk-g { background: linear-gradient(90deg, #eef0f3 25%, #dbe0e6 37%, #eef0f3 63%) !important; background-size: 200% 100% !important; animation: vds-shimmer-pulse-g 1.4s ease-in-out infinite !important; border-radius:6px; }
                        </style>
                        <div style="background:#fff; padding:12px 20px; border-radius:8px; border:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center;">
                            <div class="vds-sk-g" style="height:32px; width:240px;"></div>
                            <div style="display:flex; gap:10px;">
                                <div class="vds-sk-g" style="height:32px; width:120px;"></div>
                                <div class="vds-sk-g" style="height:32px; width:140px;"></div>
                            </div>
                        </div>
                        <div style="display:flex; gap:15px; flex:1; min-height:550px;">
                            <div style="width:28%; background:#fff; border-radius:8px; border:1px solid #e0e0e0; padding:15px; display:flex; flex-direction:column; gap:12px;">
                                <div class="vds-sk-g" style="height:36px; width:100%;"></div>
                                <div class="vds-sk-g" style="height:65px; width:100%;"></div>
                                <div class="vds-sk-g" style="height:65px; width:100%;"></div>
                                <div class="vds-sk-g" style="height:65px; width:100%;"></div>
                                <div class="vds-sk-g" style="height:65px; width:100%;"></div>
                                <div class="vds-sk-g" style="height:65px; width:100%;"></div>
                            </div>
                            <div style="flex:1; background:#efeae2; border-radius:8px; border:1px solid #e0e0e0; padding:20px; display:flex; flex-direction:column; gap:16px;">
                                <div class="vds-sk-g" style="height:55px; width:100%;"></div>
                                <div class="vds-sk-g" style="height:110px; width:65%;"></div>
                                <div class="vds-sk-g" style="height:90px; width:58%; margin-left:auto;"></div>
                                <div class="vds-sk-g" style="height:85px; width:52%; margin-left:auto;"></div>
                                <div class="vds-sk-g" style="height:45px; width:100%; margin-top:auto;"></div>
                            </div>
                        </div>
                    </div>
                `;
                if ($main.length > 0) {
                    $main.append(skeletonHtml);
                } else {
                    $('body').append(skeletonHtml);
                }
            } else {
                $('#vds-content-skeleton-overlay').show();
            }
        }
    });
    $('#listaRecursos').DataTable({
        searching: false, // Oculta o campo de busca
        paging: false, // Desativa a paginação
        select: true,
        "order": [
            [2, 'desc'], // Ordenação inicial pela primeira coluna em ordem ascendente
            [3, 'desc']
        ],
        dom: '<"top"fl>rt<"bottom"ip><"clear">',
        language: {
            url: "datatable_br.json"
        }
    });
    $('#listaSolucoes').DataTable({
        searching: true, // Oculta o campo de busca
        paging: false, // Desativa a paginação
        "order": [
            [1, 'desc'],
            [0, 'asc']
        ],
        pageLength: 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tudo"]], // permite listar todos os itens
        dom: '<"top"lf>rt<"bottom"ip><"clear">',
        language: {
            url: "datatable_br.json"
        },
        initComplete: function () {
            // Adiciona estilo para posicionar a caixa de pesquisa à esquerda
            $('.dataTables_filter').css('text-align', 'left');

            // Ajusta a altura das linhas após a inicialização
            $('.dataTables_filter tbody tr').css('height', '5px');
        }
    });

    $('#avatar').on('change', function () {
        var file = $(this)[0].files[0]; // Obtém o arquivo selecionado

        // if (file) {
        // var reader = new FileReader();
        // reader.onload = function(e) {
        // var base64Data = e.target.result.split(',')[1]; // Remove o cabeçalho de data URI
        // $('#updateThisUser').data('avatar', base64Data); // Armazena a base64 nos dados do formulário
        // };
        // reader.readAsDataURL(file); // Lê o arquivo como data URL
        // }
    });

    var $popup = $('#popup');
    var $popupContent = $('#popup-content');

    $('.recurso').on('mouseenter', function () {
        var data = {
            "numero": $(this).data('numero'),
            "status": $(this).data('status'),
            "cobranca": $(this).data('cobranca'),
            "obs": $(this).data('obs'),
            "assunto": $(this).data('assunto'),
            "tipo": $(this).data('tipo'),
            "data_envio": $(this).data('data_envio'),
            "data_email": $(this).data('data_email'),
            "data_ocorrido": $(this).data('data-ocorrido'),
        };

        var content = '<h5>Informações Adicionais</h5>' +
            '<p>Número: ' + data.numero + '</p>' +
            '<p>Tipo: ' + data.tipo + '</p>' +
            '<p>Status: ' + data.status + '</p>' +
            '<p>Cobrança: ' + data.cobranca + '</p>' +
            '<p>Observações: ' + data.obs + '</p>' +
            '<p>Assunto: ' + data.assunto + '</p>' +
            '<p>Data de Email: ' + data.data_email + '</p>' +
            '<p>Data de Envio: ' + data.data_envio + '</p>' +
            '<p>Data de Ocorrência: ' + data.data_ocorrido + '</p>';

        $popupContent.html(content);

        $popup.css({
            display: 'block',
            left: $(this).offset().left + 'px',
            top: ($(this).offset().top - $popup.height() * 1.38) + 'px'
        });

    });

    $('.recurso').on('mouseleave', function () {
        $popup.css('display', 'none');
    });

});

$(document).on('click', '.edit-user', function () {
    const userId = $(this).attr('userid-data');

    // Carrega os dados do usuário via AJAX
    $.ajax({
        url: 'metodo.php?metodo=carregarUsuario&id=' + userId,
        method: "GET",
        success: function (response) {
            try {
                const usuario = JSON.parse(response);
                console.log(usuario);

                // Localiza o formulário de edição e preenche os campos
                const $form = $('#formEditUser');

                $form.find('#edit_id').val(usuario.id);
                $form.find('#edit_nome').val(usuario.nome);
                $form.find('#edit_email').val(usuario.email);
                $form.find('#edit_unidade').val(usuario.unidade);

                // Para o campo de status (select)
                $form.find('#edit_status').val(usuario.status ? '1' : '0');
                $form.find('select').formSelect(); // Atualiza o select do Materialize

                // Limpa o campo de senha (opcional)
                $form.find('#edit_senha').val('');

                // Abre o modal (ajuste conforme sua implementação de modal)
                $('#modalEditarUsuario').modal('open');

            } catch (e) {
                console.error("Erro ao parsear resposta:", e, response);
                M.toast({ html: 'Erro ao carregar usuário', classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus, errorThrown);
            M.toast({ html: 'Erro ao carregar usuário', classes: 'rounded red' });
        }
    });
});

$(document).on('click', '#salvarEdicao', function () {
    const metodo = "editarUsuario";
    const formElement = document.getElementById('formEditUser');
    const formData = new FormData();

    // Adiciona campos manualmente, exceto avatar
    formData.append('id', $('#edit_id').val());
    formData.append('nome', $('#edit_nome').val());
    formData.append('email', $('#edit_email').val());
    formData.append('senha', $('#edit_senha').val());
    formData.append('unidade', $('#edit_unidade').val());
    formData.append('status', $('#edit_status').val());

    // Se um novo arquivo foi selecionado, adiciona ao FormData
    const avatarInput = document.getElementById('edit_avatar'); // Novo campo <input type="file" name="avatar" id="edit_avatar">
    if (avatarInput.files.length > 0) {
        formData.append('avatar', avatarInput.files[0]);
    }

    $.ajax({
        url: 'metodo.php?metodo=' + metodo,
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                M.toast({ html: 'Alterações salvas com sucesso!', classes: 'rounded green' });
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                M.toast({ html: response.error || 'Erro ao salvar alterações', classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            M.toast({ html: 'Erro ao salvar alterações', classes: 'rounded red' });
        }
    });
});



$(document).on('click', '#novoUsuario', function () { // Inserir novo Usuário
    let metodo = "novoUsuario";
    const formData = $("#formNewUser").serializeArray();
    console.log(formData);

    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
        data: formData, // Adicione o objeto 'data' aqui
        success: function (responseData) {
            M.toast({ html: responseData, classes: 'rounded' });
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '#newRecurso', function () { // Inserir novo Usuário
    let metodo = "novoRecurso";
    const formData = $("#formNewRecurso").serializeArray();
    console.log(formData);

    // Verificar se os campos obrigatórios estão preenchidos
    let camposObrigatorios = ["unidade", "bloco", "numero", "fase", "data"]; // Adicione aqui os nomes dos campos obrigatórios

    let camposVazios = camposObrigatorios.filter(function (campo) {
        return formData.find(function (item) {
            return item.name === campo && item.value === "";
        });
    });

    if (camposVazios.length > 0) {
        // Exibir um toast informando que os campos obrigatórios não foram preenchidos
        M.toast({ html: 'Por favor, preencha todos os campos obrigatórios.', classes: 'rounded red' });
        return; // Impedir o envio da solicitação AJAX
    }

    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
        data: formData, // Adicione o objeto 'data' aqui
        success: function (responseData) {
            M.toast({ html: responseData, classes: 'rounded' });
            window.location.href = "index.php";
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '#testeEnvioParecer', function () {
    // Adiciona um prompt de confirmação
    const userConfirmation = window.confirm("Nesse teste, o e-mail será enviado para o endereço do usuário logado. Você deseja continuar?");

    // Verifica se o usuário confirmou
    if (userConfirmation) {
        const mimeContent = $("#mime").text();

        let url = 'gmail/sendMailParecer.php';
        $.ajax({
            url: url,
            method: "POST",
            data: {
                mime: mimeContent
            },
            success: function (responseData) {
                M.toast({ html: responseData, classes: 'rounded' });
                $("#mime").text(responseData);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log("Erro na solicitação AJAX: " + textStatus);
                console.log("Detalhes do erro: " + errorThrown);
            }
        });
    } else {
        // Se o usuário não confirmou, você pode fazer alguma coisa aqui, ou apenas retornar
        console.log("Operação cancelada pelo usuário.");
    }
});

$(document).on('click', '#EnviaRelatorioJuridico', function () {
    // Adiciona um prompt de confirmação
    const userConfirmation = window.confirm("Será enviado relatório de notificações com o Conselho. Você deseja continuar?");

    // Verifica se o usuário confirmou
    if (userConfirmation) {
        const mimeContent = $("#mime").html();

        let url = 'gmail/sendMailParecer.php';
        $.ajax({
            url: url,
            method: "POST",
            data: {
                mime: mimeContent
            },
            success: function (responseData) {
                M.toast({ html: responseData, classes: 'rounded' });
                $("#mime").text(responseData);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log("Erro na solicitação AJAX: " + textStatus);
                console.log("Detalhes do erro: " + errorThrown);
            }
        });
    } else {
        // Se o usuário não confirmou, você pode fazer alguma coisa aqui, ou apenas retornar
        console.log("Operação cancelada pelo usuário.");
    }
});

$(document).on('click', '#finalizaEnviaParecer', function () {
    // Adiciona um prompt de confirmação
    const userConfirmation = window.confirm("Será enviado o parecer abaixo por email para o endereço cadastrado no recurso, com cópia para o síndico e cópia oculta para a soluções. Depois disso, o status será ajustado para finalizado, não será mais possível editar este parecer. A fase do recurso também será alterada para 'Concluido'. Você deseja continuar?");

    // Verifica se o usuário confirmou
    if (userConfirmation) {
        const mimeContent = $("#mime").text();
        const idParecer = $(this).attr("idparecer");

        let url = 'gmail/sendMailParecer.php';
        $.ajax({
            url: url,
            method: "POST",
            data: {
                mime: mimeContent
            },
            success: function (responseData) {
                // console.log(responseData);
                try {
                    const responseJson = JSON.parse(responseData);
                    console.log(responseJson);

                    // Verifica se a resposta possui um 'mailId'
                    if (responseJson.id) {
                        // Se 'mailId' existe, realizar a segunda chamada
                        const mailId = responseJson.id;

                        $.ajax({
                            url: 'metodo.php?metodo=finalizaParecer',
                            method: 'POST',
                            data: {
                                id_parecer: idParecer,
                                mailId: mailId
                            },
                            dataType: 'json',
                            success: function (finalizaResponse) {
                                // Verifica se a resposta da segunda chamada é bem sucedida
                                if (finalizaResponse && finalizaResponse.success) {
                                    // Se sucesso, atualiza a página
                                    location.reload();
                                } else {
                                    // Exibe um toast informando que algo deu errado
                                    M.toast({ html: 'Erro ao finalizar o parecer.', classes: 'rounded red' });
                                }
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                console.log("Erro na segunda solicitação AJAX: " + textStatus);
                                console.log("Detalhes do erro: " + errorThrown);
                                // Exibe um toast informando que algo deu errado
                                M.toast({ html: 'Erro ao finalizar o parecer.', classes: 'rounded red' });
                            }
                        });
                    } else {
                        // Se 'mailId' não existe, exibe um toast informando que algo deu errado
                        M.toast({ html: 'Erro ao enviar o e-mail. Resposta inválida.', classes: 'rounded' });
                    }
                } catch (error) {
                    // Exibe um toast informando que algo deu errado ao analisar a resposta JSON
                    M.toast({ html: 'Erro ao analisar a resposta JSON.', classes: 'rounded' });
                    console.log("Erro ao analisar a resposta JSON: " + error);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log("Erro na primeira solicitação AJAX: " + textStatus);
                console.log("Detalhes do erro: " + errorThrown);
                // Exibe um toast informando que algo deu errado
                M.toast({ html: 'Erro ao enviar o e-mail.', classes: 'rounded' });
            }
        });
    } else {
        // Se o usuário não confirmou, você pode fazer alguma coisa aqui, ou apenas retornar
        console.log("Operação cancelada pelo usuário.");
    }
});

$(document).on('click', '#btnAlterarParecer', function () { // Enviar e-mail
    $("#previaPDF").hide();
    $("#formParecer").removeClass("hide");
    $(this).remove();
    $("#testeEnvioParecer").remove();
    $("#finalizaEnviaParecer").remove();

});

$(document).on('click', '#btnSalvarParecer', function () { // Enviar e-mail
    let formData = $("#formParecer form").serializeArray();
    console.log(formData);

    $.ajax({
        url: "metodo.php?metodo=editaParecer",
        method: "POST", // Defina o método como POST
        data: formData,
        dataType: 'json',
        success: function (responseData) {
            if (responseData.success) {
                M.toast({ html: 'Parecer salvo com sucesso!', classes: 'rounded green' });
                window.location.reload();
            } else {
                M.toast({ html: responseData.error || 'Erro ao salvar o parecer.', classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });

});

$(document).on('click', '#btnAjudaIA', function () {
    const btn = $(this);
    const rec = btn.attr('data-rec');

    if (!rec) {
        M.toast({ html: 'Erro: Código do recurso não encontrado no botão.', classes: 'rounded red' });
        return;
    }

    // Colocar botão em estado de carregamento
    btn.addClass('disabled').html('<i class="material-icons left">hourglass_empty</i>Gerando...');
    M.toast({ html: 'A IA está analisando o caso e redigindo as sugestões...', classes: 'rounded blue', displayLength: 4000 });

    $.ajax({
        url: 'metodo.php?metodo=sugerirParecerIA',
        method: 'POST',
        data: { rec: rec },
        dataType: 'json',
        success: function (response) {
            if (response.success && response.suggestions) {
                const sug = response.suggestions;
                
                // Preenche os campos do formulário
                if (sug.assunto) $('#assunto').val(sug.assunto);
                if (sug.notificacao) $('#notificacao').val(sug.notificacao);
                if (sug.analise) $('#analise').val(sug.analise);
                if (sug.resultado) $('#resultado').val(sug.resultado);
                if (sug.conclusao) $('#conclusao').val(sug.conclusao);

                // Atualizar labels do Materialize e redimensionar textareas
                M.updateTextFields();
                $('#formParecer textarea').each(function () {
                    M.textareaAutoResize($(this));
                });

                M.toast({ html: 'Sugestão da IA aplicada com sucesso!', classes: 'rounded green' });
            } else {
                M.toast({ html: 'Erro da IA: ' + (response.error || 'Erro desconhecido'), classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Erro na solicitação AJAX da IA:', textStatus, errorThrown);
            M.toast({ html: 'Erro de comunicação com o servidor.', classes: 'rounded red' });
        },
        complete: function () {
            // Restaurar estado original do botão
            btn.removeClass('disabled').html('<i class="material-icons left">psychology</i>Ajuda da IA');
        }
    });
});

$(document).on('click', '.editComment', function () { // Enviar e-mail


    function ajustarAlturaTextarea() {
        let textarea = $("#messageTextComment")[0];
        textarea.style.height = "auto"; // Redefinir a altura para auto
        textarea.style.height = textarea.scrollHeight + "px"; // Definir a altura com base no conteúdo
    }

    let comentario = $(this).closest(".comment-card, li.collection-item").find(".mensagem-texto, p").html();
    comentario = comentario ? comentario.trim().replace(/<br\s*\/?>/gi, "\n") : "";
    console.log(comentario);
    $("#messageTextComment").val(comentario);

    ajustarAlturaTextarea();

    $("#messageTextComment").attr("message_id", $(this).attr("comment"));


});

$(document).on('click', '#updateComment', function () { // Enviar e-mail
    let comentario = $("#messageTextComment").val();
    let id_comentario = $("#messageTextComment").attr("message_id");

    const formData = {
        id_comentario: id_comentario,
        comentario: comentario
    };
    console.log(formData);
    $.ajax({
        url: "metodo.php?metodo=editaComentario",
        method: "POST", // Defina o método como POST
        data: formData,
        dataType: 'json',
        success: function (responseData) {
            if (responseData.success) {
                M.toast({ html: 'Comentário atualizado!', classes: 'rounded green' });
                window.location.reload();
            } else {
                M.toast({ html: responseData.error || 'Erro ao editar comentário.', classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });


});

$(document).on('click', '#logon', function () { // Logar Usuario
    let metodo = "logon";
    const formData = $("#loginForm").serializeArray();
    console.log(formData);

    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
        data: formData, // Adicione o objeto 'data' aqui
        success: function (responseData) {
            const cleanResponse = responseData.trim();
            if (cleanResponse.startsWith("ok")) {
                const parts = cleanResponse.split('|');
                if (parts.length > 1) {
                    localStorage.setItem('remember_device_token', parts[1].trim());
                } else {
                    localStorage.removeItem('remember_device_token');
                }
                M.toast({ html: 'Login realizado!', classes: 'rounded green' });
                window.location.reload();
            } else {
                M.toast({ html: responseData, classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '#logout', function () { // DesLogar Usuario
    let metodo = "logout";
    const token = localStorage.getItem('remember_device_token') || '';

    // Realizar a solicitação GET para obter os dados desejados com o token
    let url = 'metodo.php?metodo=' + metodo + '&token=' + encodeURIComponent(token);
    $.ajax({
        url: url,
        method: "GET",
        success: function (responseData) {
            localStorage.removeItem('remember_device_token');
            window.location.reload();
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '#comentar', function () { // Inserir mensagem no Recurso
    let metodo = "novoComentario";
    let idRec = $('#idRecurso').attr('idRec');
    const formElement = document.getElementById('postMessageForm');
    const formData = new FormData(formElement);
    formData.append('id_recurso', idRec);

    $.ajax({
        url: 'metodo.php?metodo=' + metodo,
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (responseData) {
            if (responseData.trim() === "ok") {
                M.toast({ html: "Comentário enviado!", classes: 'rounded green' });
                window.location.reload();
            } else {
                M.toast({ html: responseData, classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            M.toast({ html: 'Erro na solicitação', classes: 'rounded red' });
        }
    });
});

$(document).on('click', '.editComment', function () { 
    let id = $(this).attr("comment");
    let comentario = $(this).closest(".comment-card, li.collection-item").find(".mensagem-texto, p").html();
    comentario = comentario ? comentario.trim().replace(/<br\s*\/?>/gi, "\n") : "";
    
    $("#messageTextComment").val(comentario);
    $("#editMessageId").val(id);
    
    // Buscar anexos existentes
    $("#existingAttachmentsComment").html('<p class="center">Carregando anexos...</p>');
    $.ajax({
        url: "metodo.php?metodo=getComentarioAnexos",
        method: "POST",
        data: { id_mensagem: id },
        success: function (res) {
            const anexos = JSON.parse(res);
            let html = "";
            if (anexos.length > 0) {
                anexos.forEach(ax => {
                    html += `
                    <div class="chip" id="anexo_msg_${ax.id}">
                        <i class="material-icons left tiny">attach_file</i>
                        ${ax.nome_arquivo}
                        <i class="close_anexo_mensagem material-icons right" style="cursor:pointer; font-size: 1.2rem; margin-left:8px" idanexo="${ax.id}">close</i>
                    </div>`;
                });
            } else {
                html = "<p class='grey-text' style='padding-left:10px; font-size:0.8rem'>Nenhum anexo prévio.</p>";
            }
            $("#existingAttachmentsComment").html(html);
        }
    });

    // Limpar o arquivo anterior do campo caso exista
    $("#editMessageForm").find(".file-path").val("");
    $("#editMessageForm").find("input[type=file]").val("");
});

$(document).on('click', '.close_anexo_mensagem', function () {
    const idAnexo = $(this).attr('idanexo');
    if (!confirm("Deseja realmente excluir permanentemente este anexo?")) return;

    $.ajax({
        url: "metodo.php?metodo=deleteAnexoComentario",
        method: "POST",
        data: { id_anexo: idAnexo },
        success: function (res) {
            if (res.trim() === "ok") {
                $(`#anexo_msg_${idAnexo}`).fadeOut();
                M.toast({ html: "Anexo excluído!", classes: "rounded orange" });
            } else {
                M.toast({ html: res, classes: "rounded red" });
            }
        }
    });
});

$(document).on('click', '#updateComment', function () { 
    const formElement = document.getElementById('editMessageForm');
    const formData = new FormData(formElement);
    
    // Forçar os campos caso o FormData automático tenha falhado
    const idValue = $("#editMessageId").val();
    const textValue = $("#messageTextComment").val();
    
    formData.set('id_mensagem', idValue);
    formData.set('messageText', textValue);
    
    if (!idValue || !textValue) {
        return M.toast({ html: 'Erro: ID ou conteúdo do comentário ausente!', classes: 'rounded red' });
    }

    console.log("Enviando Comentário Editado (Final):", {
        method: "editaComentario",
        id: formData.get('id_mensagem'),
        texto: formData.get('messageText'),
        numFiles: formElement.querySelector('input[type=file]').files.length
    });
    
    $.ajax({
        url: 'metodo.php?metodo=editaComentario',
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (responseData) {
            if (responseData.trim() === "ok") {
                M.toast({ html: "Comentário atualizado!", classes: 'rounded green' });
                window.location.reload();
            } else {
                // Se for um erro de SQL do die(), vai cair aqui
                console.error("Erro do Servidor:", responseData);
                M.toast({ html: 'Erro ao salvar. Verifique o console.', classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            M.toast({ html: 'Erro na solicitação', classes: 'rounded red' });
        }
    });
});

$(document).on('click', '#diligenciar', function () { // Inserir diligencia no Recurso
    let metodo = "novaDiligencia";
    let idRec = $('#idRecurso').attr('idRec');
    
    // Usar FormData para suportar upload de arquivos
    const formElement = document.getElementById('postDiligenciaForm');
    const formData = new FormData(formElement);
    formData.append('id_recurso', idRec);

    // Realizar a solicitação POST
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (responseData) {
            if (responseData.trim() === "ok") {
                M.toast({ html: "Diligência salva com sucesso!", classes: 'rounded green' });
                setTimeout(() => window.location.reload(), 1000);
            } else {
                M.toast({ html: responseData, classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            M.toast({ html: 'Erro ao salvar diligência', classes: 'rounded red' });
        }
    });
});

$(document).on('click', '.editDiligence', function () {
    let id_dil = $(this).attr("comment");
    let comentario = $(this).closest(".diligence-card, li.collection-item").find(".mensagem-texto, p").html();
    comentario = comentario ? comentario.trim().replace(/<br\s*\/?>/gi, "\n") : "";
    $("#messageTextDiligencia").val(comentario);
    $("#editDiligenciaId").val(id_dil);
    
    // Buscar anexos existentes
    $("#existingAttachmentsDiligence").html('<p class="center">Carregando anexos...</p>');
    $.ajax({
        url: "metodo.php?metodo=getDiligenciaAnexos",
        method: "POST",
        data: { id_diligencia: id_dil },
        success: function (response) {
            const anexos = JSON.parse(response);
            let html = "";
            if (anexos.length > 0) {
                anexos.forEach(ax => {
                    html += `
                    <div class="chip" id="anexo_dil_${ax.id}">
                        <i class="material-icons left tiny">attach_file</i>
                        ${ax.nome_arquivo}
                        <i class="close_anexo_diligencia material-icons right" style="cursor:pointer; font-size: 1.2rem; margin-left:8px" idanexo="${ax.id}">close</i>
                    </div>`;
                });
            } else {
                html = "<p class='grey-text' style='padding-left:10px; font-size:0.8rem'>Nenhum anexo prévio.</p>";
            }
            $("#existingAttachmentsDiligence").html(html);
        }
    });

    // Limpar o arquivo anterior do campo caso exista
    $("#editDiligenciaForm").find(".file-path").val("");
    $("#editDiligenciaForm").find("input[type=file]").val("");
});

$(document).on('click', '.close_anexo_diligencia', function () {
    const idAnexo = $(this).attr('idanexo');
    if (!confirm("Deseja realmente excluir permanentemente este anexo?")) return;

    $.ajax({
        url: "metodo.php?metodo=deleteAnexoDiligencia",
        method: "POST",
        data: { id_anexo: idAnexo },
        success: function (res) {
            if (res.trim() === "ok") {
                $(`#anexo_dil_${idAnexo}`).fadeOut();
                M.toast({ html: "Anexo excluído!", classes: "rounded orange" });
            } else {
                M.toast({ html: res, classes: "rounded red" });
            }
        }
    });
});

$(document).on('click', '#updateDiligence', function () {
    let metodo = "editaDiligencia";
    
    const formElement = document.getElementById('editDiligenciaForm');
    const formData = new FormData(formElement);

    $.ajax({
        url: "metodo.php?metodo=" + metodo,
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (responseData) {
            if (responseData.trim() === "ok") {
                M.toast({ html: "Diligência atualizada!", classes: 'rounded green' });
                setTimeout(() => window.location.reload(), 1000);
            } else {
                M.toast({ html: responseData, classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            M.toast({ html: 'Erro na solicitação', classes: 'rounded red' });
        }
    });
});

// Suporte para Ctrl + V (Paste) de imagens nos campos de texto
$(document).on('paste', '#diligenciaText, #messageTextDiligencia, #messageText, #messageTextComment', function (e) {
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    const textarea = $(this);
    const form = textarea.closest('form');
    const fileInput = form.find('input[type=file]')[0];

    for (const item of items) {
        if (item.type.indexOf("image") !== -1) {
            const blob = item.getAsFile();
            const timestamp = new Date().getTime();
            const fileName = `pasted_image_${timestamp}.png`;
            const file = new File([blob], fileName, { type: "image/png" });

            // Criar preview visual
            const reader = new FileReader();
            reader.onload = function(event) {
                const previewMap = {
                    'diligenciaText': '#pastePreviewDiligence',
                    'messageTextDiligencia': '#pastePreviewDiligenceEdit',
                    'messageText': '#pastePreviewComment',
                    'messageTextComment': '#pastePreviewCommentEdit'
                };
                const previewSelector = previewMap[textarea.attr('id')];
                if (previewSelector) {
                    $(previewSelector).append(`
                        <div class="col s3 m2" style="position:relative; margin-top:10px">
                            <img src="${event.target.result}" class="responsive-img z-depth-1 materialboxed">
                            <i class="material-icons tiny red-text" style="position:absolute; top:0; right:5px; cursor:pointer; background:white; border-radius:50%" onclick="$(this).parent().remove()">cancel</i>
                        </div>`);
                    initMaterialboxed();
                }
            };
            reader.readAsDataURL(blob);

            // Criar um DataTransfer para injetar o arquivo no input file real
            const dataTransfer = new DataTransfer();
            if (fileInput.files.length > 0) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    dataTransfer.items.add(fileInput.files[i]);
                }
            }
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;

            $(fileInput).trigger('change');
            M.toast({ html: "Imagem colada!", classes: 'rounded blue' });
        }
    }
});

// Limpar previews ao fechar/abrir modais
$(document).on('click', '.modal-trigger', function() {
    $('#pastePreviewDiligence, #pastePreviewDiligenceEdit, #pastePreviewComment, #pastePreviewCommentEdit').empty();
});

$(document).on('click', '.notificarRequerente', function () {
    const id = $(this).attr('comment');
    
    M.toast({ html: 'Gerando prévia...', classes: 'rounded blue' });

    $.ajax({
        url: "metodo.php?metodo=previaEmailDiligencia",
        method: "POST",
        data: { id_diligencia: id },
        success: function (res) {
            const data = JSON.parse(res);
            if (data.success) {
                $("#previewEmailTo").text(data.to);
                $("#previewEmailCc").text(data.cc.join(", "));
                $("#previewEmailSubject").text(data.subject);
                $("#previewEmailBody").html(data.body);
                $("#btnConfirmSendDiligencia").attr("id_dil", id);
                
                const inst = M.Modal.getInstance(document.querySelector('#modalPreviewEmailDiligencia'));
                inst.open();
            } else {
                M.toast({ html: data.error || 'Erro ao gerar prévia', classes: 'rounded red' });
            }
        }
    });
});

$(document).on('click', '#btnConfirmSendDiligencia', function () {
    const id = $(this).attr('id_dil');
    const btn = $(this);
    
    btn.addClass('disabled').text('Enviando...');
    
    $.ajax({
        url: "metodo.php?metodo=notificarRequerente",
        method: "POST",
        data: { id_diligencia: id },
        success: function (responseData) {
            if (responseData.trim() === "ok") {
                M.toast({ html: "E-mail enviado com sucesso!", classes: 'rounded green' });
                setTimeout(() => window.location.reload(), 1500);
            } else {
                M.toast({ html: responseData, classes: 'rounded red' });
                btn.removeClass('disabled').text('Confirmar e Enviar');
            }
        },
        error: function () {
            M.toast({ html: 'Erro de conexão', classes: 'rounded red' });
            btn.removeClass('disabled').text('Confirmar e Enviar');
        }
    });
});

$(document).on('click', '#btnBuscaOcorrencia', function () {
    const termo = $('#buscaOcorrenciaInput').val();
    if (!termo) return M.toast({ html: "Digite um termo de busca", classes: 'rounded' });

    $.ajax({
        url: "metodo.php?metodo=buscarOcorrencia",
        method: "POST",
        data: { termo: termo },
        success: function (response) {
            const data = JSON.parse(response);
            let html = "";
            if (data.length > 0) {
                data.forEach(oc => {
                    html += `<a href="#!" class="collection-item vincularEstaOcorrencia" idOc="${oc.id}">
                                <b>ID ${oc.id}</b> - ${oc.bloco}/${oc.unidade} - ${oc.status}
                                <br><small>${oc.abertura}</small>
                             </a>`;
                });
            } else {
                html = "<p class='p-10 grey-text'>Nenhuma ocorrência encontrada.</p>";
            }
            $('#resultadoBuscaOcorrencia').html(html);
        }
    });
});

$(document).on('click', '.vincularEstaOcorrencia', function () {
    const idOc = $(this).attr('idOc');
    const idRec = $('#idRecurso').attr('idRec');

    $.ajax({
        url: "metodo.php?metodo=vincularOcorrencia",
        method: "POST",
        data: { id_recurso: idRec, id_ocorrencia: idOc },
        success: function (response) {
            if (response.trim() === "ok") {
                M.toast({ html: "Vínculo realizado!", classes: 'rounded green' });
                setTimeout(() => window.location.reload(), 1000);
            } else {
                M.toast({ html: response, classes: 'rounded red' });
            }
        }
    });
});

$(document).on('click', '.opVoto', function () { 
    let metodo = "votar";
    let idRec = $('#idRecurso').attr('idRec');
    let voto = $(this).attr('voto');
    const formData = {
        voto: voto,
        idRec: idRec
    };
    console.log(formData);

    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
        data: formData,
        dataType: 'json',
        success: function (responseData) {
            if (responseData.success) {
                M.toast({ html: 'Voto registrado!', classes: 'rounded green' });
                window.location.reload();
            } else {
                M.toast({ html: responseData.error || 'Erro ao registrar voto.', classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '.recFase', function () { // Altera a fase do recurso
    let metodo = "mudaFase";
    let idRec = $('#idRecurso').attr('idRec');
    let fase = $(this).attr('fase');
    const formData = {
        fase: fase,
        idRec: idRec
    };
    console.log(formData);

    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
        data: formData,
        dataType: 'json',
        success: function (responseData) {
            if (responseData.success) {
                M.toast({ html: 'Fase alterada!', classes: 'rounded green' });
                window.location.reload();
            } else {
                M.toast({ html: responseData.error || 'Erro ao alterar fase.', classes: 'rounded red' });
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('dblclick', '.recurso', function () { // Inserir novo Usuário
    let metodo = "recurso";
    let recurso = $(this).attr("rec");

    // Realizar a solicitação GET para obter os dados desejados
    let url = 'index.php?pag=' + metodo + '&rec=' + recurso;
    console.log(url);

    window.location.href = url;

});

$(document).on('click', '.recurso', function () { // Inserir novo Usuário
    M.toast({ html: "Clique duplo para entrar ", classes: 'rounded' });

});

$(document).on('submit', '#changePasswordForm', function (event) {
    event.preventDefault();

    var currentPassword = $('#currentPassword').val();
    var newPassword = $('#newPassword').val();
    var confirmPassword = $('#confirmPassword').val();

    if (newPassword != confirmPassword) {
        M.toast({ html: "Nova senha e confirmação não são iguais ", classes: 'rounded' });
        return;
    }

    // Exemplo de chamada AJAX para enviar os dados ao servidor
    $.post('metodo.php?metodo=trocaSenha', {
        currentPassword: currentPassword,
        newPassword: newPassword
    }, function (response) {
        if (response.success) {
            M.toast({ html: 'Senha alterada com sucesso!', classes: 'rounded green' });
        } else {
            M.toast({ html: response.error || 'Erro ao alterar senha.', classes: 'rounded red' });
        }
    }, 'json');
});

$(document).on('submit', '#updateThisUser', function (e) {
    e.preventDefault(); // Impede o envio padrão do formulário

    // Obtém os dados do formulário
    var formData = new FormData(this);

    // Obtém a base64 do avatar dos dados do formulário
    // var avatarBase64 = $(this).data('avatar');
    // if (avatarBase64) {
    // formData.append('avatarBase64', avatarBase64); // Adiciona a base64 aos dados do formulário
    // }

    $.ajax({
        type: 'POST',
        url: 'metodo.php?metodo=updateThisUser',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                M.toast({ html: 'Perfil atualizado!', classes: 'rounded green' });
                window.location.reload();
            } else {
                M.toast({ html: response.error || 'Erro ao atualizar perfil.', classes: 'rounded red' });
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
});

$(document).on('keyup', '#unidade', function (event) {

    var unidadeValue = $(this).val().toUpperCase();
    var letra = unidadeValue.match(/[A-F]/);
    if (letra) {
        letra = letra[0]; // Pega a primeira letra encontrada
        console.log("Letra digitada: " + letra);
        $("#bloco").val(letra);
        M.FormSelect.init(document.querySelector("#bloco"));
    }
    unidadeValue = unidadeValue.replace(/[^0-9]/g, '');
    $(this).val(unidadeValue);
});

$(document).on('keyup', '#numero', function (event) {
    var entrada = $(this).val();
    var formData = new FormData();

    // Use uma expressão regular para extrair o número e o ano
    var matches = entrada.match(/^(\d{1,4})\/(\d{4})$/);

    if (matches) {
        var numero = matches[1];
        var ano = matches[2];

        // Adicione os valores extraídos ao objeto FormData
        formData.append('numero', numero);
        formData.append('ano', ano);

        console.log("Número: " + numero);
        console.log("Ano: " + ano);

        $.ajax({
            method: "POST",
            url: 'metodo.php?metodo=buscaHistorico',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json', // Defina o tipo de dados como JSON
            success: function (response) {
                if (response.data.notificacoes && response.data.notificacoes.length > 0) {
                    console.log("Encontrada Notificação");
                    ajustaValores(response.data.notificacoes[0]); // Passa a primeira notificação
                } else {
                    console.log("Nenhuma notificação encontrada");
                }

                // Verifica se há recursos e exibe alerta
                if (response.data.recursos && response.data.recursos.length > 0) {
                    // Filtra os recursos que têm número não nulo
                    const recursosValidos = response.data.recursos.filter(recurso =>
                        recurso.numero !== null && recurso.numero !== undefined && recurso.numero !== ''
                    );

                    if (recursosValidos.length > 0) {
                        var qtdRecursos = recursosValidos.length;
                        var numero = $('#numero').val();
                        alert("Atenção! Recurso já cadastrado.");
                        console.log("Recursos válidos encontrados:", recursosValidos);
                        window.location.href = 'index.php?pag=recurso&rec=' + encodeURIComponent(numero);
                    }
                }
            },
            error: function (xhr, status, error) {
                // Lida com erros
                console.error(error);
            }
        });
    }
});

$(document).on('keyup', '.fato', function (event) {
    var entrada = $(this).val();

    // Remover quebras de linha e manter apenas um espaço entre palavras
    entrada = entrada.replace(/[\r\n]+/g, ' ').replace(/\s+/g, ' ');

    // Agora, você pode usar a variável 'entrada' conforme necessário
    $(this).val(entrada);
});


$(document).on('submit', '#atualizarRecursoForm', function (e) {
    e.preventDefault(); // Impede o envio padrão do formulário

    // Obtém os dados do formulário

    var formData = new FormData(this);
    var numeroValue = formData.get('numero');
    $.ajax({
        type: 'POST',
        url: 'metodo.php?metodo=atualizarRecurso',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                M.toast({ html: 'Recurso atualizado!', classes: 'rounded green' });
                window.location.href = "index.php?pag=recurso&rec=" + numeroValue;
            } else {
                M.toast({ html: response.error || 'Erro ao atualizar recurso.', classes: 'rounded red' });
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
});

$(document).on('dblclick', '.edit-retirado', function (e) {
    var id = $(this).data('id');
    var originalValue = $(this).text();
    var formattedDate = originalValue.split('/').reverse().join('-');

    // Substituir a célula pelo input
    $(this).html('<input type="date" class="edit-retirado-input" value="' + formattedDate + '">');
    $('.edit-retirado-input').focus();

    // Adicionar evento para tratar Enter e perda de foco
    $('.edit-retirado-input').on('blur keypress', function (e) {
        if (e.type === 'blur' || (e.type === 'keypress' && e.which === 13)) {
            var newValue = $(this).val();

            // Enviar dados para o servidor usando AJAX
            $.ajax({
                url: 'metodo.php?metodo=atualizaDataRetiradaNotificacao',
                method: 'POST',
                data: { virtual: id, dia_retirada: newValue },
                success: function (response) {
                    M.toast({ html: response, classes: 'rounded' });
                    // Atualizar a célula com o novo valor se a atualização for bem-sucedida
                    if (response === 'success') {
                        $('.edit-retirado[data-id="' + id + '"]').text(newValue);
                    } else {
                        // Lidar com erros, se necessário
                        console.log('Erro ao atualizar');
                    }
                },
                error: function () {
                    console.log('Erro de requisição AJAX');
                }
            });
        }
    });
});

$(document).on('dblclick', '.edit-multa-cobrada', function (e) {
    var row = $(this).closest('tr');
    var id = row.data('id');
    var numero = row.find('td:eq(0)').text();
    var ano = row.find('td:eq(1)').text();
    var unidade = row.find('td:eq(2)').text();
    var bloco = row.find('td:eq(3)').text();

    // Preencher o modal com os dados existentes
    $('#modal-multa-numero').text(numero + '/' + ano);
    $('#modal-multa-unidade').text(unidade);
    $('#modal-multa-bloco').text(bloco);
    $('#modal-multa-id').val(id);

    // Se já existir dados de multa, preencher os campos
    var valorAtual = row.find('td:eq(11)').text();
    var dataVencAtual = row.find('td:eq(12)').text();
    var dataPagAtual = row.find('td:eq(13)').text();

    if (valorAtual !== '-' && valorAtual !== '') {
        $('#valor-multa').val(valorAtual.replace('R$ ', '').replace('.', '').replace(',', '.'));
    }

    if (dataVencAtual !== '-' && dataVencAtual !== '') {
        $('#data-vencimento').val(dataVencAtual.split('/').reverse().join('-'));
    }

    if (dataPagAtual !== '-' && dataPagAtual !== '') {
        $('#data-pagamento').val(dataPagAtual.split('/').reverse().join('-'));
    }

    // Abrir o modal
    $('#modal-multa').modal('open');
});


// Função para salvar os dados da multa
$(document).on('click', '#salvar-multa', function (e) {
    var id = $('#modal-multa-id').val();
    var valor = $('#valor-multa').val();
    var dataVencimento = $('#data-vencimento').val();
    var dataPagamento = $('#data-pagamento').val();

    // Validação básica - apenas valor e data de vencimento são obrigatórios
    if (!valor || !dataVencimento) {
        M.toast({ html: 'Valor e Data de Vencimento são obrigatórios!', classes: 'red rounded' });
        return;
    }

    // Enviar dados para o servidor
    $.ajax({
        url: 'metodo.php?metodo=upsertMultaCobrada',
        method: 'POST',
        data: {
            id: id,
            valor: valor,
            data_vencimento: dataVencimento,
            data_pagamento: dataPagamento || '' // Envia string vazia se não preenchido
        },
        success: function (response) {
            if (response === 'success') {
                M.toast({ html: 'Multa salva com sucesso!', classes: 'green rounded' });
                $('#modal-multa').modal('close');
                $(`tr[data-id="${id}"]`).remove();
                // location.reload();
            } else {
                M.toast({ html: 'Erro ao salvar: ' + response, classes: 'red rounded' });
            }
        },
        error: function (xhr, status, error) {
            M.toast({ html: 'Erro de conexão: ' + error, classes: 'red rounded' });
        }
    });
});

$(document).on('click', '.parecer', function (e) {
    var $this = $(this);
    var total = -1;

    $("#listaSolucoes tr").each(function () {
        total++;
    });

    // Inicializar ou incrementar contador
    if (!$this.data('clickCount')) {
        $this.data('clickCount', 1);

        // Resetar após 1 segundo
        setTimeout(function () {
            $this.data('clickCount', 0);
        }, 1000);

    } else {
        $this.data('clickCount', $this.data('clickCount') + 1);
    }

    // Se for triplo clique
    if ($this.data('clickCount') === 3) {
        var valorParecer = $this.text().trim();

        if (valorParecer !== '') {
            // Contar quantas linhas serão removidas
            var count = 0;
            $('.parecer').each(function () {
                if ($(this).text().trim() === valorParecer) {
                    count++;
                }
            });

            // Remover todas as linhas com o mesmo parecer
            $('.parecer').each(function () {
                if ($(this).text().trim() === valorParecer) {
                    $(this).closest('tr').fadeOut(300, function () {
                        $(this).remove();
                    });
                }
            });

            M.toast({
                html: '✂️ Removidas ' + count + ' linhas com parecer: "' + valorParecer + '"',
                classes: 'orange rounded',
                displayLength: 4000
            });

            total = total - count;

            $("#listaSolucoes_info").html("Total de itens: " + total);

        } else {
            M.toast({
                html: '⚠️ Parecer vazio!',
                classes: 'red rounded'
            });
        }

        // Resetar contador imediatamente
        $this.data('clickCount', 0);
    }
});

// --- Toolset Operacional por Unidade ---
window.globalToolsetResponse = null;

// Handlers dos botões de navegação temporal por Mês
$(document).on('click', '#btnMesAnterior', function () {
    let inputMes = $('#mesAnoFiltro');
    let curVal = inputMes.val();
    if (!curVal) curVal = new Date().toISOString().slice(0, 7);
    
    let parts = curVal.split('-');
    let d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 2, 1);
    let newMes = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
    inputMes.val(newMes);
    
    if ($('#unidade').val() && $('#bloco').val()) {
        $('#buscaHistoricoUnidade').click();
    }
});

$(document).on('click', '#btnProximoMes', function () {
    let inputMes = $('#mesAnoFiltro');
    let curVal = inputMes.val();
    if (!curVal) curVal = new Date().toISOString().slice(0, 7);
    
    let parts = curVal.split('-');
    let d = new Date(parseInt(parts[0]), parseInt(parts[1]), 1);
    let newMes = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
    inputMes.val(newMes);
    
    if ($('#unidade').val() && $('#bloco').val()) {
        $('#buscaHistoricoUnidade').click();
    }
});

$(document).on('click', '#btnMesAtual', function () {
    let inputMes = $('#mesAnoFiltro');
    let newMes = new Date().toISOString().slice(0, 7);
    inputMes.val(newMes);
    
    if ($('#unidade').val() && $('#bloco').val()) {
        $('#buscaHistoricoUnidade').click();
    }
});

$(document).on('change', '#mesAnoFiltro', function () {
    if ($('#unidade').val() && $('#bloco').val()) {
        $('#buscaHistoricoUnidade').click();
    }
});

// Handler da Busca de Histórico / Toolset
$(document).on('click', '#buscaHistoricoUnidade', function (e) {
    let unidade = $("#unidade").val();
    let bloco = $("#bloco").val();
    let mesAno = $("#mesAnoFiltro").val() || new Date().toISOString().slice(0, 7);

    if (!unidade || !bloco) {
        M.toast({ html: 'Informe a Unidade e o Bloco!', classes: 'orange rounded' });
        return;
    }

    $('#emptyState').addClass('hide');
    $('#toolsetContainer').addClass('hide');
    $('#unitBrief').addClass('hide');
    $('#toolsetLoader').removeClass('hide');

    // Atualizar labels de mês nos aceleradores
    let anoStr = mesAno.substring(0, 4);
    let mesStr = mesAno.substring(5, 7);
    let mesExtenso = mesStr + '/' + anoStr;
    
    $('#labelMesEncomendas').text('(' + mesExtenso + ')');
    $('#labelMesAutorizacoes').text('(' + mesExtenso + ')');
    $('#labelMesReservas').text('(' + mesExtenso + ')');
    $('#labelAnoBoletos').text('(' + anoStr + ')');

    $.ajax({
        url: `metodo.php?metodo=toolsetUnidade&unidade=${encodeURIComponent(unidade)}&bloco=${encodeURIComponent(bloco)}&mesAno=${encodeURIComponent(mesAno)}`,
        method: 'GET',
        dataType: 'json',
        success: function (res) {
            $('#toolsetLoader').addClass('hide');

            if (res && res.success) {
                window.globalToolsetResponse = res;
                window.renderToolsetDashboard(res.estatisticas);
                window.renderToolsetMoradores(res.moradores || []);
                window.renderToolsetVeiculos(res.veiculos || []);
                window.renderToolsetNotificacoes(res.notificacoes || []);
                window.renderToolsetEncomendas(res.entregas || []);
                window.renderToolsetAutorizacoes(res.autorizacoes || []);
                window.renderToolsetReservas(res.reservas || []);
                window.renderToolsetOcorrenciasAutoria(res.ocorrenciasAutoria || []);
                window.renderToolsetOcorrenciasTag(res.ocorrenciasTag || []);
                window.renderToolsetBoletos(res.boletos || []);

                $('#toolsetContainer').removeClass('hide');
                $('#unitBrief').removeClass('hide');

                // Reinicializar collapsibles e tooltips
                $('.collapsible').collapsible({ accordion: false });
                $('.tooltipped').tooltip();
            } else {
                M.toast({ html: res.error || 'Erro ao consultar toolset da unidade', classes: 'red rounded' });
                $('#emptyState').removeClass('hide');
            }
        },
        error: function () {
            $('#toolsetLoader').addClass('hide');
            $('#emptyState').removeClass('hide');
            M.toast({ html: 'Erro de comunicação com o servidor', classes: 'red rounded' });
        }
    });
});

// Renderização da Dashboard KPI da Unidade
window.renderToolsetDashboard = function (stats) {
    if (!stats) return;

    let seloInadimplencia = stats.inadimplente ? `
        <div class="col s12" style="margin-bottom:12px;">
            <div class="card-panel red darken-2 white-text flex-responsive" style="padding:12px 18px; border-radius:8px; display:flex; align-items:center; gap:12px; box-shadow: 0 4px 12px rgba(211,47,47,0.35);">
                <i class="material-icons" style="font-size:2.2rem;">warning</i>
                <div>
                    <div style="font-weight:bold; font-size:1.1rem; text-transform:uppercase; letter-spacing:0.5px;">⚠️ UNIDADE INADIMPLENTE</div>
                    <div style="font-size:0.85rem; opacity:0.95;">Esta unidade possui débitos ou pendências financeiras registradas na administração do condomínio.</div>
                </div>
            </div>
        </div>
    ` : '';

    let kpiHtml = seloInadimplencia + `
        <div class="col s12 m4 l3" style="margin-bottom:10px;">
            <div class="kpi-card-toolset red darken-1" onclick="window.focusToolsetSection(2);">
                <div class="kpi-val">${stats.totalNotificacoes}</div>
                <div class="kpi-lbl">Notificações (${stats.totalMultas} Multas / ${stats.totalAdvertencias} Adv)</div>
            </div>
        </div>
        <div class="col s12 m4 l3" style="margin-bottom:10px;">
            <div class="kpi-card-toolset blue darken-2" onclick="window.focusToolsetSection(2);">
                <div class="kpi-val">${stats.totalRecursos}</div>
                <div class="kpi-lbl">Recursos (${stats.recursosMantidos} M / ${stats.recursosRevogados} R / ${stats.recursosConvertidos} C)</div>
            </div>
        </div>
        <div class="col s12 m4 l2" style="margin-bottom:10px;">
            <div class="kpi-card-toolset blue-grey darken-3" onclick="window.focusToolsetSection(1);">
                <div class="kpi-val">${stats.totalVeiculos || 0}</div>
                <div class="kpi-lbl">Veículos Cadastrados</div>
            </div>
        </div>
        <div class="col s12 m4 l2" style="margin-bottom:10px;">
            <div class="kpi-card-toolset amber darken-3" onclick="window.focusToolsetSection(3);">
                <div class="kpi-val">${stats.totalEntregas}</div>
                <div class="kpi-lbl">Encomendas (${stats.entregasPendentes} Pend)</div>
            </div>
        </div>
        <div class="col s12 m4 l2" style="margin-bottom:10px;">
            <div class="kpi-card-toolset teal darken-1" onclick="window.focusToolsetSection(4);">
                <div class="kpi-val">${stats.totalAutorizacoes}</div>
                <div class="kpi-lbl">Acessos Autorizados</div>
            </div>
        </div>
        <div class="col s12 m6 l6" style="margin-top:5px; margin-bottom:5px;">
            <div class="kpi-card-toolset blue-grey darken-4" onclick="window.focusToolsetSection(6);" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:0.8rem; text-transform:uppercase; opacity:0.8;">Ocorrências no Condomínio</div>
                    <div style="font-weight:600; font-size:1.05rem;">Própria Autoria: ${stats.totalChamadosAutoria} | Citada/Tag: ${stats.totalChamadosTag}</div>
                </div>
                <i class="material-icons">forum</i>
            </div>
        </div>
        <div class="col s12 m6 l6" style="margin-top:5px; margin-bottom:5px;">
            <div class="kpi-card-toolset green darken-2" onclick="window.focusToolsetSection(8);" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-size:0.8rem; text-transform:uppercase; opacity:0.8;">Situação Financeira / Boletos</div>
                    <div style="font-weight:600; font-size:1.05rem;">Total no Ano: ${stats.totalBoletos} | Em Aberto: ${stats.boletosAbertos}</div>
                </div>
                <i class="material-icons">monetization_on</i>
            </div>
        </div>
    `;

    $('#unitBrief').html(kpiHtml);
};

// 0. Renderizar Moradores da Unidade (Cards com foto, nome e tipo)
window.renderToolsetMoradores = function (list) {
    $('#badgeCountMoradores').text(list.length);
    if (!list || list.length === 0) {
        $('#conteudoMoradores').html('<div class="grey-text center-align" style="padding:20px;"><i class="material-icons tiny">people_outline</i> Nenhum morador cadastrado para esta unidade.</div>');
        return;
    }

    let cardsHtml = '<div class="row" style="margin-bottom:0;">';
    list.forEach(m => {
        let avatarEl = m.foto ? 
            `<img src="${m.foto}" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:2px solid #00acc1; margin-bottom:6px;">` : 
            `<div style="width:64px; height:64px; border-radius:50%; background:#e0f7fa; display:flex; align-items:center; justify-content:center; margin:0 auto 6px auto; border:2px solid #00acc1;"><i class="material-icons cyan-text text-darken-2" style="font-size:2.5rem;">account_circle</i></div>`;

        cardsHtml += `
            <div class="col s12 m6 l3">
                <div class="card-panel white center-align z-depth-1 hoverable" style="border-radius:10px; padding:15px 10px; border:1px solid #e0e0e0; margin-bottom:12px;">
                    ${avatarEl}
                    <div style="font-weight:bold; font-size:1rem; color:#37474f;" class="truncate" title="${m.nome}">${m.nome}</div>
                    <span class="badge-mini cyan darken-1 white-text" style="margin-top:6px; font-size:0.7rem; padding:2px 6px; border-radius:4px; display:inline-block;">${m.tipo || 'Morador'}</span>
                </div>
            </div>
        `;
    });
    cardsHtml += '</div>';

    $('#conteudoMoradores').html(cardsHtml);
};

// 0.1 Renderizar Veículos da Unidade
window.renderToolsetVeiculos = function (list) {
    $('#badgeCountVeiculos').text(list.length);
    if (!list || list.length === 0) {
        $('#conteudoVeiculos').html('<div class="grey-text center-align" style="padding:20px;"><i class="material-icons tiny">directions_car</i> Nenhum veículo cadastrado para esta unidade.</div>');
        return;
    }

    let cardsHtml = '<div class="row" style="margin-bottom:0;">';
    list.forEach(v => {
        let isMoto = (v.tipo || '').toLowerCase().includes('moto');
        let iconName = isMoto ? 'two_wheeler' : 'directions_car';
        let fotoHtml = v.foto ? 
            `<img src="${v.foto}" style="width:100%; height:110px; object-fit:cover; border-radius:6px; margin-bottom:10px; border:1px solid #ddd;">` : '';

        let descVeiculo = [v.marca, v.modelo, v.cor].filter(Boolean).join(' ') || 'Veículo';
        let propStr = v.proprietario ? `<div style="font-size:0.8rem; color:#555; margin-top:4px;" class="truncate" title="${v.proprietario}"><i class="material-icons tiny">person</i> ${v.proprietario}</div>` : '';
        let obsStr = v.observacao ? `<div style="font-size:0.78rem; color:#757575; margin-top:4px; font-style:italic;" class="truncate" title="${v.observacao}"><i class="material-icons tiny">info</i> ${v.observacao}</div>` : '';

        let pcdBadge = v.portadorNecessidade ? 
            `<span class="badge-mini blue darken-2 white-text font-weight-bold" style="font-size:0.7rem; margin-top:4px; display:inline-block;"><i class="material-icons tiny">accessible</i> Vaga PCD</span>` : '';

        cardsHtml += `
            <div class="col s12 m6 l3">
                <div class="card-panel white z-depth-1 hoverable" style="border-radius:10px; padding:15px; border:1px solid #e0e0e0; margin-bottom:12px;">
                    ${fotoHtml}
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <span class="badge blue-grey darken-3 white-text font-weight-bold" style="float:none; padding:4px 10px; border-radius:4px; font-family:monospace; font-size:1.05rem; letter-spacing:1px; display:inline-flex; align-items:center; gap:4px;">
                            <i class="material-icons tiny">${iconName}</i> ${v.placa}
                        </span>
                        <span class="badge-mini blue-grey lighten-4 blue-grey-text text-darken-4 font-weight-bold">${v.tipo}</span>
                    </div>
                    <div style="font-weight:bold; font-size:1rem; color:#263238;" class="truncate" title="${descVeiculo}">${descVeiculo}</div>
                    ${pcdBadge}
                    ${propStr}
                    ${obsStr}
                </div>
            </div>
        `;

    });
    cardsHtml += '</div>';

    $('#conteudoVeiculos').html(cardsHtml);
};




window.focusToolsetSection = function (index) {
    let collapsible = M.Collapsible.getInstance($('#toolsetCollapsible'));
    if (collapsible) {
        collapsible.open(index);
        $('html, body').animate({
            scrollTop: $('#toolsetCollapsible').offset().top - 80
        }, 400);
    }
};

// 1. Renderizar Notificações (Histórico Completo)
window.renderToolsetNotificacoes = function (list) {
    $('#badgeCountNotificacoes').text(list.length);
    if (!list || list.length === 0) {
        $('#conteudoNotificacoes').html('<div class="grey-text center-align" style="padding:20px;"><i class="material-icons tiny">info</i> Nenhuma notificação cadastrada para esta unidade.</div>');
        return;
    }

    let html = '';
    list.forEach(d => {
        let tipo = (d.notificacao || '').toUpperCase();
        let virtual = d.numero_ano_virtual || (d.numero + '/' + d.ano);
        
        let bgParecer = '';
        if (d.parecer) {
            let p = d.parecer.toUpperCase();
            if (p.includes('MANTER')) bgParecer = 'parecer-manter';
            else if (p.includes('CONVERTER')) bgParecer = 'parecer-converter';
            else if (p.includes('REVOGAR')) bgParecer = 'parecer-revogar';
        }

        let linkRecurso = d.recurso === 'Sim' ?
            `<a href="index.php?pag=recurso&rec=${encodeURIComponent(virtual)}" class="btn-small blue waves-effect waves-light tooltipped" data-tooltip="Visualizar Recurso"><i class="material-icons left" style="margin-right:4px;">visibility</i> Recurso</a>` :
            `<span class="grey-text text-lighten-1" style="font-size:0.85rem;"><i class="material-icons tiny">do_not_disturb</i> Sem recurso</span>`;

        let btnCiencia = `
            <button type="button" class="btn-small waves-effect waves-light grey lighten-3 grey-text text-darken-3 btn-abrir-modal-ciencia" data-virtual="${virtual}" data-retirada="${d.dia_retirada || ''}" style="margin-right:5px;" title="Registrar Ciência/Retirada">
                <i class="material-icons left tiny" style="margin-right:2px;">event_available</i> ${d.dia_retirada ? d.dia_retirada : 'Add Ciência'}
            </button>
        `;

        let btnCobranca = `
            <button type="button" class="btn-small waves-effect waves-light green lighten-4 green-text text-darken-4 btn-abrir-modal-cobranca" data-virtual="${virtual}" data-multa="${d.multa_cobrada || ''}" data-valor="${d.valor || ''}" data-vencimento="${d.data_vencimento || ''}" data-pagamento="${d.data_pagamento || ''}" title="Confirmar Cobrança de Multa">
                <i class="material-icons left tiny" style="margin-right:2px;">attach_money</i> ${d.multa_cobrada === 'Sim' ? ('Cobrado: R$ ' + d.valor) : 'Lançar Cobrança'}
            </button>
        `;

        html += `
            <div class="card hoverable card-notificacao-toolset ${tipo} ${bgParecer}" style="margin: 0.8rem 0;">
                <div class="card-content" style="padding: 12px 18px;">
                    <div class="row valign-wrapper flex-responsive" style="margin-bottom: 0;">
                        <div class="col s12 m2">
                            <span class="badge-mini ${tipo === 'MULTA' ? 'red' : 'orange'} white-text" style="display:inline-block; margin-bottom:4px;">${tipo || 'N/A'}</span>
                            <div style="font-weight: bold; font-size:1.05rem;">#${virtual}</div>
                            <small class="grey-text">${d.data_ocorrido ? ('Ocorrido: ' + d.data_ocorrido) : ''}</small>
                        </div>
                        <div class="col s12 m4">
                            <div style="font-weight: 600; font-size: 1.05rem;" class="indigo-text text-darken-3">${d.assunto || 'Sem Assunto'}</div>
                            ${d.detalhes ? `<div class="truncate grey-text text-darken-2" style="font-size:0.85rem;">${d.detalhes}</div>` : ''}
                            ${d.parecer ? `<div style="font-size:0.85rem; margin-top:4px;" class="teal-text text-darken-4"><b>Parecer:</b> ${d.parecer}</div>` : ''}
                        </div>
                        <div class="col s12 m6 right-align flex-responsive" style="gap:5px; justify-content:flex-end;">
                            ${btnCiencia}
                            ${btnCobranca}
                            ${linkRecurso}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    $('#conteudoNotificacoes').html(html);
};

// 2. Renderizar Encomendas
window.renderToolsetEncomendas = function (list) {
    $('#badgeCountEncomendas').text(list.length);
    if (!list || list.length === 0) {
        $('#conteudoEncomendas').html('<div class="grey-text center-align" style="padding:20px;"><i class="material-icons tiny">mark_email_read</i> Nenhuma encomenda registrada no mês selecionado.</div>');
        return;
    }

    let tableHtml = `
        <table class="striped responsive-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Rastreio / ID</th>
                    <th>Descrição</th>
                    <th>Destinatário</th>
                    <th>Data / Hora Chegada</th>
                    <th>Status</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
    `;

    list.forEach(e => {
        let fotoImg = e.foto ? `<img src="${e.foto}" style="width:36px; height:36px; object-fit:cover; border-radius:4px; border:1px solid #ccc; cursor:pointer;" class="img-preview-entrega" data-uuid="${e.uuid}">` : `<i class="material-icons grey-text">inventory_2</i>`;
        let statusBadge = (e.status || '').toLowerCase().includes('entregue') || (e.status || '').toLowerCase().includes('retirado') ? 
            `<span class="badge-mini green white-text">${e.status}</span>` : 
            `<span class="badge-mini amber darken-2 white-text">${e.status || 'Pendente'}</span>`;

        let colIdHtml = e.identificador ? 
            `<span class="badge blue lighten-4 blue-text text-darken-3 font-weight-bold" style="float:none; padding:3px 8px; border-radius:4px; display:inline-flex; align-items:center; gap:3px;"><i class="material-icons tiny">qr_code</i> ${e.identificador}</span>` : 
            `<span class="grey-text text-lighten-1" style="font-size:0.85rem;"><i class="material-icons tiny">hourglass_empty</i> Carregando...</span>`;

        tableHtml += `
            <tr class="linha-entrega-toolset" data-uuid="${e.uuid}">
                <td class="center-align col-foto">${fotoImg}</td>
                <td class="col-identificador">${colIdHtml}</td>
                <td>${e.descricao || 'Pacote'}</td>
                <td>${e.destinatario || 'Morador'}</td>
                <td><i class="material-icons tiny grey-text">access_time</i> ${e.dthoraChegada || 'N/A'}</td>
                <td>${statusBadge}</td>
                <td>
                    <button type="button" class="btn-small btn-flat waves-effect blue lighten-5 blue-text text-darken-3 btn-inspect-entrega" data-uuid="${e.uuid}">
                        <i class="material-icons tiny left">visibility</i> Ver
                    </button>
                </td>
            </tr>
        `;
    });

    tableHtml += `</tbody></table>`;
    $('#conteudoEncomendas').html(tableHtml);

    // Buscar em segundo plano identificador e foto de cada entrega por UUID (mesmo método da tela de detalheRecurso)
    const rows = document.querySelectorAll('.linha-entrega-toolset[data-uuid]');
    rows.forEach(function (row) {
        const uuid = row.getAttribute('data-uuid');
        if (!uuid) return;

        fetch(`metodo.php?metodo=obterDetalhesEntrega&uuid=${encodeURIComponent(uuid)}`)
            .then(res => res.json())
            .then(resData => {
                if (resData && resData.success && resData.data) {
                    const d = resData.data;

                    if (d.identificador) {
                        const colId = row.querySelector('.col-identificador');
                        if (colId) {
                            colId.innerHTML = `
                                <span class="badge blue lighten-4 blue-text text-darken-3 font-weight-bold" style="float:none; padding:3px 8px; border-radius:4px; display:inline-flex; align-items:center; gap:3px;">
                                    <i class="material-icons tiny">qr_code</i> ${d.identificador}
                                </span>
                            `;
                        }
                    } else {
                        const colId = row.querySelector('.col-identificador');
                        if (colId && colId.innerHTML.includes('Carregando...')) {
                            colId.innerHTML = '<span class="grey-text">N/A</span>';
                        }
                    }

                    const fotoUrl = d.fotoUrlCompleta || (d.foto ? (d.foto.startsWith('http') ? d.foto : 'https://app.vidadesindico.com.br' + d.foto) : null);
                    if (fotoUrl) {
                        const colFoto = row.querySelector('.col-foto');
                        if (colFoto) {
                            colFoto.innerHTML = `<img src="${fotoUrl}" style="width:36px; height:36px; border-radius:4px; object-fit:cover; border:1px solid #90caf9; cursor:pointer;" class="img-preview-entrega" data-uuid="${uuid}">`;
                        }
                    }
                }
            })
            .catch(err => console.error('Erro ao carregar detalhes da entrega:', err));
    });
};


// 3. Renderizar Autorizações de Acesso
window.renderToolsetAutorizacoes = function (list) {
    $('#badgeCountAutorizacoes').text(list.length);
    if (!list || list.length === 0) {
        $('#conteudoAutorizacoes').html('<div class="grey-text center-align" style="padding:20px;"><i class="material-icons tiny">no_accounts</i> Nenhuma autorização de acesso registrada no mês selecionado.</div>');
        return;
    }

    let cardsHtml = '<div class="row" style="margin-bottom:0;">';
    list.forEach(a => {
        let fotoUrl = a.foto || 'https://via.placeholder.com/60?text=Pessoa';
        cardsHtml += `
            <div class="col s12 m6 l4">
                <div class="card-panel grey lighten-5" style="border-radius:8px; padding:12px; border:1px solid #e0e0e0; margin-bottom:12px;">
                    <div style="display:flex; gap:12px; align-items:center;">
                        <img src="${fotoUrl}" style="width:50px; height:50px; border-radius:50%; object-fit:cover; border:2px solid #009688;">
                        <div style="flex:1; overflow:hidden;">
                            <div style="font-weight:bold; font-size:0.95rem;" class="truncate">${a.nome}</div>
                            <small class="grey-text">${a.documento || 'Documento N/A'}</small>
                            <div style="font-size:0.8rem; margin-top:2px;" class="teal-text text-darken-3"><b>Vigência:</b> ${a.dtInicio} até ${a.dtFim}</div>
                        </div>
                    </div>
                    <div style="margin-top:8px; border-top:1px solid #eee; padding-top:6px; display:flex; justify-content:space-between; align-items:center; font-size:0.75rem;" class="grey-text">
                        <span>Por: ${a.autorizadoPor}</span>
                        <span class="badge-mini teal white-text">${a.status}</span>
                    </div>
                </div>
            </div>
        `;
    });
    cardsHtml += '</div>';

    $('#conteudoAutorizacoes').html(cardsHtml);
};

// 4. Renderizar Reservas de Área Comum
window.renderToolsetReservas = function (list) {
    $('#badgeCountReservas').text(list.length);
    if (!list || list.length === 0) {
        $('#conteudoReservas').html('<div class="grey-text center-align" style="padding:20px;"><i class="material-icons tiny">event_busy</i> Nenhuma reserva de área comum encontrada para o mês selecionado.</div>');
        return;
    }

    let html = '<div class="row" style="margin-bottom:0;">';
    list.forEach(r => {
        html += `
            <div class="col s12 m6 l4">
                <div class="card-panel white" style="border-left: 4px solid #3f51b5; border-radius:6px; padding:12px; margin-bottom:10px; box-shadow:0 2px 6px rgba(0,0,0,0.06);">
                    <div style="font-weight:bold; font-size:1rem;" class="indigo-text text-darken-3">${r.recurso}</div>
                    <div style="font-size:0.85rem; margin-top:4px;" class="grey-text text-darken-2">
                        <i class="material-icons tiny">event</i> <b>Data:</b> ${r.dtReserva} ${r.horario ? ('(' + r.horario + ')') : ''}
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px; border-top:1px dotted #ccc; padding-top:6px;">
                        <span class="badge-mini indigo lighten-4 indigo-text text-darken-4">${r.status}</span>
                        <span style="font-weight:bold; font-size:0.9rem;" class="green-text text-darken-2">${r.valor || 'Sem taxa'}</span>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';

    $('#conteudoReservas').html(html);
};

// 5. Renderizar Ocorrências de Autoria
window.renderToolsetOcorrenciasAutoria = function (list) {
    $('#badgeCountOcorrenciasAutoria').text(list.length);
    if (!list || list.length === 0) {
        $('#conteudoOcorrenciasAutoria').html('<div class="grey-text center-align" style="padding:20px;"><i class="material-icons tiny">chat_bubble_outline</i> Nenhuma ocorrência registrada pela própria unidade.</div>');
        return;
    }

    let html = '';
    list.forEach(o => {
        let prot = o.protocolo || (o.numero + '/' + o.ano);
        html += `
            <div class="card-panel white" style="border-left:4px solid #2196f3; padding:12px 18px; margin: 8px 0;">
                <div class="row valign-wrapper flex-responsive" style="margin-bottom:0;">
                    <div class="col s12 m2">
                        <span class="badge-mini blue white-text">Prot #${prot}</span>
                        <div style="font-size:0.75rem; margin-top:4px;" class="grey-text">${o.abertura || 'N/A'}</div>
                    </div>
                    <div class="col s12 m7">
                        <div style="font-weight:bold; font-size:1rem;" class="blue-text text-darken-4">${o.assunto || 'Ocorrência Morador'}</div>
                        <div style="font-size:0.85rem;" class="grey-text text-darken-2">${o.mensagem || o.descricao || ''}</div>
                    </div>
                    <div class="col s12 m3 right-align">
                        <a href="index.php?pag=livroDeOcorrencias&prot=${encodeURIComponent(prot)}" class="btn-small blue waves-effect waves-light">
                            <i class="material-icons left tiny">chat</i> Abrir Chat
                        </a>
                    </div>
                </div>
            </div>
        `;
    });

    $('#conteudoOcorrenciasAutoria').html(html);
};

// 6. Renderizar Ocorrências com Tag
window.renderToolsetOcorrenciasTag = function (list) {
    $('#badgeCountOcorrenciasTag').text(list.length);
    if (!list || list.length === 0) {
        $('#conteudoOcorrenciasTag').html('<div class="grey-text center-align" style="padding:20px;"><i class="material-icons tiny">loyalty</i> Nenhuma ocorrência citando ou marcando a unidade.</div>');
        return;
    }

    let html = '';
    list.forEach(o => {
        let prot = o.protocolo || (o.numero + '/' + o.ano);
        let tagTipo = (o.vinculo_final || 'citada').toUpperCase();
        html += `
            <div class="card-panel white" style="border-left:4px solid #9c27b0; padding:12px 18px; margin: 8px 0;">
                <div class="row valign-wrapper flex-responsive" style="margin-bottom:0;">
                    <div class="col s12 m2">
                        <span class="badge-mini purple white-text">Tag: ${tagTipo}</span>
                        <div style="font-size:0.75rem; margin-top:4px;" class="grey-text">Prot #${prot}</div>
                    </div>
                    <div class="col s12 m7">
                        <div style="font-weight:bold; font-size:1rem;" class="purple-text text-darken-4">${o.assunto || 'Ocorrência Citada'}</div>
                        <div style="font-size:0.85rem;" class="grey-text text-darken-2">Autor: Bl. ${o.bloco || ''} - Unid. ${o.unidade || ''} | ${o.abertura || ''}</div>
                    </div>
                    <div class="col s12 m3 right-align">
                        <a href="index.php?pag=livroDeOcorrencias&prot=${encodeURIComponent(prot)}" class="btn-small purple waves-effect waves-light">
                            <i class="material-icons left tiny">chat</i> Ver Conversa
                        </a>
                    </div>
                </div>
            </div>
        `;
    });

    $('#conteudoOcorrenciasTag').html(html);
};

// Helper para extrair valor texto de campos que podem vir como string ou objeto da API
window.vdsExtractStringValue = function (val, defaultVal) {
    if (!val) return defaultVal || 'N/A';
    if (typeof val === 'string' || typeof val === 'number') return String(val);
    if (typeof val === 'object') {
        return val.descricao || val.nome || val.situacao || val.status || val.tipo || val.detalhe || defaultVal || 'N/A';
    }
    return defaultVal || 'N/A';
};

// 7. Renderizar Lista de Boletos
window.renderToolsetBoletos = function (list) {
    $('#badgeCountBoletos').text(list.length);
    if (!list || list.length === 0) {
        $('#conteudoBoletos').html('<div class="grey-text center-align" style="padding:20px;"><i class="material-icons tiny">account_balance_wallet</i> Nenhum boleto registrado na VDS para este ano.</div>');
        return;
    }

    let tableHtml = `
        <table class="striped responsive-table">
            <thead>
                <tr>
                    <th>Vencimento</th>
                    <th>Referência / Doc</th>
                    <th>Valor Total</th>
                    <th>Status</th>
                    <th>2ª Via / Multas</th>
                </tr>
            </thead>
            <tbody>
    `;

    list.forEach(b => {
        let statusStr = window.vdsExtractStringValue(b.status || b.situacao, 'Em Aberto');
        let isPago = statusStr.toLowerCase().includes('liquidado') || statusStr.toLowerCase().includes('pago');
        let statusBadge = isPago ? `<span class="badge-mini green white-text">${statusStr}</span>` : `<span class="badge-mini orange darken-2 white-text">${statusStr}</span>`;


        let valorFmt = b.valorTotal ? ('R$ ' + parseFloat(b.valorTotal).toFixed(2).replace('.', ',')) : (b.valor ? ('R$ ' + parseFloat(b.valor).toFixed(2).replace('.', ',')) : 'R$ 0,00');

        let btnSegundaVia = b.urlSegundaVia ? 
            `<a href="${b.urlSegundaVia}" target="_blank" class="btn-small green waves-effect waves-light" style="margin-right:4px;" title="Ver 2ª Via no Superlógica"><i class="material-icons tiny left">picture_as_pdf</i> 2ª Via</a>` : 
            '';

        let btnExtrairMultas = b.urlSegundaVia ?
            `<button type="button" class="btn-small btn-flat waves-effect blue lighten-5 blue-text text-darken-3 btn-extrair-multas-boleto" data-url="${encodeURIComponent(b.urlSegundaVia)}" data-status="${statusStr}" data-vencimento="${b.dtVencimento || ''}">
                <i class="material-icons tiny left">search</i> Extrair Multas
            </button>` : '';

        tableHtml += `
            <tr>
                <td><b>${b.dtVencimento ? window.formatDateBR(b.dtVencimento) : 'N/A'}</b></td>
                <td>${b.nossoNumero || b.referencia || 'Boleto Condominial'}</td>
                <td style="font-weight:bold;" class="green-text text-darken-3">${valorFmt}</td>
                <td>${statusBadge}</td>
                <td>
                    ${btnSegundaVia}
                    ${btnExtrairMultas}
                </td>
            </tr>
        `;
    });

    tableHtml += `</tbody></table>`;
    $('#conteudoBoletos').html(tableHtml);
};

// Helper simples para formatar data BR YYYY-MM-DD -> DD/MM/YYYY
window.formatDateBR = function(dtStr) {
    if (!dtStr) return 'N/A';
    if (dtStr.includes('/')) return dtStr;
    let parts = dtStr.split('-');
    if (parts.length === 3) {
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }
    return dtStr;
};

// Handlers dos Modais de Ação Contextual nas Notificações
$(document).on('click', '.btn-abrir-modal-ciencia', function () {
    let virtual = $(this).data('virtual');
    let retirada = $(this).data('retirada');
    
    $('#virtualNotificacaoTarget').val(virtual);
    $('#lblNotificacaoVirtual').text('#' + virtual);
    
    if (retirada) {
        // Converter DD/MM/YYYY para YYYY-MM-DD se necessário
        let parts = retirada.split('/');
        if (parts.length === 3) {
            $('#inputDataRetirada').val(parts[2] + '-' + parts[1] + '-' + parts[0]);
        } else {
            $('#inputDataRetirada').val(retirada);
        }
    } else {
        $('#inputDataRetirada').val(new Date().toISOString().slice(0, 10));
    }

    M.Modal.getInstance($('#modalCienciaNotificacao')).open();
});

$(document).on('click', '#btnSalvarCiencia', function () {
    let virtual = $('#virtualNotificacaoTarget').val();
    let dataRetirada = $('#inputDataRetirada').val();

    if (!dataRetirada) {
        M.toast({ html: 'Informe a data de retirada!', classes: 'orange' });
        return;
    }

    $.post('metodo.php?metodo=atualizaDataRetiradaNotificacao', { virtual: virtual, dia_retirada: dataRetirada }, function (res) {
        if (res.trim() === 'success') {
            M.toast({ html: 'Data de ciência atualizada com sucesso!', classes: 'green rounded' });
            M.Modal.getInstance($('#modalCienciaNotificacao')).close();
            $('#buscaHistoricoUnidade').click();
        } else {
            M.toast({ html: 'Erro ao atualizar data de ciência', classes: 'red rounded' });
        }
    });
});

$(document).on('click', '.btn-abrir-modal-cobranca', function () {
    let virtual = $(this).data('virtual');
    let valor = $(this).data('valor');
    let vencimento = $(this).data('vencimento');
    let pagamento = $(this).data('pagamento');

    $('#cobrancaNotificacaoTarget').val(virtual);
    $('#lblCobrancaVirtual').text('#' + virtual);
    $('#inputValorMulta').val(valor || '');
    $('#inputVencimentoMulta').val(vencimento || '');
    $('#inputPagamentoMulta').val(pagamento || '');

    M.Modal.getInstance($('#modalCobrancaMulta')).open();
});

$(document).on('click', '#btnSalvarCobranca', function () {
    let virtual = $('#cobrancaNotificacaoTarget').val();
    let valor = $('#inputValorMulta').val();
    let vencimento = $('#inputVencimentoMulta').val();
    let pagamento = $('#inputPagamentoMulta').val();

    if (!valor || !vencimento) {
        M.toast({ html: 'Preencha valor e data de vencimento!', classes: 'orange' });
        return;
    }

    $.post('metodo.php?metodo=upsertMultaCobrada', {
        id: virtual,
        valor: valor,
        data_vencimento: vencimento,
        data_pagamento: pagamento
    }, function (res) {
        if (res.trim() === 'success') {
            M.toast({ html: 'Lançamento de cobrança registrado!', classes: 'green rounded' });
            M.Modal.getInstance($('#modalCobrancaMulta')).close();
            $('#buscaHistoricoUnidade').click();
        } else {
            M.toast({ html: res || 'Erro ao registrar cobrança', classes: 'red rounded' });
        }
    });
});

// Handler para inspeção de fotos de entrega
$(document).on('click', '.btn-inspect-entrega, .img-preview-entrega', function () {
    let uuid = $(this).data('uuid');
    if (!uuid) return;

    let modal = M.Modal.getInstance($('#modalDetalhesEntrega'));
    $('#conteudoModalEntrega').html('<div class="preloader-wrapper active"><div class="spinner-layer spinner-blue-only"><div class="circle-clipper left"><div class="circle"></div></div></div></div><p>Buscando detalhes...</p>');
    modal.open();

    $.get(`metodo.php?metodo=obterDetalhesEntrega&uuid=${encodeURIComponent(uuid)}`, function (res) {
        if (res && res.success && res.data) {
            let d = res.data;
            let foto = d.fotoUrlCompleta ? `<img src="${d.fotoUrlCompleta}" style="max-width:100%; max-height:350px; border-radius:8px; border:1px solid #ddd; margin-bottom:15px;">` : '<p class="grey-text">Sem foto registrada</p>';
            
            let html = `
                ${foto}
                <div class="left-align" style="background:#f5f5f5; padding:15px; border-radius:8px;">
                    <p><b>Identificador:</b> ${d.identificador || 'N/A'}</p>
                    <p><b>Descrição:</b> ${d.descricao || 'N/A'}</p>
                    <p><b>Destinatário:</b> ${d.destinatario || 'Morador'}</p>
                    <p><b>Data / Hora Chegada:</b> ${d.dthoraFormatada || d.dthora || 'N/A'}</p>
                    <p><b>Situação:</b> ${d.status || 'N/A'}</p>
                </div>
            `;
            $('#conteudoModalEntrega').html(html);
        } else {
            $('#conteudoModalEntrega').html('<p class="red-text">Não foi possível carregar os detalhes da entrega.</p>');
        }
    }, 'json');
});

// Handler para extrair sugestões de multa de boletos com apresentação em modal para confirmação
$(document).on('click', '.btn-extrair-multas-boleto', function () {
    let $btn = $(this);
    let url = decodeURIComponent($btn.data('url'));
    let status = $btn.data('status');
    let vencimento = $btn.data('vencimento');

    let modalEl = $('#modalSugestoesMultaBoleto');
    let modal = M.Modal.getInstance(modalEl);
    
    $('#loadingSugestoesBoleto').removeClass('hide');
    $('#containerSugestoesBoleto').addClass('hide').html('');
    modal.open();

    let sugestoesMap = {};

    function addSugestao(s) {
        if (!s || !s.numero_ano) return;
        if (!sugestoesMap[s.numero_ano]) {
            sugestoesMap[s.numero_ano] = s;
            renderSugestoesModal();
        }
    }

    function renderSugestoesModal() {
        let items = Object.values(sugestoesMap);
        $('#loadingSugestoesBoleto').addClass('hide');
        $('#containerSugestoesBoleto').removeClass('hide');

        if (items.length === 0) {
            $('#containerSugestoesBoleto').html('<div class="center-align grey-text" style="padding:40px;"><i class="material-icons" style="font-size:4rem; opacity:0.3;">search_off</i><p>Nenhuma ocorrência de multa por notificação detectada na composição desta fatura.</p></div>');
            return;
        }

        let cardsHtml = '';
        items.forEach(s => {
            let btnAcao = s.ja_lancado ?
                `<span class="badge green white-text font-weight-bold" style="float:none; padding:6px 12px; border-radius:4px;">✓ Lançamento Confirmado</span>` :
                `<button type="button" class="btn waves-effect waves-light green darken-2 btn-confirmar-sugestao-multa" data-id="${s.numero_ano}" data-valor="${s.valor}" data-vencimento="${s.data_vencimento}" data-pagamento="${s.data_pagamento_sugerida || ''}">
                    <i class="material-icons left tiny">check_circle</i> Confirmar Lançamento
                </button>`;

            cardsHtml += `
                <div class="card-panel white z-depth-1" style="border-left: 5px solid #2e7d32; border-radius:8px; padding:16px; margin-bottom:15px;" data-id="${s.numero_ano}">
                    <div class="row valign-wrapper flex-responsive" style="margin-bottom:0;">
                        <div class="col s12 m8">
                            <span class="badge-mini red white-text" style="margin-bottom:4px;">MULTA DETECTADA</span>
                            <h6 style="font-weight:bold; margin:4px 0;" class="green-text text-darken-4">Notificação #${s.numero_ano}</h6>
                            <p style="margin:4px 0; font-size:0.9rem;" class="grey-text text-darken-3">${s.item_descricao || ('Multa Notificação #' + s.numero_ano)}</p>
                            <div style="font-size:0.85rem;" class="grey-text">
                                <span><b>Valor Extrato:</b> <b class="green-text text-darken-3">${s.valor_formatado || ('R$ ' + s.valor)}</b></span> | 
                                <span><b>Vencimento:</b> ${s.data_vencimento}</span>
                            </div>
                        </div>
                        <div class="col s12 m4 right-align">
                            ${btnAcao}
                        </div>
                    </div>
                </div>
            `;
        });

        $('#containerSugestoesBoleto').html(cardsHtml);
    }

    // A. Busca via API backend
    $.get(`metodo.php?metodo=extrairSugestoesBoleto&url=${encodeURIComponent(url)}&status=${encodeURIComponent(status)}&dtVencimento=${encodeURIComponent(vencimento)}`, function (res) {
        if (res && res.success && Array.isArray(res.sugestoes)) {
            res.sugestoes.forEach(addSugestao);
        }
        if (Object.keys(sugestoesMap).length === 0) {
            renderSugestoesModal();
        }
    }, 'json');

    // B. Parser dinâmico client-side no DOM via proxy fatura HTML
    fetch(`metodo.php?metodo=proxyFaturaHtml&url=${encodeURIComponent(url)}`)
        .then(response => response.text())
        .then(htmlText => {
            if (!htmlText || htmlText.length < 50) return;
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');

            const corpo = doc.querySelector('#corpoComposicao') || doc.body;
            const elements = corpo.querySelectorAll('tr, div, p, li');

            elements.forEach(el => {
                const text = el.textContent || '';
                const isMulta = /multa|infra[çc]|notifica[çc][ãa]o|not\b|penalidade|regimento|ri\b/i.test(text);
                const matchNum = text.match(/(\d+)\/(\d{2,4})/);
                const matchVal = text.match(/R\$\s*([\d\.,]+)/i);

                if (isMulta && matchNum && matchVal) {
                    const numero = matchNum[1];
                    const rawAno = matchNum[2];
                    const ano = rawAno.length === 2 ? '20' + rawAno : rawAno;
                    const valorCleanStr = matchVal[1].replace(/\./g, '').replace(',', '.');
                    const valorNum = parseFloat(valorCleanStr);

                    if (!isNaN(valorNum) && valorNum > 0) {
                        addSugestao({
                            numero: numero,
                            ano: ano,
                            numero_ano: `${numero}/${ano}`,
                            item_descricao: text.trim().replace(/\s+/g, ' '),
                            valor: valorNum,
                            valor_formatado: 'R$ ' + valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                            boleto_status: status || 'Em Aberto',
                            data_vencimento: vencimento || new Date().toISOString().slice(0, 10),
                            data_pagamento_sugerida: vencimento || new Date().toISOString().slice(0, 10),
                            ja_lancado: false
                        });
                    }
                }
            });
            renderSugestoesModal();
        })
        .catch(err => {
            console.error('Erro na extração client-side:', err);
            renderSugestoesModal();
        });
});



window.renderHistoryPagination = function (currentPage, totalPages) {
    if (totalPages <= 1) {
        $('#historyPagination').html('');
        return;
    }

    let paginationHtml = '<ul class="pagination">';
    paginationHtml += `<li class="${currentPage === 1 ? 'disabled' : 'waves-effect'}"><a href="#!" onclick="${currentPage === 1 ? '' : 'window.renderHistoryPage(' + (currentPage - 1) + ')'}"><i class="material-icons">chevron_left</i></a></li>`;

    for (let i = 1; i <= totalPages; i++) {
        paginationHtml += `<li class="${i === currentPage ? 'active blue' : 'waves-effect'}"><a href="#!" onclick="window.renderHistoryPage(${i})">${i}</a></li>`;
    }

    paginationHtml += `<li class="${currentPage === totalPages ? 'disabled' : 'waves-effect'}"><a href="#!" onclick="${currentPage === totalPages ? '' : 'window.renderHistoryPage(' + (currentPage + 1) + ')'}"><i class="material-icons">chevron_right</i></a></li>`;
    paginationHtml += '</ul>';

    $('#historyPagination').html(paginationHtml);
}


function jsonToTable(jsonData) {
    // console.log(jsonData);
    const missingValueReplacement = '';
    var tableHtml = '<table class="centered striped case-headers">';

    // Cabeçalho da tabela
    tableHtml += '<thead><tr>';
    tableHtml += '<th>Seq.</th>';
    for (var key in jsonData[0]) {
        tableHtml += '<th>' + key + '</th>';
    }
    tableHtml += '</tr></thead>';

    // Corpo da tabela
    tableHtml += '<tbody>';
    for (var i = 0; i < jsonData.length; i++) {
        let rec = jsonData[i].numero_ano_virtual;
        tableHtml += '<tr class="recurso" rec="' + rec + '">';
        tableHtml += '<td>' + (i + 1) + '</td>';
        for (var key in jsonData[i]) {
            let valor = jsonData[i][key] === null ? missingValueReplacement : jsonData[i][key];
            tableHtml += '<td>' + valor + '</td>';
        }
        tableHtml += '</tr>';
    }
    tableHtml += '</tbody>';

    tableHtml += '</table>';
    return tableHtml;
}

function ajustaValores(data) {
    console.log(data);
    let bloco = $("#bloco");
    let unidade = $("#unidade");
    let titulo = $("#titulo");

    bloco.val(data.torre);
    unidade.val(data.unidade);
    titulo.val(data.assunto);
    M.FormSelect.init(document.querySelector("#bloco"));
}

// Recupera e padroniza o transform original do Materialize, removendo o valor 'none' indesejado
function getOriginalTransform($img) {
    if (!$img.length) return '';
    var orig = $img.data('originalTransform');
    if (orig === undefined) {
        var el = $img[0];
        orig = el ? (el.style.transform || '') : '';
        if (orig === 'none') {
            orig = '';
        }
        $img.data('originalTransform', orig);
    }
    return orig === 'none' ? '' : orig;
}

// Intercepta e previne o scroll da página quando o materialbox estiver aberto, direcionando para o zoom
window.addEventListener('wheel', function(e) {
    if (window.isMaterialboxOpen) {
        e.preventDefault();
        
        var $img = $('.materialboxed.active');
        if ($img.length) {
            var el = $img[0];
            var scale = parseFloat($img.data('scale') || 1);
            var originalTransform = getOriginalTransform($img);
            
            // Normaliza o delta da roda do mouse de forma segura
            var deltaY = e.deltaY;
            if (e.wheelDelta) {
                deltaY = -e.wheelDelta;
            }
            if (e.detail) {
                deltaY = e.detail;
            }
            
            // deltaY < 0 significa scroll para cima (zoom in), deltaY > 0 significa scroll para baixo (zoom out)
            var delta = deltaY < 0 ? 0.25 : -0.25;
            scale = Math.min(Math.max(scale + delta, 1), 5);
            
            $img.data('scale', scale);
            
            if (scale === 1) {
                $img.data('translateX', 0);
                $img.data('translateY', 0);
            }
            
            var tx = parseFloat($img.data('translateX') || 0);
            var ty = parseFloat($img.data('translateY') || 0);
            
            el.style.transform = originalTransform + ' scale(' + scale + ') translate(' + tx + 'px, ' + ty + 'px)';
            console.log('Wheel zoom deltaY:', deltaY, 'scale:', scale);
        }
    }
}, { passive: false });

window.addEventListener('scroll', function(e) {
    if (window.isMaterialboxOpen) {
        e.stopImmediatePropagation();
    }
}, true);

// Bloqueia qualquer clique de fechar o visualizador (como no overlay ou na imagem) no capture phase.
// O fechamento fica restrito EXCLUSIVAMENTE ao botão de fechar 'X' ou tecla ESC, permitindo cliques nos controles.
window.addEventListener('click', function(e) {
    if (window.isMaterialboxOpen && e.target) {
        if (typeof e.target.closest === 'function') {
            // Se o clique for dentro do container de controle, permite a propagação normal
            if (e.target.closest('#materialbox-controls')) {
                return;
            }
            // Se clicar no botão de fechar ou dentro dele, permite fechar
            if (e.target.closest('#mb-close') || (e.target.closest('.material-icons') && e.target.textContent.trim() === 'close')) {
                return;
            }
        }
        
        // Bloqueia outros cliques (overlay, imagem, etc)
        e.stopImmediatePropagation();
        e.preventDefault();
    }
}, true); // true = capturing phase!

// Inicializa o materialbox com callbacks personalizados para controle de zoom e barra de botões
function initMaterialboxed() {
    $('.materialboxed').materialbox({
        onOpenStart: function(el) {
            window.isMaterialboxOpen = true;
            document.body.style.overflow = 'hidden';
            
            var $img = $(el);
            $img.data('scale', 1);
            $img.data('translateX', 0);
            $img.data('translateY', 0);
            $img.data('isDragging', false);
            $img.data('hasMoved', false);
            
            $img.css({
                'cursor': 'grab',
                'transition': 'transform 0.1s ease-out, left 0.3s, top 0.3s, width 0.3s, height 0.3s'
            });
        },
        onOpenEnd: function(el) {
            var $img = $(el);
            // Salva o transform original gerado pelo Materialize
            getOriginalTransform($img);
            
            // Adiciona a barra de controles flutuantes
            $('#materialbox-controls').remove();
            $('body').append(`
                <div id="materialbox-controls" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 10005; background: rgba(0,0,0,0.85); padding: 8px 16px; border-radius: 30px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); transition: opacity 0.3s ease;">
                    <button class="btn-floating btn-flat btn-small white-text" id="mb-zoom-in" style="background: transparent; margin:0;" title="Aumentar Zoom"><i class="material-icons">add</i></button>
                    <button class="btn-floating btn-flat btn-small white-text" id="mb-zoom-out" style="background: transparent; margin:0;" title="Diminuir Zoom"><i class="material-icons">remove</i></button>
                    <button class="btn-floating btn-flat btn-small white-text" id="mb-zoom-reset" style="background: transparent; margin:0;" title="Ajustar / Resetar"><i class="material-icons">crop_free</i></button>
                    <button class="btn-floating btn-flat btn-small white-text" id="mb-close" style="background: transparent; margin:0;" title="Fechar"><i class="material-icons">close</i></button>
                </div>
            `);
        },
        onCloseStart: function(el) {
            window.isMaterialboxOpen = false;
            document.body.style.overflow = '';
            
            var $img = $(el);
            var orig = getOriginalTransform($img);
            if (orig) {
                el.style.transform = orig;
            } else {
                el.style.transform = 'none';
            }
            $img.css('cursor', '');
            
            // Remove a barra de controles
            $('#materialbox-controls').fadeOut(200, function() {
                $(this).remove();
            });
        }
    });
}

// Ações para a barra de controles flutuantes
$(document).on('click', '#mb-zoom-in', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $img = $('.materialboxed.active');
    if ($img.length) {
        var el = $img[0];
        var scale = parseFloat($img.data('scale') || 1);
        scale = Math.min(scale + 0.3, 5);
        $img.data('scale', scale);
        
        var tx = parseFloat($img.data('translateX') || 0);
        var ty = parseFloat($img.data('translateY') || 0);
        var originalTransform = getOriginalTransform($img);
        el.style.transform = originalTransform + ' scale(' + scale + ') translate(' + tx + 'px, ' + ty + 'px)';
    }
});

$(document).on('click', '#mb-zoom-out', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $img = $('.materialboxed.active');
    if ($img.length) {
        var el = $img[0];
        var scale = parseFloat($img.data('scale') || 1);
        scale = Math.max(scale - 0.3, 1);
        $img.data('scale', scale);
        
        if (scale === 1) {
            $img.data('translateX', 0);
            $img.data('translateY', 0);
        }
        
        var tx = parseFloat($img.data('translateX') || 0);
        var ty = parseFloat($img.data('translateY') || 0);
        var originalTransform = getOriginalTransform($img);
        el.style.transform = originalTransform + ' scale(' + scale + ') translate(' + tx + 'px, ' + ty + 'px)';
    }
});

$(document).on('click', '#mb-zoom-reset', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $img = $('.materialboxed.active');
    if ($img.length) {
        var el = $img[0];
        $img.data('scale', 1);
        $img.data('translateX', 0);
        $img.data('translateY', 0);
        var originalTransform = getOriginalTransform($img);
        el.style.transform = originalTransform || 'none';
    }
});

$(document).on('click', '#mb-close', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $img = $('.materialboxed.active');
    if ($img.length) {
        var instance = M.Materialbox.getInstance($img[0]);
        if (instance) instance.close();
    }
});

// Manipuladores de mouse para arrastar (pan) a imagem com zoom (suporta clique do meio ou clique esquerdo quando com zoom)
var startX, startY;
$(document).on('mousedown', '.materialboxed.active', function(e) {
    var $img = $(this);
    // e.button === 0: clique esquerdo (permite pan se scale > 1)
    // e.button === 1: clique do meio (roda) - permite pan sempre
    if (($img.data('scale') > 1 && e.button === 0) || e.button === 1) {
        e.preventDefault();
        $img.data('isDragging', true);
        $img.data('hasMoved', false);
        $img.data('dragStartX', e.clientX);
        $img.data('dragStartY', e.clientY);
        startX = e.clientX - parseFloat($img.data('translateX') || 0) * $img.data('scale');
        startY = e.clientY - parseFloat($img.data('translateY') || 0) * $img.data('scale');
        $img.css('cursor', 'grabbing');
    }
});

$(document).on('mousemove', function(e) {
    var $img = $('.materialboxed.active');
    if ($img.length && $img.data('isDragging')) {
        var el = $img[0];
        var scale = $img.data('scale');
        
        var dx = Math.abs(e.clientX - $img.data('dragStartX'));
        var dy = Math.abs(e.clientY - $img.data('dragStartY'));
        if (dx > 5 || dy > 5) {
            $img.data('hasMoved', true);
        }
        
        var tx = (e.clientX - startX) / scale;
        var ty = (e.clientY - startY) / scale;
        
        $img.data('translateX', tx);
        $img.data('translateY', ty);
        
        var originalTransform = getOriginalTransform($img);
        el.style.transform = originalTransform + ' scale(' + scale + ') translate(' + tx + 'px, ' + ty + 'px)';
    }
});

$(document).on('mouseup mouseleave', '.materialboxed.active', function(e) {
    var $img = $(this);
    if ($img.data('isDragging')) {
        $img.data('isDragging', false);
        $img.css('cursor', 'grab');
    }
});

// Clique na imagem ativa para alternar entre 1x e 2.5x de zoom (se não tiver arrastado)
$(document).on('click', '.materialboxed.active', function(e) {
    var $img = $(this);
    if ($img.data('hasMoved')) {
        $img.data('hasMoved', false);
        return;
    }
    
    // Zoom toggle com botão esquerdo (e.button === 0)
    if (e.button === 0) {
        var el = this;
        var currentScale = parseFloat($img.data('scale') || 1);
        var newScale = currentScale > 1 ? 1 : 2.5;
        
        $img.data('scale', newScale);
        $img.data('translateX', 0);
        $img.data('translateY', 0);
        
        var originalTransform = getOriginalTransform($img);
        if (newScale === 1) {
            el.style.transform = originalTransform || 'none';
        } else {
            el.style.transform = originalTransform + ' scale(' + newScale + ') translate(0px, 0px)';
        }
    }
});

// Suporte a gestos touch em dispositivos móveis (pinch zoom e pan)
var touchStartDist = 0;
var touchStartScale = 1;
var touchStartX = 0, touchStartY = 0;
var touchStartTx = 0, touchStartTy = 0;
var isPinching = false;
var isTouchDragging = false;

$(document).on('touchstart', '.materialboxed.active', function(e) {
    var $img = $(this);
    var touches = e.originalEvent.touches;
    
    if (touches.length === 2) {
        isPinching = true;
        isTouchDragging = false;
        touchStartDist = Math.hypot(
            touches[0].clientX - touches[1].clientX,
            touches[0].clientY - touches[1].clientY
        );
        touchStartScale = parseFloat($img.data('scale') || 1);
    } else if (touches.length === 1 && $img.data('scale') > 1) {
        isTouchDragging = true;
        isPinching = false;
        $img.data('hasMoved', false);
        touchStartX = touches[0].clientX;
        touchStartY = touches[0].clientY;
        $img.data('dragStartX', touches[0].clientX);
        $img.data('dragStartY', touches[0].clientY);
        touchStartTx = parseFloat($img.data('translateX') || 0);
        touchStartTy = parseFloat($img.data('translateY') || 0);
    }
});

$(document).on('touchmove', '.materialboxed.active', function(e) {
    var $img = $(this);
    var el = this;
    var touches = e.originalEvent.touches;
    var originalTransform = getOriginalTransform($img);
    
    if (isPinching && touches.length === 2) {
        e.preventDefault();
        e.stopPropagation();
        
        var dist = Math.hypot(
            touches[0].clientX - touches[1].clientX,
            touches[0].clientY - touches[1].clientY
        );
        var factor = dist / touchStartDist;
        var scale = Math.min(Math.max(touchStartScale * factor, 1), 5);
        
        $img.data('scale', scale);
        
        if (scale === 1) {
            $img.data('translateX', 0);
            $img.data('translateY', 0);
        }
        
        var tx = parseFloat($img.data('translateX') || 0);
        var ty = parseFloat($img.data('translateY') || 0);
        
        el.style.transform = originalTransform + ' scale(' + scale + ') translate(' + tx + 'px, ' + ty + 'px)';
    } else if (isTouchDragging && touches.length === 1 && $img.data('scale') > 1) {
        e.preventDefault();
        e.stopPropagation();
        
        var scale = $img.data('scale');
        var dx = touches[0].clientX - touchStartX;
        var dy = touches[0].clientY - touchStartY;
        
        var adx = Math.abs(touches[0].clientX - $img.data('dragStartX'));
        var ady = Math.abs(touches[0].clientY - $img.data('dragStartY'));
        if (adx > 5 || ady > 5) {
            $img.data('hasMoved', true);
        }
        
        var tx = touchStartTx + (dx / scale);
        var ty = touchStartTy + (dy / scale);
        
        $img.data('translateX', tx);
        $img.data('translateY', ty);
        
        el.style.transform = originalTransform + ' scale(' + scale + ') translate(' + tx + 'px, ' + ty + 'px)';
    }
});

$(document).on('touchend', '.materialboxed.active', function(e) {
    isPinching = false;
    isTouchDragging = false;
});

// Renderiza uma linha de sugestão de multa evitando duplicatas
function renderSingleSuggestionRow(s) {
    if (!s || !s.numero_ano) return;
    const rowId = `sug-row-${s.numero_ano.replace(/[^a-zA-Z0-9]/g, '-')}`;
    if ($(`#${rowId}`).length > 0) return;

    const statusBoleto = s.boleto_status || 'N/A';
    let statusBadge = 'blue';
    if (statusBoleto.toLowerCase().includes('liquidado') || statusBoleto.toLowerCase().includes('pago')) {
        statusBadge = 'green';
    } else if (statusBoleto.toLowerCase().includes('aberto') || statusBoleto.toLowerCase().includes('vencido')) {
        statusBadge = 'red';
    }

    const dtVencFmt = s.data_vencimento ? moment(s.data_vencimento).format('DD/MM/YYYY') : '-';
    
    let btnAcao = '';
    if (s.ja_lancado) {
        btnAcao = `<span class="badge green white-text" style="border-radius:4px; padding:4px 10px; font-weight:bold;">✓ Já Lançado</span>`;
    } else {
        btnAcao = `<button type="button" class="btn-small green waves-effect waves-light btn-confirmar-sugestao-multa" 
                    data-id="${s.numero_ano}" 
                    data-valor="${s.valor}" 
                    data-vencimento="${s.data_vencimento}" 
                    data-pagamento="${s.data_pagamento_sugerida || ''}">
                        <i class="material-icons left" style="font-size:1.1rem; margin-right:4px;">check_circle</i> Confirmar Lançamento
                   </button>`;
    }

    const html = `
        <tr id="${rowId}">
            <td><b class="teal-text text-darken-2" style="font-size:1.05rem;">#${s.numero_ano}</b></td>
            <td class="left-align" style="font-size:0.85rem;">${s.item_descricao}</td>
            <td><b class="green-text text-darken-3" style="font-size:1.05rem;">${s.valor_formatado}</b></td>
            <td>${dtVencFmt}</td>
            <td><span class="badge ${statusBadge} white-text" style="border-radius:4px;">${statusBoleto}</span></td>
            <td>${btnAcao}</td>
        </tr>
    `;
    $('#boletos-sugestoes-tbody').append(html);
    $('#boletos-sugestoes-container').removeClass('hide');
}




// Manipulador do modal de inspeção de boletos da unidade
$(document).on('click', '.btn-inspecionar-boletos', function (e) {
    e.preventDefault();
    const bloco = $(this).data('bloco') || '';
    const unidade = $(this).data('unidade') || '';
    const ano = $(this).data('ano') || new Date().getFullYear();

    $('#boletos-modal-subtitle').html(`<b>Unidade:</b> Bloco ${bloco} - Apt ${unidade} &nbsp;|&nbsp; <b>Ano:</b> ${ano}`);
    $('#boletos-loading').removeClass('hide');
    $('#boletos-empty').addClass('hide');
    $('#boletos-sugestoes-container').addClass('hide');
    $('#boletos-sugestoes-tbody').html('');
    $('#boletos-cards-container').html('').addClass('hide');

    const modalElem = $('#modal-inspecionar-boletos');
    const instance = M.Modal.getInstance(modalElem);
    if (instance) {
        instance.open();
    } else {
        modalElem.modal().modal('open');
    }

    $.ajax({
        url: `metodo.php?metodo=buscarBoletosUnidade&bloco=${encodeURIComponent(bloco)}&unidade=${encodeURIComponent(unidade)}&ano=${encodeURIComponent(ano)}`,
        method: 'GET',
        dataType: 'json',
        success: function (res) {
            $('#boletos-loading').addClass('hide');

            // 1. Renderizar Cards de Boletos imediatamente
            if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
                let cardsHtml = '';
                res.data.forEach(function (b) {
                    const statusStr = b.status || 'N/A';
                    let statusBadgeClass = 'blue';
                    if (statusStr.toLowerCase().includes('liquidado') || statusStr.toLowerCase().includes('pago')) {
                        statusBadgeClass = 'green';
                    } else if (statusStr.toLowerCase().includes('aberto') || statusStr.toLowerCase().includes('vencido')) {
                        statusBadgeClass = 'red';
                    }

                    const dtVenc = b.dtVencimento ? moment(b.dtVencimento).format('DD/MM/YYYY') : '-';
                    const valorNum = parseFloat(b.valor || 0);
                    const valorFmt = isNaN(valorNum) ? '-' : 'R$ ' + valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const descStr = b.descricao || 'Taxa / Lançamento';

                    // Verificar se há indícios de Multa/RI na descrição
                    const isMultaRI = /multa|infra[çc]|regimento|ri\b|penalidade/i.test(descStr + ' ' + (b.msgReserva || ''));
                    const badgeMulta = isMultaRI ? `<span class="new badge red pulse" data-badge-caption="MULTA / RI" style="margin-left:5px"></span>` : '';

                    const btnLink = b.urlSegundaVia ? 
                        `<a href="${b.urlSegundaVia}" target="_blank" rel="noopener noreferrer" class="btn waves-effect waves-light teal" style="width:100%; margin-top:12px;">
                            <i class="material-icons left">open_in_new</i> Visualizar Boleto (2ª Via)
                         </a>` : 
                        `<button class="btn disabled" style="width:100%; margin-top:12px;">Link indisponível</button>`;

                    cardsHtml += `
                        <div class="col s12 m6 l4">
                            <div class="card hoverable z-depth-1" style="border-radius: 8px; border: 1px solid #e0e0e0; overflow:hidden;">
                                <div class="card-content" style="padding: 16px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                                        <span class="badge ${statusBadgeClass} white-text" style="margin:0; border-radius:4px; font-weight:bold;">${statusStr}</span>
                                        ${badgeMulta}
                                    </div>
                                    <span class="card-title truncate" style="font-size: 1.1rem; font-weight: bold; margin-bottom: 8px;" title="${descStr}">${descStr}</span>
                                    <div style="font-size: 0.9rem; color: #555;">
                                        <div><i class="material-icons tiny">event</i> <b>Vencimento:</b> ${dtVenc}</div>
                                        <div><i class="material-icons tiny">attach_money</i> <b>Valor:</b> <span class="teal-text text-darken-2" style="font-size:1.1rem; font-weight:bold;">${valorFmt}</span></div>
                                        ${b.nomeSacado ? `<div class="truncate" style="font-size:0.8rem; margin-top:4px;" title="${b.nomeSacado}"><i class="material-icons tiny">person</i> ${b.nomeSacado}</div>` : ''}
                                    </div>
                                    ${btnLink}
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#boletos-cards-container').html(cardsHtml).removeClass('hide');

                // 2. Disparar verificações assíncronas em segundo plano para cada boleto que possui urlSegundaVia
                res.data.forEach(function (b) {
                    if (b.urlSegundaVia) {
                        // A. Verificação via backend API
                        $.ajax({
                            url: 'metodo.php?metodo=extrairSugestoesBoleto',
                            method: 'GET',
                            data: {
                                url: b.urlSegundaVia,
                                status: b.status || '',
                                dtVencimento: b.dtVencimento || ''
                            },
                            dataType: 'json',
                            success: function (sugRes) {
                                if (sugRes && sugRes.success && Array.isArray(sugRes.sugestoes) && sugRes.sugestoes.length > 0) {
                                    sugRes.sugestoes.forEach(function (s) {
                                        renderSingleSuggestionRow(s);
                                    });
                                }
                            }
                        });

                        // B. Verificação Dinâmica pelo DOMParser do Navegador (Client-side)
                        fetch(`metodo.php?metodo=proxyFaturaHtml&url=${encodeURIComponent(b.urlSegundaVia)}`)
                            .then(response => response.text())
                            .then(htmlText => {
                                if (!htmlText || htmlText.length < 50) return;
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(htmlText, 'text/html');

                                const corpo = doc.querySelector('#corpoComposicao') || doc.body;
                                const elements = corpo.querySelectorAll('tr, div, p, li');

                                elements.forEach(el => {
                                    const text = el.textContent || '';
                                    // Filtro estrito: A linha DEVE conter termos de penalidade/multa para evitar falso-positivo em taxas mensais (ex: 08/2026)
                                    const isMulta = /multa|infra[çc]|notifica[çc][ãa]o|not\b|penalidade|regimento|ri\b/i.test(text);
                                    const matchNum = text.match(/(\d+)\/(\d{2,4})/);
                                    const matchVal = text.match(/R\$\s*([\d\.,]+)/i);

                                    if (isMulta && matchNum && matchVal) {
                                        const numero = matchNum[1];
                                        const rawAno = matchNum[2];
                                        const ano = rawAno.length === 2 ? '20' + rawAno : rawAno;
                                        const valorCleanStr = matchVal[1].replace(/\./g, '').replace(',', '.');
                                        const valorNum = parseFloat(valorCleanStr);

                                        if (!isNaN(valorNum) && valorNum > 0) {
                                            renderSingleSuggestionRow({
                                                numero: numero,
                                                ano: ano,
                                                numero_ano: `${numero}/${ano}`,
                                                item_descricao: text.trim().replace(/\s+/g, ' '),
                                                valor: valorNum,
                                                valor_formatado: 'R$ ' + valorNum.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                                                boleto_status: b.status || 'Em Aberto',
                                                data_vencimento: b.dtVencimento || moment().format('YYYY-MM-DD'),
                                                data_pagamento_sugerida: b.dtVencimento || moment().format('YYYY-MM-DD'),
                                                ja_lancado: false
                                            });
                                        }
                                    }
                                });
                            })
                            .catch(err => console.error('Erro no parser dinâmico do navegador:', err));
                    }
                });
            } else {
                $('#boletos-empty').removeClass('hide');
            }
        },
        error: function () {
            $('#boletos-loading').addClass('hide');
            $('#boletos-empty').removeClass('hide').find('p').text('Erro ao conectar à API VDS para buscar boletos.');
        }
    });
});

// Confirmar sugestão automatizada de lançamento de multa
$(document).on('click', '.btn-confirmar-sugestao-multa', function (e) {
    e.preventDefault();
    const $btn = $(this);
    const id = $btn.data('id');
    const valor = $btn.data('valor');
    const dataVencimento = $btn.data('vencimento');
    const dataPagamento = $btn.data('pagamento') || '';

    $btn.prop('disabled', true).addClass('disabled').html('<i class="material-icons left">hourglass_empty</i> Lançando...');

    $.ajax({
        url: 'metodo.php?metodo=upsertMultaCobrada',
        method: 'POST',
        data: {
            id: id,
            valor: valor,
            data_vencimento: dataVencimento,
            data_pagamento: dataPagamento
        },
        success: function (response) {
            if (response === 'success') {
                M.toast({ html: `Multa <b>${id}</b> lançada com sucesso!`, classes: 'green rounded' });
                $btn.replaceWith('<span class="badge green white-text" style="border-radius:4px; padding:4px 10px; font-weight:bold;">✓ Lançamento Confirmado</span>');
                
                // Atualizar visualmente a linha na tabela principal se existir
                const $row = $(`tr[data-id="${id}"]`);
                if ($row.length > 0) {
                    $row.addClass('tr-multa-cobrada');
                }

                // Recarregar os dados do Toolset da Unidade se o formulário estiver ativo
                if ($('#unidade').val() && $('#bloco').val()) {
                    $('#buscaHistoricoUnidade').click();
                }
            } else {
                $btn.prop('disabled', false).removeClass('disabled').html('<i class="material-icons left">check_circle</i> Confirmar Lançamento');
                M.toast({ html: 'Erro ao lançar multa: ' + response, classes: 'red rounded' });
            }
        },
        error: function (xhr, status, error) {
            $btn.prop('disabled', false).removeClass('disabled').html('<i class="material-icons left">check_circle</i> Confirmar Lançamento');
            M.toast({ html: 'Erro de comunicação: ' + error, classes: 'red rounded' });
        }
    });
});