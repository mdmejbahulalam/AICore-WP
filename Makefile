.PHONY: help setup up down restart logs bash build lint test clean

help:
	@echo "AICore WP Development Commands"
	@echo "=============================="
	@echo ""
	@echo "Setup & Container Management:"
	@echo "  make setup       - Initial setup (Docker, dependencies)"
	@echo "  make up          - Start containers"
	@echo "  make down        - Stop containers"
	@echo "  make restart     - Restart containers"
	@echo "  make clean       - Remove containers and volumes"
	@echo "  make status      - Show container status"
	@echo ""
	@echo "Development:"
	@echo "  make build       - Build admin assets (Vite)"
	@echo "  make dev         - Start Vite dev server for live reloading"
	@echo "  make lint-php    - Lint PHP files"
	@echo "  make lint-ts     - Lint TypeScript files"
	@echo "  make test        - Run PHPUnit tests"
	@echo ""
	@echo "Access:"
	@echo "  make bash        - Open WordPress shell"
	@echo "  make mysql       - Open MySQL shell"
	@echo "  make logs        - View WordPress logs (tail -f)"
	@echo ""
	@echo "Aliases:"
	@echo "  WordPress: http://localhost:8080"
	@echo "  PhpMyAdmin: http://localhost:8081"
	@echo "  Admin User: admin / AdminPassword123!"

setup:
	@echo "🚀 Running setup..."
	./scripts/setup-dev.sh

up:
	@echo "🚀 Starting containers..."
	docker-compose up -d
	@echo "✅ Containers started"
	@echo "WordPress: http://localhost:8080"
	@echo "PhpMyAdmin: http://localhost:8081"

down:
	@echo "🛑 Stopping containers..."
	docker-compose down

restart:
	@echo "🔄 Restarting containers..."
	docker-compose restart

clean:
	@echo "🧹 Cleaning up..."
	docker-compose down -v
	@echo "✅ Cleaned"

status:
	@docker-compose ps

logs:
	docker-compose logs -f wordpress

bash:
	docker-compose exec wordpress bash

mysql:
	docker-compose exec mysql mysql -u wordpress -pwordpress_password wordpress

build:
	@echo "🔨 Building admin assets..."
	docker-compose exec wordpress npm run build --prefix=/var/www/html/wp-content/plugins/aicore-wp

dev:
	@echo "🚀 Starting Vite dev server..."
	docker-compose exec wordpress npm run dev --prefix=/var/www/html/wp-content/plugins/aicore-wp

lint-php:
	@echo "📝 Linting PHP..."
	docker-compose exec wordpress php -l /var/www/html/wp-content/plugins/aicore-wp/aicore-wp.php
	docker-compose exec wordpress find /var/www/html/wp-content/plugins/aicore-wp/src /var/www/html/wp-content/plugins/aicore-wp/modules -name '*.php' -print0 | xargs -0 -n1 php -l

lint-ts:
	@echo "📝 Linting TypeScript..."
	docker-compose exec wordpress npm run lint --prefix=/var/www/html/wp-content/plugins/aicore-wp

test:
	@echo "🧪 Running tests..."
	docker-compose exec wordpress phpunit --configuration=/var/www/html/wp-content/plugins/aicore-wp/phpunit.xml.dist

.DEFAULT_GOAL := help
