#!/bin/bash

# Common development commands for AICore WP
# Usage: source scripts/dev-commands.sh

alias wp-logs="docker-compose logs -f wordpress"
alias wp-mysql="docker-compose exec mysql mysql -u wordpress -pwordpress_password wordpress"
alias wp-bash="docker-compose exec wordpress bash"
alias wp-php="docker-compose exec wordpress php"
alias wp-composer="docker-compose exec wordpress composer"
alias wp-npm="docker-compose exec wordpress npm"

wp-down() {
    echo "🛑 Stopping containers..."
    docker-compose down
}

wp-up() {
    echo "🚀 Starting containers..."
    docker-compose up -d
}

wp-restart() {
    echo "🔄 Restarting containers..."
    docker-compose restart
}

wp-clean() {
    echo "🧹 Cleaning up containers and volumes..."
    docker-compose down -v
    echo "✅ Cleaned"
}

wp-build() {
    echo "🔨 Building admin assets..."
    docker-compose exec wordpress npm run build --prefix=/var/www/html/wp-content/plugins/aicore-wp
}

wp-lint-php() {
    echo "📝 Linting PHP..."
    docker-compose exec wordpress php -l /var/www/html/wp-content/plugins/aicore-wp/aicore-wp.php
    docker-compose exec wordpress find /var/www/html/wp-content/plugins/aicore-wp/src /var/www/html/wp-content/plugins/aicore-wp/modules -name '*.php' -print0 | xargs -0 -n1 php -l
}

wp-lint-ts() {
    echo "📝 Linting TypeScript..."
    docker-compose exec wordpress npm run lint --prefix=/var/www/html/wp-content/plugins/aicore-wp
}

wp-test() {
    echo "🧪 Running tests..."
    docker-compose exec wordpress phpunit --configuration=/var/www/html/wp-content/plugins/aicore-wp/phpunit.xml.dist
}

wp-shell() {
    echo "🐚 Opening WordPress shell..."
    docker-compose exec wordpress bash
}

wp-mysql-shell() {
    echo "🗄️  Opening MySQL shell..."
    docker-compose exec mysql mysql -u wordpress -pwordpress_password wordpress
}

wp-status() {
    echo "📊 Container Status:"
    docker-compose ps
}

echo "✅ Development commands loaded!"
echo ""
echo "Available commands:"
echo "  wp-up              - Start containers"
echo "  wp-down            - Stop containers"
echo "  wp-restart         - Restart containers"
echo "  wp-clean           - Remove containers and volumes"
echo "  wp-build           - Build admin assets with Vite"
echo "  wp-lint-php        - Lint PHP files"
echo "  wp-lint-ts         - Lint TypeScript files"
echo "  wp-test            - Run PHPUnit tests"
echo "  wp-shell           - Open WordPress bash shell"
echo "  wp-mysql-shell     - Open MySQL shell"
echo "  wp-status          - Show container status"
echo ""
echo "Aliases:"
echo "  wp-logs            - View WordPress logs"
echo "  wp-mysql           - Run MySQL CLI"
echo "  wp-bash            - Execute bash in WordPress"
echo "  wp-php             - Execute PHP in WordPress"
echo "  wp-composer        - Run composer in WordPress"
echo "  wp-npm             - Run npm in WordPress"
