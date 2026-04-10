# ============================================
# WP Docker Manager - Atalhos Make
# ============================================

.PHONY: setup up down restart rebuild destroy status create list help

# Setup inicial
setup:
	@./setup.sh

# Docker
up:
	@./wp-manager.sh up

down:
	@./wp-manager.sh down

restart:
	@./wp-manager.sh restart

rebuild:
	@./wp-manager.sh rebuild

destroy:
	@./wp-manager.sh destroy

status:
	@./wp-manager.sh status

# Sites
list:
	@./wp-manager.sh list

create:
	@read -p "Nome do site: " name && ./wp-manager.sh create $$name

# Help
help:
	@./wp-manager.sh help
