$(document).ready(function(){
	
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
	$('.chips').chips();
	$('.sidenav').sidenav();
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
			url:"datatable_br.json"
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
			url:"datatable_br.json"
		},
			initComplete: function () {
                // Adiciona estilo para posicionar a caixa de pesquisa à esquerda
                $('.dataTables_filter').css('text-align', 'left');
                
                // Ajusta a altura das linhas após a inicialização
                $('.dataTables_filter tbody tr').css('height', '5px');
            }
    });
	
	$('#avatar').on('change', function() {
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
		  top: ($(this).offset().top - $popup.height()*1.38) + 'px'
		});

  });

  $('.recurso').on('mouseleave', function () {
    $popup.css('display', 'none');
  });
	
});

$(document).on('click', '.edit-user', function() {
    const userId = $(this).attr('userid-data');
    
    // Carrega os dados do usuário via AJAX
    $.ajax({
        url: 'metodo.php?metodo=carregarUsuario&id=' + userId,
        method: "GET",
        success: function(response) {
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
                M.toast({html: 'Erro ao carregar usuário', classes: 'rounded red'});
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus, errorThrown);
            M.toast({html: 'Erro ao carregar usuário', classes: 'rounded red'});
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
        success: function (response) {
            M.toast({ html: response, classes: 'rounded' });
            if (response === "ok") {
                setTimeout(function () {
                    location.reload();
                }, 1500);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            M.toast({ html: 'Erro ao salvar alterações', classes: 'rounded red' });
        }
    });
});



$(document).on('click', '#novoUsuario', function() { // Inserir novo Usuário
    let metodo = "novoUsuario";
	const formData = $("#formNewUser").serializeArray();
	console.log(formData);
    
    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
        data: formData, // Adicione o objeto 'data' aqui
        success: function(responseData) {
            M.toast({html: responseData, classes: 'rounded'});
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '#newRecurso', function() { // Inserir novo Usuário
    let metodo = "novoRecurso";
    const formData = $("#formNewRecurso").serializeArray();
    console.log(formData);

    // Verificar se os campos obrigatórios estão preenchidos
    let camposObrigatorios = ["unidade", "bloco", "numero", "fase", "data"]; // Adicione aqui os nomes dos campos obrigatórios

    let camposVazios = camposObrigatorios.filter(function(campo) {
        return formData.find(function(item) {
            return item.name === campo && item.value === "";
        });
    });

    if (camposVazios.length > 0) {
        // Exibir um toast informando que os campos obrigatórios não foram preenchidos
        M.toast({html: 'Por favor, preencha todos os campos obrigatórios.', classes: 'rounded red'});
        return; // Impedir o envio da solicitação AJAX
    }

    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
        data: formData, // Adicione o objeto 'data' aqui
        success: function(responseData) {
            M.toast({html: responseData, classes: 'rounded'});
            window.location.href = "index.php";
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '#testeEnvioParecer', function() {
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
            success: function(responseData) {
                M.toast({html: responseData, classes: 'rounded'});
                $("#mime").text(responseData);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Erro na solicitação AJAX: " + textStatus);
                console.log("Detalhes do erro: " + errorThrown);
            }
        });
    } else {
        // Se o usuário não confirmou, você pode fazer alguma coisa aqui, ou apenas retornar
        console.log("Operação cancelada pelo usuário.");
    }
});

$(document).on('click', '#EnviaRelatorioJuridico', function() {
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
            success: function(responseData) {
                M.toast({html: responseData, classes: 'rounded'});
                $("#mime").text(responseData);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Erro na solicitação AJAX: " + textStatus);
                console.log("Detalhes do erro: " + errorThrown);
            }
        });
    } else {
        // Se o usuário não confirmou, você pode fazer alguma coisa aqui, ou apenas retornar
        console.log("Operação cancelada pelo usuário.");
    }
});

