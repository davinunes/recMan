<?php
require_once "classes/repositorio.php";

$cfg = getMagnacomConfig();
?>

<div class="container" style="margin-top: 25px; margin-bottom: 50px;">
    <!-- Cabeçalho -->
    <div class="row">
        <div class="col s12">
            <a href="index.php?pag=tools" class="btn-flat waves-effect waves-teal grey-text text-darken-1" style="padding-left: 0; margin-bottom: 10px;">
                <i class="material-icons left">arrow_back</i> Voltar para Ferramentas
            </a>
            <h4 style="margin-top: 5px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
                <i class="material-icons amber-text text-darken-3" style="font-size: 2.8rem;">cloud_sync</i>
                Configurações Magnacom & Supabase
            </h4>
            <p class="grey-text text-darken-1" style="font-size: 1.05rem;">
                Gerenciamento centralizado de credenciais, tokens JWT manuais e diagnósticos de conexão com o backend de notificações da Magnacom.
            </p>
        </div>
    </div>

    <!-- Alerta Informativo sobre a migração de sistema e Captcha -->
    <div class="row">
        <div class="col s12">
            <div class="card amber lighten-5 z-depth-1" style="border-left: 5px solid #ff8f00; border-radius: 6px;">
                <div class="card-content" style="padding: 16px 20px;">
                    <span style="font-weight: 600; font-size: 1.05rem; color: #b75300; display: flex; align-items: center; gap: 8px;">
                        <i class="material-icons">info</i> Nova Proteção Cloudflare / Turnstile na Magnacom
                    </span>
                    <p style="margin-top: 8px; color: #5d4037; font-size: 0.95rem; line-height: 1.5;">
                        A tela web de login da Magnacom adicionou proteção de bot com <strong>Cloudflare Turnstile</strong> (exigindo <code>nonce</code> e <code>captcha_token</code>). No entanto, as consultas aos recursos e anexos continuam ocorrendo diretamente no <strong>Supabase</strong> via Token JWT.
                        Aqui você pode testar se o login direto sem captcha ainda funciona na API ou colar os tokens gerados externamente para manter o sistema 100% operacional.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Linha Principal: Formulário de Credenciais Básicas -->
    <div class="row">
        <div class="col s12 m6">
            <div class="card z-depth-1" style="border-radius: 8px; height: 100%;">
                <div class="card-content">
                    <span class="card-title" style="font-weight: 600; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                        <i class="material-icons blue-text text-darken-2">dns</i> Credenciais do Supabase
                    </span>
                    <p class="grey-text" style="font-size: 0.9rem; margin-bottom: 20px;">
                        Configurações salvas diretamente na tabela <code>config_sistema</code> do banco de dados (dispensando a necessidade de editar o arquivo <code>.env</code>).
                    </p>

                    <form id="formCredenciaisMagnacom">
                        <div class="input-field">
                            <input type="text" id="supabaseUrlInput" value="<?php echo htmlspecialchars($cfg['url']); ?>" placeholder="https://xyzcompany.supabase.co">
                            <label for="supabaseUrlInput" class="active">URL do Projeto Supabase (SUPABASE_URL)</label>
                        </div>

                        <div class="input-field">
                            <input type="password" id="supabaseAnonKeyInput" value="<?php echo htmlspecialchars($cfg['anon_key']); ?>" placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...">
                            <label for="supabaseAnonKeyInput" class="active">Chave Pública / Anon Key (SUPABASE_ANON_KEY)</label>
                            <span class="helper-text">Usada no cabeçalho <code>apikey</code> em todas as requisições</span>
                        </div>

                        <div class="input-field">
                            <input type="email" id="supabaseEmailInput" value="<?php echo htmlspecialchars($cfg['email']); ?>" placeholder="usuario@magnacom.com.br">
                            <label for="supabaseEmailInput" class="active">E-mail de Serviço (SUPABASE_EMAIL)</label>
                        </div>

                        <div class="input-field">
                            <input type="password" id="supabasePasswordInput" value="<?php echo htmlspecialchars($cfg['password']); ?>" placeholder="••••••••">
                            <label for="supabasePasswordInput" class="active">Senha da Conta (SUPABASE_PASSWORD)</label>
                        </div>

                        <div style="margin-top: 25px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" class="btn blue darken-2 waves-effect waves-light" id="btnSalvarCredenciais">
                                <i class="material-icons left">save</i> Salvar Credenciais
                            </button>

                            <button type="button" class="btn purple darken-1 waves-effect waves-light" id="btnTestarLoginDirect">
                                <i class="material-icons left">flash_on</i> Testar Login Direto
                            </button>
                        </div>

                        <p style="margin-top: 15px;">
                            <label>
                                <input type="checkbox" id="chkAutoSalvarTokens" checked="checked" />
                                <span style="font-size: 0.88rem;">Salvar tokens automaticamente no banco caso o login direto tenha sucesso</span>
                            </label>
                        </p>
                    </form>

                    <!-- Resultado do Teste de Login Direto -->
                    <div id="resultadoLoginDirect" style="display: none; margin-top: 20px;"></div>
                </div>
            </div>
        </div>

        <!-- Coluna 2: Importação Manual de Tokens JSON -->
        <div class="col s12 m6">
            <div class="card z-depth-1" style="border-radius: 8px;">
                <div class="card-content">
                    <span class="card-title" style="font-weight: 600; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                        <i class="material-icons green-text text-darken-2">vpn_key</i> Tokens Manuais (JSON)
                    </span>
                    <p class="grey-text" style="font-size: 0.9rem; margin-bottom: 15px;">
                        Se a tela de login com Captcha bloquear a autenticação direta, autentique-se no navegador (DevTools / Postman) e cole aqui o objeto JSON retornado:
                    </p>

                    <div class="input-field">
                        <textarea id="tokensJsonInput" class="materialize-textarea" style="font-family: monospace; font-size: 0.88rem; min-height: 140px; background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #cfd8dc;"><?php 
                            if (!empty($cfg['tokens_json'])) {
                                echo htmlspecialchars($cfg['tokens_json']);
                            } else {
                                echo "{\n  \"access_token\": \"" . htmlspecialchars($cfg['access_token']) . "\",\n  \"refresh_token\": \"" . htmlspecialchars($cfg['refresh_token']) . "\",\n  \"grant\": \"" . htmlspecialchars($cfg['grant']) . "\"\n}";
                            }
                        ?></textarea>
                        <label for="tokensJsonInput" class="active">Objeto JSON com Tokens</label>
                        <span class="helper-text">Campos aceitos: <code>access_token</code>, <code>refresh_token</code> e <code>grant</code></span>
                    </div>

                    <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" class="btn green darken-2 waves-effect waves-light" id="btnSalvarTokensJson">
                            <i class="material-icons left">check_circle</i> Salvar Tokens do JSON
                        </button>

                        <button type="button" class="btn teal darken-1 waves-effect waves-light" id="btnRenovarRefreshToken" <?php echo empty($cfg['refresh_token']) ? 'disabled' : ''; ?>>
                            <i class="material-icons left">sync</i> Renovar via Refresh Token
                        </button>
                    </div>

                    <!-- Status do Token Atual -->
                    <div class="card grey lighten-4" style="margin-top: 20px; border-radius: 6px; padding: 12px;">
                        <div style="font-size: 0.85rem;">
                            <strong>Status do Token Ativo:</strong>
                            <div style="margin-top: 6px; word-break: break-all;">
                                <?php if (!empty($cfg['access_token'])): ?>
                                    <span class="new badge green" data-badge-caption="Token Configurado" style="float: none; margin-left: 0;"></span>
                                    <span class="grey-text text-darken-2" style="margin-left: 8px;">
                                        Última atualização: <strong><?php echo htmlspecialchars($cfg['token_updated_at'] ?: 'N/D'); ?></strong>
                                    </span>
                                    <div style="margin-top: 8px; font-family: monospace; font-size: 0.78rem; color: #37474f;">
                                        <?php echo htmlspecialchars(substr($cfg['access_token'], 0, 35) . '...' . substr($cfg['access_token'], -15)); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="new badge red" data-badge-caption="Nenhum Token Salvo" style="float: none; margin-left: 0;"></span>
                                    <span class="grey-text" style="margin-left: 8px;">Nenhum access_token manual ou automático registrado.</span>
                                <?php endif; ?>
                            </div>

                            <div style="margin-top: 10px; display: flex; gap: 15px; font-size: 0.85rem;">
                                <span>Refresh Token: <strong><?php echo !empty($cfg['refresh_token']) ? '✓ Presente' : '✗ Ausente'; ?></strong></span>
                                <span>Grant: <strong><?php echo !empty($cfg['grant']) ? htmlspecialchars(substr($cfg['grant'], 0, 15) . '...') : 'N/D'; ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Botão Testar API com Token Atual -->
                    <div style="margin-top: 15px;">
                        <button type="button" class="btn-small indigo darken-1 waves-effect waves-light" id="btnTestarConsultaApi">
                            <i class="material-icons left">cloud_queue</i> Testar Leitura da API (1 Notificação)
                        </button>
                    </div>

                    <div id="resultadoConsultaApi" style="display: none; margin-top: 15px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Seção Explicativa: O que é o Nonce e como funciona a Cloudflare -->
    <div class="row" style="margin-top: 20px;">
        <div class="col s12">
            <div class="card z-depth-1" style="border-radius: 8px;">
                <div class="card-content">
                    <span class="card-title" style="font-weight: 600; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
                        <i class="material-icons deep-purple-text">psychology</i> Entendendo os Parâmetros: O que é "nonce"?
                    </span>
                    <div class="row" style="margin-bottom: 0;">
                        <div class="col s12 m6">
                            <p style="font-size: 0.95rem; line-height: 1.6; color: #424242;">
                                <strong>Nonce</strong> é a abreviação técnica para <em>"Number used ONCE"</em> (número de uso único).
                                É um identificador pseudo-aleatório gerado dinamicamente para cada tentativa de login e associado ao desafio do captcha.
                            </p>
                            <p style="font-size: 0.95rem; line-height: 1.6; color: #424242; margin-top: 10px;">
                                <strong>Para que serve:</strong>
                            </p>
                            <ul class="browser-default" style="color: #616161; font-size: 0.9rem; line-height: 1.5; margin-left: 20px;">
                                <li><strong>Evita Ataques de Repetição (Replay Attacks):</strong> Impede que alguém intercepte sua requisição e reenvie os mesmos dados para logar de novo. Uma vez utilizado, o nonce é destruído pelo servidor.</li>
                                <li><strong>Vincula o Captcha:</strong> Garante que o <code>captcha_token</code> gerado pela Cloudflare foi resolvido exclusivamente para aquela tentativa e não reaproveitado.</li>
                                <li><strong>Expiração Rápida:</strong> Se não for usado em poucos minutos, perde a validade.</li>
                            </ul>
                        </div>
                        <div class="col s12 m6">
                            <div class="card-panel grey lighten-4" style="border-radius: 6px; padding: 15px; margin: 0;">
                                <span style="font-weight: 600; font-size: 0.95rem; color: #37474f;">Por que o recMan continua funcionando com Token?</span>
                                <p style="font-size: 0.88rem; color: #546e7a; margin-top: 8px; line-height: 1.5;">
                                    A verificação do Cloudflare Turnstile protege apenas a <strong>página de login</strong> contra robôs.
                                    Uma vez que o login é efetuado, o sistema emite um <strong>JSON Web Token (JWT)</strong> assinado.
                                    Esse token possui autorização direta sobre as tabelas e o storage do Supabase, permitindo que o recMan continue consultando e sincronizando notificações sem precisar resolver captchas repetidamente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 1. Salvar Credenciais Básicas
    $('#btnSalvarCredenciais').click(function() {
        const btn = $(this);
        const data = {
            url: $('#supabaseUrlInput').val(),
            anon_key: $('#supabaseAnonKeyInput').val(),
            email: $('#supabaseEmailInput').val(),
            password: $('#supabasePasswordInput').val()
        };

        btn.addClass('disabled').html('<i class="material-icons left">hourglass_empty</i> Salvando...');

        $.post('metodo.php?metodo=salvarConfigMagnacom', data, function(res) {
            btn.removeClass('disabled').html('<i class="material-icons left">save</i> Salvar Credenciais');
            if (res.success) {
                M.toast({html: 'Credenciais do Supabase salvas no banco com sucesso!', classes: 'green rounded'});
            } else {
                M.toast({html: 'Erro ao salvar: ' + (res.error || 'Erro desconhecido'), classes: 'red rounded'});
            }
        }, 'json').fail(function(xhr, status, error) {
            btn.removeClass('disabled').html('<i class="material-icons left">save</i> Salvar Credenciais');
            M.toast({html: 'Falha na comunicação com o servidor: ' + error, classes: 'red rounded'});
        });
    });

    // 2. Testar Login Direto com Usuário e Senha
    $('#btnTestarLoginDirect').click(function() {
        const btn = $(this);
        const autoSalvar = $('#chkAutoSalvarTokens').is(':checked') ? 1 : 0;
        const data = {
            url: $('#supabaseUrlInput').val(),
            anon_key: $('#supabaseAnonKeyInput').val(),
            email: $('#supabaseEmailInput').val(),
            password: $('#supabasePasswordInput').val(),
            auto_salvar: autoSalvar
        };

        btn.addClass('disabled').html('<i class="material-icons left">hourglass_empty</i> Testando...');
        $('#resultadoLoginDirect').slideDown().html(
            '<div class="card-panel blue lighten-5 blue-text text-darken-4" style="padding: 12px; border-radius: 4px;">' +
            '<i class="material-icons left">sync</i> Conectando diretamente ao endpoint /auth/v1/token?grant_type=password...' +
            '</div>'
        );

        $.post('metodo.php?metodo=testarLoginMagnacomDirect', data, function(res) {
            btn.removeClass('disabled').html('<i class="material-icons left">flash_on</i> Testar Login Direto');
            
            let html = '';
            if (res.success) {
                html += '<div class="card-panel green lighten-5 green-text text-darken-4" style="padding: 14px; border-radius: 6px; border-left: 4px solid #2e7d32;">';
                html += '<strong style="display: flex; align-items: center; gap: 6px;"><i class="material-icons">check_circle</i> ' + res.message + '</strong>';
                html += '<div style="margin-top: 8px; font-size: 0.88rem; color: #1b5e20;">';
                html += 'HTTP Code: <strong>' + res.httpCode + '</strong> | Latência: <strong>' + res.latencyMs + 'ms</strong><br>';
                if (res.expires_in) html += 'Validade do Token: <strong>' + res.expires_in + 's</strong><br>';
                if (res.user && res.user.email) html += 'Usuário Autenticado: <strong>' + res.user.email + '</strong><br>';
                html += '</div>';

                if (autoSalvar) {
                    html += '<div style="margin-top: 10px; font-weight: 500; color: #2e7d32;">✓ O novo token já foi gravado no banco de dados! Recarregue a página para atualizar o painel.</div>';
                }
                html += '</div>';
            } else {
                html += '<div class="card-panel red lighten-5 red-text text-darken-4" style="padding: 14px; border-radius: 6px; border-left: 4px solid #c62828;">';
                html += '<strong style="display: flex; align-items: center; gap: 6px;"><i class="material-icons">cancel</i> Falha no Login Direto (HTTP ' + (res.httpCode || '-') + ')</strong>';
                html += '<div style="margin-top: 8px; font-size: 0.9rem;">';
                html += '<strong>Mensagem:</strong> ' + (res.error || 'Erro desconhecido');
                html += ' | Latência: ' + (res.latencyMs || '-') + 'ms<br>';

                if (res.hasCaptcha) {
                    html += '<div style="margin-top: 10px; padding: 8px; background: rgba(198,40,40,0.1); border-radius: 4px; font-weight: 500;">';
                    html += '⚠️ O endpoint direto confirmou a exigência de CAPTCHA/Turnstile. Utilize o formulário ao lado para colar os tokens gerados via navegador.';
                    html += '</div>';
                }
                html += '</div>';
                html += '</div>';
            }
            $('#resultadoLoginDirect').html(html);
        }, 'json').fail(function(xhr, status, error) {
            btn.removeClass('disabled').html('<i class="material-icons left">flash_on</i> Testar Login Direto');
            $('#resultadoLoginDirect').html(
                '<div class="card-panel red lighten-5 red-text text-darken-4" style="padding: 12px; border-radius: 4px;">' +
                '<strong>Erro na requisição AJAX:</strong> ' + error +
                '</div>'
            );
        });
    });

    // 3. Salvar Tokens do Objeto JSON Colado
    $('#btnSalvarTokensJson').click(function() {
        const btn = $(this);
        const jsonText = $('#tokensJsonInput').val().trim();

        if (!jsonText) {
            M.toast({html: 'Por favor, cole o JSON com os tokens antes de salvar.', classes: 'orange rounded'});
            return;
        }

        btn.addClass('disabled').html('<i class="material-icons left">hourglass_empty</i> Processando...');

        $.post('metodo.php?metodo=salvarTokensMagnacomJson', { tokens_json: jsonText }, function(res) {
            btn.removeClass('disabled').html('<i class="material-icons left">check_circle</i> Salvar Tokens do JSON');
            if (res.success) {
                M.toast({html: res.message, classes: 'green rounded'});
                setTimeout(function() {
                    location.reload();
                }, 1200);
            } else {
                M.toast({html: 'Erro: ' + (res.error || 'Não foi possível salvar'), classes: 'red rounded'});
            }
        }, 'json').fail(function(xhr, status, error) {
            btn.removeClass('disabled').html('<i class="material-icons left">check_circle</i> Salvar Tokens do JSON');
            M.toast({html: 'Falha de comunicação: ' + error, classes: 'red rounded'});
        });
    });

    // 4. Renovar via Refresh Token
    $('#btnRenovarRefreshToken').click(function() {
        const btn = $(this);
        btn.addClass('disabled').html('<i class="material-icons left">hourglass_empty</i> Renovando...');

        $.post('metodo.php?metodo=testarRefreshTokenMagnacom', {}, function(res) {
            btn.removeClass('disabled').html('<i class="material-icons left">sync</i> Renovar via Refresh Token');
            if (res.success) {
                M.toast({html: 'Token JWT renovado com SUCESSO via refresh_token!', classes: 'green rounded'});
                setTimeout(function() {
                    location.reload();
                }, 1200);
            } else {
                M.toast({html: 'Falha na renovação: ' + (res.error || 'Erro desconhecido'), classes: 'red rounded'});
            }
        }, 'json').fail(function(xhr, status, error) {
            btn.removeClass('disabled').html('<i class="material-icons left">sync</i> Renovar via Refresh Token');
            M.toast({html: 'Falha de comunicação: ' + error, classes: 'red rounded'});
        });
    });

    // 5. Testar Consulta na API com o Token Ativo
    $('#btnTestarConsultaApi').click(function() {
        const btn = $(this);
        btn.addClass('disabled').html('<i class="material-icons left">hourglass_empty</i> Consultando...');
        $('#resultadoConsultaApi').slideDown().html(
            '<div class="card-panel grey lighten-4" style="padding: 10px; font-size: 0.88rem;">' +
            '<i class="material-icons left">sync</i> Testando GET /rest/v1/notificacoes no Supabase com o Bearer token...' +
            '</div>'
        );

        $.post('metodo.php?metodo=testarConsultaNotificacaoMagnacom', {}, function(res) {
            btn.removeClass('disabled').html('<i class="material-icons left">cloud_queue</i> Testar Leitura da API (1 Notificação)');

            let html = '';
            if (res.success) {
                html += '<div class="card-panel green lighten-5 green-text text-darken-4" style="padding: 14px; border-radius: 6px; border-left: 4px solid #2e7d32; font-size: 0.88rem;">';
                html += '<strong><i class="material-icons left" style="margin-right: 6px; font-size: 1.2rem;">check_circle</i> Consulta realizada com SUCESSO! (HTTP ' + res.httpCode + ' - ' + res.latencyMs + 'ms)</strong><br>';
                html += '<div style="margin-top: 6px; color: #1b5e20;">O token está 100% válido e autorizado a ler a tabela <code>notificacoes</code> no Supabase.</div>';
                
                if (res.colunas && res.colunas.length > 0) {
                    html += '<div style="margin-top: 10px;"><strong>Colunas Detectadas no Supabase (' + res.colunas.length + '):</strong><br>';
                    html += '<div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px;">';
                    res.colunas.forEach(function(col) {
                        html += '<span class="chip" style="height: 24px; line-height: 24px; font-size: 0.75rem; background-color: #c8e6c9; color: #1b5e20; font-family: monospace; padding: 0 8px; margin: 0;">' + col + '</span>';
                    });
                    html += '</div></div>';
                }

                if (res.exemplo_registro) {
                    html += '<div style="margin-top: 12px;">';
                    html += '<strong>Payload Bruto da Notificação (Exemplo):</strong>';
                    html += '<pre style="max-height: 250px; overflow-y: auto; background-color: #263238; color: #eceff1; padding: 10px; border-radius: 4px; font-size: 0.78rem; font-family: monospace; white-space: pre-wrap; margin-top: 6px;">' + JSON.stringify(res.exemplo_registro, null, 2) + '</pre>';
                    html += '</div>';
                }
                html += '</div>';
            } else {
                html += '<div class="card-panel red lighten-5 red-text text-darken-4" style="padding: 12px; border-radius: 4px; border-left: 4px solid #c62828; font-size: 0.88rem;">';
                html += '<strong>✗ Falha ao consultar a API (HTTP ' + (res.httpCode || '-') + ' - ' + (res.latencyMs || '-') + 'ms)</strong><br>';
                html += '<div style="margin-top: 6px;">' + (res.error || 'Erro desconhecido') + '</div>';
                html += '</div>';
            }
            $('#resultadoConsultaApi').html(html);
        }, 'json').fail(function(xhr, status, error) {
            btn.removeClass('disabled').html('<i class="material-icons left">cloud_queue</i> Testar Leitura da API (1 Notificação)');
            $('#resultadoConsultaApi').html(
                '<div class="card-panel red lighten-5 red-text text-darken-4" style="padding: 10px; font-size: 0.88rem;">' +
                'Falha de comunicação: ' + error +
                '</div>'
            );
        });
    });
});
</script>
