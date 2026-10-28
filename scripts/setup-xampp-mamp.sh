#!/bin/bash

###############################################################################
# AICore WP Setup Script for XAMPP/MAMP
#
# This script automates WordPress installation with AICore WP plugin
# Works on macOS, Windows (WSL), and Linux
#
# Usage:
#   chmod +x scripts/setup-xampp-mamp.sh
#   ./scripts/setup-xampp-mamp.sh
###############################################################################

set -e

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="aicore-wp"
DB_NAME="wordpress_aicore"
DB_USER="wordpress"
DB_PASSWORD="wordpress_password"
DB_HOST="localhost"
WP_ADMIN_USER="admin"
WP_ADMIN_PASSWORD="AdminPassword123!"
WP_ADMIN_EMAIL="admin@example.local"

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════╗"
echo "║     AICore WP - XAMPP/MAMP Setup                       ║"
echo "╚════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# Step 1: Detect XAMPP/MAMP location
echo -e "${YELLOW}Step 1: Detecting XAMPP/MAMP installation...${NC}"

HTDOCS_PATH=""
XAMPP_PATH=""

# Check for XAMPP
if [ -d "/Applications/XAMPP/xamppfiles/htdocs" ]; then
    HTDOCS_PATH="/Applications/XAMPP/xamppfiles/htdocs"
    XAMPP_PATH="/Applications/XAMPP"
    SYSTEM="XAMPP (macOS)"
elif [ -d "/opt/lampp/htdocs" ]; then
    HTDOCS_PATH="/opt/lampp/htdocs"
    XAMPP_PATH="/opt/lampp"
    SYSTEM="XAMPP (Linux)"
elif [ -d "C:/xampp/htdocs" ]; then
    HTDOCS_PATH="C:/xampp/htdocs"
    XAMPP_PATH="C:/xampp"
    SYSTEM="XAMPP (Windows)"
elif [ -d "/Applications/MAMP/htdocs" ]; then
    HTDOCS_PATH="/Applications/MAMP/htdocs"
    XAMPP_PATH="/Applications/MAMP"
    SYSTEM="MAMP (macOS)"
else
    echo -e "${RED}✗ XAMPP/MAMP not found!${NC}"
    echo ""
    echo "Please ensure XAMPP or MAMP is installed:"
    echo "  XAMPP: https://www.apachefriends.org"
    echo "  MAMP:  https://www.mamp.info"
    echo ""
    echo "Common installation paths:"
    echo "  XAMPP macOS:  /Applications/XAMPP/xamppfiles/htdocs"
    echo "  XAMPP Linux:  /opt/lampp/htdocs"
    echo "  MAMP macOS:   /Applications/MAMP/htdocs"
    exit 1
fi

echo -e "${GREEN}✓ Found: $SYSTEM${NC}"
echo "  htdocs: $HTDOCS_PATH"
echo ""

# Step 2: Check PHP & MySQL
echo -e "${YELLOW}Step 2: Checking PHP and MySQL...${NC}"

PHP_BIN="$XAMPP_PATH/bin/php"
if [ ! -f "$PHP_BIN" ]; then
    # Try system PHP
    PHP_BIN=$(which php)
    if [ -z "$PHP_BIN" ]; then
        echo -e "${RED}✗ PHP not found!${NC}"
        exit 1
    fi
fi

echo -e "${GREEN}✓ PHP: $($PHP_BIN -v | head -1)${NC}"

MYSQL_BIN="$XAMPP_PATH/bin/mysql"
if [ ! -f "$MYSQL_BIN" ]; then
    MYSQL_BIN=$(which mysql)
    if [ -z "$MYSQL_BIN" ]; then
        echo -e "${RED}✗ MySQL not found!${NC}"
        echo "Please start your XAMPP/MAMP MySQL service first"
        exit 1
    fi
fi

echo -e "${GREEN}✓ MySQL: Found${NC}"
echo ""

