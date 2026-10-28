# AICore WP Local Development Setup

Since Docker isn't available in the cloud environment, this guide covers setting up WordPress locally on your machine.

## Option 1: Docker Desktop (Recommended)

### Prerequisites
- Docker Desktop (https://www.docker.com/products/docker-desktop)
- 4GB+ RAM available
- 10GB+ disk space

### Setup

```bash
# 1. Clone and navigate to the project
cd /path/to/AICore-WP

# 2. Start the environment
docker compose up -d

# 3. Wait for services (30-60 seconds)
docker compose ps

# 4. Install dependencies
docker compose exec wordpress composer install --working-dir=/var/www/html/wp-content/plugins/aicore-wp
docker compose exec wordpress npm install --prefix=/var/www/html/wp-content/plugins/aicore-wp

# 5. Access WordPress
# WordPress: http://localhost:8080
# PhpMyAdmin: http://localhost:8081
# Credentials: admin / AdminPassword123!
```

### Useful Commands

```bash
# View logs
docker compose logs -f wordpress

# Access WordPress shell
docker compose exec wordpress bash

# Build frontend
docker compose exec wordpress npm run build --prefix=/var/www/html/wp-content/plugins/aicore-wp

# Run tests
docker compose exec wordpress phpunit --configuration=/var/www/html/wp-content/plugins/aicore-wp/phpunit.xml.dist

# Stop everything
docker compose down
```

---

## Option 2: Local WordPress with LocalWP

### Prerequisites
- LocalWP (https://localwp.com)
- Free tool with great DX

### Setup

1. **Create a new site in LocalWP**
   - Open LocalWP
   - Click "Create New"
   - Name: "aicore-wp"
   - WordPress: 6.5+
   - PHP: 8.2
   - Start the site

2. **Link the plugin**
   ```bash
   # Find your LocalWP site directory (usually ~/Local Sites/aicore-wp/app/public/wp-content/plugins)
   
   # Clone AICore WP as a symbolic link or copy
   cd ~/Local\ Sites/aicore-wp/app/public/wp-content/plugins
   ln -s /path/to/AICore-WP aicore-wp
   ```

3. **Install dependencies**
   ```bash
   cd /path/to/AICore-WP
   composer install
   npm install
   ```

4. **Activate plugin**
   - Open LocalWP admin panel
   - Login to WordPress admin
   - Go to Plugins
   - Find "AICore WP" and activate

---

## Option 3: XAMPP/MAMP + Manual Setup

### Prerequisites
- XAMPP (https://www.apachefriends.org) or MAMP (https://www.mamp.info)
- PHP 8.2+
- MySQL 5.7+
- Composer
- Node.js 18+

### Setup

1. **Start XAMPP/MAMP services**
   ```bash
   # XAMPP: Start Apache and MySQL from control panel
   # MAMP: Start servers from app
   ```

2. **Create WordPress directory**
   ```bash
   # XAMPP
   cd /Applications/XAMPP/xamppfiles/htdocs
   # or /opt/lampp/htdocs on Linux
   
   # MAMP
   cd ~/Applications/MAMP/htdocs
   
   # Create wp-content/plugins/aicore-wp symlink or copy
   mkdir -p wp-content/plugins
   cd wp-content/plugins
   ln -s /path/to/AICore-WP aicore-wp
   ```

3. **Download WordPress**
   ```bash
   # From htdocs directory
   # Download from https://wordpress.org/download/
   # Or use WP-CLI:
   wp core download
   ```

4. **Create MySQL database**
   ```bash
   # Via PhpMyAdmin or command line
   mysql -u root -p
   > CREATE DATABASE wordpress;
   > CREATE USER 'wordpress'@'localhost' IDENTIFIED BY 'wordpress_password';
   > GRANT ALL PRIVILEGES ON wordpress.* TO 'wordpress'@'localhost';
   > FLUSH PRIVILEGES;
   ```

5. **Configure wp-config.php**
   ```php
   define('DB_NAME', 'wordpress');
   define('DB_USER', 'wordpress');
   define('DB_PASSWORD', 'wordpress_password');
   define('DB_HOST', 'localhost');
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

6. **Complete WordPress setup**
   - Visit http://localhost/wordpress
   - Fill in site info
   - Create admin account

7. **Install plugin dependencies**
   ```bash
   cd /path/to/AICore-WP
   composer install
   npm install
   ```

8. **Activate plugin**
   - Go to wp-admin/plugins.php
   - Find "AICore WP" and activate

---

## Option 4: Laravel Valet (macOS)

### Prerequisites
- macOS
- Laravel Valet (https://laravel.com/docs/valet)
- PHP 8.2+
- MySQL (via Valet or Homebrew)

### Setup

```bash
# 1. Navigate to project directory
cd /path/to/AICore-WP

# 2. Link with Valet
valet link aicore-wp

# 3. Create database
valet db create aicore-wp

# 4. Download WordPress
wp core download

# 5. Create wp-config
wp config create --dbname=aicore-wp --dbuser=root

# 6. Install WordPress
wp core install \
  --url=http://aicore-wp.test \
  --title="AICore WP" \
  --admin_user=admin \
  --admin_password=AdminPassword123! \
  --admin_email=admin@example.local

# 7. Install plugin dependencies
composer install
npm install

# 8. Activate plugin
wp plugin activate aicore-wp
```

Access at: http://aicore-wp.test

---

## Option 5: WP-CLI Setup Script

If you have WP-CLI installed:

```bash
#!/bin/bash
set -e

# Database configuration
DB_NAME="wordpress"
DB_USER="wordpress"
DB_PASS="wordpress_password"
WP_URL="http://localhost/wordpress"
WP_ADMIN_USER="admin"
WP_ADMIN_PASS="AdminPassword123!"
WP_ADMIN_EMAIL="admin@example.local"

echo "🚀 Setting up WordPress with AICore WP..."

# Create database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
mysql -u root -p -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Download WordPress
wp core download --locale=en_US

# Create wp-config
wp config create \
  --dbname=$DB_NAME \
  --dbuser=$DB_USER \
  --dbpass=$DB_PASS \
  --dbhost=localhost \
  --skip-check

# Install WordPress
wp core install \
  --url=$WP_URL \
  --title="AICore WP" \
  --admin_user=$WP_ADMIN_USER \
  --admin_password=$WP_ADMIN_PASS \
  --admin_email=$WP_ADMIN_EMAIL

# Install plugin dependencies
echo "📦 Installing PHP dependencies..."
composer install

echo "📦 Installing Node dependencies..."
npm install

# Activate plugin
wp plugin activate aicore-wp

echo "✅ Setup complete!"
echo "📍 WordPress: $WP_URL"
echo "👤 Admin: $WP_ADMIN_USER / $WP_ADMIN_PASS"
```

---

## Development Workflow (All Options)

### Frontend Development (Live Reload)

```bash
# Terminal 1: Start Vite dev server
npm run dev

# Terminal 2: Your editor or browser
# Changes to React components auto-reload
# http://localhost:5173 (or configured Vite port)
```

### Backend Development

```bash
# Make changes to PHP files
# They take effect immediately

# Check syntax
php -l aicore-wp.php
find src modules -name '*.php' -print0 | xargs -0 -n1 php -l

# View debug log
tail -f wp-content/debug.log
```

### Building for Production

```bash
# Build frontend assets
npm run build

# Verify PHP syntax
composer run lint

# All assets ready for deployment
```

---

## Testing

### Run Tests

```bash
# PHPUnit tests
phpunit --configuration=phpunit.xml.dist

# TypeScript linting
npm run lint

# PHP linting
composer run lint
```

### Manual Testing Checklist

- [ ] Plugin activates without errors
- [ ] Admin dashboard loads
- [ ] Create a chat conversation
- [ ] Test each module (chat, workflows, etc.)
- [ ] Check debug.log for warnings
- [ ] Test with multiple browsers
- [ ] Test with mobile viewport

---

## Troubleshooting

### PHP Version Issues
```bash
# Check PHP version
php -v

# Should be 8.2 or higher
# If not, check your local environment settings
```

### Database Connection Error
```bash
# Verify MySQL is running
mysql -u root -p -e "SELECT 1;"

# Check wp-config.php database settings
# Ensure credentials match
```

### Plugin Not Activating
```bash
# Check debug log
tail -f wp-content/debug.log

# Check PHP syntax
php -l aicore-wp.php
find src modules -name '*.php' -print0 | xargs -0 -n1 php -l
```

### Node Modules Issues
```bash
# Clear npm cache
npm cache clean --force

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install
```

### Port Already in Use
```bash
# Change Vite dev port
# Edit vite.config.ts:
// server: { port: 3000 }

# Or use different port in your environment
```

---

## IDE Setup

### VS Code Recommended Extensions
- PHP Intelephense
- TypeScript Vue Plugin
- Tailwind CSS IntelliSense
- ES7+ React/Redux/React-Native snippets
- Error Lens
- Rest Client

### PHPStorm Setup
- Settings > Languages & Frameworks > PHP > CLI Interpreter
- Point to local PHP 8.2
- Enable TypeScript support
- Configure Run Configuration for PHPUnit

---

## CI/CD Integration

The project is ready for GitHub Actions, GitLab CI, or any CI/CD platform:

```yaml
# GitHub Actions example
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
        env:
          MYSQL_DATABASE: wordpress
          MYSQL_ROOT_PASSWORD: root
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: npm install
      - run: npm run lint
      - run: composer run lint
      - run: phpunit --configuration=phpunit.xml.dist
```

---

## Next Steps

1. Choose your preferred setup option above
2. Follow the setup instructions
3. Activate the AICore WP plugin
4. Configure AI providers (OpenAI, Anthropic, etc.)
5. Start developing!

Need help? Check the main DEVELOPMENT.md guide.
