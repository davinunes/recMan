# Raciocínio: Aplicação dos favicons no sistema recMan

**Data**: 2026-09-10  
**Tópico**: Instalação e configuração global de favicon (`.ico`, `.png`, `apple-touch-icon`, `site.webmanifest`) nas telas do sistema.

## 1. Contexto e Solicitação
O usuário forneceu o bloco de tags HTML para favicons:
```html
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
```
e indicou a pasta `e:\DEV\recMan\favicon`.

## 2. Análise da Estrutura
- A pasta `e:\DEV\recMan\favicon\` continha os 7 arquivos de ícone/manifesto:
  - `android-chrome-192x192.png`
  - `android-chrome-512x512.png`
  - `apple-touch-icon.png`
  - `favicon-16x16.png`
  - `favicon-32x32.png`
  - `favicon.ico`
  - `site.webmanifest`
- O arquivo `site.webmanifest` apontava para `/android-chrome-192x192.png` e `/android-chrome-512x512.png` na raiz `/`.
- Copiamos todos os arquivos da pasta `favicon/` para a raiz do repositório (`e:\DEV\recMan\`) para que ficassem disponíveis em URLs absolutas (`/apple-touch-icon.png`, `/favicon-32x32.png`, etc.).

## 3. Alterações Efetuadas nos Arquivos HTML/PHP
Foram atualizados as seções `<head>` dos seguintes arquivos principais:
1. `index.php` (painel principal do sistema)
2. `forms/login.php` (tela de login)
3. `portal/index.php` (central pública de recursos)
4. `git.php` (painel de controle de deploy)
5. `backup.php` (gerenciador de backups)
6. `finalizarPareceresEnviados.php`
7. `ocorrenciasCondominioDigital/relatorio.php`
8. `ocorrenciasCondominioDigital/quantitativos.php`
9. `recursosSemEmail.php`
10. `tools/datasRetirada.php`

## 4. Validação
- Verificado se os arquivos de favicons existem na raiz do projeto.
- Confirmada inclusão correta dos elementos `<link>` e `<link rel="shortcut icon" href="/favicon.ico">`.