# Step 3: Verify XAMPP/MAMP is running
echo -e "${YELLOW}Step 3: Checking if services are running...${NC}"

if ! $MYSQL_BIN -u root -e "SELECT 1" > /dev/null 2>&1; then
    echo -e "${RED}✗ MySQL is not running!${NC}"
    echo ""
    echo "Please start your XAMPP/MAMP services:"
    echo "  XAMPP: Open XAMPP Control Panel > Start Apache & MySQL"
    echo "  MAMP:  Open MAMP > Start Servers"
    echo ""
    exit 1
fi

echo -e "${GREEN}✓ MySQL is running${NC}"
echo ""

# Step 4: Create WordPress directory
echo -e "${YELLOW}Step 4: Setting up WordPress directory...${NC}"

WP_DIR="$HTDOCS_PATH/$PROJECT_NAME"

if [ -d "$WP_DIR" ]; then
    echo -e "${YELLOW}⚠ Directory already exists: $WP_DIR${NC}"
    read -p "Overwrite? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf "$WP_DIR"
    else
        echo "Cancelled"
        exit 1
    fi
fi

mkdir -p "$WP_DIR"
echo -e "${GREEN}✓ Created: $WP_DIR${NC}"
echo ""

# Step 5: Download WordPress
echo -e "${YELLOW}Step 5: Downloading WordPress...${NC}"

cd "$WP_DIR"

# Check if wp-cli is available
if command -v wp &> /dev/null; then
    echo "Using WP-CLI..."
    wp core download --locale=en_US
    echo -e "${GREEN}✓ WordPress downloaded${NC}"
else
    # Fallback: Download manually
    echo "Downloading from wordpress.org..."
    curl -s https://wordpress.org/latest.tar.gz | tar xz

    if [ -d "wordpress" ]; then
        mv wordpress/* .
        rmdir wordpress
    fi
    echo -e "${GREEN}✓ WordPress extracted${NC}"
fi
echo ""

# Step 6: Create database
echo -e "${YELLOW}Step 6: Creating database...${NC}"

$MYSQL_BIN -u root << EOF
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;
CREATE USER IF NOT EXISTS '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'$DB_HOST';
FLUSH PRIVILEGES;
EOF

echo -e "${GREEN}✓ Database created: $DB_NAME${NC}"
echo -e "${GREEN}✓ User created: $DB_USER${NC}"
echo ""

# Step 7: Create wp-config.php
echo -e "${YELLOW}Step 7: Configuring WordPress...${NC}"

# Generate salts from wordpress.org
SALTS=$(curl -s https://api.wordpress.org/secret-key/1.1/salt/)

cat > "$WP_DIR/wp-config.php" << 'WPCONFIG'
<?php
/**
 * The base configuration for WordPress
 */

// ** Database settings ** //
define( 'DB_NAME', 'wordpress_aicore' );
define( 'DB_USER', 'wordpress' );
define( 'DB_PASSWORD', 'wordpress_password' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// ** Authentication Unique Keys and Salts ** //
WPCONFIG

echo "$SALTS" >> "$WP_DIR/wp-config.php"

cat >> "$WP_DIR/wp-config.php" << 'WPCONFIG'

// ** Database table prefix ** //
$table_prefix = 'wp_';

// ** Debug settings ** //
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );

/* That's all, stop editing! */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
WPCONFIG

echo -e "${GREEN}✓ wp-config.php created${NC}"
echo ""

# Step 8: Install WordPress
echo -e "${YELLOW}Step 8: Installing WordPress...${NC}"

WP_URL="http://localhost/$PROJECT_NAME"

if command -v wp &> /dev/null; then
    wp core install \
        --url="$WP_URL" \
        --title="AICore WP Development" \
        --admin_user="$WP_ADMIN_USER" \
        --admin_password="$WP_ADMIN_PASSWORD" \
        --admin_email="$WP_ADMIN_EMAIL" \
        --skip-email

    echo -e "${GREEN}✓ WordPress installed${NC}"
