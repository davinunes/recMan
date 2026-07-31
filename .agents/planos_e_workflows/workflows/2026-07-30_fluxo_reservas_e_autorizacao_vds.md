# Workflows da API v8 Vida de Síndico (VDS)

- **Data**: 2026-07-30
- **Tópico**: Fluxos operacionais de Reservas/Fila de Espera, Autorizações de Acesso / Convites e Anexo de Arquivos em Ocorrências

---

## 1. Fluxo de Reserva & Fila de Espera Automatizada

O endpoint `POST /reserva` é unificado no backend da VDS. O servidor determina autonomamente se o morador obtém a reserva aprovada ou entra na fila de espera.

```mermaid
sequenceDiagram
    autonumber
    actor Morador as Morador / Cliente
    participant Postman as Postman / Sistema Integrador
    participant VDS as API v8 VDS (apiv8.vds.app.br)
    participant DB as Banco de Dados VDS

    Morador->>Postman: Solicita Reserva (Data, Horário, Recurso)
    Postman->>VDS: POST /reserva (morador, unidade, recurso, dtIni, dtFim)
    VDS->>DB: Verifica disponibilidade do recurso no período
    alt Recurso Livre
        VDS-->>Postman: HTTP 200 OK {"message": "Registrada com status: Reserva aprovada", status: {uuid: "2"}}
        Postman-->>Morador: Confirma Reserva Aprovada
    else Recurso Ocupado (Permite Fila)
        VDS-->>Postman: HTTP 200 OK {"message": "Registrada com status: Fila de espera", status: {uuid: "5"}}
        Postman-->>Morador: Enfileirado na Fila de Espera
    else Limite/Antecedência Excedida
        VDS-->>Postman: HTTP 200 OK {"status": false, "message": "Data de reserva não pode ser superior..."}
        Postman-->>Morador: Erro de Validação
    end
```

---

## 2. Fluxo de Autorização de Acesso, Convite Social & QR Code

Fluxo completo de criação de autorização de acesso a visitantes, geração de QR Code/convite, aprovação e notificação por e-mail.

```mermaid
sequenceDiagram
    autonumber
    actor Morador as Morador / Unidade
    participant API as API v8 VDS (/autorizacao_acesso)
    participant Visitor as Visitante / Convidado
    participant Portaria as Sistema de Portaria / Catraca

    Morador->>API: 1. POST /autorizacao_acesso (Criar Autorização)
    API-->>Morador: Retorna Autorização criada (UUID)
    Morador->>API: 2. POST /autorizacao_acesso_qrcode/gerar ou /convite/gerar
    API-->>Morador: Retorna Link/QR Code do Convite
    Morador->>Visitor: Compartilha QR Code / Convite Social
    Visitor->>API: 3. POST /autorizacao_acesso/convite (Preenche Dados)
    API-->>Visitor: Registrado como Pendente de Aprovação
    Morador->>API: 4. POST /autorizacao_acesso_qrcode/aprovar
    API-->>Morador: Convite Aprovado
    Visitor->>Portaria: Apresenta QR Code na Entrada
    Portaria->>API: 5. PUT /autorizacao_acesso/{uuid}/confirmar_convite
    API-->>Portaria: Entrada Liberada e Registrada em evento_acesso
```

---

## 3. Fluxo de Anexo de Arquivos em Ocorrências / Comentários (3 Etapas Sequenciais)

Fluxo descoberto via inspeção detalhada de rede para upload e vinculação de imagens/documentos a mensagens ou ocorrências.

```mermaid
sequenceDiagram
    autonumber
    actor User as Usuário / Cliente
    participant Upload as API Upload (/upload)
    participant Ocorrencia as API Comentário (/ocorrencia/comentario)
    participant Anexo as API Anexo (/anexo)

    User->>Upload: 1. POST /upload {"base64String": "data:image/jpeg;base64,..."}
    Upload-->>User: HTTP 200 {"url": "app\\dados\\tmp\\1d55b4af-a8d7-4b20-a4a0-d807f1dc7e6f.jpeg"}
    User->>Ocorrencia: 2. POST /ocorrencia/comentario {"uuid": "...", "mensagem": "...", "ocorrenciaPaiId": 51970481}
    Ocorrencia-->>User: HTTP 200 {"ocorrenciaId": 52075990}
    User->>Anexo: 3. POST /anexo {"anexoCaminho": "1d55b4af...jpeg*NomeOriginal.jpeg", "tipoId": "35", "destinoUuid": "52075990"}
    Anexo-->>User: HTTP 200 {"message": "Anexos salvo com sucesso!"}
```
