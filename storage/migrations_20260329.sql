-- Migrations for RecMan updates

-- 1. Update diligencia table to support Gmail integration and status
ALTER TABLE `diligencia` ADD COLUMN IF NOT EXISTS `gmail_id` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `diligencia` ADD COLUMN IF NOT EXISTS `enviada_ao_requerente` TINYINT(1) DEFAULT 0;

-- 2. Table for diligence attachments
CREATE TABLE IF NOT EXISTS `diligencia_anexos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_diligencia` BIGINT UNSIGNED NOT NULL,
  `nome_arquivo` VARCHAR(255) NOT NULL,
  `caminho_arquivo` TEXT NOT NULL,
  `data_envio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_diligencia`) REFERENCES `diligencia` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Table for linking resources to digital occurrences
CREATE TABLE IF NOT EXISTS `recurso_ocorrencia` (
  `id_recurso` BIGINT(20) UNSIGNED NOT NULL,
  `id_ocorrencia` INT NOT NULL,
  PRIMARY KEY (`id_recurso`, `id_ocorrencia`),
  CONSTRAINT `fk_recurso_ocorrencia_recurso` FOREIGN KEY (`id_recurso`) REFERENCES `recurso` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Table for email configurations (Board/Management)
CREATE TABLE IF NOT EXISTS `config_emails_diretoria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bloco` CHAR(1) DEFAULT NULL, -- NULL for global/sindico, A-F for subsindicos
  `funcao` ENUM('sindico', 'subsindico', 'administracao') NOT NULL,
  `nome` VARCHAR(255),
  `email` VARCHAR(255),
  `ativo` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Table for global system settings
CREATE TABLE IF NOT EXISTS `config_sistema` (
    `chave` VARCHAR(100) PRIMARY KEY,
    `valor` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Table for comment attachments
CREATE TABLE IF NOT EXISTS `mensagem_anexos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_mensagem` BIGINT(20) UNSIGNED NOT NULL,
  `nome_arquivo` VARCHAR(255) NOT NULL,
  `caminho_arquivo` TEXT NOT NULL,
  `data_envio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_mensagem`) REFERENCES `mensagem` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Initial default configs
INSERT IGNORE INTO `config_sistema` (`chave`, `valor`) VALUES ('copiar_subsindicos_diligencia', '0');
INSERT IGNORE INTO `config_sistema` (`chave`, `valor`) VALUES ('copiar_subsindicos_parecer', '0');
INSERT IGNORE INTO `config_sistema` (`chave`, `valor`) VALUES ('copiar_subsindicos_novo_recurso', '1');
INSERT IGNORE INTO `config_sistema` (`chave`, `valor`) VALUES ('gemini_api_key', '');
INSERT IGNORE INTO `config_sistema` (`chave`, `valor`) VALUES ('gemini_system_prompt', 'Você é um assistente de inteligência artificial encarregado de redigir pareceres formais e profissionais para o Conselho Consultivo e Fiscal de um condomínio residencial de alto padrão.\nSeu objetivo é sugerir textos profissionais e bem redigidos para cada campo do Parecer com base nas informações fornecidas.\n\nInstruções importantes para a redação:\n1. O estilo deve ser extremamente formal, profissional, claro e objetivo, adequado para um parecer administrativo/jurídico de condomínio de luxo.\n2. Evite linguagem informal. Utilize a norma padrão da língua portuguesa (Português do Brasil).\n3. Adapte a fundamentação e a análise à decisão do Conselho:\n   - Se for MANTER, justifique tecnicamente por que a multa/advertência está de acordo com as regras e por que a defesa do morador não prospera.\n   - Se for REVOGAR, explique com razoabilidade por que a infração deve ser desconsiderada/anulada.\n   - Se for CONVERTER, fundamente a aplicação da conversão da multa para advertência (ex: por primariedade ou menor gravidade do fato).\n4. O campo \'\'assunto\'\' deve conter o título formal (ex: "Parecer do Conselho - Recurso de Notificação nº [NUMERO_RECURSO]").\n5. O campo \'\'notificacao\'\' deve resumir formalmente o fato ocorrido.\n6. O campo \'\'analise\'\' deve conter a fundamentação confrontando os argumentos do morador com o regimento interno.\n7. O campo \'\'resultado\'\' deve conter as considerações finais.\n8. O campo \'\'conclusao\'\' deve conter a decisão e veredito final em letras maiúsculas.');
