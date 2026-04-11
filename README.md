# WP Docker Manager

Ambiente Docker completo para desenvolvimento local de múltiplos sites WordPress, com painel de gerenciamento visual em Laravel.

## Stack

- **Nginx** (Alpine) — Reverse proxy com subdomínios automáticos
- **PHP 8.3-FPM** — Com extensões otimizadas para WordPress
- **MySQL 8.0** — Banco de dados com charset utf8mb4
- **phpMyAdmin** — Gerenciamento visual do banco
- **Mailpit** — Servidor de emails para testes
- **WP-CLI** — Linha de comando para WordPress
- **Laravel 13** — Painel de gerenciamento visual

## Requisitos

- Docker Desktop (ou Docker Engine + Docker Compose V2)
- Git (opcional)
- Portas livres: 80, 3309, 8080, 8025, 1025

## Instalação

```bash
git clone <repo-url> wp-docker-manager
cd wp-docker-manager
chmod +x setup.sh wp-manager.sh
./setup.sh
```

O setup vai construir os containers, instalar o Laravel e configurar tudo automaticamente.

## Acessos

| Serviço         | URL                          |
| --------------- | ---------------------------- |
| Painel Manager  | http://manager.localhost      |
| phpMyAdmin      | http://localhost:8080         |
| Mailpit         | http://localhost:8025         |
| Sites WordPress | http://{nome}.localhost       |

## Comandos

### Gerenciamento de Sites

```bash
# Criar site (WordPress latest, pt-BR)
./wp-manager.sh create meusite

# Criar com versão específica
./wp-manager.sh create loja --version 6.5

# Criar com WooCommerce
./wp-manager.sh create ecommerce --woocommerce

# Criar multisite
./wp-manager.sh create rede --multisite

# Criar com todas as opções
./wp-manager.sh create projeto \
  --version 6.6 \
  --title "Meu Projeto" \
  --admin-user gabriel \
  --admin-pass minhasenha \
  --woocommerce

# Listar sites
./wp-manager.sh list

# Remover site (com banco)
./wp-manager.sh remove meusite

# Remover site (mantendo banco)
./wp-manager.sh remove meusite --keep-db

# Remover sem confirmação
./wp-manager.sh remove meusite --force

# Clonar site
./wp-manager.sh clone meusite meusite-staging

# Ativar/Desativar site
./wp-manager.sh stop-site meusite
./wp-manager.sh start-site meusite

# Atualizar WordPress
./wp-manager.sh update meusite
./wp-manager.sh update meusite 6.7
```

### Banco de Dados

```bash
# Exportar banco para SQL
./wp-manager.sh db export meusite

# Importar SQL
./wp-manager.sh db import meusite backup.sql

# Resetar banco (apaga tudo!)
./wp-manager.sh db reset meusite
```

### Backup & Restore

```bash
# Criar backup completo (arquivos + banco)
./wp-manager.sh backup meusite

# Restaurar de um backup
./wp-manager.sh restore meusite
```

### WP-CLI

```bash
# Abrir shell WP-CLI
./wp-manager.sh shell meusite

# Executar comando WP-CLI diretamente
./wp-manager.sh shell meusite plugin list
./wp-manager.sh shell meusite user list

# Instalar plugin
./wp-manager.sh plugin meusite install contact-form-7

# Instalar e ativar tema
./wp-manager.sh theme meusite install astra
```

### Logs

```bash
# Logs do WordPress (debug.log)
./wp-manager.sh logs meusite

# Logs dos containers
./wp-manager.sh logs nginx
./wp-manager.sh logs php
./wp-manager.sh logs mysql
```

### Docker

```bash
# Iniciar tudo
./wp-manager.sh up

# Parar tudo
./wp-manager.sh down

# Reiniciar
./wp-manager.sh restart

# Reconstruir containers (após mudar Dockerfile)
./wp-manager.sh rebuild

# Destruir tudo (containers + volumes + dados)
./wp-manager.sh destroy

# Status
./wp-manager.sh status
```

## O que vem configurado automaticamente

Cada site WordPress criado já vem com:

