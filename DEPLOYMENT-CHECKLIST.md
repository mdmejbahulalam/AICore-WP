# AICore WP Deployment Checklist

Use this checklist as you follow the deployment process on your local machine.

---

## 📋 PRE-DEPLOYMENT

### Prerequisites Check

- [ ] XAMPP or MAMP installed
  - XAMPP: https://www.apachefriends.org
  - MAMP: https://www.mamp.info
- [ ] Git installed (`git --version`)
- [ ] Composer installed (`composer --version`)
- [ ] Node.js 18+ installed (`node -v`)
- [ ] npm installed (`npm -v`)
- [ ] Terminal/Command Prompt access
- [ ] Admin access on computer
- [ ] 2GB+ free disk space

### Required Versions

- [ ] PHP 8.2 or higher
  ```bash
  php -v
  # Should show: PHP 8.2.x or higher
  ```
- [ ] Composer 2.x or higher
  ```bash
  composer --version
  # Should show: Composer 2.x
  ```
- [ ] Node 18+ LTS
  ```bash
  node -v
  # Should show: v18.x or higher
  ```

---

## 🚀 DEPLOYMENT STEPS

### Step 1: Clone Repository ✓

**Action:**
```bash
cd ~/Projects  # or your preferred location
git clone https://github.com/mdmejbahulalam/AICore-WP.git
cd AICore-WP
```

**Verification:**
```bash
git branch
# Should show: claude/cool-pasteur-cdQQV
```

- [ ] Repository cloned successfully
- [ ] Correct branch (claude/cool-pasteur-cdQQV)
- [ ] All files present
  ```bash
  ls -la
  # Should show: Makefile, QUICK-START.md, src/, modules/, etc.
  ```

---

### Step 2: Start Services ✓

**XAMPP:**

- [ ] Opened XAMPP Control Panel
- [ ] Apache: Clicked "Start" (shows green)
- [ ] MySQL: Clicked "Start" (shows green)
- [ ] Both running for at least 10 seconds

**MAMP:**

- [ ] Opened MAMP application
- [ ] Clicked "Start Servers"
- [ ] Status indicator is green

**Verification:**
```bash
mysql -u root -e "SELECT 1;"
# Should show output with +---+ | 1 | +---+
```

- [ ] MySQL is responding to connections
- [ ] No "Connection refused" error

---

### Step 3: Run Setup Script ✓

**macOS/Linux:**

```bash
cd /path/to/AICore-WP
chmod +x scripts/setup-xampp-mamp.sh
./scripts/setup-xampp-mamp.sh
```

**Windows (Run as Administrator):**

```batch
cd C:\path\to\AICore-WP
scripts\setup-xampp-mamp.bat
```

**During Execution:**

- [ ] Script detects XAMPP/MAMP location
- [ ] PHP version verified (8.2+)
- [ ] MySQL connection confirmed
- [ ] WordPress directory created
- [ ] WordPress downloaded and extracted
- [ ] Database created
- [ ] wp-config.php generated
- [ ] Plugin linked successfully
- [ ] Composer dependencies installed
- [ ] npm dependencies installed
- [ ] Success message displayed

**If errors occur:**
```bash
# Check the error message carefully
# Scroll up to see what failed
# Common fixes:
# - Ensure XAMPP/MAMP services running
# - Check MySQL is responding
# - Verify PHP version 8.2+
```

- [ ] Script completed successfully with no fatal errors
- [ ] Success summary visible

---

### Step 4: Verify Installation ✓

**Check WordPress Directory:**

```bash
# macOS/Linux
ls -la /Applications/XAMPP/xamppfiles/htdocs/aicore-wp/

# Windows
dir C:\xampp\htdocs\aicore-wp\
```

- [ ] Directory exists and contains WordPress files
- [ ] Files: wp-config.php, wp-content/, wp-admin/, wp-includes/

**Check Database:**

```bash
mysql -u wordpress -pwordpress_password wordpress_aicore -e "SHOW TABLES;"
```

- [ ] Database connection successful
- [ ] Tables visible (wp_posts, wp_users, etc.)

