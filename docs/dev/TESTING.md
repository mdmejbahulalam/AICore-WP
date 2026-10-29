# AICore WP Testing Guide

Comprehensive guide for testing the AICore WP plugin across all modules and features.

## Quick Start

```bash
# Run all tests
phpunit --configuration=phpunit.xml.dist

# Run specific test class
phpunit --configuration=phpunit.xml.dist tests/Unit/AI/ProviderRegistryTest.php

# Run tests with coverage
phpunit --configuration=phpunit.xml.dist --coverage-html=coverage

# Run TypeScript/JavaScript tests
npm test

# Lint all code
npm run lint
composer run lint
```

## Manual Testing Checklist

### 1. Plugin Installation & Activation

- [ ] Plugin appears in WordPress admin
- [ ] Plugin activates without errors
- [ ] Database tables are created
- [ ] No PHP warnings in debug.log
- [ ] Admin menu items appear

**Test Command:**
```bash
wp plugin activate aicore-wp
# Check: wp-content/debug.log for errors
```

### 2. Core Framework

- [ ] Container resolves dependencies correctly
- [ ] Module registry auto-discovers modules
- [ ] Events fire and listeners respond
- [ ] Hooks execute in correct order
- [ ] Database migrations run successfully

**Test Files:**
```
tests/Unit/Core/ContainerTest.php
tests/Unit/Modules/ModuleRegistryTest.php
```

### 3. AI Provider Integration

#### Test Each Provider

```bash
# Set API keys in wp-config.php or .env
define('OPENAI_API_KEY', 'sk-...');
define('ANTHROPIC_API_KEY', 'sk-ant-...');
define('GOOGLE_API_KEY', '...');
```

**Test Checklist:**
- [ ] Provider registry recognizes all providers
- [ ] Can fetch provider details via API
- [ ] Credentials validation works
- [ ] API calls succeed with valid keys
- [ ] Error handling for invalid keys
- [ ] Rate limiting prevents abuse

**Test Command:**
```bash
# Via WordPress admin
wp eval 'do_action("aicore_test_providers");'

# Via REST API
curl http://localhost:8080/wp-json/aicore/v1/providers
```

### 4. REST API Endpoints

```bash
# Get CSRF nonce
NONCE=$(wp eval 'echo wp_create_nonce("wp_rest");')

# Test each endpoint
curl -H "X-WP-Nonce: $NONCE" http://localhost:8080/wp-json/aicore/v1/providers
curl -H "X-WP-Nonce: $NONCE" http://localhost:8080/wp-json/aicore/v1/chat/conversations
curl -H "X-WP-Nonce: $NONCE" http://localhost:8080/wp-json/aicore/v1/workflows
```

**Test Endpoints:**
- [ ] `/wp-json/aicore/v1/providers` - Provider registry
- [ ] `/wp-json/aicore/v1/chat/conversations` - Chat operations
- [ ] `/wp-json/aicore/v1/workflows` - Workflows
- [ ] `/wp-json/aicore/v1/memory` - Memory/RAG
- [ ] `/wp-json/aicore/v1/analytics` - Analytics
- [ ] All endpoints require nonce validation
- [ ] Rate limiting is enforced

### 5. Chat Module

**Functional Tests:**
- [ ] Create new conversation
- [ ] Send message to conversation
- [ ] Receive AI response
- [ ] Chat history persists
- [ ] Multiple conversations work independently
- [ ] Delete conversation
- [ ] Export conversation as JSON

**Database Checks:**
```sql
SELECT * FROM wp_aicore_conversations;
SELECT * FROM wp_aicore_messages;
```

### 6. Chatbots Module

**Functional Tests:**
- [ ] Create chatbot
- [ ] Configure chatbot settings
- [ ] Test chatbot conversation flow
- [ ] Embed chatbot on page
- [ ] Chatbot appears on frontend
- [ ] Chat messages are logged
- [ ] Analytics tracks interactions

### 7. Workflows Module

**Functional Tests:**
- [ ] Create workflow
- [ ] Add workflow steps
- [ ] Configure conditional logic
- [ ] Test workflow execution
- [ ] Variables pass between steps
- [ ] Error handling in workflows
- [ ] Workflow history is logged

### 8. Memory/RAG Module