- Idioma: **Português (Brasil)**
- Timezone: **America/Sao_Paulo**
- Formato de data: **d/m/Y**
- Upload máximo: **256MB**
- **SVG e WebP** habilitados
- **Mailpit** configurado como SMTP
- **WP_DEBUG** ativado com log
- Permalinks: **/%postname%/**
- **Todo conteúdo padrão removido** (posts, páginas, comentários)
- **Plugins padrão removidos** (Hello Dolly, Akismet)
- **Temas padrão removidos** — apenas **Hello Elementor** instalado (sem plugin Elementor)
- Comentários desabilitados por padrão
- Badge "LOCAL DEV" na admin bar
- Atualizações automáticas desabilitadas
- **Logo customizada** na tela de login do wp-admin
- **Hardening de segurança**: XML-RPC desabilitado, enumeração de usuários bloqueada, proteção brute-force, headers de segurança, spam de comentários bloqueado

## Painel de Gerenciamento (Laravel)

Acesse **http://manager.localhost** para:

- Ver todos os sites WordPress, status dos containers e logs de atividade
- Criar e remover sites visualmente
- Ver detalhes de cada site (plugins, temas, disco, banco)
- **Logs**: visualizar registro de atividades e log do Laravel, com detalhes de erros
- **Configurações PHP**: editar memory_limit, upload máximo, tempo de execução e mais — aplicados em tempo real com verificação
- **Registro de Plugins**: cadastrar plugins (do WordPress.org ou upload de ZIP) para ficarem disponíveis na criação de sites
- **Configurações**: gerenciar credenciais padrão e trocar a logo do login
- **Exportar para Produção**: gera um ZIP pronto para deploy com domínio, credenciais de banco, wp-config.php otimizado, .htaccess com segurança e cache
- Selecionar plugins do registro ao criar um site (tela de seleção aparece automaticamente)
- Fazer backup e clonar sites
- Links rápidos para phpMyAdmin e Mailpit

## Estrutura de Pastas

```
wp-docker-manager/
├── docker/
│   ├── nginx/
│   │   ├── nginx.conf              # Config global do Nginx
│   │   ├── conf.d/                 # Configs por site (gerado auto)
│   │   │   ├── default.conf        # Manager + localhost
│   │   │   └── site-*.conf         # Um por WordPress
│   │   └── templates/
│   │       └── wordpress.conf.template
│   ├── php/
│   │   ├── Dockerfile              # PHP-FPM para WordPress (+ WP-CLI + php.ini embutido)
│   │   ├── Dockerfile.manager      # PHP-FPM para Laravel (+ Docker CLI)
│   │   ├── entrypoint-manager.sh   # Entrypoint (acesso Docker socket)
│   │   ├── php.ini                 # Config PHP otimizada
│   │   └── www.conf                # Config PHP-FPM
│   ├── mysql/
│   │   ├── my.cnf                  # Config MySQL
│   │   └── init/                   # Scripts de inicialização
│   └── plugins/                    # ZIPs de plugins enviados via painel
├── manager/                        # Projeto Laravel (painel)
│   ├── app/
│   │   ├── Http/Controllers/
│   │   ├── Models/
│   │   └── Services/
│   ├── resources/views/
│   ├── routes/
│   └── ...
├── sites/                          # WordPress instalados
│   ├── meusite/
│   ├── loja/
│   └── ...
├── backups/                        # Backups dos sites
├── docker-compose.yml
├── wp-manager.sh                   # CLI principal
├── setup.sh                        # Setup inicial
└── README.md
```

## Configuração de Hosts (opcional)

Os subdomínios `*.localhost` geralmente já funcionam no Chrome e Firefox. Se algum navegador não resolver, adicione no `/etc/hosts`:

```
127.0.0.1 manager.localhost
127.0.0.1 meusite.localhost
127.0.0.1 loja.localhost
```

## Portas

| Porta | Serviço      |
| ----- | ------------ |
| 80    | Nginx        |
| 3309  | MySQL        |
| 8080  | phpMyAdmin   |
| 8025  | Mailpit Web  |
| 1025  | Mailpit SMTP |

A porta externa do MySQL é **3309** (a interna entre containers continua 3306).
Para conectar via cliente MySQL local: `mysql -h 127.0.0.1 -P 3309 -u root -proot`

## Credenciais Padrão

| Serviço    | Usuário      | Senha          |
| ---------- | ------------ | -------------- |
| WordPress  | devconecta   | Ga96911431@    |
| MySQL Root | root         | root           |
| MySQL User | wordpress    | wordpress      |

Todas as credenciais ficam visíveis e editáveis em **http://manager.localhost/settings**.

## Logo do Login WordPress

A logo da tela de login (`/wp-login.php`) é customizada sem nenhum plugin externo.
O arquivo `docker/assets/login-logo.svg` é copiado para `wp-content/mu-plugins/assets/` de cada site, e um arquivo PHP em `wp-content/mu-plugins/wp-local-dev.php` injeta CSS puro no hook `login_enqueue_scripts` para substituir a logo padrão do WordPress.

Para trocar a logo: acesse **http://manager.localhost/settings** (aba Logo), faça upload da nova imagem (SVG, PNG, JPG ou WebP), e ela será atualizada em todos os sites de uma vez.

## Registro de Plugins

O painel permite cadastrar plugins para ficarem disponíveis ao criar novos sites. Acesse **http://manager.localhost/settings?tab=plugins** para gerenciar.

**Duas formas de cadastrar:**
- **WordPress.org**: informe o slug do plugin (ex: `contact-form-7`) — será instalado do repositório oficial via WP-CLI
- **Upload ZIP**: envie o arquivo `.zip` do plugin (max 50MB) — armazenado em `docker/plugins/`

**Fluxo de criação com plugins:**
1. Preencha o formulário de criação do site normalmente
2. Se houver plugins cadastrados, uma tela de seleção aparece para marcar quais instalar
3. Se não houver nenhum plugin cadastrado, o site é criado diretamente (sem tela extra)
4. Todos os plugins selecionados são instalados e ativados automaticamente via WP-CLI

## Dicas

- Todos os emails enviados pelos WordPress caem no **Mailpit** (http://localhost:8025)
- Use `./wp-manager.sh shell <site>` para acessar o WP-CLI de qualquer site
- O painel em **http://manager.localhost** sincroniza automaticamente os sites do filesystem
- Erros de criação de sites ficam visíveis em **http://manager.localhost/logs** e no flash de erro com botão "Ver detalhes"
- Backups são salvos em `backups/<site>/<timestamp>/`
- Cada site tem seus próprios logs do Nginx separados
- Para exportar um site pronto para produção, use o botão "Exportar para Produção" na página de detalhes do site no painel
- Configurações do PHP (memória, upload, execução) podem ser alteradas em **http://manager.localhost/settings** (aba PHP) — as mudanças são aplicadas imediatamente e verificadas em tempo real