$(document).on('click', '#finalizaEnviaParecer', function() {
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
            success: function(responseData) {
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
                            success: function(finalizaResponse) {
                                // Verifica se a resposta da segunda chamada é 'ok'
                                if (finalizaResponse.trim().toLowerCase() === 'ok') {
                                    // Se 'ok', atualiza a página
                                    location.reload();
                                } else {
                                    // Exibe um toast informando que algo deu errado
                                    M.toast({html: 'Erro ao finalizar o parecer.', classes: 'rounded'});
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.log("Erro na segunda solicitação AJAX: " + textStatus);
                                console.log("Detalhes do erro: " + errorThrown);
                                // Exibe um toast informando que algo deu errado
                                M.toast({html: 'Erro ao finalizar o parecer.', classes: 'rounded'});
                            }
                        });
                    } else {
                        // Se 'mailId' não existe, exibe um toast informando que algo deu errado
                        M.toast({html: 'Erro ao enviar o e-mail. Resposta inválida.', classes: 'rounded'});
                    }
                } catch (error) {
                    // Exibe um toast informando que algo deu errado ao analisar a resposta JSON
                    M.toast({html: 'Erro ao analisar a resposta JSON.', classes: 'rounded'});
                    console.log("Erro ao analisar a resposta JSON: " + error);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Erro na primeira solicitação AJAX: " + textStatus);
                console.log("Detalhes do erro: " + errorThrown);
                // Exibe um toast informando que algo deu errado
                M.toast({html: 'Erro ao enviar o e-mail.', classes: 'rounded'});
            }
        });
    } else {
        // Se o usuário não confirmou, você pode fazer alguma coisa aqui, ou apenas retornar
        console.log("Operação cancelada pelo usuário.");
    }
});

$(document).on('click', '#btnAlterarParecer', function() { // Enviar e-mail
	$("#previaPDF").hide();
	$("#formParecer").removeClass("hide");
	$(this).remove();
	$("#testeEnvioParecer").remove();
	$("#finalizaEnviaParecer").remove();
	
});

