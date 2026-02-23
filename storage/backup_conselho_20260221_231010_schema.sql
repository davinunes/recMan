-- MariaDB dump 10.19  Distrib 10.5.19-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: conselho
-- ------------------------------------------------------
-- Server version	10.5.19-MariaDB-0+deb11u2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `conselho`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `conselho` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `conselho`;

--
-- Table structure for table `DatasDeRetirada`
--

DROP TABLE IF EXISTS `DatasDeRetirada`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DatasDeRetirada` (
  `bloco` enum('A','B','C','D','E','F') DEFAULT NULL,
  `apartamento` int(10) unsigned DEFAULT NULL CHECK (`apartamento` between 101 and 1912),
  `notificacao` int(10) unsigned NOT NULL,
  `ano` int(10) unsigned NOT NULL,
  `dia_retirada` date DEFAULT NULL,
  `obs` text DEFAULT NULL,
  `virtual` varchar(20) GENERATED ALWAYS AS (concat(`notificacao`,'/',`ano`)) STORED,
  PRIMARY KEY (`notificacao`,`ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `diligencia`
--

DROP TABLE IF EXISTS `diligencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `diligencia` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` bigint(20) unsigned NOT NULL,
  `id_recurso` bigint(20) unsigned NOT NULL,
  `texto` varchar(1500) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_recurso` (`id_recurso`),
  CONSTRAINT `diligencia_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `diligencia_ibfk_2` FOREIGN KEY (`id_recurso`) REFERENCES `recurso` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `estacionamento`
--

DROP TABLE IF EXISTS `estacionamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `estacionamento` (
  `id_estacionamento` int(11) NOT NULL,
  `bloco` enum('A','B','C','D','E','F') DEFAULT NULL,
  `unidade` int(11) DEFAULT NULL CHECK (`unidade` > 0),
  `bloco_unidade` varchar(10) DEFAULT NULL,
  `tipo` text DEFAULT NULL,
  `local` text DEFAULT NULL,
  PRIMARY KEY (`id_estacionamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fase`
--

DROP TABLE IF EXISTS `fase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fase` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID da Fase',
  `texto` varchar(100) NOT NULL COMMENT 'Descrição da Fase',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='enumera as fases do recurso';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mensagem`
--

DROP TABLE IF EXISTS `mensagem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mensagem` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID da Mensagem',
  `id_usuario` bigint(20) unsigned NOT NULL COMMENT 'ID do Usuario que postou a Mensagem',
  `id_recurso` bigint(20) unsigned NOT NULL COMMENT 'ID do recurso ao qual a mensagem referencia',
  `texto` varchar(1500) NOT NULL COMMENT 'Corpo da Mensagem, que pode ser um endereço de um arquivo também',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data e hora a qual a mensagem foi postada',
  PRIMARY KEY (`id`),
  KEY `mensagem_FK_recurso` (`id_recurso`),
  KEY `mensagem_FK_usuario` (`id_usuario`),
  CONSTRAINT `mensagem_FK_recurso` FOREIGN KEY (`id_recurso`) REFERENCES `recurso` (`id`),
  CONSTRAINT `mensagem_FK_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=606 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `multas_cobradas`
--

DROP TABLE IF EXISTS `multas_cobradas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `multas_cobradas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unidade` int(11) NOT NULL,
  `bloco` enum('A','B','C','D','E','F') NOT NULL,
  `data_vencimento` date NOT NULL,
  `data_pagamento` date DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `numero` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_numero_ano` (`numero`,`ano`),
  KEY `idx_unidade_bloco` (`unidade`,`bloco`),
  KEY `idx_data_vencimento` (`data_vencimento`),
  KEY `idx_data_pagamento` (`data_pagamento`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notificacoes`
--

