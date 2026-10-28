# AICore WP Setup with XAMPP/MAMP - Step by Step

Complete guide for setting up AICore WP with XAMPP or MAMP on your local machine.

## Quick Start

### macOS / Linux
```bash
chmod +x scripts/setup-xampp-mamp.sh
./scripts/setup-xampp-mamp.sh
```

### Windows
```batch
scripts\setup-xampp-mamp.bat
```

The scripts automate all steps below. If you prefer manual setup, follow the detailed instructions.

---

## Prerequisites

### XAMPP or MAMP Installation

**XAMPP** (All Platforms)
- Download: https://www.apachefriends.org
- Includes: Apache, MySQL/MariaDB, PHP
- Default paths:
  - macOS: `/Applications/XAMPP/xamppfiles`
  - Windows: `C:\xampp`
  - Linux: `/opt/lampp`

**MAMP** (macOS/Windows)
- Download: https://www.mamp.info
- Includes: Apache, MySQL, PHP
- Default path:
  - macOS: `/Applications/MAMP`
  - Windows: `C:\MAMP`

### Additional Requirements
- Composer (for PHP dependencies)
- Node.js 18+ (for frontend build)
- curl or wget (for downloads)
- Terminal/Command Prompt access

---

## Detailed Manual Setup

### Step 1: Start XAMPP/MAMP Services

**XAMPP:**
1. Open XAMPP Control Panel
2. Click "Start" for:
   - Apache
   - MySQL (or MariaDB)
   - FileZilla (optional)
3. Wait for status to show "Running" (green)

**MAMP:**
1. Open MAMP application
2. Click "Start Servers"
3. Wait for status indicator to show green

**Verify MySQL is Running:**
```bash
# macOS/Linux
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "SELECT 1;"

# Windows (in Command Prompt)
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT 1;"
```

You should see:
```
+---+
| 1 |
+---+
| 1 |
+---+
```

---

### Step 2: Create WordPress Directory

Choose your location:

**XAMPP:**
- macOS: `/Applications/XAMPP/xamppfiles/htdocs`
- Windows: `C:\xampp\htdocs`
- Linux: `/opt/lampp/htdocs`

**MAMP:**
- macOS: `/Applications/MAMP/htdocs`
- Windows: `C:\MAMP\htdocs`

**Create directory:**

```bash
# macOS/Linux
mkdir -p /Applications/XAMPP/xamppfiles/htdocs/aicore-wp
cd /Applications/XAMPP/xamppfiles/htdocs/aicore-wp

# Windows (Command Prompt)
mkdir C:\xampp\htdocs\aicore-wp
cd C:\xampp\htdocs\aicore-wp
```

---

### Step 3: Download WordPress

**Option A: Using curl**

```bash
curl -s https://wordpress.org/latest.tar.gz | tar xz
# Then move files up one level
mv wordpress/* .
rmdir wordpress
```

**Option B: Using PowerShell (Windows)**

```powershell
Invoke-WebRequest -Uri 'https://wordpress.org/latest.zip' -OutFile 'wordpress.zip'
Expand-Archive -Path 'wordpress.zip' -DestinationPath '.'
# Move files up
Move-Item -Path '.\wordpress\*' -Destination '.' -Force
Remove-Item -Path '.\wordpress'
Remove-Item -Path '.\wordpress.zip'
```

**Option C: Download Manually**
1. Go to https://wordpress.org/download/
2. Download the ZIP file
3. Extract to your htdocs/aicore-wp directory

---

### Step 4: Create Database

**Using MySQL Command Line:**

```bash
# macOS/Linux
/Applications/XAMPP/xamppfiles/bin/mysql -u root

# Windows (Command Prompt)
C:\xampp\mysql\bin\mysql.exe -u root
```

**Then run these SQL commands:**

```sql
CREATE DATABASE IF NOT EXISTS `wordpress_aicore`;
CREATE USER IF NOT EXISTS 'wordpress'@'localhost' IDENTIFIED BY 'wordpress_password';
GRANT ALL PRIVILEGES ON `wordpress_aicore`.* TO 'wordpress'@'localhost';
FLUSH PRIVILEGES;
exit
```

**Or using a script file:**

**Create file `mysql_setup.sql`:**
```sql
CREATE DATABASE IF NOT EXISTS `wordpress_aicore`;
CREATE USER IF NOT EXISTS 'wordpress'@'localhost' IDENTIFIED BY 'wordpress_password';
GRANT ALL PRIVILEGES ON `wordpress_aicore`.* TO 'wordpress'@'localhost';
FLUSH PRIVILEGES;
```

**Execute it:**
```bash
# macOS/Linux
/Applications/XAMPP/xamppfiles/bin/mysql -u root < mysql_setup.sql

# Windows
C:\xampp\mysql\bin\mysql.exe -u root < mysql_setup.sql
```

---

### Step 5: Create wp-config.php

