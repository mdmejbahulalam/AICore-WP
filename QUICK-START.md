# AICore WP - Quick Start Guide

Choose your setup method below and follow the instructions.

---

## 🚀 Fastest Setup: Automated Scripts

### macOS / Linux (with XAMPP)

```bash
# 1. Clone the repository
git clone https://github.com/mdmejbahulalam/AICore-WP.git
cd AICore-WP

# 2. Run automated setup
chmod +x scripts/setup-xampp-mamp.sh
./scripts/setup-xampp-mamp.sh

# 3. Start XAMPP services
# Open XAMPP Control Panel → Start Apache & MySQL

# 4. Visit in browser
open http://localhost/aicore-wp
```

### Windows (with XAMPP)

```batch
REM 1. Clone the repository
git clone https://github.com/mdmejbahulalam/AICore-WP.git
cd AICore-WP

REM 2. Right-click Command Prompt → Run as Administrator
REM 3. Run setup script
scripts\setup-xampp-mamp.bat

REM 4. Start XAMPP
REM Open XAMPP Control Panel → Start Apache & MySQL

REM 5. Visit in browser
start http://localhost/aicore-wp
```

---

## 📚 Full Setup Guides

| Method | Documentation | Platform | Difficulty |
|--------|---------------|----------|-----------|
| **Docker Compose** | `DEVELOPMENT.md` | All | ⭐⭐ |
| **XAMPP/MAMP (Auto)** | `SETUP-XAMPP-MAMP.md` | All | ⭐ |
| **XAMPP/MAMP (Manual)** | `SETUP-XAMPP-MAMP.md` | All | ⭐⭐ |
| **LocalWP** | `SETUP-LOCAL.md` | macOS/Windows | ⭐ |
| **Laravel Valet** | `SETUP-LOCAL.md` | macOS | ⭐⭐ |

---

## ✅ Installation Checklist

After setup, verify everything works:

- [ ] WordPress running at http://localhost:8080 (or your URL)
- [ ] Can login to WordPress admin
- [ ] AICore WP plugin appears in plugins list
- [ ] Plugin activates without errors
- [ ] No errors in `wp-content/debug.log`
- [ ] Database tables created (`wp_aicore_*`)

---

## 🎯 Access Your Installation

Once running:

| What | URL / Command |
|------|---------------|
| **WordPress** | http://localhost/aicore-wp |
| **Admin Panel** | http://localhost/aicore-wp/wp-admin |
| **Admin Username** | `admin` |
| **Admin Password** | `AdminPassword123!` |
| **Database** | `wordpress_aicore` |
| **DB User** | `wordpress` / `wordpress_password` |

---

## 💻 Development Commands

After setup, you can use these commands:

### Using Make (Recommended)

```bash
cd /path/to/AICore-WP

make help              # Show all available commands
make dev              # Start Vite dev server (live reload)
make build            # Build admin assets
make lint-php         # Check PHP syntax
make lint-ts          # Check TypeScript syntax
make test             # Run tests
make logs             # View WordPress logs
```

### Using npm / composer

```bash
# Frontend development (live reload)
npm run dev

# Build production assets
npm run build

# Run tests
npm test

# PHP linting
composer run lint
```

---

## 🔧 Configuration

### Add AI Provider Keys

Edit `.env` file in project root:

```bash
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
GOOGLE_API_KEY=...
```

Then configure in WordPress admin:
**Settings → AICore WP → Providers**

### Enable Debug Logging

WordPress debug log is already enabled and located at:
```
wp-content/debug.log
```

View in real-time:
```bash
tail -f wp-content/debug.log
```

---

## 📖 Documentation Files

| File | Purpose |
|------|---------|
| **QUICK-START.md** | This file - quick reference |
| **DEVELOPMENT.md** | Complete development guide |
| **SETUP-LOCAL.md** | 5 different setup options |
| **SETUP-XAMPP-MAMP.md** | Detailed XAMPP/MAMP instructions |
| **TESTING.md** | Comprehensive testing guide |
| **README.md** | Project overview |
| **Makefile** | Convenient command shortcuts |

---

## 🧪 Testing

### Quick Test

```bash
# Test WordPress connection
curl http://localhost/aicore-wp

# Test admin login
curl -b cookies.txt -c cookies.txt \
  -d "log=admin&pwd=AdminPassword123!" \
  http://localhost/aicore-wp/wp-login.php
```

### Full Test Suite

