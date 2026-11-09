# WP Docker Manager · Início rápido

Ambiente de desenvolvimento para criar e gerenciar múltiplos sites WordPress. Inclui um painel Laravel, uma interface de linha de comando e serviços locais no Docker.

## Rodar localmente

Use Git, Docker e Compose v2. Execute em uma máquina de desenvolvimento.

```bash
git clone https://github.com/bagaaz/gestor_wp.git
cd gestor_wp
chmod +x setup.sh wp-manager.sh
./setup.sh
```

Abra [manager.localhost](http://manager.localhost). Para criar um site de teste, rode `./wp-manager.sh create meusite` e abra [meusite.localhost](http://meusite.localhost). Para listar sites, use `./wp-manager.sh list`.
