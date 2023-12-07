$(document).ready(function(){
	$('select').formSelect();
	$('.modal').modal();
	$('.chips').chips();
	$('.sidenav').sidenav();
	$('#listaRecursos').DataTable({
        searching: false, // Oculta o campo de busca
        paging: false, // Desativa a paginação
		"order": [
			[3, 'desc'] // Ordenação inicial pela primeira coluna em ordem ascendente
		]
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


$(document).on('click', '#btnAlterarParecer', function() { // Enviar e-mail
	$("#previaPDF").hide();
	$("#formParecer").removeClass("hide");
	$(this).remove();
	$("#testeEnvioParecer").remove();
	
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
    let comentario = $(this).closest("li.collection-item").find("p").text();
	console.log(comentario);
	$("#messageTextComment").val(comentario);
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
					// window.location.reload();
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
					console.log(response);
				if (response.length > 0 && response[0].ano) {
					console.log("faz algo");
					ajustaValores(response[0]);
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
					// window.location.reload();
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