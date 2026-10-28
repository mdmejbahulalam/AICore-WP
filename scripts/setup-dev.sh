#!/bin/bash
set -e

echo "🚀 AICore WP Development Environment Setup"
echo "=========================================="

# Check Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

echo "✅ Docker found: $(docker --version)"

# Check Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✅ Docker Compose found: $(docker-compose --version)"

# Start containers
echo ""
echo "📦 Starting Docker containers..."
docker-compose up -d

echo ""
echo "⏳ Waiting for services to be ready..."
sleep 5

# Check if WordPress is ready
READY=0
for i in {1..30}; do
    if docker-compose exec -T wordpress curl -s http://localhost:80 > /dev/null 2>&1; then
        READY=1
        break
    fi
    echo "  Attempt $i/30..."
    sleep 2
done

if [ $READY -eq 0 ]; then
    echo "❌ WordPress failed to start. Check logs with: docker-compose logs wordpress"
    exit 1
fi

echo ""
echo "✅ WordPress is ready!"
echo ""
echo "📝 Installing PHP dependencies..."
docker-compose exec -T wordpress composer install --working-dir=/var/www/html/wp-content/plugins/aicore-wp

echo ""
echo "📝 Installing Node dependencies..."
docker-compose exec -T wordpress npm install --prefix=/var/www/html/wp-content/plugins/aicore-wp

echo ""
echo "🎉 Setup complete!"
echo ""
echo "=========================================="
echo "🌐 Access Points:"
echo "=========================================="
echo "WordPress:  http://localhost:8080"
echo "PhpMyAdmin: http://localhost:8081"
echo ""
echo "Admin Credentials:"
echo "Username: admin"
echo "Password: AdminPassword123!"
echo ""
echo "Database Credentials:"
echo "Host: localhost:3306"
echo "User: wordpress"
echo "Password: wordpress_password"
echo "Database: wordpress"
echo ""
echo "📚 Next Steps:"
echo "1. Activate the AICore WP plugin in WordPress admin"
echo "2. Configure AI providers (OpenAI, Anthropic, etc.)"
echo "3. Run: npm run dev (in plugin directory for live frontend dev)"
echo "4. View logs: docker-compose logs wordpress"
echo ""
