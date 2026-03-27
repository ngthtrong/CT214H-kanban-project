# Testing Setup — PHPUnit

## Quickstart

### 1. Install Dependencies

```bash
# Install Composer (if not already installed)
# https://getcomposer.org/download/

# Install project dependencies
composer install
```

### 2. Setup Test Database

```bash
# Start MySQL from XAMPP Control Panel, then create test database
C:\xampp\mysql\bin\mysql.exe -u root -p -e "CREATE DATABASE kanban_test;"

# Copy test environment config
copy .env.testing.example .env.testing

# Edit .env.testing if needed (default: root user, no password)
# DB_HOST=localhost
# DB_DATABASE=kanban_test
# DB_USERNAME=root
# DB_PASSWORD=
```

### 3. Run Tests

```bash
# Run all tests
composer test

# Run with coverage report
composer test:coverage

# Run specific test file
C:\xampp\php\php.exe vendor\bin\phpunit tests\Unit\AuthTest.php

# Run specific test method
C:\xampp\php\php.exe vendor\bin\phpunit tests\Unit\AuthTest.php::testUserCanLogin
```

---

## Project Structure

```
tests/
├── Unit/              # Kiểm thử đơn vị (không FE/BE dependencies)
├── Integration/       # Kiểm thử tích hợp (multiple classes)
├── Feature/           # Kiểm thử tính năng (end-to-end behavior)
└── bootstrap.php      # PHPUnit initialization
```

---

## Test Naming Convention

```php
class AuthenticationTest extends TestCase
{
    // ✅ GOOD
    public function testUserCanLoginWithValidCredentials()
    public function testLoginFailsWithInvalidPassword()
    public function testLoginRequiresEmail()
    
    // ❌ BAD
    public function test1()
    public function testLogin()  // too vague
}
```

---

## Quality Commands

```bash
# Run all linting and tests
composer quality

# Lint code (check style)
composer lint

# Auto-fix style issues
composer lint:fix

# Run PHP Mess Detector (code smells)
composer mess

# Run development server
composer dev-server
```

---

## Coverage Reports

PHPUnit generates coverage reports in multiple formats:

1. **Terminal Text Report** — printed to console
2. **HTML Report** — detailed view at `coverage/html/index.html`

### Coverage Threshold

Gate: **80% minimum** (enforced in CI/CD)

```bash
# Check current coverage
composer test:coverage

# Output example:
# Code Coverage Metrics:
#   Classes:     85.3% (23/27)
#   Methods:     82.1% (45/55)
#   Lines:       78.9% (156/198)
```

---

## CI/CD Integration

Included in `.github/workflows/test.yaml` (when created):

```yaml
- name: Run Tests with Coverage
  run: |
    composer install
    php vendor/bin/phpunit --coverage-percentage
    
    # Fail if coverage < 80%
    if [ $(php vendor/bin/phpunit --coverage-percentage 2>&1 | grep "Lines:" | awk '{print $NF}' | cut -d'%' -f1) -lt 80 ]; then
      echo "Coverage below 80%"
      exit 1
    fi
```

---

## Common Issues

### Q: "Cannot connect to MySQL test database"
**A:** Ensure MySQL is running and `.env.testing` has correct credentials
```bash
mysql -u root -p -e "SHOW DATABASES;" | grep kanban_test
```

### Q: "PHPUnit not found"
**A:** Install via composer
```bash
composer install
```

### Q: "Coverage not showing"
**A:** Ensure PCOV or Xdebug is installed
```bash
php -m | grep -E "pcov|xdebug"
```

---

### References

- [PHPUnit Documentation](https://phpunit.de)
- [PHP_CodeSniffer Ruleset Docs](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-Ruleset)
- [PHPMD Rules](https://phpmd.org/rules/index.html)
- Agentic Team: `.github-copilot/workspace/blockers/TECH-DEBT-CHECKPOINT.md`
