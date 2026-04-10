#!/bin/bash
set -e

# Dá acesso ao Docker socket para www-data (necessário para wp-manager.sh)
if [ -S /var/run/docker.sock ]; then
    DOCKER_GID=$(stat -c '%g' /var/run/docker.sock)
    if ! getent group "$DOCKER_GID" > /dev/null 2>&1; then
        groupadd -g "$DOCKER_GID" docker-host
    fi
    DOCKER_GROUP=$(getent group "$DOCKER_GID" | cut -d: -f1)
    usermod -aG "$DOCKER_GROUP" www-data
fi

# Cria diretório .docker para www-data (evita warning do Docker CLI)
mkdir -p /var/www/.docker
chown www-data:www-data /var/www/.docker

exec "$@"