**Functional Tests:**
- [ ] Upload documents
- [ ] Index documents for search
- [ ] Query memory system
- [ ] Retrieve relevant context
- [ ] Memory improves over time
- [ ] Memory is organization-scoped

**Test Memory Integration:**
```php
// In WordPress admin or WP-CLI
$memory = new \AICore\WP\Memory\MemoryService();
$context = $memory->retrieve('search query', limit: 5);
```

### 9. Security

**Security Tests:**
- [ ] API nonces are validated
- [ ] User capabilities are checked
- [ ] SQL injection attempts blocked
- [ ] XSS attempts blocked
- [ ] Rate limiting prevents brute force
- [ ] API keys are hashed in database
- [ ] Audit logs record sensitive actions

**Test Audit Logging:**
```bash
wp eval 'do_action("aicore_audit_log", "test_action", ["user_id" => get_current_user_id()]);'

# Check database
SELECT * FROM wp_aicore_audit_logs ORDER BY created_at DESC LIMIT 10;
```

### 10. Admin Dashboard (React)

**UI Tests:**
- [ ] Dashboard loads without errors
- [ ] All tabs/panels render correctly
- [ ] Forms submit without errors
- [ ] Modals open and close
- [ ] Notifications display correctly
- [ ] Dark mode works
- [ ] Responsive design on mobile

**Browser DevTools:**
- [ ] No console errors
- [ ] No warnings
- [ ] React DevTools shows correct component tree
- [ ] Network requests succeed
- [ ] Local storage working

### 11. Performance Tests

**Page Load Speed:**
```bash
# Measure admin dashboard load time
curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8080/wp-admin
```

**Database Query Performance:**
```bash
# Enable query logging
wp eval 'define("SAVEQUERIES", true);'

# Check query log
wp eval 'global $wpdb; print_r($wpdb->queries);'
```

**Memory Usage:**
```php
// In plugin code
echo "Memory: " . size_format(memory_get_usage(true));
echo "Peak: " . size_format(memory_get_peak_usage(true));
```

### 12. WooCommerce Integration

**Functional Tests:**
- [ ] Products can be enhanced with AI
- [ ] AI generates product descriptions
- [ ] Product recommendations work
- [ ] AI helps with customer support
- [ ] Analytics track WooCommerce events

### 13. Analytics Module

**Functional Tests:**
- [ ] Events are captured
- [ ] Analytics dashboard shows data
- [ ] Charts render correctly
- [ ] Filters work
- [ ] Date range selection works
- [ ] Export functionality works

### 14. Billing Module

**Functional Tests:**
- [ ] Create subscription plans
- [ ] Subscribe to plan
- [ ] Usage is tracked
- [ ] Billing generates invoices
- [ ] Payment webhook handling
- [ ] Subscription renewal works

### 15. Module System

**Test Each Module:**
```bash
# List all modules
wp eval 'echo json_encode(apply_filters("aicore_modules", []));'

# Disable module
wp option update aicore_disabled_modules '["chat"]'

# Check module is disabled
wp eval 'var_dump(apply_filters("aicore_module_enabled", true, "chat"));'
```

- [ ] Each module has module.json
- [ ] Module can be enabled/disabled
- [ ] Disabling doesn't break plugin
- [ ] Module permissions work
- [ ] Module events fire

---

## Automated Testing

### Run Full Test Suite

```bash
# PHP Tests
phpunit --configuration=phpunit.xml.dist

# JavaScript/TypeScript Tests
npm test

# Linting
composer run lint
npm run lint
```

### Test Coverage

```bash
# Generate coverage report
phpunit --configuration=phpunit.xml.dist --coverage-html=coverage

# View coverage
open coverage/index.html
```

### CI/CD Testing (GitHub Actions)

The `.github/workflows` should include:
1. PHP Lint Check
2. PHPUnit Test Suite
3. JavaScript Linting
4. Security scanning
5. Database migration testing

---

## Load Testing

### Simulate High Traffic

```bash
# Using Apache Bench
ab -n 1000 -c 10 http://localhost:8080/wp-json/aicore/v1/providers

# Using WRK (if installed)
wrk -t12 -c400 -d30s \
  -H "X-WP-Nonce: $NONCE" \
  http://localhost:8080/wp-json/aicore/v1/chat/conversations
```

### Database Load Testing

