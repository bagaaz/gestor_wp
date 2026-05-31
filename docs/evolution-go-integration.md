# Integração com Evolution Go

Documentação da integração do WP Manager com o **Evolution Go** — versão em Go da Evolution API para WhatsApp.

---

## Evolution Go vs Evolution API (Node.js)

| | Evolution API v2 (Node.js) | Evolution Go |
|---|---|---|
| Linguagem | Node.js | Go |
| Endpoint envio de texto | `POST /message/sendText/{instance}` | `POST /send/text` |
| Instância no envio | Na URL (`/{instance}`) | No header (`apikey`) |
| Listar instâncias | `GET /instance/fetchInstances` | `GET /instance/all` |
| Docs interativas | — | `GET /swagger/index.html` |
| ID da mensagem | Gerado automaticamente | Campo `id` no body (opcional) |

> **Atenção:** a documentação do Evolution API v2 (Node.js) **não se aplica** ao Evolution Go. Use sempre o Swagger do próprio servidor para referência.

---

## Autenticação

O Evolution Go usa **duas chaves diferentes** para propósitos distintos:

### Global API Key
- Configurada no `.env` do servidor como `GLOBAL_API_KEY`
- Usada para **operações de gerenciamento** (criar/listar/deletar instâncias)
- Header: `apikey: <GLOBAL_API_KEY>`
- Exemplo de uso: `GET /instance/all`

### Instance Token
- Gerado automaticamente ao criar uma instância
- Usado para **operações de mensagem** (enviar texto, mídia, etc.)
- Header: `apikey: <instance_token>`
- Obtido via `GET /instance/all` → campo `token`
- **Não confundir com a Global API Key** — são valores distintos

```
# Exemplo: listar instâncias (usa global key)
curl -H "apikey: GLOBAL_API_KEY" https://evolution.exemplo.com/instance/all

# Exemplo: enviar mensagem (usa instance token)
curl -H "apikey: INSTANCE_TOKEN" -X POST https://evolution.exemplo.com/send/text \
  -d '{"number":"5511999999999","text":"Olá"}'
```

---

## Rotas principais

Base URL: `https://evolution.devconecta.com.br`
Docs Swagger: `https://evolution.devconecta.com.br/swagger/index.html`

### Instâncias

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| `GET` | `/instance/all` | Global Key | Lista todas as instâncias com nome, token, JID e status de conexão |
| `POST` | `/instance/create` | Global Key | Cria nova instância |
| `GET` | `/instance/qr` | Instance Token | Obtém QR code para parear |
| `GET` | `/instance/status` | Instance Token | Status de conexão da instância |
| `POST` | `/instance/connect` | Instance Token | Reconecta instância |
| `POST` | `/instance/disconnect` | Instance Token | Desconecta instância |
| `DELETE` | `/instance/delete/{instanceId}` | Global Key | Remove instância |
| `GET` | `/instance/get/{instanceId}` | Global Key | Dados de uma instância específica |

### Envio de Mensagens

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| `POST` | `/send/text` | Instance Token | Envia mensagem de texto |
| `POST` | `/send/media` | Instance Token | Envia mídia (imagem, vídeo, documento) |
| `POST` | `/send/link` | Instance Token | Envia link com preview |
| `POST` | `/send/location` | Instance Token | Envia localização |
| `POST` | `/send/contact` | Instance Token | Envia contato |
| `POST` | `/send/poll` | Instance Token | Envia enquete |
| `POST` | `/send/button` | Instance Token | Envia botões interativos |
| `POST` | `/send/list` | Instance Token | Envia lista interativa |

### Usuários / Contatos

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| `POST` | `/user/check` | Instance Token | Verifica se número(s) têm WhatsApp |
| `GET` | `/user/contacts` | Instance Token | Lista contatos |
| `POST` | `/user/info` | Instance Token | Dados de um usuário |
| `POST` | `/user/block` | Instance Token | Bloqueia contato |

---

## Envio de Texto — Detalhes

**Endpoint:** `POST /send/text`
**Auth:** `apikey: <instance_token>`

### Body (JSON)

```json
{
  "number": "5527998700053",
  "text": "Sua mensagem aqui",
  "delay": 1000,
  "formatJid": true
}
```

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `number` | string | ✅ | Número destino com código do país (apenas dígitos) |
| `text` | string | ✅ | Texto da mensagem. Suporta formatação WhatsApp: `*negrito*`, `_itálico_`, `~tachado~` |
| `id` | string | ❌ | ID único da mensagem. **Se omitido, o Evolution Go gera automaticamente.** Ver seção abaixo |
| `delay` | int | ❌ | Delay em ms antes de enviar |
| `formatJid` | bool | ❌ | Se `true`, formata o número automaticamente para JID WhatsApp |
| `mentionAll` | bool | ❌ | Menciona todos (grupos) |
| `mentionedJid` | array | ❌ | JIDs específicos para mencionar |

### Resposta de sucesso (200)

```json
{
  "data": {
    "Info": {
      "Chat": "5527998700053@s.whatsapp.net",
      "Sender": "5527992475991:2@s.whatsapp.net",
      "IsFromMe": true,
      "Timestamp": "2026-05-31T05:39:36Z"
    },
    "Message": {
      "extendedTextMessage": { "text": "Sua mensagem" }
    }
  },
  "message": "success"
}
```

---

## ⚠️ Regra Crítica: Campo `id` é ID da Mensagem

O campo `id` no body do `/send/text` **não é o nome da instância** — é o **ID da mensagem para deduplicação do WhatsApp**.

