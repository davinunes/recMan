<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - recMan / Gestão de Notificações</title>
    
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="meu.css?<?php echo time(); ?>">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script defer src="meu.js?<?php echo time(); ?>"></script>
</head>
<body style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; padding: 20px;">

    <div class="container">
        <div class="row" style="margin-bottom: 0;">
            <div class="col s12 m8 offset-m2 l6 offset-l3">
                <div class="card z-depth-4" style="border-radius: 12px; overflow: hidden; background: #ffffff;">
                    <div style="background: #1565c0; padding: 28px 24px; text-align: center; color: white;">
                        <i class="material-icons" style="font-size: 3.5rem; margin-bottom: 5px;">gavel</i>
                        <h4 style="margin: 0; font-weight: bold; font-size: 1.8rem; letter-spacing: 0.5px;">Recurso Management</h4>
                        <p style="margin: 6px 0 0 0; opacity: 0.88; font-size: 0.95rem;">Painel do Conselho & Gestão de Notificações</p>
                    </div>
                    
                    <div class="card-content" style="padding: 28px 24px 20px 24px;">
                        <form id="loginForm" onsubmit="event.preventDefault(); $('#logon').click();">
                            <div class="input-field" style="margin-top: 10px;">
                                <i class="material-icons prefix blue-text text-darken-2">email</i>
                                <input type="email" id="email" name="email" class="validate" required autofocus>
                                <label for="email">E-mail de Acesso</label>
                            </div>

                            <div class="input-field" style="margin-top: 20px;">
                                <i class="material-icons prefix blue-text text-darken-2">lock</i>
                                <input type="password" id="password" name="password" class="validate" required>
                                <label for="password">Senha</label>
                            </div>

                            <div class="row" style="margin-top: 15px; margin-bottom: 25px;">
                                <div class="col s12">
                                    <label>
                                        <input type="checkbox" id="remember_device" name="remember_device" checked class="filled-in" />
                                        <span style="color: #424242; font-weight: 500;">Lembrar este dispositivo</span>
                                    </label>
                                </div>
                            </div>

                            <div class="input-field center" style="margin-bottom: 10px;">
                                <button id="logon" class="btn-large waves-effect waves-light blue darken-2" style="width: 100%; border-radius: 6px; font-weight: bold; font-size: 1.1rem; height: 50px; line-height: 50px;" type="button">
                                    Acessar Sistema <i class="material-icons right">send</i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            M.updateTextFields();
        });
    </script>

</body>
</html>