Navigate to your WordPress directory and create `wp-config.php`:

```php
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
// Generate from: https://api.wordpress.org/secret-key/1.1/salt/
// Copy and paste the output below:
define( 'AUTH_KEY',         'put your unique phrase here' );
define( 'SECURE_AUTH_KEY',  'put your unique phrase here' );
define( 'LOGGED_IN_KEY',    'put your unique phrase here' );
define( 'NONCE_KEY',        'put your unique phrase here' );
define( 'AUTH_SALT',        'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT',   'put your unique phrase here' );
define( 'NONCE_SALT',       'put your unique phrase here' );

// ** Database table prefix ** //
$table_prefix = 'wp_';

// ** Debug settings ** //
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );

/* That's all, stop editing! Happy blogging. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
?>
```

**Important:** Replace the salt values with unique keys from:
https://api.wordpress.org/secret-key/1.1/salt/

---

### Step 6: Install WordPress

Open your browser and navigate to:
```
http://localhost/aicore-wp
```

**You should see WordPress installation page:**

1. **Select Language:** Choose your language (English recommended)
2. **Welcome:** Click "Let's go!"
3. **Database Connection:** 
   - Should be pre-filled with your database settings
   - Click "Submit"
4. **WordPress Setup:**
   - Site Title: "AICore WP Development"
   - Username: `admin`
   - Password: `AdminPassword123!`
   - Email: `admin@example.local`
   - Check "Discourage search engines"
   - Click "Install WordPress"
5. **Success:** Click "Log In"

---

### Step 7: Link AICore WP Plugin

The plugin needs to be accessible to WordPress.

**Option A: Symbolic Link (Recommended)**

```bash
# macOS/Linux
cd /Applications/XAMPP/xamppfiles/htdocs/aicore-wp/wp-content/plugins
ln -s /path/to/AICore-WP aicore-wp

# Windows (Run as Administrator)
cd C:\xampp\htdocs\aicore-wp\wp-content\plugins
mklink /d aicore-wp "C:\path\to\AICore-WP"
```

**Option B: Copy Plugin**

```bash
# macOS/Linux
cp -r /path/to/AICore-WP /Applications/XAMPP/xamppfiles/htdocs/aicore-wp/wp-content/plugins/aicore-wp

# Windows
xcopy "C:\path\to\AICore-WP" "C:\xampp\htdocs\aicore-wp\wp-content\plugins\aicore-wp" /E /I
```

---

### Step 8: Install Plugin Dependencies

Navigate to the AICore WP directory:

```bash
cd /path/to/AICore-WP
```

**Install PHP dependencies:**
```bash
composer install
```

If composer isn't installed:
1. Download from: https://getcomposer.org
2. Install globally
3. Run `composer install` again

**Install Node dependencies:**
```bash
npm install
```

If Node.js isn't installed:
1. Download from: https://nodejs.org (LTS version)
2. Install
3. Run `npm install` again

---

### Step 9: Activate Plugin

1. Open WordPress Admin:
   ```
   http://localhost/aicore-wp/wp-admin
   ```

2. Login with credentials:
   - Username: `admin`
   - Password: `AdminPassword123!`

3. Go to **Plugins** menu

4. Find **AICore WP** plugin

5. Click **Activate**

6. You should see success message:
   ```
   Plugin activated successfully.
   ```

---

### Step 10: Verify Installation

**Check Admin Menu:**
- New menu items should appear in WordPress admin sidebar
- Look for "AICore WP" or related menu items

**Check Database Tables:**
```bash
# macOS/Linux
/Applications/XAMPP/xamppfiles/bin/mysql -u wordpress -pwordpress_password wordpress_aicore

# Windows
C:\xampp\mysql\bin\mysql.exe -u wordpress -pwordpress_password wordpress_aicore

# Then run:
SHOW TABLES LIKE 'wp_aicore%';
```

You should see tables like:
- `wp_aicore_conversations`
- `wp_aicore_messages`
- etc.

**Check Debug Log:**
```bash
# macOS/Linux
tail -f /Applications/XAMPP/xamppfiles/htdocs/aicore-wp/wp-content/debug.log

# Windows
type C:\xampp\htdocs\aicore-wp\wp-content\debug.log
```

Should show plugin activation logs with no errors.

---

## Development Workflow

### Frontend Development (Live Reload)

```bash
cd /path/to/AICore-WP
npm run dev
```

This starts Vite development server with hot module reloading.

Your browser will show:
```
VITE v5.x.x ready in xxx ms

➜  Local:   http://localhost:5173/
```

The admin dashboard will auto-reload when you make changes to React components.

### Backend Development

1. Edit PHP files in `src/` or `modules/`
2. Changes take effect immediately
3. Monitor logs:
   ```bash
   tail -f /path/to/wordpress/wp-content/debug.log
   ```

### Building for Production

```bash
npm run build
composer run lint
```

