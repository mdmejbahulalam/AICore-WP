# AICore WP Development Guide

This guide covers setting up and working with the AICore WP plugin in a local development environment.

## Prerequisites

- Docker & Docker Compose
- Git
- Node.js 18+ (for frontend development, optional if using Docker)
- PHP 8.2+ (for local development, optional if using Docker)

## Quick Start

### 1. Initial Setup

```bash
cd /home/user/AICore-WP

# Make setup script executable
chmod +x scripts/setup-dev.sh

# Run setup
./scripts/setup-dev.sh
```

This will:
- Start MySQL and WordPress containers
- Wait for services to be ready
- Install PHP and Node dependencies
- Display access credentials and endpoints

### 2. Access WordPress

- **WordPress**: http://localhost:8080
- **PhpMyAdmin**: http://localhost:8081
- **Admin**: admin / AdminPassword123!

### 3. Activate the Plugin

1. Go to http://localhost:8080/wp-admin
2. Login with admin credentials
3. Navigate to Plugins
4. Find "AICore WP" and click "Activate"

## Development Workflow

### Load Development Commands

```bash
source scripts/dev-commands.sh
```

This loads helpful aliases and functions:

```bash
wp-up               # Start containers
wp-down             # Stop containers
wp-logs             # View WordPress logs (with -f for tail)
wp-bash             # Execute bash in container
wp-build            # Build admin assets
wp-lint-php         # Lint PHP
wp-lint-ts          # Lint TypeScript
wp-test             # Run tests
```

### Frontend Development

The admin dashboard uses Vite for fast development:

```bash
# Terminal 1: Build admin assets
docker-compose exec wordpress npm run dev --prefix=/var/www/html/wp-content/plugins/aicore-wp

# Terminal 2: View WordPress
# Navigate to http://localhost:8080/wp-admin
```

The Vite dev server provides hot module reloading for React components.

### Backend Development

Make changes to PHP files in `src/` and `modules/`. Changes take effect immediately:

```bash
# View logs in real-time
wp-logs

# Check PHP syntax
wp-lint-php

# Test specific functionality
docker-compose exec wordpress bash
# Then run commands inside the container
```

### Database Access

**Via PhpMyAdmin**:
- URL: http://localhost:8081
- Server: mysql
- User: wordpress
- Password: wordpress_password

**Via MySQL CLI**:
```bash
wp-mysql
# Then run SQL queries
```

### Running Tests

```bash
wp-test
```

This runs PHPUnit tests defined in `phpunit.xml.dist`.

## Project Structure

```
AICore-WP/
├── src/                 # Core framework
│   ├── Core/           # Plugin bootstrap, container, modules
│   ├── AI/             # AI provider registry & orchestration
│   ├── Api/            # REST API router
│   ├── Database/       # Migrations & repository
│   ├── Security/       # Audit logs, rate limiting, permissions
│   ├── Queue/          # Async task processing
│   ├── Admin/          # Admin dashboard integration
│   └── Modules/        # Module registry & management
│
├── modules/            # Feature modules (18 total)
│   ├── chat/           # Chat capabilities
│   ├── chatbots/       # Chatbot platform
│   ├── agents/         # AI agents
│   ├── workflows/      # Automation
│   ├── memory/         # RAG system
│   ├── rag/            # Retrieval-augmented generation
│   ├── mcp/            # MCP tool integration
│   ├── woocommerce/    # E-commerce AI
│   ├── analytics/      # Usage analytics
│   ├── billing/        # Subscriptions & billing
│   └── ... (9 more)
│
├── assets/admin/       # Admin dashboard frontend
│   └── src/
│       ├── components/ # React components
│       ├── pages/      # Admin pages
│       ├── hooks/      # Custom React hooks
│       ├── stores/     # Zustand state
│       └── types/      # TypeScript definitions
│
├── templates/          # WordPress templates
├── tests/              # PHPUnit tests
├── docs/               # Documentation
└── docker-compose.yml  # Local environment setup
```

## Common Development Tasks

### Adding a New Module

1. Create `modules/your-module/module.json`:
```json
{
  "id": "your-module",
  "name": "Your Module Name",
  "enabledByDefault": true,
  "description": "Module description",
  "events": ["aicore.your-module.event"],
  "permissions": ["aicore:your-module:read", "aicore:your-module:write"]
}
```

2. Create PHP module class in `src/Modules/YourModule.php`
3. Register in module registry

### Debugging PHP

```bash
# View real-time logs
wp-logs

# Enable debugging by checking /var/www/html/wp-content/debug.log
```

### Debugging React

1. Open browser DevTools (F12)
2. React DevTools extension recommended
3. Zustand store accessible via `window.__zustand` (in dev mode)

### Building for Production

```bash
# Build PHP (linting)
composer run lint

# Build frontend
npm run build
```

## API Development

The plugin provides REST API endpoints for:
- AI operations
- Conversations
- Chatbots
- Workflows
- Memory/RAG
- Analytics
- Billing

Access via: `/wp-json/aicore/v1/`

Example:
```bash
curl -H "X-WP-Nonce: <nonce>" \
  http://localhost:8080/wp-json/aicore/v1/providers
```

## Configuration

Edit `.env` file to:
- Change database credentials
- Add AI provider API keys
- Modify WordPress settings
- Enable/disable debugging

**Important**: Never commit `.env` to git. Use `.env.example` as template.

## Troubleshooting

### WordPress won't start
```bash
docker-compose logs wordpress
# Check for database connection errors
```

### Database connection errors
```bash
# Verify MySQL is running and healthy
docker-compose ps

# Reset database
docker-compose down -v
./scripts/setup-dev.sh
```

### Node modules not found
```bash
docker-compose exec wordpress npm install --prefix=/var/www/html/wp-content/plugins/aicore-wp
```

### Plugin not appearing
1. Check PHP syntax: `wp-lint-php`
2. Check error logs: `wp-logs`
3. Verify activation hook ran: Check database tables were created

### Port conflicts
If ports 8080/8081/3306 are in use, edit `docker-compose.yml`:
```yaml
ports:
  - "8080:80"      # Change first number
  - "8081:80"      # Change first number
  - "3306:3306"    # Change first number
```

## Performance Tips

- Use `wp-logs` to monitor performance
- Check slow query logs in PhpMyAdmin
- Profile API endpoints with browser DevTools
- Use React DevTools to identify re-render issues

## Next Steps

- [ ] Activate the plugin in WordPress admin
- [ ] Configure AI providers
- [ ] Test chat functionality
- [ ] Run full test suite
- [ ] Build production assets
- [ ] Deploy documentation

## Additional Resources

- WordPress Plugin Handbook: https://developer.wordpress.org/plugins/
- PHP PSR-4 Autoloading: https://www.php-fig.org/psr/psr-4/
- React Documentation: https://react.dev
- Vite Guide: https://vitejs.dev
- TailwindCSS: https://tailwindcss.com

## Getting Help

- Check logs: `wp-logs`
- Inspect database: `wp-mysql`
- Review docker state: `wp-status`
- Run tests: `wp-test`
