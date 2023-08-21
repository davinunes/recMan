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

$(document).on('click', '.recurso', function() { // Inserir novo Usuário
    let metodo = "recurso";
	let recurso  = $(this).attr("rec");
    
    // Realizar a solicitação GET para obter os dados desejados
    let url = 'index.php?pag=' + metodo+'&rec='+recurso;
	console.log(url);
	
	window.location.href = url;

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