```sql
-- Insert test data
INSERT INTO wp_aicore_conversations (user_id, title, created_at)
SELECT 1, CONCAT('Conversation ', id), NOW() 
FROM (SELECT 1 AS id UNION SELECT 2 UNION SELECT 3 UNION ... SELECT 1000) t;

-- Test query performance
EXPLAIN SELECT * FROM wp_aicore_messages 
WHERE conversation_id = 1 
ORDER BY created_at DESC 
LIMIT 100;
```

---

## Browser Compatibility Testing

Test in:
- [ ] Chrome/Chromium (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)

Test on:
- [ ] Desktop (1920x1080)
- [ ] Tablet (768x1024)
- [ ] Mobile (375x667)

---

## API Testing with Postman/REST Client

1. **Create collection for AICore endpoints**
2. **Set up variables**
   ```
   base_url: http://localhost:8080
   wp_nonce: (generate from wp_create_nonce)
   api_version: aicore/v1
   ```

3. **Test each endpoint**
   - GET /wp-json/{{api_version}}/providers
   - POST /wp-json/{{api_version}}/chat/conversations
   - GET /wp-json/{{api_version}}/workflows
   - etc.

4. **Test error responses**
   - 401 Unauthorized
   - 403 Forbidden
   - 404 Not Found
   - 429 Rate Limited
   - 500 Server Error

---

## Debugging Tips

### Enable Debug Mode

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
define('SAVEQUERIES', true);
```

### View Debug Log

```bash
tail -f wp-content/debug.log
```

### WordPress CLI Debugging

```bash
# Enable debug output
WP_DEBUG=1 wp eval 'var_dump(get_option("aicore_config"));'

# List all hooks
wp hook list | grep aicore

# Fire action manually
wp eval 'do_action("aicore_test_action");'
```

### Database Inspection

```bash
# View all AICore tables
wp db query "SHOW TABLES LIKE 'wp_aicore%';"

# Check table structure
wp db query "DESCRIBE wp_aicore_conversations;"

# Count records
wp db query "SELECT COUNT(*) FROM wp_aicore_messages;"
```

---

## Troubleshooting

### Plugin Won't Activate
```bash
# Check PHP syntax
php -l aicore-wp.php
find src modules -name '*.php' -print0 | xargs -0 -n1 php -l

# Check debug log
tail -f wp-content/debug.log

# Check WordPress requirements
wp core version
wp plugin verify-match aicore-wp
```

### Tests Failing
```bash
# Ensure test database exists
wp db tables --all | grep test

# Run single test with verbose output
phpunit --verbose tests/Unit/Core/ContainerTest.php

# Check test database credentials
grep AICORE_WP_TEST phpunit.xml.dist
```

### API Returning 401
```bash
# Verify user is logged in
wp user list

# Check nonce
wp eval 'echo wp_create_nonce("wp_rest");'

# Add to request header
curl -H "X-WP-Nonce: <nonce>" http://localhost:8080/wp-json/aicore/v1/providers
```

---

## Regression Testing

Before each release:

1. **Smoke Tests**
   - [ ] Plugin activates
   - [ ] Admin dashboard loads
   - [ ] Create conversation
   - [ ] View analytics
   - [ ] All modules enabled/disabled

2. **Feature Tests**
   - Run manual testing checklist above
   - Test each module
   - Test integrations

3. **Integration Tests**
   - Test with other popular plugins
   - Test with different WordPress themes
   - Test on different PHP versions

4. **Security Tests**
   - Run security scanner
   - Test rate limiting
   - Verify audit logs
   - Check API key hashing

---

## Performance Regression

Track these metrics:

```bash
# Page load time
curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8080/wp-admin

# Database query count
wp eval 'global $wpdb; echo $wpdb->num_queries;'

# Memory usage
wp eval 'echo size_format(memory_get_peak_usage(true));'

# Admin dashboard load time
npm run build
# Then measure dashboard response time
```

Set baselines and alert if metrics degrade by >10%.

---

## Recording Issues

When reporting bugs, include:

1. **Environment**
   - WordPress version
   - PHP version
   - MySQL version
   - Browser/OS

2. **Steps to Reproduce**
   - Exact steps taken
   - Expected behavior
   - Actual behavior

3. **Screenshots/Videos**
   - Browser console errors
   - Network requests
   - Debug log output

4. **Code Samples**
   - API request/response
   - PHP stack trace
   - Test case that fails
