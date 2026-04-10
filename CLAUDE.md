# CLAUDE.md — WP Docker Manager

## Objetivo do Projeto

Ambiente Docker para desenvolvimento local de múltiplos sites WordPress, acessíveis via subdomínios (`site1.localhost`, `site2.localhost`), com gerenciamento via CLI (`wp-manager.sh`) e painel visual em Laravel (`manager.localhost`).

O projeto foi criado para a **Impacta Soluções Web** como ferramenta interna de desenvolvimento, permitindo subir e derrubar instâncias WordPress de forma rápida e isolada.

## Stack Tecnológica

- **Docker Compose** — Orquestração dos serviços
- **Nginx Alpine** — Reverse proxy com configs dinâmicas por site
- **PHP 8.3-FPM** — Duas instâncias: uma para WordPress, outra para o painel Laravel
- **MySQL 8.0** — Banco com charset `utf8mb4_unicode_ci`
- **phpMyAdmin** — Interface web para banco (porta 8080)
- **Mailpit** — SMTP fake para capturar emails de teste (porta 8025)
- **WP-CLI** — Automação de instalação e configuração do WordPress
- **Laravel 13** — Painel de gerenciamento visual (pasta `manager/`)

## Estrutura de Pastas

```
wp-docker-manager/
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf                          # Config global (client_max_body_size 256M, gzip)
│   │   ├── conf.d/
│   │   │   ├── default.conf                    # manager.localhost + redirect localhost
│   │   │   └── site-{nome}.conf                # Gerado automaticamente pelo wp-manager.sh
│   │   └── templates/
│   │       └── wordpress.conf.template         # Template usado para gerar configs de sites
│   ├── php/
│   │   ├── Dockerfile                          # PHP-FPM para WordPress (gd, imagick, redis, wp-cli, msmtp, php.ini embutido)
│   │   ├── Dockerfile.manager                  # PHP-FPM para Laravel (composer + Docker CLI + Compose plugin)
│   │   ├── entrypoint-manager.sh               # Entrypoint que dá acesso ao Docker socket para www-data
│   │   ├── php.ini                             # upload_max=256M, memory=512M, display_errors=On, msmtp
│   │   └── www.conf                            # Pool config do PHP-FPM
│   └── mysql/
│       ├── my.cnf                              # utf8mb4, slow_query_log, max_allowed_packet=256M
│       └── init/
│           └── 01-create-manager-db.sql        # Cria o banco `wp_manager` no primeiro boot
├── manager/                                    # Projeto Laravel 13 (painel visual)
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── Controller.php                  # Base controller (classe abstrata)
│   │   │   ├── DashboardController.php         # Dashboard com stats, containers, logs
│   │   │   ├── LogController.php               # Página de logs (atividades + laravel.log)
│   │   │   ├── SettingsController.php          # Configurações: credenciais, PHP, logo
│   │   │   └── SiteController.php              # CRUD de sites + API para o CLI
│   │   ├── Models/
│   │   │   ├── Site.php                        # Model principal (sites WordPress)
│   │   │   ├── Backup.php                      # Registros de backup
│   │   │   └── ActivityLog.php                 # Log de atividades
│   │   ├── Services/
│   │   │   ├── DockerService.php               # Interação com Docker (status, mysql queries)
│   │   │   ├── PhpConfigService.php            # Leitura/escrita do php.ini, reload, verificação
│   │   │   └── WordPressService.php            # Lógica de criar/remover/clonar sites
│   │   └── Providers/
│   │       └── AppServiceProvider.php          # Singletons dos Services
│   ├── database/migrations/                    # 3 migrations: sites, backups, activity_logs
│   ├── resources/views/
│   │   ├── layouts/app.blade.php               # Layout base (sidebar, Tailwind CDN, Alpine.js)
│   │   ├── dashboard/index.blade.php           # Dashboard principal
│   │   └── sites/
│   │       ├── index.blade.php                 # Grid de cards dos sites
│   │       ├── create.blade.php                # Formulário de criação
│   │       ├── show.blade.php                  # Detalhes do site (plugins, backups, ações)
│   │       └── export.blade.php                # Formulário de export para produção
│   │   ├── logs/
│   │   │   └── index.blade.php                 # Logs: atividades (banco) + log Laravel (arquivo)
│   │   └── settings/
│   │       └── index.blade.php                 # Abas: Geral (credenciais), PHP (config), Logo
│   │   └── components/
│   │       └── confirm-modal.blade.php         # Modal de confirmação reutilizável (Alpine.js)
│   ├── routes/
│   │   ├── web.php                             # Rotas web (dashboard + sites + logs + settings + export)
│   │   └── api.php                             # API interna (POST/DELETE /api/sites)
│   ├── config/                                 # app, database, cache, session, view
│   ├── composer.json
│   └── .env.example
├── sites/                                      # Sites WordPress ficam aqui (um por pasta)
├── backups/                                    # Backups automáticos (pasta/timestamp/)
├── docker-compose.yml                          # Orquestração principal (7 services)
├── wp-manager.sh                               # CLI principal (~700 linhas)
├── setup.sh                                    # Setup inicial (roda 1x)
├── Makefile                                    # Atalhos make
├── .gitignore
└── README.md
```

