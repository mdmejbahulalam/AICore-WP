# Deploy AICore WP to Local XAMPP/MAMP - Complete Walkthrough

This guide walks you through deploying AICore WP to your local XAMPP/MAMP environment step-by-step.

---

## 📋 Pre-Deployment Checklist

Before you start, ensure you have:

- [ ] **XAMPP or MAMP installed** on your computer
  - XAMPP: https://www.apachefriends.org
  - MAMP: https://www.mamp.info
- [ ] **Git installed** (to clone the repository)
- [ ] **Composer installed** (for PHP dependencies)
- [ ] **Node.js 18+ installed** (for frontend build)
- [ ] **Terminal/Command Prompt** access
- [ ] **Admin access** on your computer (to start services)
- [ ] **At least 2GB free disk space**

**Verify prerequisites:**

```bash
# Check Git
git --version
# Should show: git version 2.x.x

# Check PHP
php -v
# Should show: PHP 8.2 or higher

# Check Composer
composer --version
# Should show: Composer 2.x.x

# Check Node
node -v
npm -v
# Should show: Node 18+ and npm 9+
```

If any are missing, download and install them before proceeding.

---

## 🚀 **STEP 1: Download & Prepare Repository**

### Step 1a: Clone the Repository

Choose a location on your computer where you want to keep the project:

```bash
# Recommended: Your home directory or projects folder
cd ~/Projects
# or: cd C:\Users\YourUsername\Projects

# Clone the repository
git clone https://github.com/mdmejbahulalam/AICore-WP.git
cd AICore-WP

# Verify we're on the correct branch
git branch
# Should show: claude/cool-pasteur-cdQQV
```

**On Windows, you can also:**
1. Open GitHub Desktop
2. Click "Clone a repository"
3. Enter: `https://github.com/mdmejbahulalam/AICore-WP.git`
4. Choose location on your computer

### Step 1b: Check Files

Verify the setup scripts are present:

```bash
# macOS/Linux
ls -la scripts/setup-xampp-mamp.sh
# Should show: -rwxr-xr-x ... setup-xampp-mamp.sh

# Windows
dir scripts\setup-xampp-mamp.bat
# Should show: setup-xampp-mamp.bat
```

---

## 🔧 **STEP 2: Start XAMPP/MAMP Services**

This is crucial - WordPress needs MySQL and Apache running.

### XAMPP Setup

**macOS:**
1. Open `/Applications/XAMPP/XAMPP Control.app`
2. Click "Start" next to "Apache"
3. Click "Start" next to "MySQL"
4. Wait for both to show "Running" (green)

**Windows:**
1. Open `C:\xampp\xampp-control.exe`
2. Click "Start" next to "Apache"
3. Click "Start" next to "MySQL"
4. Wait for both to show "Running"

**Linux:**
```bash
sudo /opt/lampp/manager-linux-x64.run
# Click Start for Apache and MySQL
```

### MAMP Setup

**macOS/Windows:**
1. Open the MAMP application
2. Click "Start Servers"
3. Wait for status indicator to turn green

### Verify Services Are Running

```bash
# Test MySQL connection
mysql -u root -e "SELECT 1;"

# Should output:
# +---+
# | 1 |
# +---+
# | 1 |
# +---+
```

If you see an error, go back and ensure services are running.

---

## ⚡ **STEP 3: Run Automated Setup Script**

This script will automate most of the setup.

### macOS / Linux

```bash
cd /path/to/AICore-WP

# Make script executable
chmod +x scripts/setup-xampp-mamp.sh

# Run the setup
./scripts/setup-xampp-mamp.sh
```

**You should see:**
```
╔════════════════════════════════════════════════════════╗
║     AICore WP - XAMPP/MAMP Setup                       ║
╚════════════════════════════════════════════════════════╝

Step 1: Detecting XAMPP/MAMP installation...
✓ Found: XAMPP (macOS)
  htdocs: /Applications/XAMPP/xamppfiles/htdocs

Step 2: Checking PHP and MySQL...
✓ PHP: PHP 8.2.x ...
✓ MySQL: Found

Step 3: Checking if services are running...
✓ MySQL is running

[... more steps ...]

Setup Complete! 🎉
```

### Windows

```batch
REM Open Command Prompt as Administrator
REM (Right-click Command Prompt → Run as Administrator)

cd C:\path\to\AICore-WP

REM Run the setup
scripts\setup-xampp-mamp.bat
```