DROP TABLE IF EXISTS `notificacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificacoes` (
  `numero` int(11) NOT NULL,
  `torre` enum('A','B','C','D','E','F') DEFAULT NULL,
  `unidade` int(11) DEFAULT NULL,
  `data_email` date DEFAULT NULL,
  `data_envio` date DEFAULT NULL,
  `data_ocorrido` date DEFAULT NULL,
  `assunto` text DEFAULT NULL,
  `notificacao` text DEFAULT NULL,
  `ano` int(11) NOT NULL,
  `numero_ano_virtual` varchar(15) GENERATED ALWAYS AS (concat(`numero`,'/',`ano`)) STORED,
  `cobranca` text DEFAULT NULL,
  `status` text DEFAULT NULL,
  `obs` text DEFAULT NULL,
  PRIMARY KEY (`numero`,`ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ocorrencias`
--

DROP TABLE IF EXISTS `ocorrencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ocorrencias` (
  `id` int(11) NOT NULL,
  `abertura` datetime NOT NULL,
  `mes` tinyint(2) GENERATED ALWAYS AS (month(`abertura`)) STORED,
  `bloco` enum('A','B','C','D','E','F','Z') DEFAULT NULL,
  `unidade` varchar(100) DEFAULT NULL,
  `sindico` tinyint(1) DEFAULT 0,
  `sub` tinyint(1) DEFAULT 0,
  `adm` tinyint(1) DEFAULT 0,
  `responsabilidade` enum('sindico','sub') DEFAULT NULL,
  `status` varchar(100) DEFAULT 'Aberto',
  `url` varchar(2083) DEFAULT NULL,
  `conselheiro_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conselheiro_ids`)),
  `ultimaAtualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolvido` tinyint(1) NOT NULL DEFAULT 0,
  `total_mensagens` int(11) NOT NULL DEFAULT 0,
  `data_ultima_mensagem` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mes` (`mes`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parecer`
--

DROP TABLE IF EXISTS `parecer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parecer` (
  `id` varchar(255) NOT NULL,
  `data` date DEFAULT curdate(),
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `unidade` text DEFAULT NULL,
  `resultado` text DEFAULT 'Considerações finais a serem realizadas',
  `assunto` text DEFAULT NULL,
  `notificacao` text DEFAULT 'Fato narrado na cópia da Notificação',
  `analise` text DEFAULT 'Foram apreciadas as provas apresentadas pela administração e confrontadas com a argumentação e demais fatos descritos no recurso',
  `conclusao` text DEFAULT NULL,
  `concluido` tinyint(1) DEFAULT NULL,
  `mailId` text DEFAULT NULL,
  `quemFinalizou` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recurso`
--

DROP TABLE IF EXISTS `recurso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recurso` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador da trupa na tabela',
  `unidade` int(11) NOT NULL COMMENT 'Numero da unidade de 101 a 1912',
  `bloco` char(1) NOT NULL COMMENT 'bloco de A a F',
  `numero` varchar(100) NOT NULL COMMENT 'Numero do recurso gerado pelo condominio',
  `artigo` varchar(100) DEFAULT NULL COMMENT 'Numero do artigo relacionado no Regimento',
  `fase` int(11) NOT NULL DEFAULT 0 COMMENT 'Numero do estágio no qual este recurso se encontra',
  `email` varchar(300) DEFAULT NULL COMMENT 'endereço de email para o qual o recurso deverá ser enviado quando finalizado',
  `Nome` varchar(100) DEFAULT NULL COMMENT 'Nome da pessoa que entrou com recurso',
  `detalhes` text DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL COMMENT 'Titulo do Recurso a ser exibido em listas',
  `data` date DEFAULT NULL COMMENT 'Data de interposição do recurso',
  `fato` text DEFAULT 'Fato narrado na cópia da Notificação',
  PRIMARY KEY (`id`),
  UNIQUE KEY `recurso_UN` (`numero`),
  KEY `recurso_unidade_IDX` (`unidade`,`bloco`,`numero`,`id`) USING BTREE,
  KEY `recurso_FK_fase` (`fase`),
  CONSTRAINT `recurso_FK_fase` FOREIGN KEY (`fase`) REFERENCES `fase` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=513 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tokens`
--

DROP TABLE IF EXISTS `tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `access_token` text NOT NULL,
  `expires_in` int(11) NOT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `token_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `refresh_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6586 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID do usuário',
  `email` varchar(100) NOT NULL COMMENT 'e-mail do usuario',
  `senha` varchar(1024) NOT NULL COMMENT 'Hash da senha',
  `nome` varchar(100) DEFAULT NULL COMMENT 'Nome completo do usuário',
  `status` tinyint(1) DEFAULT 1 COMMENT 'Se o usuário está ativo',
  `unidade` varchar(100) DEFAULT NULL COMMENT 'Unidade do Usuário',
  `avatar` varchar(200) DEFAULT NULL COMMENT 'Foto ou avatar do usuário',
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuarios_UN` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `votos`
--

DROP TABLE IF EXISTS `votos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `votos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id do voto',
  `id_recurso` bigint(20) unsigned NOT NULL COMMENT 'id do recurso',
  `id_usuario` bigint(20) unsigned NOT NULL COMMENT 'ID do usuario que registrou o voto',
  `voto` enum('manter','revogar','converter') NOT NULL COMMENT 'Opção do Voto',
  `data` timestamp NULL DEFAULT current_timestamp() COMMENT 'Data do registro do Voto',
  PRIMARY KEY (`id`),
  UNIQUE KEY `votos_usuario_recurso` (`id_recurso`,`id_usuario`),
  KEY `votos_FK_usuario` (`id_usuario`),
  KEY `votos_id_IDX` (`id`,`id_recurso`,`id_usuario`) USING BTREE,
  CONSTRAINT `votos_FK_recurso` FOREIGN KEY (`id_recurso`) REFERENCES `recurso` (`id`),
  CONSTRAINT `votos_FK_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1417 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-21 23:10:10