## Regras e Convenções

### Nomes de Sites
- Apenas letras minúsculas (`a-z`), números (`0-9`) e hífens (`-`)
- Não pode começar ou terminar com hífen
- Regex: `^[a-z0-9][a-z0-9-]*[a-z0-9]$` (ou `^[a-z0-9]$` para nomes de 1 caractere)
- Exemplos válidos: `meusite`, `loja-01`, `cliente-abc`

### Nomes de Banco de Dados
- Derivado do nome do site: `wp_` + nome com hífens/pontos convertidos em `_`
- Exemplo: site `meu-site` → banco `wp_meu_site`

### Configs Nginx
- Cada site gera um arquivo `docker/nginx/conf.d/site-{nome}.conf`
- Baseado no template `docker/nginx/templates/wordpress.conf.template`
- Placeholder `{{SITE_NAME}}` substituído via `sed`
- Sites desativados têm extensão `.conf.disabled`

### WordPress — Padrões Aplicados
- Locale: `pt_BR`
- Timezone: `America/Sao_Paulo`
- Formato de data: `d/m/Y`, hora: `H:i`
- Permalinks: `/%postname%/`
- Upload máximo: `256MB` (php.ini + nginx + wp-config)
- SVG/WebP/AVIF: habilitados via mu-plugin em `wp-content/mu-plugins/wp-local-dev.php`
- SMTP: Mailpit (host `mailpit`, porta `1025`, sem auth, sem TLS)
- Debug: `WP_DEBUG`, `WP_DEBUG_LOG`, `SCRIPT_DEBUG` todos `true`
- Admin padrão: `devconecta` / `Ga96911431@` / `admin@localhost.test`
- Atualizações automáticas desabilitadas
- **Todo conteúdo padrão removido**: posts (Hello World), páginas (Sample Page, Privacy Policy), comentários
- **Plugins padrão removidos**: Hello Dolly, Akismet
- **Temas padrão removidos**: todos os twenty* — apenas **Hello Elementor** ativado (sem plugin Elementor)
- Comentários desabilitados por padrão (fechados, pingbacks off, trackbacks off)
- **Logo customizada** na tela `/wp-login.php` (carregada de `wp-content/mu-plugins/assets/login-logo.svg`)
- Logo master fica em `docker/assets/login-logo.svg` e é copiada para cada site na criação

### WordPress — Hardening de Segurança
Aplicado via mu-plugin `wp-local-dev.php` (e `wp-production-security.php` no export):
- XML-RPC completamente desabilitado
- Header `X-Pingback` removido
- Enumeração de usuários via REST API (`/wp/v2/users`) bloqueada para não-logados
- Redirect de `?author=N` para home (anti-enumeration)
- Meta generator removido do HTML e feeds
- Headers desnecessários removidos (`wlwmanifest`, `rsd_link`, `shortlink`, `oembed`)
- Emojis do WordPress removidos (performance + segurança)
- Proteção brute-force: máximo 5 tentativas por IP, bloqueio de 15 minutos
- Pingbacks e trackbacks desabilitados
- Comentários com mais de 2 links bloqueados (anti-spam)
- Headers de segurança: `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`
- `DISALLOW_FILE_EDIT` ativado (bloqueia editor de código no admin)