**Check Plugin Link:**

```bash
# macOS/Linux
ls -la /Applications/XAMPP/xamppfiles/htdocs/aicore-wp/wp-content/plugins/aicore-wp/

# Windows
dir "C:\xampp\htdocs\aicore-wp\wp-content\plugins\aicore-wp\"
```

- [ ] aicore-wp directory exists
- [ ] aicore-wp.php is present
- [ ] src/ and modules/ directories present

---

### Step 5: Access WordPress ✓

**Open Browser:**

```
http://localhost/aicore-wp
```

- [ ] WordPress setup page loads
- [ ] Installation form visible

**Fill Setup Form:**

- [ ] Site Title: "AICore WP Development"
- [ ] Username: "admin"
- [ ] Password: "AdminPassword123!"
- [ ] Email: "admin@example.local"
- [ ] "Discourage search engines" checked
- [ ] Clicked "Install WordPress"

**Verify Success:**

- [ ] "Success! WordPress has been installed" message shown
- [ ] Click "Log In" button visible

---

### Step 6: Login to Admin ✓

**URL:**
```
http://localhost/aicore-wp/wp-login.php
```

**Credentials:**
- Username: `admin`
- Password: `AdminPassword123!`

**After Login:**

- [ ] WordPress Dashboard loads
- [ ] Admin sidebar visible
- [ ] "Plugins" menu available

---

### Step 7: Activate Plugin ✓

**Navigation:**
1. Click **"Plugins"** in left sidebar
2. Find **"AICore WP"** in plugins list
3. Click **"Activate"**

**Verification:**

- [ ] Plugin appears in plugins list
- [ ] "Activate" link visible initially
- [ ] After clicking "Activate":
  ```
  Plugin activated successfully
  ```
- [ ] "Deactivate" link now shows (instead of "Activate")
- [ ] New menu items appear in sidebar (look for "AICore")

---

### Step 8: Plugin Verification ✓

**Check Admin Menu:**

- [ ] New AICore menu items appear in sidebar
- [ ] Click on menu items - they load without errors

**Check Database Tables:**

```bash
mysql -u wordpress -pwordpress_password wordpress_aicore

# Run in MySQL:
SHOW TABLES LIKE 'wp_aicore%';
```

- [ ] Multiple wp_aicore_* tables listed
- [ ] Tables like conversations, messages, etc.

**Check Debug Log:**

```bash
# macOS/Linux
tail -f /Applications/XAMPP/xamppfiles/htdocs/aicore-wp/wp-content/debug.log

# Windows
type C:\xampp\htdocs\aicore-wp\wp-content\debug.log

# Exit with: Ctrl+C (macOS/Linux) or close window (Windows)
```

- [ ] No fatal errors in log
- [ ] Plugin activation logged
- [ ] No "class not found" errors

---

## 🧪 TESTING

### Quick Test

```bash
cd /path/to/AICore-WP

# Test PHP syntax
php -l aicore-wp.php
```

- [ ] "No syntax errors detected" message

**Test Plugin Files:**

```bash
find src modules -name '*.php' -print0 | xargs -0 -n1 php -l
```

- [ ] All files show "No syntax errors"

### Run Test Suite

```bash
# PHP tests
phpunit --configuration=phpunit.xml.dist

# JavaScript tests
npm test

# Linting
composer run lint
npm run lint
```

- [ ] All tests pass (or show expected results)
- [ ] No critical errors

---

## 🎨 DEVELOPMENT

### Frontend Development

```bash
cd /path/to/AICore-WP
npm run dev
```

**Verify:**

- [ ] Vite server starts
- [ ] Shows: `Local: http://localhost:5173`
- [ ] No compilation errors
- [ ] Can access: http://localhost:5173

### Backend Development

```bash
# Edit a file in src/
# Make a simple change
# Save file
```

**Verify:**

```bash
# Monitor logs
tail -f wp-content/debug.log
```

- [ ] Changes reflected without restart
- [ ] No PHP errors in log

---

## 📊 FINAL VERIFICATION

