$(document).ready(function(){
	$('select').formSelect();
	$('.modal').modal();
	$('.chips').chips();
	$('.sidenav').sidenav();
	
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