### O problema

Se você enviar sempre o mesmo `id` (ex: `"id": "devconecta"`):

1. A primeira mensagem é entregue normalmente ✅
2. Todas as mensagens seguintes são **descartadas silenciosamente** pelo WhatsApp como duplicatas ❌
3. A API retorna `success` normalmente — o erro não é visível na resposta

Nos logs do Evolution Go, o comportamento aparece assim:
```
Message sent successfully! ID: devconecta
Message duplicated ignored: devconecta   ← todas as mensagens seguintes
Message duplicated ignored: devconecta
```

### A solução

**Omita o campo `id`** — o Evolution Go gera um ID único automaticamente (ex: `3EB04FC640EAC7FAFD7DEA`).

```json
{
  "number": "5527998700053",
  "text": "Mensagem que chega normalmente"
}
```

Se precisar de um ID customizado (para rastreamento), use um valor **único por mensagem**:
```json
{
  "id": "msg-1748671234567-abc",
  "number": "5527998700053",
  "text": "Mensagem rastreável"
}
```

### Como a instância é identificada sem o `id`?

A instância é identificada exclusivamente pelo **instance token no header `apikey`**. O campo `id` no body não tem relação com isso.

---

## Verificar se Número tem WhatsApp

**Endpoint:** `POST /user/check`
**Auth:** `apikey: <instance_token>`

```json
{
  "number": ["5527998700053", "5511999999999"],
  "formatJid": true
}
```

**Resposta:**
```json
{
  "data": {
    "Users": [
      {
        "Query": "+5527998700053",
        "IsInWhatsapp": true,
        "JID": "5527998700053@s.whatsapp.net"
      }
    ]
  }
}
```

> **Nota:** o campo `number` é um **array**, mesmo para um único número.

---

## Configuração no WP Manager

### Variáveis de ambiente (`.env`)

```env
EVOLUTION_API_URL=https://evolution.devconecta.com.br
EVOLUTION_GLOBAL_API_KEY=429683C4C977415CAAFCCE10F7D57E11
```

Após alterar: `php artisan config:clear && php artisan config:cache`

### Settings no banco (`wp_manager.settings`)

| Chave | Valor | Descrição |
|-------|-------|-----------|
| `whatsapp_phone` | `5527998700053` | Número destino das notificações |
| `whatsapp_instance` | `devconecta` | Nome da instância (para logs) |
| `whatsapp_instance_token` | `ad579aaa-...` | Token da instância (tem prioridade sobre a global key para envio) |

Configurável em: `https://wp.devconecta.com.br/settings?tab=whatsapp`

### Fluxo de autenticação no `EvolutionService`

```
Painel (settings tab=whatsapp)
  └─ fetchInstances()
       └─ GET /instance/all
            Header: apikey = GLOBAL_API_KEY (gerenciamento)
            Retorna: name, token, connected, jid

  └─ sendText(instance, number, text)
       └─ POST /send/text
            Header: apikey = whatsapp_instance_token (DB) OU GLOBAL_API_KEY (.env)
            Body: { number, text }  ← sem "id" para evitar duplicatas
```

---

## Instância atual (devconecta)

| Campo | Valor |
|-------|-------|
| ID | `91addc9c-bf00-4593-b62b-73d547bb07ea` |
| Nome | `devconecta` |
| Token | `ad579aaa-817f-4788-bc9a-040f0de283c1` |
| JID (número WA) | `5527992475991` |
| Status | Conectada |

---

## Diagnóstico rápido

```bash
# Verificar se instância está conectada
curl -s -H "apikey: 429683C4C977415CAAFCCE10F7D57E11" \
  https://evolution.devconecta.com.br/instance/all | python3 -m json.tool

# Verificar se número tem WhatsApp
curl -s -X POST \
  -H "apikey: ad579aaa-817f-4788-bc9a-040f0de283c1" \
  -H "Content-Type: application/json" \
  -d '{"number":["5527998700053"],"formatJid":true}' \
  https://evolution.devconecta.com.br/user/check | python3 -m json.tool

# Enviar mensagem de teste
curl -s -X POST \
  -H "apikey: ad579aaa-817f-4788-bc9a-040f0de283c1" \
  -H "Content-Type: application/json" \
  -d '{"number":"5527998700053","text":"Teste"}' \
  https://evolution.devconecta.com.br/send/text | python3 -m json.tool

# Ver logs do serviço Evolution Go
journalctl -u evolution-go.service -n 100 --no-pager

# Acompanhar logs em tempo real
journalctl -u evolution-go.service -f
```

---

## Problemas conhecidos e soluções

| Problema | Causa | Solução |
|----------|-------|---------|
| API retorna `success` mas mensagem não chega | Campo `id` fixo — WhatsApp descarta como duplicata | Remover campo `id` do body |
| `404 page not found` em todos os endpoints | Confusão com endpoints do Evolution API v2 (Node.js) | Evolution Go usa rotas diferentes: `/send/text`, `/instance/all`, etc. |
| `not authorized` ao enviar mensagem com Global API Key | Envio exige Instance Token, não Global Key | Usar `instance_token` no header `apikey` para `/send/text` |
| `not authorized` ao listar instâncias com Instance Token | Listagem exige Global API Key | Usar `GLOBAL_API_KEY` para endpoints `/instance/*` |
| Mensagens chegam com atraso ou não chegam | WhatsApp anti-spam por rajada de mensagens idênticas | Aguardar alguns minutos; garantir que `id` seja único ou omitido |
