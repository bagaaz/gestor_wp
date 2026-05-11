#!/bin/bash

# ===========================================================
#  WP Docker Manager - Gerenciador de sites WordPress locais
#  Autor: Gabriel @ Impacta Web
#  Versão: 1.0.0
# ===========================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # Sem cor
BOLD='\033[1m'

# Diretórios
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
SITES_DIR="${PROJECT_DIR}/sites"
NGINX_CONF_DIR="${PROJECT_DIR}/docker/nginx/conf.d"
NGINX_TEMPLATE="${PROJECT_DIR}/docker/nginx/templates/wordpress.conf.template"
BACKUPS_DIR="${PROJECT_DIR}/backups"
SCRIPTS_DIR="${PROJECT_DIR}/scripts"

# MySQL
MYSQL_ROOT_PASSWORD="root"
MYSQL_USER="wordpress"
MYSQL_PASSWORD="wordpress"
MYSQL_HOST="mysql"

# Defaults
DEFAULT_WP_VERSION="latest"
DEFAULT_WP_LOCALE="pt_BR"
DEFAULT_ADMIN_USER="devconecta"
DEFAULT_ADMIN_PASSWORD="Ga96911431@"
DEFAULT_ADMIN_EMAIL="admin@localhost.test"

# ===========================================================
# Funções utilitárias
# ===========================================================

print_banner() {
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════╗"
    echo "║          WP Docker Manager v1.0.0               ║"
    echo "║       Gerenciador de WordPress Local             ║"
    echo "╚══════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[AVISO]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERRO]${NC} $1"
}

confirm() {
    local message="$1"
    local default="${2:-n}"
    if [[ "$default" == "y" ]]; then
        read -p "$(echo -e "${YELLOW}${message} [Y/n]:${NC} ")" response
        response="${response:-y}"
    else
        read -p "$(echo -e "${YELLOW}${message} [y/N]:${NC} ")" response
        response="${response:-n}"
    fi
    [[ "$response" =~ ^[Yy]$ ]]
}

check_running() {
    if ! docker compose ps --status running 2>/dev/null | grep -q "wp-nginx"; then
        log_error "Os containers não estão rodando. Execute primeiro: ./wp-manager.sh up"
        exit 1
    fi
}

run_mysql() {
    docker compose exec -T mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD} -e "$1" 2>/dev/null
}

run_wpcli() {
    local site_name="$1"
    shift
    docker exec -u www-data -w "/var/www/sites/${site_name}" wp-php wp "$@"
}

get_db_name() {
    echo "wp_$(echo "$1" | tr '-' '_' | tr '.' '_')"
}