### Access Points Working

- [ ] WordPress: http://localhost/aicore-wp ✅
- [ ] Admin: http://localhost/aicore-wp/wp-admin ✅
- [ ] Frontend Dev: http://localhost:5173 ✅ (when running `npm run dev`)

### Credentials Correct

- [ ] Admin Username: `admin` ✅
- [ ] Admin Password: `AdminPassword123!` ✅
- [ ] Database: `wordpress_aicore` ✅
- [ ] DB User: `wordpress` ✅
- [ ] DB Password: `wordpress_password` ✅

### No Errors

- [ ] No errors in browser console (F12)
- [ ] No errors in wp-content/debug.log
- [ ] No errors in browser Network tab
- [ ] All network requests returning 200/3xx

### Plugin Features

- [ ] Plugin menu items visible
- [ ] Can click on menu items
- [ ] Database tables exist
- [ ] No permission errors
- [ ] Can navigate admin pages

---

## 🎉 SUCCESS!

All items checked? You're ready to develop!

### Next Steps

1. **Review Documentation**
   - [ ] Read `DEVELOPMENT.md`
   - [ ] Read `QUICK-START.md`
   - [ ] Review `TESTING.md`

2. **Start Development**
   - [ ] Make first code change
   - [ ] Run `npm run dev`
   - [ ] Verify hot reload works
   - [ ] Test backend change

3. **Configure (Optional)**
   - [ ] Add AI provider keys to .env
   - [ ] Test with real API calls
   - [ ] Create test conversation

4. **Run Full Test Suite**
   - [ ] `npm test`
   - [ ] `composer run lint`
   - [ ] `npm run lint`

---

## 🐛 TROUBLESHOOTING

### If Something Failed

**Step 1: Review Error Message**
- Scroll to top of terminal/console
- Look for red error text
- Note the exact error

**Step 2: Check Common Issues**

| Issue | Solution |
|-------|----------|
| MySQL Connection Refused | Restart MySQL in XAMPP/MAMP |
| PHP not found | Check PHP version (`php -v`) |
| Port already in use | Change port in XAMPP settings |
| Permission Denied | Run Terminal as Administrator |
| Plugin not found | Check symlink: `ls wp-content/plugins/aicore-wp/` |
| WordPress won't load | Check Apache is running |
| npm modules error | Run: `npm cache clean --force && npm install` |

**Step 3: Run Setup Again**
- Stop XAMPP/MAMP services
- Wait 10 seconds
- Start services again
- Re-run the setup script

**Step 4: Check Logs**
```bash
# WordPress debug log
tail -f wp-content/debug.log

# Browser console
Press F12 → Console tab

# PHP syntax
php -l aicore-wp.php
```

**Step 5: Ask for Help**
- Provide the error message exactly
- Include log output (redacted if needed)
- Describe steps taken so far

---

## 📱 Quick Commands Reference

```bash
# Useful shortcuts
cd /path/to/AICore-WP

# Start dev
npm run dev

# Build production
npm run build

# Run tests
npm test

# Check PHP
php -l aicore-wp.php

# View logs
tail -f wp-content/debug.log

# Database
mysql -u wordpress -pwordpress_password wordpress_aicore

# Lint
composer run lint
npm run lint

# Make commands (if available)
make help
make build
make lint-php
make test
```

---

## 🎓 Learning Path

After deployment:

1. **Understand Structure** (DEVELOPMENT.md)
2. **Make Small Change** (edit button text, change color)
3. **Test Hot Reload** (see change immediately)
4. **Read Code Comments** (understand architecture)
5. **Run Tests** (ensure nothing broke)
6. **Review Modules** (chat, workflows, etc.)
7. **Create New Feature** (add a simple page)

---

## ✅ Deployment Complete!

Mark this when fully deployed:

- [ ] WordPress running locally
- [ ] AICore WP plugin activated
- [ ] All verification steps passed
- [ ] Can develop and hot reload works
- [ ] Ready to start coding

**Congratulations! You're ready to develop AICore WP!** 🚀