```bash
# Run all PHP tests
phpunit --configuration=phpunit.xml.dist

# Run JavaScript tests
npm test

# Run all linters
make lint-php
make lint-ts
```

---

## 🐛 Common Issues

### "Connection refused"
- Check XAMPP/MAMP services are running
- Try: Open XAMPP Control Panel → Start Apache & MySQL

### "Database error"
- Verify MySQL is running
- Check credentials in wp-config.php
- Run: `mysql -u wordpress -pwordpress_password wordpress_aicore`

### "Plugin not found"
- Check plugin symlink is correct
- Verify PHP syntax: `php -l aicore-wp.php`
- Check error log: `tail wp-content/debug.log`

### "Port already in use"
- Default: http://localhost/aicore-wp
- Change to different port in XAMPP settings
- Or modify vite.config.ts for frontend

---

## 🚀 Next Steps

1. **Activate Plugin**
   - Go to wp-admin/plugins.php
   - Activate "AICore WP"

2. **Configure Providers**
   - Go to Settings → AICore WP
   - Add your API keys

3. **Test Chat**
   - Create a conversation
   - Send a message to AI

4. **Explore Modules**
   - Check each module is working
   - Test workflows, chatbots, etc.

5. **Start Development**
   - Edit code in src/ and modules/
   - Run `npm run dev` for frontend
   - Changes hot-reload automatically

---

## 📚 Learning Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [React Documentation](https://react.dev)
- [PHP Namespace & PSR-4](https://www.php-fig.org/psr/psr-4/)
- [Vite Documentation](https://vitejs.dev)
- [TailwindCSS](https://tailwindcss.com)
- [Zustand State Management](https://github.com/pmndrs/zustand)

---

## 🆘 Getting Help

### Check These First

1. **Debug Log:** `wp-content/debug.log`
2. **Browser Console:** Press F12 → Console
3. **PHP Syntax:** Run `php -l aicore-wp.php`
4. **Database:** Check tables exist in `wordpress_aicore`

### Run Diagnostic Tests

```bash
# Check PHP version (need 8.2+)
php -v

# Check MySQL connection
mysql -u wordpress -pwordpress_password wordpress_aicore -e "SELECT 1;"

# Check WordPress setup
wp core version
wp plugin list

# Check plugin syntax
php -l aicore-wp.php
find src modules -name '*.php' -print0 | xargs -0 -n1 php -l
```

### Review Documentation

- See `DEVELOPMENT.md` for full development guide
- See `TESTING.md` for testing procedures
- See `SETUP-XAMPP-MAMP.md` for detailed XAMPP/MAMP setup

---

## 🎯 Your Setup Path

```
Choose Setup Method
       ↓
┌─────────────────────┐
│ 1. Docker? (fastest) │ → DEVELOPMENT.md
│ 2. LocalWP? (easiest)│ → SETUP-LOCAL.md
│ 3. XAMPP/MAMP?      │ → SETUP-XAMPP-MAMP.md
└─────────────────────┘
       ↓
    Run Setup
       ↓
   Verify Works
       ↓
  Start Coding! 🚀
```

---

## 📋 Verification Checklist

After installing, verify each item:

- [ ] WordPress loads at http://localhost/aicore-wp
- [ ] Can login with admin / AdminPassword123!
- [ ] AICore WP plugin is activated
- [ ] No errors in wp-content/debug.log
- [ ] Database has tables: wp_aicore_conversations, etc.
- [ ] Can create a new conversation
- [ ] Frontend Vite dev server works: `npm run dev`
- [ ] Tests pass: `npm test`
- [ ] PHP lint passes: `php -l aicore-wp.php`

---

## 🎓 First Development Task

Try this to get familiar with the codebase:

1. **Open frontend in editor:**
   ```bash
   code /path/to/AICore-WP
   ```

2. **Start dev server:**
   ```bash
   npm run dev
   ```

3. **Edit a component:**
   - Open: `assets/admin/src/components/`
   - Change a button label or color
   - Save the file

4. **Watch it update:**
   - Admin dashboard auto-reloads
   - See your changes live!

5. **Try backend:**
   - Edit a PHP file in `src/`
   - Changes take effect immediately
   - Check `wp-content/debug.log` for any issues

---

## 🎉 You're Ready!

Everything is now set up and ready for development. Choose your setup method from the guide above and get started!

**Questions?** Check the relevant documentation file listed above.

**Ready to code?** See `DEVELOPMENT.md` for best practices and workflows.

Happy developing! 🚀
