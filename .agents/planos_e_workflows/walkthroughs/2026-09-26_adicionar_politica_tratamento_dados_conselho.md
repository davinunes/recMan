# Walkthrough: Inclusão do Capítulo de Política de Tratamento de Dados do Conselho

## Resumo das Modificações

O arquivo [`docs/inspect/ptd-conselho.txt`](file:///e:/DEV/recMan/docs/inspect/ptd-conselho.txt) foi atualizado com a adição do capítulo **"Política de Tratamento e Retenção de Dados do Conselho"**, posicionado imediatamente antes do Apêndice.

### Estrutura do Novo Capítulo

1. **Dados que o Conselho ANOTA (Armazenamento Local)**:
   - Dados de recursos administrativos (número da notificação, unidade, bloco, nome, e-mail, razões de defesa e arquivos anexados pelo recorrente);
   - Registros de trâmite e julgamento (relator, pareceres opinativos, votos, atas e logs de envio de e-mails);
   - Documentos oficiais editados pelo Conselho (Orientações Técnicas, Pareceres Opinativos, Instruções Normativas);
   - Controle relacional de leitura por conselheiro.

2. **Dados que o Conselho NÃO ANOTA (Apenas Visualização Via API)**:
   - Registros em tempo real de acessos de portaria (pedestres, veículos, leitores faciais e placas);
   - Ocorrências operacionais e livro de portaria da Administradora (consumidos via API VDS v8);
   - Cadastros gerais de moradores, dependentes, veículos e boletos (mantidos no sistema remoto da Administradora).

3. **Não Compartilhamento e Sigilo dos Processos**:
   - Garantia de não compartilhamento com terceiros não autorizados ou sem Termo de Compromisso de Tratamento de Dados (DPA);
   - Sigilo absoluto dos recursos administrativos e pareceres (restritos ao próprio recorrente, ao síndico/administração e ao Conselho — nunca abertos a outros moradores);
   - Divulgação estatística ou assemblear feita exclusivamente em caráter anonimizado.
