# mkit-api

Backend da plataforma **mkit.com.br**, responsável por autenticação, integração com Instagram e entrega dos dados que alimentam o micro bio site público de creators.

## Visão do produto

O **mkit** resolve um problema comum de micro-influencers: apresentar credibilidade para marcas com dados reais e atualizados, sem depender de PDF estático.

Este repositório (`mkit-api`) expõe uma API REST em `api.mkit.com.br` que:

- processa OAuth com Instagram;
- sincroniza perfil, posts e métricas;
- gerencia dados públicos do creator (bio, links, portfólio, marcas parceiras);
- entrega os dados consumidos pelo frontend `mkit-web` (Nuxt).

## Arquitetura do ecossistema

- `mkit-api` (este repo): Laravel 13, domínio `api.mkit.com.br`.
- `mkit-web`: Nuxt, domínio `mkit.com.br`.

Fluxo principal:
1. Usuário inicia login no front.
2. API executa OAuth (`/auth/instagram` + callback).
3. Conta e token são persistidos.
4. Front consome endpoints autenticados e públicos.
5. Scheduler/queue mantém dados do Instagram atualizados.

## Stack técnica

- PHP 8.3+
- Laravel 13
- MySQL 8
- Redis (cache, sessão e filas)
- Laravel Sanctum (auth SPA/token)
- Laravel Socialite + `socialiteproviders/instagram` (OAuth Instagram)
- PHPUnit + Laravel Test Runner
- Laravel Pint (padronização de código)

## Padrões de arquitetura do projeto

Seguimos o padrão **Services + Actions**:

- **Controllers**: finos, sem regra de negócio pesada;
- **Actions**: operações atômicas e reutilizáveis;
- **Services**: orquestração de fluxos complexos;
- **Jobs**: processamento assíncrono/sincronização;
- **Resources**: serialização de payload da API;
- **Form Requests**: validação e saneamento de entrada.

## Funcionalidades atuais (núcleo)

- OAuth Instagram e callback;
- endpoint público de creator por handle;
- área autenticada (`/api/me`) para perfil e edição;
- CRUD e ordenação de portfólio;
- CRUD e ordenação de marcas parceiras;
- CRUD e ordenação de links personalizados;
- sincronização manual e agendada de dados do Instagram;
- endpoint de insights do Instagram.

## Endpoints relevantes

Público:
- `GET /api/creators/{handle}`

Autenticação OAuth:
- `GET /auth/instagram`
- `GET /auth/instagram/callback`

Protegidos por Sanctum (`auth:sanctum`):
- `GET /api/me`
- `PUT /api/me`
- `GET|PUT /api/me/creator-profile`
- `GET|POST|PUT|DELETE /api/me/portfolio`
- `GET|POST|PUT|DELETE /api/me/partner-brands`
- `GET|POST|PUT|DELETE /api/me/links`
- `POST /api/me/instagram/sync`
- `GET /api/me/instagram/insights`

## Setup local

### 1) Pré-requisitos

- PHP 8.3+
- Composer
- MySQL 8
- Redis
- Node.js + npm

### 2) Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### 3) Configuração de ambiente

Preencha no `.env` (mínimo):

```env
APP_URL=https://api.mkit.com.br
FRONTEND_URL=https://mkit.com.br

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mkit
DB_USERNAME=
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis

INSTAGRAM_CLIENT_ID=
INSTAGRAM_CLIENT_SECRET=
INSTAGRAM_REDIRECT_URI=https://api.mkit.com.br/auth/instagram/callback
```

### 4) Banco e execução

```bash
php artisan migrate
composer run dev
```

Esse comando sobe servidor, fila, logs e Vite em paralelo.

## Deploy com Docker / Coolify

A stack de produção usa `docker-compose.yml` com os serviços:

| Serviço | Função |
|---|---|
| `nginx` | Proxy HTTP público (porta exposta ao Coolify) |
| `app` | PHP-FPM (Laravel) |
| `queue` | Worker de filas Redis |
| `scheduler` | `php artisan schedule:work` (sync diário) |
| `mysql` | Banco MySQL 8 com volume persistente |
| `redis` | Cache, sessão e filas |