# ===========================================================
# Comando: create - Criar novo site WordPress
# ===========================================================
cmd_create() {
    local site_name=""
    local wp_version="${DEFAULT_WP_VERSION}"
    local wp_locale="${DEFAULT_WP_LOCALE}"
    local admin_user="${DEFAULT_ADMIN_USER}"
    local admin_pass="${DEFAULT_ADMIN_PASSWORD}"
    local admin_email="${DEFAULT_ADMIN_EMAIL}"
    local site_title=""
    local multisite=false
    local install_woocommerce=false

    # Parse argumentos
    while [[ $# -gt 0 ]]; do
        case $1 in
            --name|-n)       site_name="$2"; shift 2;;
            --version|-v)    wp_version="$2"; shift 2;;
            --locale|-l)     wp_locale="$2"; shift 2;;
            --admin-user)    admin_user="$2"; shift 2;;
            --admin-pass)    admin_pass="$2"; shift 2;;
            --admin-email)   admin_email="$2"; shift 2;;
            --title|-t)      site_title="$2"; shift 2;;
            --multisite)     multisite=true; shift;;
            --woocommerce)   install_woocommerce=true; shift;;
            *)               site_name="$1"; shift;;
        esac
    done

    if [[ -z "$site_name" ]]; then
        read -p "$(echo -e "${CYAN}Nome do site (ex: meusite):${NC} ")" site_name
    fi

    # Validar nome
    if [[ ! "$site_name" =~ ^[a-z0-9][a-z0-9-]*[a-z0-9]$ ]] && [[ ! "$site_name" =~ ^[a-z0-9]$ ]]; then
        log_error "Nome inválido. Use apenas letras minúsculas, números e hífens."
        exit 1
    fi

    if [[ -d "${SITES_DIR}/${site_name}" ]]; then
        log_error "O site '${site_name}' já existe!"
        exit 1
    fi

    site_title="${site_title:-${site_name}}"
    local db_name=$(get_db_name "$site_name")
    local site_url="http://${site_name}.localhost"

    echo ""
    log_info "Criando WordPress: ${BOLD}${site_name}${NC}"
    echo -e "  URL:      ${CYAN}${site_url}${NC}"
    echo -e "  Versão:   ${CYAN}${wp_version}${NC}"
    echo -e "  Idioma:   ${CYAN}${wp_locale}${NC}"
    echo -e "  Banco:    ${CYAN}${db_name}${NC}"
    echo -e "  Admin:    ${CYAN}${admin_user} / ${admin_pass}${NC}"
    echo ""

    check_running

    # 1. Criar banco de dados
    log_info "Criando banco de dados..."
    run_mysql "CREATE DATABASE IF NOT EXISTS \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    run_mysql "GRANT ALL PRIVILEGES ON \`${db_name}\`.* TO '${MYSQL_USER}'@'%'; FLUSH PRIVILEGES;"
    log_success "Banco '${db_name}' criado."

    # 2. Baixar WordPress
    log_info "Baixando WordPress ${wp_version}..."
    mkdir -p "${SITES_DIR}/${site_name}"

    if [[ "$wp_version" == "latest" ]]; then
        run_wpcli "$site_name" core download --locale="${wp_locale}"
    else
        run_wpcli "$site_name" core download --version="${wp_version}" --locale="${wp_locale}"
    fi
    log_success "WordPress baixado."

    # 3. Configurar wp-config.php
    log_info "Configurando wp-config.php..."
    run_wpcli "$site_name" config create \
        --dbname="${db_name}" \
        --dbuser="${MYSQL_USER}" \
        --dbpass="${MYSQL_PASSWORD}" \
        --dbhost="${MYSQL_HOST}" \
        --dbcharset="utf8mb4" \
        --dbcollate="utf8mb4_unicode_ci" \
        --locale="${wp_locale}" \
        --extra-php <<PHP
// Configurações de desenvolvimento
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
define('SCRIPT_DEBUG', true);
define('WP_ENVIRONMENT_TYPE', 'local');

// Desabilitar atualizações automáticas
define('AUTOMATIC_UPDATER_DISABLED', true);
define('WP_AUTO_UPDATE_CORE', false);

// Email via Mailpit
define('SMTP_HOST', 'mailpit');
define('SMTP_PORT', 1025);

// Upload de arquivos grandes
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// Permitir SVG e tipos extras
define('ALLOW_UNFILTERED_UPLOADS', true);

// URL do site
define('WP_HOME', '${site_url}');
define('WP_SITEURL', '${site_url}');

// File system direto
define('FS_METHOD', 'direct');
PHP

    log_success "wp-config.php configurado."

    # 4. Instalar WordPress
    log_info "Instalando WordPress..."
    if [[ "$multisite" == true ]]; then
        run_wpcli "$site_name" core multisite-install \
            --url="${site_url}" \
            --title="${site_title}" \
            --admin_user="${admin_user}" \
            --admin_password="${admin_pass}" \
            --admin_email="${admin_email}" \
            --skip-email
    else
        run_wpcli "$site_name" core install \
            --url="${site_url}" \
            --title="${site_title}" \
            --admin_user="${admin_user}" \
            --admin_password="${admin_pass}" \
            --admin_email="${admin_email}" \
            --skip-email
    fi
    log_success "WordPress instalado."

    # 5. Configurações pós-instalação
    log_info "Aplicando configurações pt-BR e otimizações..."

    # Permalinks
    run_wpcli "$site_name" rewrite structure '/%postname%/' --hard

    # Timezone e formato de data brasileiro
    run_wpcli "$site_name" option update timezone_string 'America/Sao_Paulo'
    run_wpcli "$site_name" option update date_format 'd/m/Y'
    run_wpcli "$site_name" option update time_format 'H:i'
    run_wpcli "$site_name" option update start_of_week '0'

    # Upload máximo
    run_wpcli "$site_name" option update upload_max_filesize '256M'

    # Desabilitar comentários por padrão
    run_wpcli "$site_name" option update default_comment_status 'closed'
    run_wpcli "$site_name" option update default_ping_status 'closed'
    run_wpcli "$site_name" option update default_pingback_flag '0'
    run_wpcli "$site_name" option update require_name_email '1'
    run_wpcli "$site_name" option update comment_registration '1'
    run_wpcli "$site_name" option update close_comments_for_old_posts '1'
    run_wpcli "$site_name" option update close_comments_days_old '0'
    run_wpcli "$site_name" option update thread_comments '0'

    log_success "Configurações aplicadas."

    # 6. Remover TODO conteúdo padrão
    log_info "Removendo conteúdo padrão (posts, páginas, comentários)..."

    # Remover TODOS os posts (Hello World, etc)
    local post_ids=$(run_wpcli "$site_name" post list --post_type=post --format=ids 2>/dev/null || echo "")
    if [[ -n "$post_ids" ]]; then
        run_wpcli "$site_name" post delete $post_ids --force 2>/dev/null || true
    fi

    # Remover TODAS as páginas de exemplo (Sample Page, Privacy Policy, etc)
    local page_ids=$(run_wpcli "$site_name" post list --post_type=page --format=ids 2>/dev/null || echo "")
    if [[ -n "$page_ids" ]]; then
        run_wpcli "$site_name" post delete $page_ids --force 2>/dev/null || true
    fi

    # Remover todos os comentários padrão
    local comment_ids=$(run_wpcli "$site_name" comment list --format=ids 2>/dev/null || echo "")
    if [[ -n "$comment_ids" ]]; then
        run_wpcli "$site_name" comment delete $comment_ids --force 2>/dev/null || true
    fi

    # Limpar lixeira
    run_wpcli "$site_name" post delete $(run_wpcli "$site_name" post list --post_status=trash --format=ids 2>/dev/null) --force 2>/dev/null || true

    log_success "Conteúdo padrão removido."

    # 7. Instalar Hello Elementor e remover temas extras
    log_info "Instalando tema Hello Elementor e removendo temas padrão..."

    run_wpcli "$site_name" theme install hello-elementor --activate

    # Remover todos os temas padrão (twentytwenty*, etc)
    local all_themes=$(run_wpcli "$site_name" theme list --status=inactive --field=name 2>/dev/null || echo "")
    for theme in $all_themes; do
        run_wpcli "$site_name" theme delete "$theme" 2>/dev/null || true
    done

    log_success "Hello Elementor ativado, temas padrão removidos."

    # 8. Desativar e remover plugins padrão desnecessários
    log_info "Removendo plugins padrão..."
    run_wpcli "$site_name" plugin deactivate hello --quiet 2>/dev/null || true
    run_wpcli "$site_name" plugin delete hello 2>/dev/null || true
    run_wpcli "$site_name" plugin deactivate akismet --quiet 2>/dev/null || true
    run_wpcli "$site_name" plugin delete akismet 2>/dev/null || true
    log_success "Plugins padrão removidos."

    # 9. Instalar mu-plugins (SVG/WebP, Mailpit, Login Logo, Segurança)
    log_info "Instalando mu-plugins..."
    mkdir -p "${SITES_DIR}/${site_name}/wp-content/mu-plugins"
    mkdir -p "${SITES_DIR}/${site_name}/wp-content/mu-plugins/assets"

    # Copiar logo para assets do mu-plugin
    if [[ -f "${PROJECT_DIR}/docker/assets/login-logo.svg" ]]; then
        cp "${PROJECT_DIR}/docker/assets/login-logo.svg" "${SITES_DIR}/${site_name}/wp-content/mu-plugins/assets/login-logo.svg"
    fi

    cat > "${SITES_DIR}/${site_name}/wp-content/mu-plugins/wp-local-dev.php" << 'MUPLUGIN'
<?php
/**
 * Plugin: WP Local Dev Helpers
 * SVG/WebP, SMTP Mailpit, Login Logo, Segurança, Badge DEV
 */

// ==============================
// Permitir upload de SVG e WebP
// ==============================
add_filter('upload_mimes', function ($mimes) {
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    $mimes['webp'] = 'image/webp';
    $mimes['avif'] = 'image/avif';
    return $mimes;
});

add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'svg' || $ext === 'svgz') {
        $data['type'] = 'image/svg+xml';
        $data['ext']  = $ext;
        $data['proper_filename'] = $filename;
    }
    return $data;
}, 10, 4);

add_action('admin_head', function () {
    echo '<style>
        .attachment-266x266, .thumbnail img {
            width: 100% !important;
            height: auto !important;
        }
    </style>';
});

// ==============================
// Configurar SMTP via Mailpit
// ==============================
add_action('phpmailer_init', function ($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'mailpit';
    $phpmailer->Port       = defined('SMTP_PORT') ? SMTP_PORT : 1025;
    $phpmailer->SMTPAuth   = false;
    $phpmailer->SMTPSecure = false;
    $phpmailer->SMTPAutoTLS = false;
});

// ==============================
// Aumentar limite de upload
// ==============================
add_filter('upload_size_limit', function () {
    return 256 * 1024 * 1024; // 256MB
});

// ==============================
// Logo customizada na tela de login
// ==============================
// Remover o script nativo de caps lock do WP (substituído pelo nosso)
add_action('login_footer', function () {
    wp_dequeue_script('user-profile');
    wp_deregister_script('user-profile');
}, 1);

add_action('login_enqueue_scripts', function () {
    $logo_url = content_url('mu-plugins/assets/login-logo.svg');

    // Cores configuráveis — atualizadas pelo painel em Configurações > Login
    $primary = '{{LOGIN_PRIMARY_COLOR}}';
    $bg      = '{{LOGIN_BG_COLOR}}';
    $text    = '{{LOGIN_TEXT_COLOR}}';

    // Cor escura do primary para hover
    $r = max(0, hexdec(substr($primary, 1, 2)) - 38);
    $g = max(0, hexdec(substr($primary, 3, 2)) - 38);
    $b = max(0, hexdec(substr($primary, 5, 2)) - 38);
    $primaryDark = sprintf('#%02x%02x%02x', $r, $g, $b);

    // RGBA do primary para sombras
    $pr = hexdec(substr($primary, 1, 2));
    $pg = hexdec(substr($primary, 3, 2));
    $pb = hexdec(substr($primary, 5, 2));
    $primaryRgba = "rgba({$pr}, {$pg}, {$pb}, 0.3)";

    // Encoded primary para SVG inline
    $primaryEncoded = '%23' . substr($primary, 1);

    echo '<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        /* Fonte */
        body.login,
        .login form,
        .login label,
        .login input,
        .login .message,
        .login #nav,
        .login #backtoblog {
            font-family: "Poppins", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }

        /* Fundo */
        body.login {
            background-color: ' . esc_attr($bg) . ' !important;
        }

        /* Logo */
        #login h1 a, .login h1 a {
            background-image: url(' . esc_url($logo_url) . ') !important;
            background-size: contain !important;
            background-repeat: no-repeat !important;
            background-position: center !important;
            width: 100% !important;
            height: 80px !important;
            margin-bottom: 20px !important;
        }

        /* Card do formulário */
        .login form#loginform,
        .login form#lostpasswordform,
        .login form#registerform {
            background: #fff !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px !important;
            box-shadow: 0 2px 8px rgba(17, 19, 23, 0.08) !important;
        }

        /* Labels */
        .login label {
            color: ' . esc_attr($text) . ' !important;
            font-weight: 500 !important;
        }

        /* Inputs */
        .login input[type="text"],
        .login input[type="password"] {
            border: 1px solid #d0d0d0 !important;
            border-radius: 6px !important;
            color: ' . esc_attr($text) . ' !important;
        }
        .login input[type="text"]:focus,
        .login input[type="password"]:focus {
            border-color: ' . esc_attr($primary) . ' !important;
            box-shadow: 0 0 0 1px ' . esc_attr($primary) . ' !important;
        }

        /* Botão principal */
        .wp-core-ui .button-primary {
            background: ' . esc_attr($primary) . ' !important;
            border-color: ' . esc_attr($primary) . ' !important;
            color: ' . esc_attr($bg) . ' !important;
            border-radius: 6px !important;
            text-shadow: none !important;
            box-shadow: 0 1px 3px ' . $primaryRgba . ' !important;
            transition: opacity 0.2s !important;
        }
        .wp-core-ui .button-primary:hover,
        .wp-core-ui .button-primary:focus {
            background: ' . esc_attr($primaryDark) . ' !important;
            border-color: ' . esc_attr($primaryDark) . ' !important;
            color: ' . esc_attr($bg) . ' !important;
        }

        /* Links */
        .login #nav a,
        .login #backtoblog a {
            color: ' . esc_attr($text) . ' !important;
            transition: color 0.2s !important;
        }
        .login #nav a:hover,
        .login #backtoblog a:hover {
            color: ' . esc_attr($primary) . ' !important;
        }

        /* Mensagens */
        .login .message,
        .login .success {
            border-left-color: ' . esc_attr($primary) . ' !important;
        }

        /* Checkbox */
        .login input[type="checkbox"]:checked::before {
            content: url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 20 20\'><path d=\'M14.83 4.89l1.34.94-7.37 10.5-5.02-5.02 1.42-1.42 3.36 3.36 6.27-8.36z\' fill=\'' . $primaryEncoded . '\'/></svg>") !important;
        }
        .login input[type="checkbox"]:focus {
            border-color: ' . esc_attr($primary) . ' !important;
            box-shadow: 0 0 0 1px ' . esc_attr($primary) . ' !important;
        }

        /* Botão mostrar/ocultar senha */
        .login .wp-hide-pw:focus {
            outline: none !important;
            box-shadow: none !important;
            border: none !important;
        }

        /* Seletor de idioma */
        .language-switcher {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            margin-top: 16px !important;
            padding: 0 !important;
        }
        .language-switcher #language-switcher {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
        }
        .language-switcher .dashicons {
            color: ' . esc_attr($text) . ' !important;
            opacity: 0.5 !important;
        }
        .language-switcher select {
            border: 1px solid #d0d0d0 !important;
            border-radius: 6px !important;
            padding: 4px 8px !important;
            font-size: 13px !important;
            color: ' . esc_attr($text) . ' !important;
            background: #fff !important;
            font-family: "Poppins", sans-serif !important;
        }
        .language-switcher select:focus {
            border-color: ' . esc_attr($primary) . ' !important;
            box-shadow: 0 0 0 1px ' . esc_attr($primary) . ' !important;
            outline: none !important;
        }
        .language-switcher .button {
            background: transparent !important;
            border: 1px solid #d0d0d0 !important;
            border-radius: 6px !important;
            color: ' . esc_attr($text) . ' !important;
            font-size: 13px !important;
            padding: 4px 12px !important;
            cursor: pointer !important;
            font-family: "Poppins", sans-serif !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }
        .language-switcher .button:hover {
            border-color: ' . esc_attr($primary) . ' !important;
            color: ' . esc_attr($primary) . ' !important;
        }

        /* Aviso Caps Lock */
        .caps-warning {
            background: ' . esc_attr($bg) . ' !important;
            border: 1px solid #d0d0d0 !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            margin-top: 8px !important;
            font-size: 12px !important;
            color: ' . esc_attr($text) . ' !important;
            font-family: "Poppins", sans-serif !important;
            font-weight: 500 !important;
        }
        .caps-warning .caps-icon {
            vertical-align: middle !important;
            margin-right: 6px !important;
        }
        .caps-warning .caps-icon svg {
            width: 16px !important;
            height: 16px !important;
            vertical-align: middle !important;
            fill: ' . esc_attr($primary) . ' !important;
            stroke: ' . esc_attr($primary) . ' !important;
        }
        .caps-warning .caps-warning-text {
            vertical-align: middle !important;
        }
    </style>';

    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        var passInput = document.getElementById("user_pass");
        if (!passInput) return;

        var warning = document.getElementById("caps-warning");

        // Se o WP ainda não criou o elemento, criar manualmente via DOM
        if (!warning) {
            var wrapper = passInput.closest("div");
            if (!wrapper) return;
            warning = document.createElement("div");
            warning.id = "caps-warning";
            warning.className = "caps-warning";
            warning.style.display = "none";

            var icon = document.createElement("span");
            icon.className = "caps-icon";
            icon.setAttribute("aria-hidden", "true");
            var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            svg.setAttribute("viewBox", "0 0 24 26");
            var path = document.createElementNS("http://www.w3.org/2000/svg", "path");
            path.setAttribute("d", "M12 5L19 15H16V19H8V15H5L12 5Z");
            var rect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
            rect.setAttribute("x", "8"); rect.setAttribute("y", "21");
            rect.setAttribute("width", "8"); rect.setAttribute("height", "1.5");
            rect.setAttribute("rx", "0.75");
            svg.appendChild(path); svg.appendChild(rect);
            icon.appendChild(svg);

            var text = document.createElement("span");
            text.className = "caps-warning-text";
            text.textContent = "Caps Lock ativado";

            warning.appendChild(icon);
            warning.appendChild(text);
            wrapper.appendChild(warning);
        }

        function updateCapsLock(e) {
            if (typeof e.getModifierState === "function") {
                var capsOn = e.getModifierState("CapsLock");
                warning.style.display = capsOn ? "block" : "none";
            }
        }

        passInput.addEventListener("keydown", updateCapsLock);
        passInput.addEventListener("keyup", updateCapsLock);
    });
    </script>';
});

// Link da logo aponta para o próprio site
add_filter('login_headerurl', function () {
    return home_url();
});

add_filter('login_headertext', function () {
    return get_bloginfo('name');
});

// ==============================
// SEGURANÇA - Hardening WordPress
// ==============================

// Desabilitar XML-RPC completamente
add_filter('xmlrpc_enabled', '__return_false');
add_filter('wp_headers', function ($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});

// Bloquear acesso ao xmlrpc.php
add_action('init', function () {
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
        wp_die('XML-RPC desabilitado.', 'Acesso negado', ['response' => 403]);
    }
});

// Desabilitar REST API para usuários não logados (endpoints sensíveis)
add_filter('rest_authentication_errors', function ($result) {
    if (!is_user_logged_in()) {
        $allowed = ['/wp/v2/pages', '/wp/v2/posts', '/wp/v2/categories', '/wp/v2/tags', '/wp/v2/media'];
        $path = $_SERVER['REQUEST_URI'] ?? '';
        // Bloquear /wp/v2/users para não logados (evita enumeration)
        if (strpos($path, '/wp/v2/users') !== false) {
            return new WP_Error('rest_forbidden', 'Acesso negado.', ['status' => 403]);
        }
    }
    return $result;
});

// Desabilitar enumeração de usuários via ?author=N
add_action('template_redirect', function () {
    if (is_author() && !is_user_logged_in()) {
        wp_redirect(home_url(), 301);
        exit;
    }
});

// Remover versão do WordPress do head e feeds
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

// Remover headers desnecessários
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'wp_resource_hints', 2);

// Desabilitar emojis do WordPress (melhora performance + menos surface)
add_action('init', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
});

// Limitar tentativas de login (proteção brute-force básica)
add_filter('authenticate', function ($user, $username, $password) {
    if (empty($username) || empty($password)) return $user;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $transient_key = 'login_attempts_' . md5($ip);
    $attempts = (int) get_transient($transient_key);

    if ($attempts >= 5) {
        return new WP_Error('too_many_attempts',
            '<strong>BLOQUEADO:</strong> Muitas tentativas de login. Aguarde 15 minutos.');
    }

    return $user;
}, 30, 3);

add_action('wp_login_failed', function () {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $transient_key = 'login_attempts_' . md5($ip);
    $attempts = (int) get_transient($transient_key);
    set_transient($transient_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
});

// Resetar contador ao logar com sucesso
add_action('wp_login', function () {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    delete_transient('login_attempts_' . md5($ip));
});

// Desabilitar pingbacks
add_filter('pings_open', '__return_false', 20, 2);

// Desabilitar trackbacks
add_filter('pre_option_default_ping_status', '__return_zero');

// Bloquear spam comments com links
add_filter('preprocess_comment', function ($commentdata) {
    $content = $commentdata['comment_content'] ?? '';
    // Bloquear se tiver mais de 2 links
    if (preg_match_all('/https?:\/\//', $content) > 2) {
        wp_die('Comentário bloqueado: muitos links detectados.', 'Spam detectado', ['response' => 403]);
    }
    return $commentdata;
});

// Headers de segurança
add_action('send_headers', function () {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
});

// Desabilitar edição de arquivos pelo admin (segurança)
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

// ==============================
// Desabilitar update nags no admin
// ==============================
add_action('admin_init', function () {
    remove_action('admin_notices', 'update_nag', 3);
});

// ==============================
// Badge LOCAL DEV na admin bar
// ==============================
add_action('admin_bar_menu', function ($wp_admin_bar) {
    $wp_admin_bar->add_node([
        'id'    => 'local-env',
        'title' => '🔧 LOCAL DEV',
        'meta'  => ['class' => 'local-env-badge'],
    ]);
}, 999);

add_action('admin_head', function () {
    echo '<style>
        #wpadminbar .local-env-badge .ab-item {
            background: #e74c3c !important;
            color: #fff !important;
            font-weight: bold;
        }
    </style>';
});
MUPLUGIN

    # Substituir placeholders de cores do login
    local login_primary login_bg login_text
    login_primary=$(docker compose exec -T mysql mysql -u root -proot wp_manager -N -e "SELECT value FROM settings WHERE \`key\`='login_primary_color'" 2>/dev/null | tr -d '\r\n')
    login_bg=$(docker compose exec -T mysql mysql -u root -proot wp_manager -N -e "SELECT value FROM settings WHERE \`key\`='login_bg_color'" 2>/dev/null | tr -d '\r\n')
    login_text=$(docker compose exec -T mysql mysql -u root -proot wp_manager -N -e "SELECT value FROM settings WHERE \`key\`='login_text_color'" 2>/dev/null | tr -d '\r\n')

    # Usar defaults se não houver configuração
    login_primary="${login_primary:-#204AE3}"
    login_bg="${login_bg:-#f5f5f5}"
    login_text="${login_text:-#111317}"

    sed -i "s|{{LOGIN_PRIMARY_COLOR}}|${login_primary}|g" "${SITES_DIR}/${site_name}/wp-content/mu-plugins/wp-local-dev.php"
    sed -i "s|{{LOGIN_BG_COLOR}}|${login_bg}|g" "${SITES_DIR}/${site_name}/wp-content/mu-plugins/wp-local-dev.php"
    sed -i "s|{{LOGIN_TEXT_COLOR}}|${login_text}|g" "${SITES_DIR}/${site_name}/wp-content/mu-plugins/wp-local-dev.php"

    log_success "mu-plugins instalados."

    # 10. WooCommerce (se solicitado)
    if [[ "$install_woocommerce" == true ]]; then
        log_info "Instalando WooCommerce..."
        run_wpcli "$site_name" plugin install woocommerce --activate
        log_success "WooCommerce instalado e ativado."
    fi

    # 11. Configurar Nginx
    log_info "Configurando Nginx..."
    sed "s/{{SITE_NAME}}/${site_name}/g" "${NGINX_TEMPLATE}" > "${NGINX_CONF_DIR}/site-${site_name}.conf"
    docker compose exec nginx nginx -s reload 2>/dev/null || docker compose restart nginx
    log_success "Nginx configurado."

    # 12. Permissões
    log_info "Ajustando permissões..."
    chmod -R 775 "${SITES_DIR}/${site_name}"
    log_success "Permissões ajustadas."

    # 13. Registrar no painel manager (via API)
    log_info "Registrando site no painel de gerenciamento..."
    curl -s -X POST "http://manager.localhost/api/sites" \
        -H "Content-Type: application/json" \
        -d "{\"name\":\"${site_name}\",\"url\":\"${site_url}\",\"db_name\":\"${db_name}\",\"wp_version\":\"${wp_version}\",\"admin_user\":\"${admin_user}\",\"admin_email\":\"${admin_email}\",\"status\":\"active\"}" \
        > /dev/null 2>&1 || true

    # Resultado final
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║  ✅ WordPress criado com sucesso!                ║${NC}"
    echo -e "${GREEN}╠══════════════════════════════════════════════════╣${NC}"
    echo -e "${GREEN}║${NC}  Site:    ${CYAN}${site_url}${NC}"
    echo -e "${GREEN}║${NC}  Admin:   ${CYAN}${site_url}/wp-admin${NC}"
    echo -e "${GREEN}║${NC}  Usuário: ${CYAN}${admin_user}${NC}"
    echo -e "${GREEN}║${NC}  Senha:   ${CYAN}${admin_pass}${NC}"
    echo -e "${GREEN}║${NC}  Banco:   ${CYAN}${db_name}${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════╝${NC}"
    echo ""
}

# ===========================================================
# Comando: remove - Remover site WordPress
# ===========================================================
cmd_remove() {
    local site_name=""
    local drop_db=true
    local force=false

    while [[ $# -gt 0 ]]; do
        case $1 in
            --name|-n)     site_name="$2"; shift 2;;
            --keep-db)     drop_db=false; shift;;
            --force|-f)    force=true; shift;;
            *)             site_name="$1"; shift;;
        esac
    done

    if [[ -z "$site_name" ]]; then
        read -p "$(echo -e "${CYAN}Nome do site a remover:${NC} ")" site_name
    fi

    if [[ ! -d "${SITES_DIR}/${site_name}" ]]; then
        log_error "Site '${site_name}' não encontrado."
        exit 1
    fi

    local db_name=$(get_db_name "$site_name")

    echo ""
    log_warn "Você está prestes a remover: ${BOLD}${site_name}${NC}"
    echo -e "  Diretório: ${SITES_DIR}/${site_name}"
    echo -e "  Banco:     ${db_name} $([ "$drop_db" == true ] && echo "(SERÁ REMOVIDO)" || echo "(será mantido)")"
    echo ""

    if [[ "$force" != true ]]; then
        if ! confirm "Tem certeza que deseja continuar?"; then
            log_info "Operação cancelada."
            exit 0
        fi
    fi

    check_running

    # 1. Backup antes de remover (segurança)
    log_info "Criando backup de segurança..."
    cmd_backup "$site_name" 2>/dev/null || true

    # 2. Remover banco de dados
    if [[ "$drop_db" == true ]]; then
        log_info "Removendo banco de dados '${db_name}'..."
        run_mysql "DROP DATABASE IF EXISTS \`${db_name}\`;"
        log_success "Banco removido."
    else
        log_info "Mantendo banco de dados '${db_name}'."
    fi

    # 3. Remover config do nginx
    log_info "Removendo configuração do Nginx..."
    rm -f "${NGINX_CONF_DIR}/site-${site_name}.conf"
    docker compose exec nginx nginx -s reload 2>/dev/null || docker compose restart nginx
    log_success "Config Nginx removida."

    # 4. Remover arquivos (usar docker exec como root para garantir permissão sobre arquivos criados pelo WP-CLI)
    log_info "Removendo arquivos do site..."
    docker exec wp-php rm -rf "/var/www/sites/${site_name}" 2>/dev/null || rm -rf "${SITES_DIR}/${site_name}"
    log_success "Arquivos removidos."

    # 5. Remover do painel manager
    curl -s -X DELETE "http://manager.localhost/api/sites/${site_name}" > /dev/null 2>&1 || true

    echo ""
    log_success "Site '${site_name}' removido com sucesso!"
    echo ""
}

# ===========================================================
# Comando: list - Listar sites
# ===========================================================
cmd_list() {
    echo ""
    echo -e "${BOLD}Sites WordPress instalados:${NC}"
    echo -e "${CYAN}──────────────────────────────────────────────────${NC}"

    if [[ ! -d "$SITES_DIR" ]] || [[ -z "$(ls -A "$SITES_DIR" 2>/dev/null)" ]]; then
        echo -e "  ${YELLOW}Nenhum site encontrado.${NC}"
        echo ""
        return
    fi

    printf "  ${BOLD}%-20s %-30s %-15s${NC}\n" "NOME" "URL" "STATUS"
    echo -e "  ${CYAN}──────────────────────────────────────────────────${NC}"

    for site_dir in "${SITES_DIR}"/*/; do
        if [[ -f "${site_dir}wp-config.php" ]]; then
            local name=$(basename "$site_dir")
            local url="http://${name}.localhost"
            local status="${GREEN}ativo${NC}"

            # Verificar se o nginx conf existe
            if [[ ! -f "${NGINX_CONF_DIR}/site-${name}.conf" ]]; then
                status="${RED}sem nginx${NC}"
            fi

            printf "  %-20s %-30s %-15b\n" "$name" "$url" "$status"
        fi
    done

    echo ""
    echo -e "  ${BOLD}Links úteis:${NC}"
    echo -e "  Painel:      ${CYAN}http://manager.localhost${NC}"
    echo -e "  phpMyAdmin:  ${CYAN}http://localhost:8080${NC}"
    echo -e "  Mailpit:     ${CYAN}http://localhost:8025${NC}"
    echo ""
}

# ===========================================================
# Comando: backup - Backup de um site
# ===========================================================
cmd_backup() {
    local site_name="$1"

    if [[ -z "$site_name" ]]; then
        read -p "$(echo -e "${CYAN}Nome do site para backup:${NC} ")" site_name
    fi

    if [[ ! -d "${SITES_DIR}/${site_name}" ]]; then
        log_error "Site '${site_name}' não encontrado."
        exit 1
    fi

    check_running

    local db_name=$(get_db_name "$site_name")
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_dir="${BACKUPS_DIR}/${site_name}/${timestamp}"

    mkdir -p "$backup_dir"

    # Backup do banco
    log_info "Exportando banco de dados..."
    docker compose exec -T mysql mysqldump -uroot -p${MYSQL_ROOT_PASSWORD} "${db_name}" > "${backup_dir}/database.sql" 2>/dev/null
    log_success "Banco exportado."

    # Backup dos arquivos
    log_info "Compactando arquivos do site..."
    tar -czf "${backup_dir}/files.tar.gz" -C "${SITES_DIR}" "${site_name}"
    log_success "Arquivos compactados."

    echo ""
    log_success "Backup salvo em: ${backup_dir}"
    echo ""
}

# ===========================================================
# Comando: restore - Restaurar backup
# ===========================================================
cmd_restore() {
    local site_name="$1"

    if [[ -z "$site_name" ]]; then
        read -p "$(echo -e "${CYAN}Nome do site para restaurar:${NC} ")" site_name
    fi

    local backup_base="${BACKUPS_DIR}/${site_name}"

    if [[ ! -d "$backup_base" ]]; then
        log_error "Nenhum backup encontrado para '${site_name}'."
        exit 1
    fi

    # Listar backups disponíveis
    echo ""
    echo -e "${BOLD}Backups disponíveis para '${site_name}':${NC}"
    local i=1
    local backups=()
    for bdir in "${backup_base}"/*/; do
        backups+=("$(basename "$bdir")")
        echo "  ${i}) $(basename "$bdir")"
        ((i++))
    done

    read -p "$(echo -e "${CYAN}Escolha o backup (número):${NC} ")" choice
    local selected="${backups[$((choice-1))]}"
    local backup_dir="${backup_base}/${selected}"

    if [[ ! -d "$backup_dir" ]]; then
        log_error "Backup inválido."
        exit 1
    fi

    check_running

    local db_name=$(get_db_name "$site_name")

    # Restaurar banco
    if [[ -f "${backup_dir}/database.sql" ]]; then
        log_info "Restaurando banco de dados..."
        run_mysql "DROP DATABASE IF EXISTS \`${db_name}\`; CREATE DATABASE \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        docker compose exec -T mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD} "${db_name}" < "${backup_dir}/database.sql"
        log_success "Banco restaurado."
    fi

    # Restaurar arquivos
    if [[ -f "${backup_dir}/files.tar.gz" ]]; then
        log_info "Restaurando arquivos..."
        rm -rf "${SITES_DIR}/${site_name}"
        tar -xzf "${backup_dir}/files.tar.gz" -C "${SITES_DIR}"
        log_success "Arquivos restaurados."
    fi

    # Reconfigurar nginx
    if [[ ! -f "${NGINX_CONF_DIR}/site-${site_name}.conf" ]]; then
        sed "s/{{SITE_NAME}}/${site_name}/g" "${NGINX_TEMPLATE}" > "${NGINX_CONF_DIR}/site-${site_name}.conf"
        docker compose exec nginx nginx -s reload 2>/dev/null || docker compose restart nginx
    fi

    echo ""
    log_success "Site '${site_name}' restaurado com sucesso!"
    echo ""
}

# ===========================================================
# Comando: clone - Clonar site existente
# ===========================================================
cmd_clone() {
    local source="$1"
    local target="$2"

    if [[ -z "$source" ]] || [[ -z "$target" ]]; then
        echo "Uso: $0 clone <site-origem> <site-destino>"
        exit 1
    fi

    if [[ ! -d "${SITES_DIR}/${source}" ]]; then
        log_error "Site de origem '${source}' não encontrado."
        exit 1
    fi

    if [[ -d "${SITES_DIR}/${target}" ]]; then
        log_error "Site de destino '${target}' já existe."
        exit 1
    fi

    check_running

    local source_db=$(get_db_name "$source")
    local target_db=$(get_db_name "$target")
    local target_url="http://${target}.localhost"

    log_info "Clonando '${source}' para '${target}'..."

    # Copiar arquivos
    log_info "Copiando arquivos..."
    cp -r "${SITES_DIR}/${source}" "${SITES_DIR}/${target}"
    log_success "Arquivos copiados."

    # Clonar banco
    log_info "Clonando banco de dados..."
    run_mysql "CREATE DATABASE IF NOT EXISTS \`${target_db}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    docker compose exec -T mysql mysqldump -uroot -p${MYSQL_ROOT_PASSWORD} "${source_db}" | \
        docker compose exec -T mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD} "${target_db}"
    run_mysql "GRANT ALL PRIVILEGES ON \`${target_db}\`.* TO '${MYSQL_USER}'@'%'; FLUSH PRIVILEGES;"
    log_success "Banco clonado."

    # Atualizar wp-config
    log_info "Atualizando configurações..."
    sed -i "s/${source_db}/${target_db}/g" "${SITES_DIR}/${target}/wp-config.php"
    sed -i "s|http://${source}.localhost|${target_url}|g" "${SITES_DIR}/${target}/wp-config.php"

    # Search-replace no banco
    run_wpcli "$target" search-replace "http://${source}.localhost" "${target_url}" --all-tables

    # Nginx
    sed "s/{{SITE_NAME}}/${target}/g" "${NGINX_TEMPLATE}" > "${NGINX_CONF_DIR}/site-${target}.conf"
    docker compose exec nginx nginx -s reload 2>/dev/null || docker compose restart nginx

    log_success "Site clonado com sucesso!"
    echo -e "  Acesse: ${CYAN}${target_url}${NC}"
    echo ""
}

# ===========================================================
# Comando: stop-site / start-site - Parar/Iniciar site
# ===========================================================
cmd_stop_site() {
    local site_name="$1"
    if [[ -z "$site_name" ]]; then
        log_error "Especifique o nome do site."
        exit 1
    fi

    if [[ -f "${NGINX_CONF_DIR}/site-${site_name}.conf" ]]; then
        mv "${NGINX_CONF_DIR}/site-${site_name}.conf" "${NGINX_CONF_DIR}/site-${site_name}.conf.disabled"
        docker compose exec nginx nginx -s reload 2>/dev/null || docker compose restart nginx
        log_success "Site '${site_name}' desativado."
    else
        log_warn "Site já está desativado ou não existe."
    fi
}

cmd_start_site() {
    local site_name="$1"
    if [[ -z "$site_name" ]]; then
        log_error "Especifique o nome do site."
        exit 1
    fi

    if [[ -f "${NGINX_CONF_DIR}/site-${site_name}.conf.disabled" ]]; then
        mv "${NGINX_CONF_DIR}/site-${site_name}.conf.disabled" "${NGINX_CONF_DIR}/site-${site_name}.conf"
        docker compose exec nginx nginx -s reload 2>/dev/null || docker compose restart nginx
        log_success "Site '${site_name}' ativado."
    else
        log_warn "Site já está ativo ou não existe."
    fi
}

# ===========================================================
# Comando: shell - Acessar WP-CLI de um site
# ===========================================================
cmd_shell() {
    local site_name="$1"
    if [[ -z "$site_name" ]]; then
        read -p "$(echo -e "${CYAN}Nome do site:${NC} ")" site_name
    fi
    shift 2>/dev/null || true
    if [[ $# -gt 0 ]]; then
        run_wpcli "$site_name" "$@"
    else
        docker exec -it -w "/var/www/sites/${site_name}" wp-php bash
    fi
}

# ===========================================================
# Comando: plugin/theme - Instalar plugin ou tema
# ===========================================================
cmd_plugin() {
    local site_name="$1"
    local action="${2:-install}"
    local plugin_name="$3"

    if [[ -z "$site_name" ]] || [[ -z "$plugin_name" ]]; then
        echo "Uso: $0 plugin <site> <install|activate|deactivate|delete> <plugin-slug>"
        exit 1
    fi

    check_running
    run_wpcli "$site_name" plugin "$action" "$plugin_name"
    log_success "Plugin '${plugin_name}' - ação '${action}' executada em '${site_name}'."
}

cmd_theme() {
    local site_name="$1"
    local action="${2:-install}"
    local theme_name="$3"

    if [[ -z "$site_name" ]] || [[ -z "$theme_name" ]]; then
        echo "Uso: $0 theme <site> <install|activate|delete> <theme-slug>"
        exit 1
    fi

    check_running
    run_wpcli "$site_name" theme "$action" "$theme_name"
    log_success "Tema '${theme_name}' - ação '${action}' executada em '${site_name}'."
}

# ===========================================================
# Comando: update - Atualizar WordPress de um site
# ===========================================================
cmd_update() {
    local site_name="$1"
    local target_version="$2"

    if [[ -z "$site_name" ]]; then
        read -p "$(echo -e "${CYAN}Nome do site:${NC} ")" site_name
    fi

    check_running

    # Backup antes
    log_info "Criando backup antes da atualização..."
    cmd_backup "$site_name"

    if [[ -n "$target_version" ]]; then
        log_info "Atualizando WordPress para versão ${target_version}..."
        run_wpcli "$site_name" core update --version="$target_version"
    else
        log_info "Atualizando WordPress para a versão mais recente..."
        run_wpcli "$site_name" core update
    fi

    run_wpcli "$site_name" core update-db
    log_success "WordPress atualizado com sucesso!"
}

# ===========================================================
# Comando: logs - Ver logs de um site
# ===========================================================
cmd_logs() {
    local site_name="$1"
    local lines="${2:-50}"

    if [[ -z "$site_name" ]]; then
        echo "Uso: $0 logs <site> [número-de-linhas]"
        echo ""
        echo "Logs disponíveis:"
        echo "  nginx   - Logs do Nginx (todos os sites)"
        echo "  php     - Logs do PHP-FPM"
        echo "  mysql   - Logs do MySQL"
        echo "  <site>  - Debug log do WordPress"
        return
    fi

    case "$site_name" in
        nginx)  docker compose logs --tail="$lines" -f nginx;;
        php)    docker compose logs --tail="$lines" -f php;;
        mysql)  docker compose logs --tail="$lines" -f mysql;;
        *)
            local debug_log="${SITES_DIR}/${site_name}/wp-content/debug.log"
            if [[ -f "$debug_log" ]]; then
                tail -n "$lines" -f "$debug_log"
            else
                log_warn "Nenhum debug.log encontrado para '${site_name}'."
                log_info "Mostrando logs do Nginx para este site..."
                docker compose exec nginx tail -n "$lines" -f "/var/log/nginx/${site_name}-access.log" "/var/log/nginx/${site_name}-error.log"
            fi
            ;;
    esac
}

# ===========================================================
# Comando: db - Operações de banco de dados
# ===========================================================
cmd_db() {
    local action="$1"
    local site_name="$2"

    case "$action" in
        export)
            if [[ -z "$site_name" ]]; then
                log_error "Especifique o nome do site."
                exit 1
            fi
            local db_name=$(get_db_name "$site_name")
            local export_file="${PROJECT_DIR}/${site_name}-db-$(date +%Y%m%d_%H%M%S).sql"
            docker compose exec -T mysql mysqldump -uroot -p${MYSQL_ROOT_PASSWORD} "${db_name}" > "$export_file"
            log_success "Banco exportado para: ${export_file}"
            ;;
        import)
            local sql_file="$3"
            if [[ -z "$site_name" ]] || [[ -z "$sql_file" ]]; then
                echo "Uso: $0 db import <site> <arquivo.sql>"
                exit 1
            fi
            local db_name=$(get_db_name "$site_name")
            docker compose exec -T mysql mysql -uroot -p${MYSQL_ROOT_PASSWORD} "${db_name}" < "$sql_file"
            log_success "Banco importado com sucesso!"
            ;;
        reset)
            if [[ -z "$site_name" ]]; then
                log_error "Especifique o nome do site."
                exit 1
            fi
            if ! confirm "Isso vai APAGAR todos os dados do banco de '${site_name}'. Continuar?"; then
                exit 0
            fi
            local db_name=$(get_db_name "$site_name")
            run_mysql "DROP DATABASE IF EXISTS \`${db_name}\`; CREATE DATABASE \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
            run_mysql "GRANT ALL PRIVILEGES ON \`${db_name}\`.* TO '${MYSQL_USER}'@'%'; FLUSH PRIVILEGES;"
            log_success "Banco '${db_name}' resetado."
            ;;
        *)
            echo "Uso: $0 db <export|import|reset> <site> [arquivo.sql]"
            ;;
    esac
}

# ===========================================================
# Comando: status - Status dos containers
# ===========================================================
cmd_status() {
    echo ""
    echo -e "${BOLD}Status dos containers:${NC}"
    echo -e "${CYAN}──────────────────────────────────────────────────${NC}"
    docker compose ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"
    echo ""
    cmd_list
}

# ===========================================================
# Comandos Docker: up, down, restart, rebuild
# ===========================================================
cmd_up() {
    log_info "Iniciando todos os serviços..."
    docker compose up -d --build
    echo ""
    log_success "Todos os serviços iniciados!"
    echo ""
    echo -e "  Painel:      ${CYAN}http://manager.localhost${NC}"
    echo -e "  phpMyAdmin:  ${CYAN}http://localhost:8080${NC}"
    echo -e "  Mailpit:     ${CYAN}http://localhost:8025${NC}"
    echo ""
}

cmd_down() {
    log_info "Parando todos os serviços..."
    docker compose down
    log_success "Serviços parados."
}

cmd_restart() {
    log_info "Reiniciando serviços..."
    docker compose restart
    log_success "Serviços reiniciados."
}

cmd_rebuild() {
    log_info "Reconstruindo containers..."
    docker compose down
    docker compose build --no-cache
    docker compose up -d
    log_success "Containers reconstruídos!"
}

cmd_destroy() {
    log_warn "Isso vai REMOVER todos os containers, volumes e dados!"
    if confirm "Tem certeza absoluta?"; then
        docker compose down -v --remove-orphans
        log_success "Tudo removido."
    fi
}

# ===========================================================
# Comando: help
# ===========================================================
cmd_help() {
    print_banner
    echo -e "${BOLD}Uso:${NC} ./wp-manager.sh <comando> [opções]"
    echo ""
    echo -e "${BOLD}Gerenciamento de Sites:${NC}"
    echo -e "  ${GREEN}create${NC} <nome> [opções]     Criar novo site WordPress"
    echo -e "    --version, -v <ver>       Versão do WP (default: latest)"
    echo -e "    --locale, -l <locale>     Idioma (default: pt_BR)"
    echo -e "    --title, -t <título>      Título do site"
    echo -e "    --admin-user <user>       Usuário admin (default: admin)"
    echo -e "    --admin-pass <pass>       Senha admin (default: admin123)"
    echo -e "    --multisite               Instalar como multisite"
    echo -e "    --woocommerce             Instalar WooCommerce"
    echo ""
    echo -e "  ${GREEN}remove${NC} <nome> [opções]     Remover site"
    echo -e "    --keep-db                 Manter o banco de dados"
    echo -e "    --force, -f               Não pedir confirmação"
    echo ""
    echo -e "  ${GREEN}list${NC}                       Listar todos os sites"
    echo -e "  ${GREEN}clone${NC} <origem> <destino>   Clonar site existente"
    echo -e "  ${GREEN}start-site${NC} <nome>          Ativar site desativado"
    echo -e "  ${GREEN}stop-site${NC} <nome>           Desativar site"
    echo -e "  ${GREEN}update${NC} <nome> [versão]     Atualizar WordPress"
    echo -e "  ${GREEN}status${NC}                     Status dos containers e sites"
    echo ""
    echo -e "${BOLD}Banco de Dados:${NC}"
    echo -e "  ${GREEN}db export${NC} <nome>           Exportar banco para SQL"
    echo -e "  ${GREEN}db import${NC} <nome> <arquivo> Importar SQL no banco"
    echo -e "  ${GREEN}db reset${NC} <nome>            Resetar banco (CUIDADO!)"
    echo ""
    echo -e "${BOLD}Backup & Restore:${NC}"
    echo -e "  ${GREEN}backup${NC} <nome>              Criar backup completo"
    echo -e "  ${GREEN}restore${NC} <nome>             Restaurar de um backup"
    echo ""
    echo -e "${BOLD}WP-CLI & Plugins:${NC}"
    echo -e "  ${GREEN}shell${NC} <nome> [comando]     Acessar WP-CLI do site"
    echo -e "  ${GREEN}plugin${NC} <site> <ação> <slug> Gerenciar plugins"
    echo -e "  ${GREEN}theme${NC} <site> <ação> <slug>  Gerenciar temas"
    echo ""
    echo -e "${BOLD}Logs:${NC}"
    echo -e "  ${GREEN}logs${NC} <nome|nginx|php|mysql> [linhas]  Ver logs"
    echo ""
    echo -e "${BOLD}Docker:${NC}"
    echo -e "  ${GREEN}up${NC}                         Iniciar todos os serviços"
    echo -e "  ${GREEN}down${NC}                       Parar todos os serviços"
    echo -e "  ${GREEN}restart${NC}                    Reiniciar serviços"
    echo -e "  ${GREEN}rebuild${NC}                    Reconstruir containers"
    echo -e "  ${GREEN}destroy${NC}                    Remover TUDO (containers + dados)"
    echo ""
    echo -e "${BOLD}Exemplos:${NC}"
    echo -e "  ./wp-manager.sh create meusite"
    echo -e "  ./wp-manager.sh create loja --version 6.4.3 --woocommerce"
    echo -e "  ./wp-manager.sh clone meusite meusite-staging"
    echo -e "  ./wp-manager.sh remove meusite --keep-db"
    echo -e "  ./wp-manager.sh shell meusite plugin list"
    echo -e "  ./wp-manager.sh db export meusite"
    echo ""
}

# ===========================================================
# Router principal
# ===========================================================
main() {
    cd "$PROJECT_DIR"

    case "${1:-help}" in
        create)     shift; cmd_create "$@";;
        remove|rm)  shift; cmd_remove "$@";;
        list|ls)    cmd_list;;
        clone)      shift; cmd_clone "$@";;
        backup)     shift; cmd_backup "$@";;
        restore)    shift; cmd_restore "$@";;
        stop-site)  shift; cmd_stop_site "$@";;
        start-site) shift; cmd_start_site "$@";;
        shell|wp)   shift; cmd_shell "$@";;
        plugin)     shift; cmd_plugin "$@";;
        theme)      shift; cmd_theme "$@";;
        update)     shift; cmd_update "$@";;
        logs)       shift; cmd_logs "$@";;
        db)         shift; cmd_db "$@";;
        status)     cmd_status;;
        up)         cmd_up;;
        down)       cmd_down;;
        restart)    cmd_restart;;
        rebuild)    cmd_rebuild;;
        destroy)    cmd_destroy;;
        help|--help|-h) cmd_help;;
        *)
            log_error "Comando desconhecido: $1"
            echo "Use './wp-manager.sh help' para ver os comandos disponíveis."
            exit 1
            ;;
    esac
}

main "$@"