else
    echo -e "${YELLOW}⚠ WP-CLI not found. Complete installation manually:${NC}"
    echo "  1. Visit: $WP_URL"
    echo "  2. Fill in the setup form"
fi
echo ""

# Step 9: Link AICore WP plugin
echo -e "${YELLOW}Step 9: Linking AICore WP plugin...${NC}"

PLUGIN_SOURCE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DEST="$WP_DIR/wp-content/plugins/aicore-wp"

if [ -L "$PLUGIN_DEST" ]; then
    rm "$PLUGIN_DEST"
fi

ln -s "$PLUGIN_SOURCE" "$PLUGIN_DEST"

echo -e "${GREEN}✓ Plugin linked: $PLUGIN_DEST -> $PLUGIN_SOURCE${NC}"
echo ""

# Step 10: Install PHP dependencies
echo -e "${YELLOW}Step 10: Installing PHP dependencies...${NC}"

cd "$PLUGIN_SOURCE"

if command -v composer &> /dev/null; then
    composer install
    echo -e "${GREEN}✓ Composer dependencies installed${NC}"
else
    echo -e "${YELLOW}⚠ Composer not found${NC}"
    echo "  Install from: https://getcomposer.org"
    echo "  Then run: composer install"
fi
echo ""

# Step 11: Install Node dependencies
echo -e "${YELLOW}Step 11: Installing Node dependencies...${NC}"

if command -v npm &> /dev/null; then
    npm install
    echo -e "${GREEN}✓ Node dependencies installed${NC}"
else
    echo -e "${YELLOW}⚠ Node.js not found${NC}"
    echo "  Install from: https://nodejs.org"
    echo "  Then run: npm install"
fi
echo ""

# Step 12: Activate plugin
echo -e "${YELLOW}Step 12: Activating AICore WP plugin...${NC}"

if command -v wp &> /dev/null; then
    wp plugin activate aicore-wp
    echo -e "${GREEN}✓ Plugin activated${NC}"
else
    echo -e "${YELLOW}⚠ WP-CLI not found. Activate manually in WordPress admin${NC}"
fi
echo ""

# Summary
echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════╗"
echo "║              Setup Complete! 🎉                        ║"
echo "╚════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${GREEN}WordPress Installation:${NC}"
echo "  URL:      $WP_URL"
echo "  Path:     $WP_DIR"
echo ""

echo -e "${GREEN}Admin Credentials:${NC}"
echo "  Username: $WP_ADMIN_USER"
echo "  Password: $WP_ADMIN_PASSWORD"
echo ""

echo -e "${GREEN}Database Credentials:${NC}"
echo "  Host:     $DB_HOST"
echo "  Database: $DB_NAME"
echo "  User:     $DB_USER"
echo "  Password: $DB_PASSWORD"
echo ""

echo -e "${GREEN}Plugin:${NC}"
echo "  Path: $PLUGIN_DEST"
echo "  Status: $(command -v wp &> /dev/null && wp plugin is-active aicore-wp && echo 'Activated' || echo 'Install and activate manually')"
echo ""

echo -e "${YELLOW}Next Steps:${NC}"
echo "  1. Start XAMPP/MAMP services"
echo "  2. Visit: $WP_URL"
echo "  3. Login with credentials above"
echo "  4. Configure AI providers (Settings > AICore WP)"
echo ""

echo -e "${YELLOW}Development:${NC}"
echo "  Frontend:  cd '$PLUGIN_SOURCE' && npm run dev"
echo "  Backend:   Make PHP changes in src/ and modules/"
echo "  View logs: tail -f $WP_DIR/wp-content/debug.log"
echo ""

echo -e "${YELLOW}Useful commands:${NC}"
echo "  cd '$PLUGIN_SOURCE'"
echo "  make build    # Build admin assets"
echo "  make lint-php # Lint PHP files"
echo "  npm run test  # Run tests"
echo ""
