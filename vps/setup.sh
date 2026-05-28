#!/bin/bash

# ===========================================================
#  Setup VPS - WP Manager (sem Docker)
#  Ubuntu 24 LTS | PHP 8.4 | MySQL | Nginx
#  Execute como root: bash vps/setup.sh
# ===========================================================

set -e

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'; BOLD='\033[1m'

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
SITES_DIR="/var/www/wordpress"
SECRETS_FILE="/etc/wp-manager/secrets.env"

echo -e "${CYAN}${BOLD}=== Setup WP Manager VPS ===${NC}"
echo ""

# --- 0. Verificar root ---
if [[ "$EUID" -ne 0 ]]; then
    echo -e "${RED}Execute como root: sudo bash vps/setup.sh${NC}"
    exit 1
fi

# --- 1. Credenciais seguras ---
echo -e "${CYAN}[0/8]${NC} Configurando credenciais..."

if [[ -f "$SECRETS_FILE" ]]; then
    source "$SECRETS_FILE"
    echo -e "  ${YELLOW}Arquivo de segredos existente carregado: ${SECRETS_FILE}${NC}"
fi

if [[ -z "${MYSQL_ROOT_PASSWORD}" ]]; then
    read -s -p "$(echo -e "${CYAN}Senha root do MySQL:${NC} ")" MYSQL_ROOT_PASSWORD
    echo ""
fi

if [[ -z "${WP_DB_PASSWORD}" ]]; then
    WP_DB_PASSWORD=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 24)
    echo -e "  ${GREEN}Senha do usuário WordPress gerada automaticamente${NC}"
fi

mkdir -p /etc/wp-manager
cat > "$SECRETS_FILE" <<SECRETS
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD}"
WP_DB_PASSWORD="${WP_DB_PASSWORD}"
SECRETS
chmod 600 "$SECRETS_FILE"
chown root:root "$SECRETS_FILE"
echo -e "  ${GREEN}Segredos salvos em ${SECRETS_FILE} (modo 600)${NC}"
echo -e "${GREEN}OK${NC}"

# --- 2. Extensões PHP necessárias ---
echo -e "${CYAN}[1/8]${NC} Instalando extensões PHP 8.4..."
apt-get update -qq
apt-get install -y -qq \
    php8.4-fpm php8.4-mysql php8.4-gd php8.4-zip php8.4-intl \
    php8.4-xml php8.4-mbstring php8.4-bcmath php8.4-curl \
    php8.4-imagick php8.4-redis \
    msmtp msmtp-mta unzip curl
echo -e "${GREEN}OK${NC}"

# --- 3. WP-CLI ---
echo -e "${CYAN}[2/8]${NC} Instalando WP-CLI..."
if [[ ! -f /usr/local/bin/wp ]]; then
    curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    mv wp-cli.phar /usr/local/bin/wp
fi
echo -e "${GREEN}OK ($(wp --info --allow-root 2>/dev/null | grep 'WP-CLI version' | awk '{print $3}'))${NC}"

# --- 4. Composer ---
echo -e "${CYAN}[3/8]${NC} Verificando Composer..."
if [[ ! -f /usr/local/bin/composer ]]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
echo -e "${GREEN}OK${NC}"

# --- 5. Diretórios ---
echo -e "${CYAN}[4/8]${NC} Criando diretórios..."
mkdir -p "${SITES_DIR}"
mkdir -p "${PROJECT_DIR}/backups"
chown -R www-data:www-data "${SITES_DIR}"
chmod -R 755 "${SITES_DIR}"
echo -e "${GREEN}OK${NC}"

