# Walkthrough: Aplicação de Favicon no recMan

**Data**: 2026-09-10  
**Status**: Concluído  

## Resumo das Alterações

1. **Cópia de Arquivos para a Raiz (`/`)**:
   Os seguintes 7 arquivos foram copiados da pasta `favicon/` para a raiz do repositório (`e:\DEV\recMan\`):
   - [`apple-touch-icon.png`](file:///e:/DEV/recMan/apple-touch-icon.png)
   - [`favicon-32x32.png`](file:///e:/DEV/recMan/favicon-32x32.png)
   - [`favicon-16x16.png`](file:///e:/DEV/recMan/favicon-16x16.png)
   - [`favicon.ico`](file:///e:/DEV/recMan/favicon.ico)
   - [`site.webmanifest`](file:///e:/DEV/recMan/site.webmanifest)
   - [`android-chrome-192x192.png`](file:///e:/DEV/recMan/android-chrome-192x192.png)
   - [`android-chrome-512x512.png`](file:///e:/DEV/recMan/android-chrome-512x512.png)

2. **Inclusão das Tags no `<head>`**:
   Inseridas as marcas de favicon em 10 páginas principais do projeto:
   - [`index.php`](file:///e:/DEV/recMan/index.php)
   - [`forms/login.php`](file:///e:/DEV/recMan/forms/login.php)
   - [`portal/index.php`](file:///e:/DEV/recMan/portal/index.php)
   - [`git.php`](file:///e:/DEV/recMan/git.php)
   - [`backup.php`](file:///e:/DEV/recMan/backup.php)
   - [`finalizarPareceresEnviados.php`](file:///e:/DEV/recMan/finalizarPareceresEnviados.php)
   - [`ocorrenciasCondominioDigital/relatorio.php`](file:///e:/DEV/recMan/ocorrenciasCondominioDigital/relatorio.php)
   - [`ocorrenciasCondominioDigital/quantitativos.php`](file:///e:/DEV/recMan/ocorrenciasCondominioDigital/quantitativos.php)
   - [`recursosSemEmail.php`](file:///e:/DEV/recMan/recursosSemEmail.php)
   - [`tools/datasRetirada.php`](file:///e:/DEV/recMan/tools/datasRetirada.php)

## Estrutura de Tags Aplicada

```html
<!-- Favicon -->
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="shortcut icon" href="/favicon.ico">
<link rel="manifest" href="/site.webmanifest">
```

## Como Validar no Navegador
1. Recarregue qualquer uma das telas atualizadas limpando o cache (`Ctrl + F5` ou `Cmd + Shift + R`).
2. Verifique se o ícone da aba do navegador exibe a nova marca do sistema.
3. Teste o atalho "Adicionar à Tela Inicial" em um dispositivo móvel para verificar a leitura do `site.webmanifest` e do `apple-touch-icon.png`.