**You should see:**
```
========================================================
     AICore WP - XAMPP/MAMP Setup (Windows)
========================================================

Step 1: Detecting XAMPP installation...
Found: XAMPP at C:\xampp

Step 2: Checking PHP...
Found: PHP

[... more steps ...]

Setup Complete!
```

### What the Script Does

The script will automatically:
1. ✅ Find your XAMPP/MAMP installation
2. ✅ Verify PHP and MySQL are available
3. ✅ Create WordPress directory
4. ✅ Download WordPress 6.5
5. ✅ Create database and MySQL user
6. ✅ Generate wp-config.php
7. ✅ Link the AICore WP plugin
8. ✅ Install PHP dependencies (Composer)
9. ✅ Install Node dependencies (npm)
10. ✅ Show success summary

**Note:** The script will ask for:
- Sudo password (macOS/Linux) for some operations
- Confirmation if directory exists

Just follow the prompts!

---

## 📍 **STEP 4: Verify Installation**

After the script completes, verify everything worked:

### Check WordPress Directory Created

```bash
# macOS/Linux XAMPP
ls -la /Applications/XAMPP/xamppfiles/htdocs/aicore-wp/

# Windows XAMPP
dir C:\xampp\htdocs\aicore-wp

# Should show files like: wp-config.php, wp-content/, wp-admin/, etc.
```

### Check Database Created

```bash
# Connect to MySQL
mysql -u wordpress -pwordpress_password wordpress_aicore

# You should see: mysql>
# Type: SHOW TABLES;
# You should see tables like: wp_posts, wp_users, etc.

# Exit with: exit
```

### Check Plugin Link

```bash
# macOS/Linux
ls -la /Applications/XAMPP/xamppfiles/htdocs/aicore-wp/wp-content/plugins/aicore-wp

# Windows
dir "C:\xampp\htdocs\aicore-wp\wp-content\plugins\aicore-wp"

# Should show the plugin files (aicore-wp.php, src/, modules/, etc.)
```

---

## 🌐 **STEP 5: Access WordPress**

Open your browser and navigate to:

```
http://localhost/aicore-wp
```

You should see the **WordPress setup page** with these fields:

### WordPress Setup Form

Fill in:
- **Site Title:** "AICore WP Development"
- **Username:** "admin" (keep as is)
- **Password:** "AdminPassword123!" (keep as is)
- **Email:** "admin@example.local" (keep as is)
- **Search Engine Visibility:** Check "Discourage search engines from indexing this site"

Click "Install WordPress"

You should see: **Success! WordPress has been installed.**

Click "Log In"

---

## 🔐 **STEP 6: Login to WordPress Admin**

After clicking "Log In", you'll see the WordPress login page:

```
http://localhost/aicore-wp/wp-login.php
```

Enter:
- **Username:** `admin`
- **Password:** `AdminPassword123!`

Click "Log In"

You should now see the **WordPress Dashboard**.

---

## 🔌 **STEP 7: Activate AICore WP Plugin**

In the WordPress Dashboard:

1. Go to **Plugins** (left sidebar)
2. Find **"AICore WP"** in the plugins list
3. Click **"Activate"**

You should see:
```
Plugin activated successfully.
```

**New menu items should appear:**
- Look for "AICore" or "AICore WP" in the left sidebar

---

## ✅ **STEP 8: Verify Plugin Installation**

### Check Admin Menu

- Navigate to WordPress admin
- Should see new AICore menu items in sidebar
- Click on them to verify they load

### Check Database Tables

```bash
mysql -u wordpress -pwordpress_password wordpress_aicore

# Run:
SHOW TABLES LIKE 'wp_aicore%';

# You should see:
# wp_aicore_conversations
# wp_aicore_messages
# wp_aicore_workflows
# etc.
```

### Check Debug Log

```bash
# macOS/Linux XAMPP
tail -f /Applications/XAMPP/xamppfiles/htdocs/aicore-wp/wp-content/debug.log

# Windows XAMPP
type C:\xampp\htdocs\aicore-wp\wp-content\debug.log

# Should show activation logs with no fatal errors
```

---

## 🎨 **STEP 9: (Optional) Configure Providers**

To test AI functionality, add API keys:

1. Go to WordPress Admin
2. **Settings → AICore WP** (if available)
3. Add your API keys:
   - OpenAI
   - Anthropic (Claude)
   - Google
   - etc.

**Note:** This is optional for initial setup.

---

## 💻 **STEP 10: Start Development**

Now that everything is running, you can start developing:

### Frontend Development (Live Reload)