### Painel Laravel
- Acesso: `http://manager.localhost`
- Banco próprio: `wp_manager` (separado dos bancos WordPress)
- Credenciais MySQL: `root` / `root`
- Sincroniza automaticamente sites do filesystem ao acessar o dashboard
- API interna em `/api/sites` usada pelo `wp-manager.sh` para registrar/remover sites
- Frontend: Tailwind CSS (CDN) + Alpine.js (CDN) + Font Awesome
- Sem autenticação (é ambiente local)

### Painel Laravel — Funcionalidades
- **Dashboard**: stats (total sites, ativos, disco), lista de sites, containers Docker, logs de atividade
- **Sites CRUD**: criar, visualizar detalhes (plugins/temas/disco/banco), remover
- **Clone**: clonar site com banco e search-replace de URLs
- **Backup**: backup completo (arquivos + banco)
- **Logs** (`/logs`): duas abas — registro de atividades (banco) e log do Laravel (arquivo), com opção de limpar
- **Configurações** (`/settings`): três abas — **Geral** (credenciais WP e MySQL, referência rápida), **PHP** (memory_limit, upload, execução, com verificação em tempo real), **Logo** (upload de logo do login)
- **Export para Produção** (`/sites/{id}/export`): gera ZIP com domínio/banco substituídos

### Export para Produção — O que faz
O export (`ExportController`) gera um ZIP pronto para deploy:
1. Copia todos os arquivos do site para diretório temporário
2. Exporta o banco SQL e faz search-replace de URLs (local → produção), incluindo dados serializados
3. Gera novo `wp-config.php` com: credenciais de produção, salt keys novas, debug off, FORCE_SSL, WP_CACHE
4. Gera `.htaccess` com: permalinks, bloqueio de wp-config/xmlrpc/readme, headers de segurança, gzip, cache estático
5. Substitui o mu-plugin dev por versão de produção (sem SMTP Mailpit, sem badge LOCAL DEV)
6. Faz search-replace em arquivos de tema/plugins (CSS, JS, JSON)
7. Remove arquivos desnecessários (readme.html, license.txt, debug.log)
8. Logo customizada do login é mantida no ZIP

### Logo do Login WordPress
- **Não usa nenhum plugin externo**. A logo é aplicada via arquivo PHP em `wp-content/mu-plugins/wp-local-dev.php`
- O mu-plugin usa o hook `login_enqueue_scripts` para injetar CSS puro que substitui o `background-image` do `#login h1 a`
- Logo master: `docker/assets/login-logo.svg` (o arquivo SVG original, sem conversão)
- Copiada para `wp-content/mu-plugins/assets/login-logo.svg` de cada site na criação
- Troca rápida via painel: **Configurações → Logo do Login WordPress** (faz upload e atualiza TODOS os sites existentes)
- Aceita: SVG, PNG, JPG, WebP (max 2MB)

### Docker
- Network: `wp-network` (bridge)
- Volume persistente: `mysql_data` para dados do MySQL
- O WP-CLI é executado via `docker exec` no container `wp-php` (que já possui WP-CLI instalado e os volumes corretos)
- Containers WordPress compartilham o mesmo PHP-FPM (`wp-php`)
- Container separado `wp-php-manager` para o Laravel, com Docker CLI + Compose plugin instalados e acesso ao Docker socket
- O container `php-manager` monta o projeto raiz em `/var/www/project` e os sites em `/var/www/sites`
- Env `COMPOSE_PROJECT_NAME=gestor_wp` garante que comandos `docker compose` de dentro do container encontrem os containers corretos
- O `php.ini` customizado é embutido na imagem PHP (via `COPY` no Dockerfile) para garantir que o WP-CLI use `memory_limit=512M`

## Portas

| Porta | Serviço     |
|-------|-------------|
| 80    | Nginx        |
| 3309  | MySQL (ext)  |
| 8080  | phpMyAdmin   |
| 8025  | Mailpit Web  |
| 1025  | Mailpit SMTP |

**Nota sobre MySQL**: A porta externa é `3309` para evitar conflito com MySQL local na `3306`. A porta **interna** entre containers permanece `3306` (os containers se comunicam via rede Docker `wp-network`, sem usar a porta do host).

## Comandos CLI Principais

