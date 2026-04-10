#!/bin/bash

# ===========================================================
#  WP Docker Manager - Setup Inicial
#  Executa uma única vez para configurar todo o ambiente
# ===========================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'
BOLD='\033[1m'

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo -e "${CYAN}"
echo "╔══════════════════════════════════════════════════╗"
echo "║       WP Docker Manager - Setup Inicial          ║"
echo "╚══════════════════════════════════════════════════╝"
echo -e "${NC}"

# 1. Verificar Docker
echo -e "${BLUE}[1/6]${NC} Verificando Docker..."
if ! command -v docker &>/dev/null; then
    echo -e "${RED}Docker não encontrado. Instale o Docker primeiro.${NC}"
    exit 1
fi

if ! command -v docker compose &>/dev/null; then
    echo -e "${RED}Docker Compose não encontrado. Instale o Docker Compose V2.${NC}"
    exit 1
fi
echo -e "${GREEN}  ✓ Docker e Docker Compose encontrados.${NC}"

# 2. Criar diretórios necessários
echo -e "${BLUE}[2/6]${NC} Criando diretórios..."
mkdir -p "${PROJECT_DIR}/sites"
mkdir -p "${PROJECT_DIR}/backups"
mkdir -p "${PROJECT_DIR}/manager/storage/framework/{cache/data,sessions,views}"
mkdir -p "${PROJECT_DIR}/manager/storage/logs"
mkdir -p "${PROJECT_DIR}/manager/bootstrap/cache"
chmod -R 775 "${PROJECT_DIR}/manager/storage"
chmod -R 775 "${PROJECT_DIR}/manager/bootstrap/cache"
echo -e "${GREEN}  ✓ Diretórios criados.${NC}"

# 3. Configurar .env do Laravel Manager
echo -e "${BLUE}[3/6]${NC} Configurando ambiente Laravel..."
if [[ ! -f "${PROJECT_DIR}/manager/.env" ]]; then
    cp "${PROJECT_DIR}/manager/.env.example" "${PROJECT_DIR}/manager/.env"
fi
echo -e "${GREEN}  ✓ Arquivo .env configurado.${NC}"

# 4. Construir e iniciar containers
echo -e "${BLUE}[4/6]${NC} Construindo containers Docker (pode levar alguns minutos)..."
cd "${PROJECT_DIR}"
docker compose build --no-cache
echo -e "${GREEN}  ✓ Containers construídos.${NC}"

echo -e "${BLUE}[5/6]${NC} Iniciando containers..."
docker compose up -d
echo -e "${GREEN}  ✓ Containers iniciados.${NC}"

# 5. Instalar dependências Laravel e configurar
echo -e "${BLUE}[6/6]${NC} Configurando painel Laravel..."

# Esperar MySQL ficar pronto
echo -e "  Aguardando MySQL..."
for i in {1..30}; do
    if docker compose exec -T mysql mysql -uroot -proot -e "SELECT 1" &>/dev/null; then
        break
    fi
    sleep 2
done

# Instalar Composer e rodar migrations
docker compose exec -T php-manager composer install --no-interaction --optimize-autoloader 2>/dev/null
docker compose exec -T php-manager php artisan key:generate --force 2>/dev/null
docker compose exec -T php-manager php artisan migrate --force 2>/dev/null

echo -e "${GREEN}  ✓ Painel Laravel configurado.${NC}"

# Resultado final
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              Setup concluído com sucesso!                ║${NC}"
echo -e "${GREEN}╠══════════════════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║${NC}                                                          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}  ${BOLD}Painel de Gerenciamento:${NC}                                ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${CYAN}http://manager.localhost${NC}                               ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}  ${BOLD}phpMyAdmin:${NC}                                              ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${CYAN}http://localhost:8080${NC}                                   ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}  ${BOLD}Mailpit (emails de teste):${NC}                               ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${CYAN}http://localhost:8025${NC}                                   ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}  ${BOLD}Para criar seu primeiro site WordPress:${NC}                  ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${YELLOW}./wp-manager.sh create meusite${NC}                          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                          ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}  ${BOLD}Para ver todos os comandos:${NC}                              ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}    ${YELLOW}./wp-manager.sh help${NC}                                    ${GREEN}║${NC}"
echo -e "${GREEN}║${NC}                                                          ${GREEN}║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
