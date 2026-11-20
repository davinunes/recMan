# Plano de Implementação: Respostas Rápidas no Livro de Ocorrências

**Data**: 2026-11-20  
**Arquivo principal**: `livroDeOcorrencias.php`  
**Migração**: `migrates/migrate_respostas_rapidas.php`  
**Escopo**: Compartilhado (todos os membros do conselho têm acesso aos modelos)

---

## 1. Objetivos
Implementar um sistema de respostas rápidas pré-cadastradas no Livro de Ocorrências (`index.php?pag=livroDeOcorrencias`), permitindo:
1. Selecionar respostas prontas diretamente ao lado do campo de texto de resposta/nota interna via botão com ícone de raio (`bolt`).
2. Substituir automaticamente tags dinâmicas (`{unidade}`, `{bloco}`, `{morador}`, `{protocolo}`, `{data}`) pelos dados do chamado atual.
3. Inserir o texto formatado no campo de texto (`#input-texto-nota-interna`) para ajuste fino antes de salvar ou publicar na VDS.
4. Gerenciar modelos (CRUD: Adicionar, Editar, Excluir) em um modal dedicado sem sair da tela.

---

## 2. Estrutura do Banco de Dados
Arquivo: `migrates/migrate_respostas_rapidas.php`
Tabela: `ocorrencias_respostas_rapidas`

```sql
CREATE TABLE IF NOT EXISTS ocorrencias_respostas_rapidas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    categoria VARCHAR(80) NOT NULL DEFAULT 'Geral',
    texto TEXT NOT NULL,
    conselheiro_id INT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ativo (ativo),
    KEY idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Sementes Iniciais (Exemplos de Modelos Pré-Cadastrados):
1. **Barulho fora de horário**:
   *"Prezado(a) morador(a) da unidade {bloco}-{unidade}, acusamos o recebimento da ocorrência protocolo {protocolo}. Informamos que as normas do regimento interno estabelecem silêncio no condomínio após o horário estipulado. O caso está sob acompanhamento da administração."*
2. **Mudança / Entrega fora de horário**:
   *"Prezado(a) morador(a) da unidade {bloco}-{unidade}, esclarecemos que mudanças e grandes entregas devem ser previamente agendadas com a administração para reserva do elevador de serviço e organização da portaria."*
3. **Solicitação de mais detalhes/evidências**:
   *"Prezado(a) condômino(a), para darmos o devido andamento à apuração deste chamado (protocolo {protocolo}), solicitamos o envio de mais informações, como data/horário exatos do ocorrido e eventuais fotos ou registros adicionais."*
4. **Áreas Comuns / Lixo**:
   *"Prezado(a) morador(a), lembramos que os descartes devem ser realizados nos locais devidamente sinalizados e embalados de acordo com as regras de convivência do condomínio."*
5. **Chamado Encaminhado à Administração/Síndico**:
   *"Agradecemos o seu contato. A situação relatada no protocolo {protocolo} foi encaminhada para a equipe de administração/síndico para providências cabíveis."*

---

## 3. Endpoints AJAX em `livroDeOcorrencias.php`
- `action=listar_respostas_rapidas`: Retorna a lista de respostas ativas organizadas por categoria (JSON).
- `action=salvar_resposta_rapida`: Cria ou edita um modelo (validação de `titulo`, `categoria`, `texto`).
- `action=excluir_resposta_rapida`: Exclui logicamente ou remove o modelo pelo `id`.

---

## 4. Componentes de Interface (UI/UX)
1. **Botão de Acesso Rápido**:
   - Inserido no formulário de resposta `#form-adicionar-nota-interna`, ao lado do botão de anexo.
   - Ícone: `<i class="material-icons">bolt</i>` com tooltip *"Respostas Rápidas"*.
2. **Menu / Dropdown de Seleção de Resposta**:
   - Menu flutuante moderno ou modal compacto com:
     - Campo de busca instantânea (filtro em tempo real por título ou conteúdo).
     - Lista categorizada com prévia do texto.
     - Botão `[+ Gerenciar Respostas]` no rodapé do seletor.
3. **Injeção de Tags Dinâmicas**:
   - Identifica os dados da ocorrência selecionada:
     - `{unidade}` -> Unidade/Apto
     - `{bloco}` -> Bloco
     - `{protocolo}` -> Número do protocolo VDS ou ID
     - `{morador}` -> Nome do morador/solicitante
     - `{data}` -> Data de hoje (`d/m/Y`)
   - Insere o texto interpolado diretamente no textarea com cursor pronto para digitação/ajuste.
4. **Modal de Gerenciamento (CRUD)**:
   - Formulário para Adicionar / Editar modelo (Título, Categoria, Texto com dica das variáveis disponíveis).
   - Tabela/lista de modelos existentes com botões de Editar e Excluir.

---

## 5. Plano de Validação
- Validar a execução da migração no banco de dados.
- Testar a listagem de modelos ao clicar no botão `bolt`.
- Testar a inserção no textarea e a substituição das variáveis `{unidade}`, `{bloco}`, etc.
- Testar a criação e edição de uma nova resposta rápida no modal.
- Garantir que não interfira no comportamento já existente de anexo e envio de nota interna.