$(document).on('click', '#btnSalvarParecer', function() { // Enviar e-mail
	let formData = $("#formParecer form").serializeArray();
	console.log(formData);
	
    $.ajax({
        url: "metodo.php?metodo=editaParecer",
        method: "POST", // Defina o método como POST
		data: formData,
        // data: formData, // Adicione o objeto 'data' aqui
        success: function(responseData) {
			if(responseData === "ok"){
				M.toast({html: responseData, classes: 'rounded'});
				window.location.reload();
			}else{
				M.toast({html: responseData, classes: 'rounded'});
				// window.location.reload();
				
			}

        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
	
});

$(document).on('click', '.editComment', function() { // Enviar e-mail


	function ajustarAlturaTextarea() {
		let textarea = $("#messageTextComment")[0];
		textarea.style.height = "auto"; // Redefinir a altura para auto
		textarea.style.height = textarea.scrollHeight + "px"; // Definir a altura com base no conteúdo
	}
	
    let comentario = $(this).closest("li.collection-item").find("p").html();
	
	
	comentario = comentario.replace(/<br\s*\/?>/gi, "\n");
	console.log(comentario);
	$("#messageTextComment").val(comentario);
	
	ajustarAlturaTextarea();
	
	$("#messageTextComment").attr("message_id",$(this).attr("comment"));
	
	
});

$(document).on('click', '#updateComment', function() { // Enviar e-mail
    let comentario = $("#messageTextComment").val();
	let id_comentario = $("#messageTextComment").attr("message_id");
	
	const formData = { 
		id_comentario: id_comentario,
		comentario:comentario
	};
	console.log(formData);
    $.ajax({
        url: "metodo.php?metodo=editaComentario",
        method: "POST", // Defina o método como POST
		data: formData,
        // data: formData, // Adicione o objeto 'data' aqui
        success: function(responseData) {
			if(responseData === "ok"){
				M.toast({html: responseData, classes: 'rounded'});
				window.location.reload();
			}else{
				M.toast({html: responseData, classes: 'rounded'});
				// window.location.reload();
				
			}

        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
	
	
});

$(document).on('click', '#logon', function() { // Logar Usuario
    let metodo = "logon";
	const formData = $("#loginForm").serializeArray();
	console.log(formData);
    
    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
        data: formData, // Adicione o objeto 'data' aqui
        success: function(responseData) {
			if(responseData === "ok"){
				M.toast({html: responseData, classes: 'rounded'});
				window.location.reload();
			}else{
				M.toast({html: responseData, classes: 'rounded'});
				// window.location.reload();
				
			}
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '#logout', function() { // DesLogar Usuario
    let metodo = "logout";
	    
    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "GET", // Defina o método como POST
        // data: formData, // Adicione o objeto 'data' aqui
        success: function(responseData) {
			window.location.reload();

        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '#comentar', function() { // Inserir mensagem no Recurso
    let metodo = "novoComentario";
	let idRec = $('#idRecurso').attr('idRec');
	const formData = $("#postMessageForm").serializeArray();
	formData.push({ name: 'id_recurso', value: idRec }); // Adiciona o idRec ao formData
	console.log(formData);
	    
    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
		data: formData,
        // data: formData, // Adicione o objeto 'data' aqui
        success: function(responseData) {
			if(responseData === "ok"){
				M.toast({html: responseData, classes: 'rounded'});
				window.location.reload();
			}else{
				M.toast({html: responseData, classes: 'rounded'});
				// window.location.reload();
				
			}

        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '#diligenciar', function() { // Inserir diligencia no Recurso
    let metodo = "novaDiligencia";
	let idRec = $('#idRecurso').attr('idRec');
	const formData = $("#postDiligenciaForm").serializeArray();
	formData.push({ name: 'id_recurso', value: idRec }); // Adiciona o idRec ao formData
	console.log(formData);
	    
    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
		data: formData,
        // data: formData, // Adicione o objeto 'data' aqui
        success: function(responseData) {
			if(responseData === "ok"){
				M.toast({html: responseData, classes: 'rounded'});
				window.location.reload();
			}else{
				M.toast({html: responseData, classes: 'rounded'});
				// window.location.reload();
				
			}

        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '.opVoto', function() { // Inserir mensagem no Recurso
    let metodo = "votar";
	let idRec = $('#idRecurso').attr('idRec');
	let voto = $(this).attr('voto');
	const formData = { 
				voto: voto,
				idRec:idRec
			};
	console.log(formData);
	    
    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
		data: formData,
        data: formData, // Adicione o objeto 'data' aqui
        success: function(responseData) {
			if(responseData === "ok"){
				M.toast({html: responseData, classes: 'rounded'});
				window.location.reload();
			}else{
				M.toast({html: responseData, classes: 'rounded'});
				// window.location.reload();
				
			}

        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('click', '.recFase', function() { // Altera a fase do recurso
    let metodo = "mudaFase";
	let idRec = $('#idRecurso').attr('idRec');
	let fase = $(this).attr('fase');
	const formData = { 
				fase: fase,
				idRec:idRec
			};
	console.log(formData);
	    
    // Realizar a solicitação GET para obter os dados desejados
    let url = 'metodo.php?metodo=' + metodo;
    $.ajax({
        url: url,
        method: "POST", // Defina o método como POST
		data: formData,
        data: formData, // Adicione o objeto 'data' aqui
        success: function(responseData) {
			if(responseData === "ok"){
				M.toast({html: responseData, classes: 'rounded'});
				window.location.reload();
			}else{
				M.toast({html: responseData, classes: 'rounded'});
				// window.location.reload();
				
			}

        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log("Erro na solicitação AJAX: " + textStatus);
            console.log("Detalhes do erro: " + errorThrown);
        }
    });
});

$(document).on('dblclick', '.recurso', function() { // Inserir novo Usuário
    let metodo = "recurso";
	let recurso  = $(this).attr("rec");
    
    // Realizar a solicitação GET para obter os dados desejados
    let url = 'index.php?pag=' + metodo+'&rec='+recurso;
	console.log(url);
	
	window.location.href = url;

});

$(document).on('click', '.recurso', function() { // Inserir novo Usuário
    M.toast({html: "Clique duplo para entrar ", classes: 'rounded'});

});

$(document).on('submit', '#changePasswordForm', function(event) {
        event.preventDefault();
        
        var currentPassword = $('#currentPassword').val();
        var newPassword = $('#newPassword').val();
        var confirmPassword = $('#confirmPassword').val();
		
		if(newPassword != confirmPassword){
			M.toast({html: "Nova senha e confirmação não são iguais ", classes: 'rounded'});
			return;
		}
		
        // Exemplo de chamada AJAX para enviar os dados ao servidor
        $.post('metodo.php?metodo=trocaSenha', {
            currentPassword: currentPassword,
            newPassword: newPassword
        }, function(response) {
			if(response === "ok"){
				M.toast({html: response, classes: 'rounded'});
				// window.location.reload();
			}else{
				M.toast({html: response, classes: 'rounded'});
				// window.location.reload();
				
			}
        });
    });

$(document).on('submit', '#updateThisUser', function(e) {
         e.preventDefault(); // Impede o envio padrão do formulário
        
        // Obtém os dados do formulário
        var formData = new FormData(this);

        // Obtém a base64 do avatar dos dados do formulário
        // var avatarBase64 = $(this).data('avatar');
        // if (avatarBase64) {
            // formData.append('avatarBase64', avatarBase64); // Adiciona a base64 aos dados do formulário
        // }

        // Envia os dados usando AJAX
        $.ajax({
            type: 'POST',
            url: 'metodo.php?metodo=updateThisUser',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
				if(response === "ok"){
					M.toast({html: response, classes: 'rounded'});
					window.location.reload();
				}else{
					M.toast({html: response, classes: 'rounded'});
					// window.location.reload();
					
				}
            },
            error: function(xhr, status, error) {
                // Lida com erros
                console.error(error);
            }
        });
});

$(document).on('keyup','#unidade', function(event) {
	
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

$(document).on('keyup', '#numero', function(event) {
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
            success: function(response) {
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
            error: function(xhr, status, error) {
                // Lida com erros
                console.error(error);
            }
        });
    }
});

$(document).on('keyup', '.fato', function(event) {
    var entrada = $(this).val();

    // Remover quebras de linha e manter apenas um espaço entre palavras
    entrada = entrada.replace(/[\r\n]+/g, ' ').replace(/\s+/g, ' ');

    // Agora, você pode usar a variável 'entrada' conforme necessário
    $(this).val(entrada);
});


$(document).on('submit', '#atualizarRecursoForm', function(e) {
         e.preventDefault(); // Impede o envio padrão do formulário
        
        // Obtém os dados do formulário
		
        var formData = new FormData(this);
		var numeroValue = formData.get('numero');
        // Envia os dados usando AJAX
        $.ajax({
            type: 'POST',
            url: 'metodo.php?metodo=atualizarRecurso',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
				if(response === "ok"){
					M.toast({html: response, classes: 'rounded'});
					window.location.href = "index.php?pag=recurso&rec=" + numeroValue;
				}else{
					M.toast({html: response, classes: 'rounded'});
					// window.location.reload();
					
				}
            },
            error: function(xhr, status, error) {
                // Lida com erros
                console.error(error);
            }
        });
});

$(document).on('dblclick', '.edit-retirado', function(e) {
	var id = $(this).data('id');
	var originalValue = $(this).text();
	var formattedDate = originalValue.split('/').reverse().join('-');
	
	// Substituir a célula pelo input
	$(this).html('<input type="date" class="edit-retirado-input" value="' + formattedDate  + '">');
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
					M.toast({html: response, classes: 'rounded'});
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

$(document).on('dblclick', '.edit-multa-cobrada', function(e) {
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
$(document).on('click', '#salvar-multa', function(e) {
    var id = $('#modal-multa-id').val();
    var valor = $('#valor-multa').val();
    var dataVencimento = $('#data-vencimento').val();
    var dataPagamento = $('#data-pagamento').val();
    
    // Validação básica - apenas valor e data de vencimento são obrigatórios
    if (!valor || !dataVencimento) {
        M.toast({html: 'Valor e Data de Vencimento são obrigatórios!', classes: 'red rounded'});
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
        success: function(response) {
            if (response === 'success') {
                M.toast({html: 'Multa salva com sucesso!', classes: 'green rounded'});
                $('#modal-multa').modal('close');
                $(`tr[data-id="${id}"]`).remove();
                // location.reload();
            } else {
                M.toast({html: 'Erro ao salvar: ' + response, classes: 'red rounded'});
            }
        },
        error: function(xhr, status, error) {
            M.toast({html: 'Erro de conexão: ' + error, classes: 'red rounded'});
        }
    });
});

$(document).on('click', '.parecer', function(e) {
    var $this = $(this);
    var total = -1;

    $("#listaSolucoes tr").each(function() {
        total++;
    });
    
    // Inicializar ou incrementar contador
    if (!$this.data('clickCount')) {
        $this.data('clickCount', 1);
        
        // Resetar após 1 segundo
        setTimeout(function() {
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
            $('.parecer').each(function() {
                if ($(this).text().trim() === valorParecer) {
                    count++;
                }
            });
            
            // Remover todas as linhas com o mesmo parecer
            $('.parecer').each(function() {
                if ($(this).text().trim() === valorParecer) {
                    $(this).closest('tr').fadeOut(300, function() {
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

            $("#listaSolucoes_info").html("Total de itens: "+ total);
            
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

$(document).on('click', '#buscaHistoricoUnidade', function(e) {
	
	var unidade = $("#unidade").val();
	var bloco = $("#bloco").val();
	
	$.ajax({
		url: 'metodo.php?metodo=historicoPorUnidade&unidade='+unidade+'&torre='+bloco,
		method: 'POST',
		data: "",
		dataType: 'json',
		success: function (response) {
			// M.toast({html: response, classes: 'rounded'});
			let tableHtml = jsonToTable(response);
			console.log(tableHtml);
			$('#listaRetorno1').html(tableHtml);
			
		},
		error: function () {
			console.log('Erro de requisição AJAX');
		}
	});
});

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
        tableHtml += '<tr class="recurso" rec="'+rec+'">';
		tableHtml += '<td>' + (i+1) + '</td>';
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