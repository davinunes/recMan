$(document).ready(function(){
	$('select').formSelect();
	$('.modal').modal();
	$('.chips').chips();
	
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
				// window.location.reload();
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