```bash
./wp-manager.sh create <nome> [--version X.X] [--woocommerce] [--multisite]
./wp-manager.sh remove <nome> [--keep-db] [--force]
./wp-manager.sh list
./wp-manager.sh clone <origem> <destino>
./wp-manager.sh backup <nome>
./wp-manager.sh restore <nome>
./wp-manager.sh update <nome> [versão]
./wp-manager.sh shell <nome> [comando wp-cli]
./wp-manager.sh plugin <nome> <install|activate|delete> <slug>
./wp-manager.sh theme <nome> <install|activate|delete> <slug>
./wp-manager.sh db <export|import|reset> <nome> [arquivo.sql]
./wp-manager.sh logs <nome|nginx|php|mysql>
./wp-manager.sh stop-site <nome>
./wp-manager.sh start-site <nome>
./wp-manager.sh up | down | restart | rebuild | destroy | status
```

## Fluxo de Criação de um Site

1. Valida o nome do site
2. Cria banco MySQL (`wp_{nome}`)
3. Baixa WordPress via WP-CLI (versão especificada ou latest, locale pt_BR)
4. Gera `wp-config.php` com todas as constantes de dev
5. Instala WordPress com `wp core install` (título, admin `devconecta`, email)
6. Aplica configs pt-BR (timezone, data, permalinks)
7. Desabilita comentários, pingbacks, trackbacks
8. Remove TODO conteúdo padrão (posts, páginas, comentários, lixeira)
9. Instala tema **Hello Elementor** (sem plugin Elementor) e remove TODOS os temas padrão (twenty*)
10. Remove plugins padrão (Hello Dolly, Akismet)
11. Instala `mu-plugin` completo: SVG/WebP, Mailpit SMTP, logo login, hardening segurança, badge LOCAL DEV
12. Copia logo de `docker/assets/login-logo.svg` para `wp-content/mu-plugins/assets/`
13. Opcionalmente instala WooCommerce
14. Gera config Nginx a partir do template e recarrega
15. Registra site na API do painel Laravel
16. Exibe resumo com URL, credenciais e banco

## Fluxo de Remoção de um Site

1. Cria backup de segurança automático antes de remover
2. Remove banco MySQL (a menos que `--keep-db`)
3. Remove config Nginx e recarrega
4. Remove diretório do site em `sites/`
5. Remove registro do painel Laravel via API

## Notas para Desenvolvimento

- Ao modificar o `Dockerfile` ou `Dockerfile.manager`, rode `./wp-manager.sh rebuild`
- Ao modificar `php.ini`, rode `./wp-manager.sh rebuild` (o php.ini é embutido na imagem via COPY, não apenas montado como volume)
- Ao modificar `my.cnf`, rode `./wp-manager.sh restart`
- Ao modificar templates Nginx, os sites existentes NÃO são afetados (só novos)
- O `docker-compose.yml` monta `./sites` e `./manager` como volumes bind — alterações nos arquivos locais refletem imediatamente nos containers
- O WP-CLI roda via `docker exec` no container `wp-php` já existente (não cria containers efêmeros)
- O painel Laravel usa `shell_exec()` para interagir com Docker e o `wp-manager.sh` — o container `php-manager` tem Docker CLI instalado e o Docker socket montado (`/var/run/docker.sock`)
- O `entrypoint-manager.sh` detecta o GID do Docker socket e adiciona `www-data` ao grupo correspondente em runtime (portável entre hosts com GIDs diferentes)
- Erros de criação de sites são registrados no `ActivityLog` e no `laravel.log`, visíveis na página `/logs` do painel
- Flash de erro no painel inclui botão "Ver detalhes" que exibe a saída completa do comando que falhou
- Configurações PHP são editáveis via painel (`/settings?tab=php`). O `PhpConfigService` atualiza o `php.ini`, o `client_max_body_size` do Nginx, reinicia o container PHP e verifica os valores ativos em tempo real
- O container PHP é reiniciado (não apenas reload) ao mudar `php.ini` porque o bind mount de arquivo único perde a referência quando o arquivo é reescrito (inode muda)
- Modais de confirmação usam componente Alpine.js (`components/confirm-modal.blade.php`) em vez de `confirm()` nativo do navegador — dispatch evento `confirm-action` com título, mensagem, ID do form e variante (danger/warning)