```bash
cd /path/to/AICore-WP

# Start Vite dev server
npm run dev

# You'll see:
# ➜  Local:   http://localhost:5173/

# Open your browser to see dashboard with live reloading
```

### Backend Development

```bash
# Make changes to PHP files in src/ and modules/
# Changes take effect immediately

# Monitor logs:
tail -f wp-content/debug.log
```

### Build Commands

```bash
# Build admin assets for production
npm run build

# Lint PHP
composer run lint

# Lint TypeScript
npm run lint

# Run tests
npm test
```

---

## 🐛 **Troubleshooting**

### Problem: "MySQL Connection Refused"

**Solution:**
```bash
# Ensure MySQL is running
mysql -u root -e "SELECT 1;"

# If error, restart XAMPP/MAMP:
# 1. Open XAMPP/MAMP Control Panel
# 2. Click Stop for MySQL
# 3. Wait 5 seconds
# 4. Click Start for MySQL
# 5. Try again
```

### Problem: "Permission Denied" on Script

**macOS/Linux:**
```bash
chmod +x scripts/setup-xampp-mamp.sh
./scripts/setup-xampp-mamp.sh
```

**Windows:**
- Right-click Command Prompt
- Select "Run as Administrator"
- Then run the .bat file

### Problem: "WordPress Not Found"

**Check:**
```bash
# XAMPP directory exists
ls -la /Applications/XAMPP/xamppfiles/htdocs/aicore-wp/

# PHP is executable
php -v

# Restart Apache in XAMPP Control Panel
```

### Problem: "Plugin Not Activating"

**Check:**
1. PHP syntax: `php -l aicore-wp.php`
2. Debug log: `tail wp-content/debug.log`
3. Plugin link: `ls -la wp-content/plugins/aicore-wp`
4. Permissions: `chmod -R 755 wp-content/plugins/aicore-wp`

### Problem: "npm Modules Not Found"

```bash
cd /path/to/AICore-WP

# Clear cache
npm cache clean --force

# Reinstall
rm -rf node_modules package-lock.json
npm install
```

---

## 📚 **Quick Reference**

### Useful Commands

```bash
# Access WordPress directory
cd /Applications/XAMPP/xamppfiles/htdocs/aicore-wp

# View WordPress logs
tail -f wp-content/debug.log

# View MySQL
mysql -u wordpress -pwordpress_password wordpress_aicore

# Access plugin directory
cd /path/to/AICore-WP

# Build frontend
npm run build

# Start dev server
npm run dev

# Run tests
npm test
```

### Access Points

| Service | URL |
|---------|-----|
| WordPress | http://localhost/aicore-wp |
| Admin | http://localhost/aicore-wp/wp-admin |
| Frontend Dev | http://localhost:5173 |

### Credentials

| Item | Value |
|------|-------|
| Admin Username | admin |
| Admin Password | AdminPassword123! |
| Database | wordpress_aicore |
| DB User | wordpress |
| DB Password | wordpress_password |

---

## 🎓 **Next Steps**

After successful deployment:

1. ✅ Explore the WordPress admin dashboard
2. ✅ Check all AICore modules are working
3. ✅ Create a test conversation (if chat enabled)
4. ✅ Make a small code change to test hot reload
5. ✅ Review the documentation in `DEVELOPMENT.md`
6. ✅ Run the test suite with `npm test`

---

## 🆘 **Getting Help**

If something goes wrong:

1. **Check debug log:** `wp-content/debug.log`
2. **Check browser console:** F12 → Console tab
3. **Review documentation:**
   - `SETUP-XAMPP-MAMP.md` - Detailed setup guide
   - `DEVELOPMENT.md` - Development workflows
   - `TESTING.md` - Testing procedures
4. **Verify services:** `mysql -u root -e "SELECT 1;"`
5. **Check PHP:** `php -l aicore-wp.php`

---

## ✨ **Success Indicators**

You'll know it worked when:

✅ WordPress loads at http://localhost/aicore-wp  
✅ Can login with admin/AdminPassword123!  
✅ AICore WP plugin is activated  
✅ Database tables exist (wp_aicore_*)  
✅ No errors in debug.log  
✅ Frontend hot reloads with `npm run dev`  
✅ Tests pass with `npm test`  

---

## 📞 **Still Need Help?**

1. Review the detailed guide: `SETUP-XAMPP-MAMP.md`
2. Check the development guide: `DEVELOPMENT.md`
3. Run the automated setup again
4. Verify all prerequisites are installed
5. Check error logs carefully

Good luck! 🚀
