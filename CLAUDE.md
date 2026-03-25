# CLAUDE.md — mkit-api
> Backend Laravel 13 | api.mkit.com.br
> Leia o PRD.md antes de qualquer decisão arquitetural ou de produto.

---

## Contexto do projeto

API REST do mkit — plataforma de micro bio sites para (micro)influencers.
Este repo é exclusivamente o backend. O frontend vive em `mkit-web` (NuxtJS).

**Contrato entre repos:**
- Este repo expõe endpoints REST em `api.mkit.com.br`
- Autentica via Laravel Sanctum (SPA tokens)
- OAuth do Instagram é processado aqui e redireciona para o front após sucesso
- CORS configurado para aceitar `mkit.com.br`

---

## Stack

| | |
|---|---|
| PHP | 8.3+ |
| Laravel | 13 (latest) |
| MySQL | 8 |
| Redis | Cache + Queues |
| Auth | Laravel Sanctum + Socialite (Instagram) |

---

## Arquitetura

Padrão **Services + Actions**:

- **Controllers**: finos — recebem request, chamam Action/Service, retornam Resource
- **Actions**: operações atômicas e reutilizáveis (`SyncInstagramProfileAction`, `CreateCreatorAction`)
- **Services**: orquestram fluxos complexos (`InstagramSyncService`, `CreatorService`)
- **Jobs**: operações assíncronas (`SyncInstagramDataJob`)
- **Resources**: serialização de respostas de API
- **Form Requests**: validação de inputs

```
app/
  Actions/
    Creator/
    Instagram/
  Services/
    InstagramSyncService.php
    CreatorService.php
  Jobs/
    SyncInstagramDataJob.php
  Http/
    Controllers/
      Auth/
        InstagramController.php
      Api/
        CreatorController.php
        ProfileController.php
        PortfolioController.php
    Requests/
    Resources/
  Models/
    User.php
    InstagramProfile.php
    InstagramPost.php
    PortfolioPost.php
```

---

## Banco de dados

MySQL 8 — migrations para tudo, nunca alterar schema manualmente.

### Tabelas principais (MVP)

**users**
- id, name, email, handle (único, gerado do username do Instagram), plan (free/pro), timestamps, soft deletes

**instagram_profiles**
- id, user_id, instagram_id, username, full_name, biography, profile_picture_url, followers_count, following_count, media_count, access_token (encrypted), token_expires_at, last_synced_at, timestamps

**instagram_posts**
- id, instagram_profile_id, instagram_media_id, media_type, media_url, thumbnail_url, permalink, caption, like_count, comments_count, timestamp, timestamps

**portfolio_posts**
- id, user_id, title, description, image_url, partner_name, published_at, order, timestamps, soft deletes

### Convenções
- snake_case plural nas tabelas
- Soft deletes nas entidades principais
- Índices em colunas usadas em WHERE/JOIN frequentes
- `access_token` sempre criptografado com `encrypt()`

---

## Instagram Graph API

- OAuth via **Laravel Socialite** (provider Instagram)
- Fluxo: front redireciona para `api.mkit.com.br/auth/instagram` → callback → token salvo → redirect para `mkit.com.br/app`
- Tokens armazenados criptografados
- Refresh automático antes de expirar
- Sync via Job na queue

### Permissões (Fase 1 — sem App Review)
- `instagram_basic`
- `pages_show_list` (se necessário)

### Endpoints utilizados
- `GET /me` — perfil básico
- `GET /me/media` — posts recentes
- `GET /{media-id}` — detalhes do post

---

## Convenções de código

- PSR-12
- Tipagem forte — sempre declarar tipos de retorno e parâmetros
- Sem `array` genérico — usar typed collections ou DTOs
- Docblocks apenas quando o tipo não é auto-explicativo
- Nunca hardcode de credenciais — tudo via `.env`

---

## Segurança

- Tokens do Instagram: `encrypt()` / `decrypt()` do Laravel
- Rate limiting nas rotas públicas e de auth
- CORS restrito a `mkit.com.br`
- Validar assinatura HMAC nos webhooks do Instagram (Fase 2)

---

## Variáveis de ambiente necessárias

```env
INSTAGRAM_CLIENT_ID=
INSTAGRAM_CLIENT_SECRET=
INSTAGRAM_REDIRECT_URI=https://api.mkit.com.br/auth/instagram/callback

FRONTEND_URL=https://mkit.com.br

DB_CONNECTION=mysql
DB_HOST=
DB_DATABASE=mkit
DB_USERNAME=
DB_PASSWORD=

REDIS_HOST=
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
```

---

## Rotas principais (MVP)

```
GET  /auth/instagram              → redirect OAuth
GET  /auth/instagram/callback     → processa token, cria conta, redirect front

GET  /api/creators/@{handle}      → dados públicos do bio site (sem auth)
GET  /api/me                      → dados do creator logado
PUT  /api/me                      → atualiza perfil
GET  /api/me/portfolio            → lista portfolio posts
POST /api/me/portfolio            → cria portfolio post
PUT  /api/me/portfolio/{id}       → atualiza
DELETE /api/me/portfolio/{id}     → remove
POST /api/me/instagram/sync       → força sync manual
```

---

## Scheduler

```php
// routes/console.php ou App\Console\Kernel
Schedule::job(new SyncAllCreatorsJob)->daily();
```

---

## Fase atual: MVP (Fase 1)

Foco em:
1. OAuth Instagram funcionando com usuários de teste
2. Sync de perfil + posts
3. Endpoint público `GET /api/creators/@{handle}`
4. CRUD de portfolio posts

**Não implementar ainda**: insights, webhooks, pagamentos, múltiplas redes.