### 1) Variáveis de ambiente

Crie um `.env` na raiz do repositório (Coolify: aba **Environment Variables**) com, no mínimo:

```env
APP_KEY=base64:... # gere com: php artisan key:generate --show
APP_URL=https://api.mkit.com.br
FRONTEND_URL=https://mkit.com.br

DB_DATABASE=mkit
DB_USERNAME=mkit
DB_PASSWORD=senha_forte
MYSQL_ROOT_PASSWORD=senha_root_forte

INSTAGRAM_CLIENT_ID=
INSTAGRAM_CLIENT_SECRET=
INSTAGRAM_REDIRECT_URI=https://api.mkit.com.br/auth/instagram/callback

SANCTUM_STATEFUL_DOMAINS=mkit.com.br,www.mkit.com.br
CORS_ALLOWED_ORIGINS=https://mkit.com.br
SESSION_DOMAIN=.mkit.com.br

RUN_MIGRATIONS=true
```

> No Coolify, **não** exponha portas no host (`ports:`). O proxy do Coolify roteia pelo domínio via rede interna do Docker. O compose já usa `expose` para isso.

### 2) Subir localmente (teste)

```bash
docker compose up -d --build
```

Para testar no host, publique a porta manualmente:

```bash
docker compose run -d -p 8080:80 --service-ports nginx
```

Ou crie um `docker-compose.override.yml` local com `ports: ["8080:80"]` no serviço `nginx`.

### 3) Configurar no Coolify

1. Crie um novo recurso **Docker Compose** apontando para este repositório.
2. Defina o domínio `api.mkit.com.br` no serviço **`nginx`** (não no `app`).
3. Configure as variáveis de ambiente listadas acima.
4. Faça o deploy.

### 4) Importar banco da hospedagem antiga

Com a stack rodando e o dump SQL em mãos:

```bash
# Copiar dump para o container (se necessário)
docker compose cp ./backup-mkit.sql mysql:/tmp/backup-mkit.sql

# Importar
docker compose exec mysql sh -c \
  'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < /tmp/backup-mkit.sql'
```

Alternativa direta pelo host:

```bash
docker compose exec -T mysql mysql -u mkit -p mkit < backup-mkit.sql
```

Após importar, defina `RUN_MIGRATIONS=false` se não quiser rodar migrations automaticamente no próximo deploy (recomendado após migração inicial).

### 5) Comandos úteis

```bash
# Logs
docker compose logs -f app queue scheduler

# Artisan manual
docker compose exec app php artisan migrate:status
docker compose exec app php artisan instagram:sync

# Shell no container
docker compose exec app sh
```

## Rotinas e sincronização

- Job diário configurado em `routes/console.php`:
  - `SyncAllCreatorsJob` (sync global de creators).
- Também existe sync manual por endpoint:
  - `POST /api/me/instagram/sync`.

## Boas práticas de coding neste projeto

- Seguir **PSR-12** e rodar `./vendor/bin/pint` antes de abrir PR.
- Preferir tipagem explícita em parâmetros e retornos.
- Evitar controllers com lógica de negócio.
- Centralizar regras de domínio em `Actions` e `Services`.
- Usar `FormRequest` para validação, nunca validar “na mão” em controller.
- Nunca alterar schema manualmente: toda mudança via migration.
- Nunca hardcode de segredos: usar somente `.env`.
- Tokens do Instagram devem permanecer criptografados (`encrypt`/`decrypt`).
- Manter cobertura de testes para fluxos críticos (auth, sync, endpoints públicos).

## Segurança e operação

- Sanctum para autenticação de sessão/token.
- CORS restrito às origens do front.
- Sessão, cache e queue em Redis.
- Rate limit deve ser aplicado a rotas públicas e sensíveis de auth.

## Escopo de produto

Fase atual prioriza MVP:

- login com Instagram;
- sync de perfil/posts;
- bio site público;
- gestão de portfólio no dashboard.

Itens como webhooks, insights avançados e múltiplas redes sociais são de fases posteriores.