This creates optimized production assets.

---

## Troubleshooting

### "MySQL Connection Refused"

**Problem:** Can't connect to MySQL
```
Error establishing database connection
```

**Solution:**
1. Ensure XAMPP/MAMP MySQL is running
2. Verify credentials in wp-config.php
3. Check MySQL port (default: 3306)

```bash
# Test connection
mysql -u wordpress -pwordpress_password -h localhost wordpress_aicore
```

### "Permission Denied" on Symlink

**Problem:** Can't create symlink
```
Access Denied
```

**Solution:**
- Windows: Run Command Prompt as Administrator
- macOS/Linux: Use `sudo` if needed
- Or copy the plugin directory instead of symlinking

### "PHP Version Mismatch"

**Problem:** PHP version error
```
Fatal error: Unsupported operand types
```

**Solution:**
1. Check PHP version:
   ```bash
   php -v
   ```
2. Ensure PHP 8.2 or higher
3. Update XAMPP/MAMP or set correct PHP version

### "Composer Not Found"

**Problem:** 
```
composer: command not found
```

**Solution:**
1. Install Composer: https://getcomposer.org
2. Add to PATH if needed
3. Or use full path:
   ```bash
   php /path/to/composer.phar install
   ```

### "Plugin Not Appearing"

**Problem:** Plugin not in plugins list

**Solution:**
1. Check plugin symlink/copy is correct:
   ```bash
   ls -la /path/to/plugins/aicore-wp
   ```

2. Check PHP syntax:
   ```bash
   php -l aicore-wp.php
   ```

3. Check debug log:
   ```bash
   tail wp-content/debug.log
   ```

4. Verify WordPress can read the directory:
   ```bash
   chmod -R 755 /path/to/wp-content/plugins/aicore-wp
   ```

---

## Configuration

Edit `.env` file for:
- AI Provider API keys (OpenAI, Anthropic, etc.)
- Database credentials (already configured)
- WordPress admin settings

```bash
# Copy template
cp .env.example .env

# Edit with your settings
nano .env
```

---

## Useful Commands

```bash
# Build admin assets
npm run build

# Start dev server with live reload
npm run dev

# Lint PHP files
composer run lint

# Lint TypeScript
npm run lint

# Run tests
phpunit --configuration=phpunit.xml.dist

# View WordPress logs
tail -f wp-content/debug.log

# Access WordPress CLI (if installed)
wp plugin list
wp option get siteurl
```

---

## Database Management

### PhpMyAdmin (Web Interface)

Access via your browser:
```
http://localhost/phpmyadmin
```

Credentials:
- User: `root`
- Password: (blank for XAMPP)
- Or: `root` for MAMP

### MySQL Command Line

```bash
# macOS/Linux XAMPP
/Applications/XAMPP/xamppfiles/bin/mysql -u wordpress -pwordpress_password wordpress_aicore

# Windows XAMPP
C:\xampp\mysql\bin\mysql.exe -u wordpress -pwordpress_password wordpress_aicore

# Then run SQL:
SELECT * FROM wp_aicore_conversations;
SELECT * FROM wp_aicore_messages;
```

---

## Stop Services

**XAMPP:**
1. Open XAMPP Control Panel
2. Click "Stop" for Apache and MySQL

**MAMP:**
1. Open MAMP
2. Click "Stop Servers"

Or from terminal:
```bash
# macOS/Linux XAMPP
sudo /Applications/XAMPP/xamppfiles/bin/mysql.server stop

# Windows XAMPP
C:\xampp\mysql\bin\mysqladmin -u root shutdown
```

---

## Next Steps

1. ✅ WordPress is running
2. ✅ AICore WP plugin is installed and activated
3. ⬜ Configure AI providers (Settings > AICore WP)
4. ⬜ Test chat functionality
5. ⬜ Explore other modules
6. ⬜ Run test suite
7. ⬜ Start development

---

## Getting Help

- Check `DEVELOPMENT.md` for development best practices
- Check `TESTING.md` for testing guide
- Review WordPress documentation: https://developer.wordpress.org
- Check plugin debug log: `wp-content/debug.log`

---

## Performance Tips

1. **Enable WordPress caching:**
   - Install plugin: WP Super Cache or W3 Total Cache
   - Or use Redis (if available)

2. **Optimize database:**
   ```sql
   OPTIMIZE TABLE wp_aicore_conversations;
   OPTIMIZE TABLE wp_aicore_messages;
   ```

3. **Monitor development:**
   ```bash
   # Check query count
   wp eval 'global $wpdb; echo "Queries: " . $wpdb->num_queries;'
   
   # Check memory usage
   wp eval 'echo "Memory: " . size_format(memory_get_peak_usage(true));'
   ```

4. **Profile frontend:**
   - Open DevTools (F12)
   - Go to Performance tab
   - Record and analyze

Happy development! 🎉
