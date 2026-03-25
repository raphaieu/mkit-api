# PRD — mkit.com.br
> Micro bio sites dinâmicos para (micro)influencers venderem sua audiência para marcas e parceiros

---

## 1. Problema

Influencers — especialmente os micro — não têm um lugar profissional e centralizado para se apresentar para marcas e potenciais parceiros. O que existe hoje:

- **Linktree / Beacons**: agrega links, mas não mostra dados reais de audiência
- **Media kit em PDF**: estático, desatualizado, trabalhoso de manter
- **DM no Instagram**: informal, sem credibilidade

**O gap**: nenhuma solução entrega automaticamente os dados reais de performance do creator (alcance, engajamento, seguidores, crescimento) de forma dinâmica e visual — justamente o que as marcas mais querem ver.

---

## 2. Solução

O **mkit** gera um micro bio site público e dinâmico para cada influencer, alimentado automaticamente pelos dados do Instagram via Graph API.

O creator faz login, conecta o Instagram, personaliza e compartilha o link. A marca acessa e vê dados reais, portfólio de trabalhos e formas de contato — tudo em uma página profissional.

---

## 3. Usuários

| Perfil | Descrição |
|---|---|
| **Creator (usuário primário)** | Micro-influencer (1k–100k seguidores), Instagram como canal principal, busca parcerias com marcas |
| **Marca / Parceiro (visitante)** | Empresa ou agência que avalia creators para campanhas, acessa o link do creator |

---

## 4. Diferencial

> **Dados reais e automáticos do Instagram, sempre atualizados.**

Enquanto concorrentes mostram apenas links, o mkit exibe métricas reais: seguidores, taxa de engajamento, alcance médio, crescimento — sem o creator precisar atualizar nada manualmente.

---

## 5. Arquitetura geral

### Repositórios
Dois repos separados, mesmo workspace no Cursor:

| Repo | Domínio | Stack |
|---|---|---|
| `mkit-api` | `api.mkit.com.br` | Laravel 13 |
| `mkit-web` | `mkit.com.br` | NuxtJS (latest) |

### Estrutura de domínios

| URL | Descrição |
|---|---|
| `mkit.com.br` | Landing page premium (apresentação do produto) |
| `mkit.com.br/@handle` | Bio site público do creator |
| `mkit.com.br/app/*` | Dashboard do creator (área logada) |
| `api.mkit.com.br` | API REST + OAuth callbacks |

---

## 6. Frontend — decisões técnicas

- **PWA**: service worker, manifest, installable, offline-ready
- **Mobile-first**: todo layout pensado primeiro para mobile, depois desktop
- **SSR**: landing page e páginas públicas `/@handle` renderizadas no servidor (SEO crítico)
- **SPA**: dashboard `/app/*` pode ser client-side

### Organização de componentes

```
components/
  landing/          # exclusivos da landing page
  bio/              # exclusivos do micro bio site
    BioHeader.vue
    BioMetrics.vue
    BioPostGrid.vue
    BioPortfolio.vue
    BioContact.vue
  dashboard/        # painel do creator
  ui/               # genéricos reutilizáveis
    Button.vue
    Card.vue
    Avatar.vue
    Badge.vue

layouts/
  default.vue       # landing page
  bio.vue           # micro bio site
  dashboard.vue     # área logada
```

---

## 7. Funcionalidades

### 7.1 MVP (Fase 1 — sem App Review do Instagram)

> Objetivo: validar com conta de testes e até 25 usuários testers cadastrados manualmente no Meta Developer.

#### Autenticação
- [ ] Login via OAuth do Instagram
- [ ] Criação automática de conta ao logar pela primeira vez
- [ ] Armazenamento seguro do access token (criptografado, com refresh)

#### Dados do Instagram
- [ ] Nome, foto de perfil, bio
- [ ] Número de seguidores
- [ ] Posts recentes (últimas 12 mídias: thumbnail, caption, likes, comentários)
- [ ] Taxa de engajamento calculada automaticamente

#### Landing page (`mkit.com.br`)
- [ ] Hero com proposta de valor clara
- [ ] Como funciona (3 passos)
- [ ] Exemplo/demo de bio site
- [ ] Planos e preços
- [ ] CTA: "Criar meu media kit"

#### Micro bio site público (`mkit.com.br/@handle`)
- [ ] Foto + nome + bio do Instagram
- [ ] Métricas em destaque (seguidores, engajamento médio)
- [ ] Grid de posts recentes
- [ ] Seção "últimos trabalhos" (alimentada manualmente pelo creator)
- [ ] Botão de contato
- [ ] Meta tags OG dinâmicas (SEO por creator)
- [ ] PWA installable

#### Dashboard (`mkit.com.br/app`)
- [ ] Preview do próprio bio site
- [ ] Gerenciar "últimos trabalhos"
- [ ] Copiar link do bio site
- [ ] Status da conexão com Instagram

### 7.2 Fase 2 — com App Review aprovado
- [ ] Webhooks de menções
- [ ] Dados de alcance e impressões (`instagram_manage_insights`)
- [ ] Gráfico de crescimento de seguidores
- [ ] Abertura para qualquer usuário do Instagram

### 7.3 Backlog
- Múltiplas redes sociais (TikTok, YouTube)
- Analytics de visitantes
- Proposta de parceria pela plataforma
- Plano agência

---

## 8. Monetização

### Free
- Bio site com dados automáticos do Instagram
- Limite de 3 posts em "últimos trabalhos"
- Personalização básica
- Branding mkit no rodapé

### Pro (recorrência)
- Até 20 posts em "últimos trabalhos"
- Personalização avançada (cores, fontes, layout)
- Sem branding mkit
- Analytics de visitantes
- ~R$ 19–39/mês (a definir)

### Premium high-touch (fora do SaaS)
- Landing page / site completo feito pelo Raphael
- Cobrança avulsa ou recorrência anual
- Atendimento direto

---

## 9. Fora do escopo (MVP)

- ❌ Outras redes além do Instagram
- ❌ App mobile nativo
- ❌ Mensagens internas
- ❌ Marketplace de campanhas
- ❌ Insights avançados (Fase 2)
- ❌ Pagamentos integrados (Fase 2)

---

## 10. Fluxos principais

### Onboarding
```
mkit.com.br → "Criar meu media kit"
  → OAuth → api.mkit.com.br/auth/instagram
  → Callback → conta criada → token salvo
  → Redirect → mkit.com.br/app
  → Bio site no ar em mkit.com.br/@handle
```

### Visitante
```
Recebe link mkit.com.br/@handle
  → Página SSR com dados do creator
  → Vê métricas, bio, portfólio
  → Contato
```

### Sync de dados
```
Laravel Scheduler (diário)
  → SyncInstagramDataJob por creator
  → Atualiza instagram_profiles + instagram_posts
```

---

## 11. Restrições técnicas do Instagram

### Fase 1 (sem App Review)
- Apenas contas de teste cadastradas manualmente no Meta Developer
- Limite de 25 testers por app
- Dados disponíveis: perfil básico, posts, seguidores
- Suficiente para validar

### Fase 2 (App Review)
- Permissões avançadas: `instagram_manage_insights`, webhooks
- Processo pode levar semanas — iniciar documentação cedo

---

## 12. Métricas de sucesso (MVP)

- 5+ bio sites criados por testers reais
- 1+ feedback positivo de marca
- Fluxo OAuth → bio site público sem erros
- Dados atualizando via cron corretamente

---

## 13. Mercado inicial

Salvador/BA — micro-influencers locais, abordagem direta.

---

*Versão: 0.2 — março/2026*