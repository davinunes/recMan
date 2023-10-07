-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: 172.24.100.11    Database: conselho
-- ------------------------------------------------------
-- Server version	5.5.5-10.5.19-MariaDB-0+deb11u2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `fase`
--

DROP TABLE IF EXISTS `fase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fase` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID da Fase',
  `texto` varchar(100) NOT NULL COMMENT 'Descrição da Fase',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='enumera as fases do recurso';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fase`
--

LOCK TABLES `fase` WRITE;
/*!40000 ALTER TABLE `fase` DISABLE KEYS */;
/*!40000 ALTER TABLE `fase` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensagem`
--

DROP TABLE IF EXISTS `mensagem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensagem`
--

LOCK TABLES `mensagem` WRITE;
/*!40000 ALTER TABLE `mensagem` DISABLE KEYS */;
/*!40000 ALTER TABLE `mensagem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recurso`
--

DROP TABLE IF EXISTS `recurso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recurso` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador da trupa na tabela',
  `unidade` int(11) NOT NULL COMMENT 'Numero da unidade de 101 a 1912',
  `bloco` char(1) NOT NULL COMMENT 'bloco de A a F',
  `numero` varchar(100) NOT NULL COMMENT 'Numero do recurso gerado pelo condominio',
  `artigo` varchar(100) DEFAULT NULL COMMENT 'Numero do artigo relacionado no Regimento',
  `fase` int(11) NOT NULL DEFAULT 0 COMMENT 'Numero do estágio no qual este recurso se encontra',
  `email` varchar(300) DEFAULT NULL COMMENT 'endereço de email para o qual o recurso deverá ser enviado quando finalizado',
  `Nome` varchar(100) DEFAULT NULL COMMENT 'Nome da pessoa que entrou com recurso',
  `detalhes` varchar(1000) DEFAULT NULL COMMENT 'Detalhes sobre o recurso',
  `titulo` varchar(100) DEFAULT NULL COMMENT 'Titulo do Recurso a ser exibido em listas',
  PRIMARY KEY (`id`),
  KEY `recurso_unidade_IDX` (`unidade`,`bloco`,`numero`,`id`) USING BTREE,
  KEY `recurso_FK_fase` (`fase`),
  CONSTRAINT `recurso_FK_fase` FOREIGN KEY (`fase`) REFERENCES `fase` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recurso`
--

LOCK TABLES `recurso` WRITE;
/*!40000 ALTER TABLE `recurso` DISABLE KEYS */;
/*!40000 ALTER TABLE `recurso` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID do usuário',
  `email` varchar(100) NOT NULL COMMENT 'e-mail do usuario',
  `senha` varchar(150) NOT NULL COMMENT 'Hash da senha',
  `nome` varchar(100) DEFAULT NULL COMMENT 'Nome completo do usuário',
  `status` tinyint(1) DEFAULT 1 COMMENT 'Se o usuário está ativo',
  `unidade` varchar(100) DEFAULT NULL COMMENT 'Unidade do Usuário',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `votos`
--

DROP TABLE IF EXISTS `votos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `votos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id do voto',
  `id_recurso` bigint(20) unsigned NOT NULL COMMENT 'id do recurso',
  `id_usuario` bigint(20) unsigned NOT NULL COMMENT 'ID do usuario que registrou o voto',
  `voto` enum('manter','revogar','converter') NOT NULL COMMENT 'Opção do Voto',
  `data` timestamp NULL DEFAULT current_timestamp() COMMENT 'Data do registro do Voto',
  PRIMARY KEY (`id`),
  KEY `votos_FK_usuario` (`id_usuario`),
  KEY `votos_FK_recurso` (`id_recurso`),
  KEY `votos_id_IDX` (`id`,`id_recurso`,`id_usuario`) USING BTREE,
  CONSTRAINT `votos_FK_recurso` FOREIGN KEY (`id_recurso`) REFERENCES `recurso` (`id`),
  CONSTRAINT `votos_FK_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `votos`
--

LOCK TABLES `votos` WRITE;
/*!40000 ALTER TABLE `votos` DISABLE KEYS */;
/*!40000 ALTER TABLE `votos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'conselho'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-08-19 18:32:14