# --- 6. MySQL: banco wp_manager + usuário wordpress ---
echo -e "${CYAN}[5/8]${NC} Configurando MySQL..."
MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" mysql -uroot -h127.0.0.1 <<SQL
CREATE DATABASE IF NOT EXISTS \`wp_manager\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'wordpress'@'localhost' IDENTIFIED BY '${WP_DB_PASSWORD}';
ALTER USER 'wordpress'@'localhost' IDENTIFIED BY '${WP_DB_PASSWORD}';
FLUSH PRIVILEGES;
SQL
echo -e "${GREEN}OK${NC}"

# --- 7. Laravel Manager ---
echo -e "${CYAN}[6/8]${NC} Configurando Laravel Manager..."
cd "${PROJECT_DIR}/manager"

if [[ ! -f .env ]]; then
    cp .env.example .env
    # Substituir paths e senha no .env gerado
    sed -i "s|/var/www/gestor_wp|${PROJECT_DIR}|g" .env
    sed -i "s|DB_PASSWORD=sua_senha_aqui|DB_PASSWORD=${MYSQL_ROOT_PASSWORD}|g" .env
    echo -e "  ${YELLOW}Arquivo .env criado com paths e senha configurados${NC}"
fi

composer install --no-dev --optimize-autoloader --quiet
php artisan key:generate --quiet
php artisan migrate --force --quiet
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

chown -R www-data:www-data "${PROJECT_DIR}/manager/storage"
chown -R www-data:www-data "${PROJECT_DIR}/manager/bootstrap/cache"
chmod -R 775 "${PROJECT_DIR}/manager/storage"
chmod -R 775 "${PROJECT_DIR}/manager/bootstrap/cache"

echo -e "${GREEN}OK${NC}"

# --- 8. Nginx ---
echo -e "${CYAN}[7/8]${NC} Configurando Nginx..."

# Config do manager — substitui o path padrão pelo path real do projeto
sed "s|/var/www/gestor_wp|${PROJECT_DIR}|g" "${PROJECT_DIR}/vps/nginx/wp-manager.conf" \
    > /etc/nginx/sites-available/wp-manager.conf

if [[ ! -L /etc/nginx/sites-enabled/wp-manager.conf ]]; then
    ln -s /etc/nginx/sites-available/wp-manager.conf /etc/nginx/sites-enabled/wp-manager.conf
fi

nginx -t && systemctl reload nginx
echo -e "${GREEN}OK${NC}"

# --- 9. Permissões do script ---
echo -e "${CYAN}[8/8]${NC} Ajustando permissões do wp-manager.sh..."
chmod +x "${PROJECT_DIR}/wp-manager.sh"
echo -e "${GREEN}OK${NC}"

echo ""
echo -e "${GREEN}${BOLD}╔══════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}${BOLD}║  ✅ Setup concluído!                             ║${NC}"
echo -e "${GREEN}${BOLD}╠══════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}${BOLD}║${NC}  Painel:  ${CYAN}https://wp.devconecta.com.br${NC}"
echo -e "${GREEN}${BOLD}║${NC}"
echo -e "${GREEN}${BOLD}║${NC}  ${YELLOW}Próximos passos:${NC}"
echo -e "${GREEN}${BOLD}║${NC}  1. Configure DNS:"
echo -e "${GREEN}${BOLD}║${NC}     ${CYAN}wp.devconecta.com.br${NC}   → IP do VPS"
echo -e "${GREEN}${BOLD}║${NC}     ${CYAN}*.wp.devconecta.com.br${NC} → IP do VPS"
echo -e "${GREEN}${BOLD}║${NC}  2. Verifique os certs SSL:"
echo -e "${GREEN}${BOLD}║${NC}     ${CYAN}/etc/ssl/cloudflare/devconecta.com.br.crt${NC}"
echo -e "${GREEN}${BOLD}║${NC}     ${CYAN}/etc/ssl/cloudflare/devconecta.com.br.key${NC}"
echo -e "${GREEN}${BOLD}║${NC}  3. Crie seu primeiro site:"
echo -e "${GREEN}${BOLD}║${NC}     ${CYAN}./wp-manager.sh create meusite${NC}"
echo -e "${GREEN}${BOLD}╚══════════════════════════════════════════════════╝${NC}"
echo ""
