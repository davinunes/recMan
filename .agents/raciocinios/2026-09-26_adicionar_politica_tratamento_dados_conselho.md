# Raciocínio: Inclusão do Capítulo de Política de Tratamento de Dados do Conselho

## Requisito
Compor um novo capítulo no documento de justificativa de acesso a dados (`docs/inspect/ptd-conselho.txt`), posicionado imediatamente antes do Apêndice, descrevendo a Política de Tratamento de Dados do Conselho Consultivo e Fiscal.

## Mapeamento do Sistema (`recMan`)
Com base no levantamento das tabelas do banco de dados local (`recurso`, `diligencia`, `documento_oficial`, `ocorrencia_leitura_conselheiro`, `vds_uuid_mapping`) e da arquitetura de integração via API v8 (Vida de Síndico - VDS):

1. **Dados ANOTADOS (Armazenamento Local)**:
   - Processos de Recursos Administrativos: Notificação, bloco, unidade, nome/e-mail/telefone do recorrente, razões de defesa e documentos anexados pelo condômino.
   - Atos de Julgamento: Relator, pareceres opinativos, votos, atas, notificações por e-mail e histórico de trâmite.
   - Documentos Oficiais: Orientações Técnicas, Pareceres Opinativos, Instruções Normativas.
   - Metadados: Controle relacional de leitura por conselheiro e registros de auditoria interna.

2. **Dados NÃO ANOTADOS (Apenas Visualizados via API VDS v8)**:
   - Eventos de Acesso: Registros de portaria, entradas/saídas de pedestres/veículos, leituras faciais e placas.
   - Livro de Ocorrências e Chamados VDS: Fotos anexadas remotamente e chat operacionais.
   - Cadastros Expandidos: Fotos de perfil, dependentes, veículos, QR Codes e boletos mantidos no servidor da Administradora.

3. **Garantias de Sigilo e Não Compartilhamento**:
   - Ausência de cessão de dados para terceiros alheios ao condomínio (salvo operadores com DPA/Termo de Compromisso firmado segundo a LGPD).
   - Sigilo absoluto dos processos de recurso: defesas, anexos e pareceres individuais NÃO são compartilhados com outros moradores (acesso exclusivo ao recorrente, administração/síndico e conselheiros).
   - Divulgação assemblear feita exclusivamente em formato anonimizado ou abstrato.

## Atualização Efetuada
O capítulo `Política de Tratamento e Retenção de Dados do Conselho` foi adicionado ao arquivo `docs/inspect/ptd-conselho.txt` imediatamente antes do Apêndice.
