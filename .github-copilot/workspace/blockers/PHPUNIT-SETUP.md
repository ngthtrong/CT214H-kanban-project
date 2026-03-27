# PHPUnit Setup — Completion Checklist

> Status: ✅ Configuration files created  
> Next: Run `composer install` to finalize setup

---

## ✅ Completed

### Configuration Files
- [x] `phpunit.xml` — PHPUnit 11 config with 80% coverage gate
- [x] `.phpcs.xml` — PHP_CodeSniffer PSR-12 ruleset
- [x] `phpmd.xml` — PHP Mess Detector rules
- [x] `composer.json` — Package manager with dev dependencies
- [x] `tests/bootstrap.php` — Test environment initialization
- [x] `.env.testing.example` — Test database template
- [x] `TESTING.md` — Comprehensive testing guide
- [x] `tests/Unit/ExampleTest.php` — Test template
- [x] `.github/workflows/test.yaml` — CI/CD pipeline
- [x] `.github-copilot/PROJECT-PROFILE.md` — Updated with commands

### Guard Rails Updated
- [x] Developer agent: Authorization enforcement guardrails added
- [x] Developer agent: Concurrency (task claiming) guardrails added
- [x] Developer agent: N+1 query prevention guardrails added
- [x] Tech Lead agent: ADR requirements for auth + concurrency
- [x] Tester agent: Smoke test authorization scenarios added

### Git Status
- [x] Commit: `626fd0e` — "chore: update agent guardrails from fragile zones analysis"
- [x] Commit: `3ab2688` — "test(setup): configure PHPUnit test automation stack"
- [ ] `develop` branch → ready for next sprint

---

## 📋 TODO: Finalize Setup

### Step 1: Install Composer Dependencies (5 min)
```bash
composer install
```

**Effect**: Downloads PHPUnit, CodeSniffer, PHPMD into `vendor/`

### Step 2: Create Test Database (5 min)
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE kanban_test;"

# Or with no password:
mysql -u root -e "CREATE DATABASE kanban_test;"

# Verify
mysql -u root -e "SHOW DATABASES;" | grep kanban_test
```

### Step 3: Setup Test Environment (2 min)
```bash
# Copy example config
cp .env.testing.example .env.testing

# Edit if needed (default usually works)
# DB_HOST=localhost
# DB_DATABASE=kanban_test
# DB_USERNAME=root
# DB_PASSWORD=
```

### Step 4: Verify PHPUnit Works (3 min)
```bash
# Run example test
composer test

# Expected output:
# PHPUnit 11.0.0 by Sebastian Bergmann and contributors
# 
# Tests: 1, Assertions: 1, OK (3 ms)
```

### Step 5: Check Coverage (2 min)
```bash
# Generate coverage report
composer test:coverage

# Expected output:
# Code Coverage Metrics:
#   Classes:    100.0% (1/1)
#   Methods:    100.0% (3/3)
#   Lines:      100.0% (3/3)
```

### Step 6: Run All Quality Checks (varies)
```bash
# Lint (PSR-12)
composer lint

# Mess detection
composer mess

# Full suite: lint + mess + test
composer quality
```

---

## 📊 CLI Commands Reference

| Command | Purpose | Time |
|---------|---------|------|
| `composer test` | Run all tests | ~10s |
| `composer test:coverage` | Tests + coverage report | ~15s |
| `composer lint` | Check code style | ~5s |
| `composer lint:fix` | Auto-fix style issues | ~5s |
| `composer mess` | Detect code smells | ~5s |
| `composer quality` | All checks (lint + mess + test) | ~25s |
| `composer dev-server` | Start local PHP server | continuous |

---

## 🔄 Agent Guard Rails Now Enforceable

Once `composer install` completes:

### Developer Agent Can Enforce
```
✅ /write-unit-tests TASK-{ID}
   → Run: php vendor/bin/phpunit
   → Verify tests FAIL (red phase)

✅ /implement-task TASK-{ID}
   → Run: php vendor/bin/phpunit (verify pass)
   → Run: php vendor/bin/phpunit --coverage-percentage
   → GATE: Coverage must be ≥ 80%
   → SCAN: Check for N+1 queries, missing indexes, missing LIMIT

✅ /check-coverage [TASK-ID]
   → Run coverage and report % vs. 80% gate
```

### Tech Lead Agent Can Enforce
```
✅ /techlead-review TASK-{ID}
   → PR checklist includes:
     - [ ] Unit test coverage ≥ 80% (from: composer test:coverage)
     - [ ] No lint warnings (from: composer lint)
     - [ ] No code smells (from: composer mess)
     - [ ] No N+1 queries (from query profiling)
     - [ ] Indexes on WHERE/JOIN columns
     - [ ] LIMIT on list endpoints
```

### CI/CD Pipeline
```
GitHub Actions (.github/workflows/test.yaml):
  1. Setup PHP 8.0 + MySQL 8.0
  2. composer install
  3. composer lint
  4. composer mess
  5. composer test
  6. Coverage check: FAIL if < 80%
  7. Report to Codecov
```

---

## 🚀 Ready for Sprint

Once all steps above complete:

```bash
# Run smoke test to verify setup
composer quality

# If passing → Ready for /plan-sprint
# Agent commands will now automatically:
#   - Use PHPUnit for tests
#   - Enforce 80% coverage
#   - Validate code style & quality
#   - Block PRs that don't meet standards
```

---

## 💡 Tips

**Tip 1**: Run `composer quality` before pushing to avoid CI/CD failures
```bash
# Before git push
composer quality

# If failures:
composer lint:fix        # Auto-fix style
composer test            # Fix logic
```

**Tip 2**: Coverage report available after `composer quality`
```bash
# HTML report at:
open coverage/html/index.html
```

**Tip 3**: Customize thresholds in `phpunit.xml`
```xml
<!-- Change 80 to 65 if needed (not recommended) -->
<check>
  <line>80</line>  ← Change here
</check>
```

---

## ✅ Completion Tracked

| Phase | Status | Evidence |
|-------|--------|----------|
| 1. Framework selection | ✅ Complete | PHPUnit in composer.json |
| 2. Coverage (PCOV) | ⏳ Pending | Install: `php -m \| grep pcov` after `composer install` |
| 3. Coverage threshold | ✅ Complete | phpunit.xml configured |
| 4. Linting (CodeSniffer + PHPMD) | ✅ Complete | .phpcs.xml + phpmd.xml |
| 5. Docker/Dev env | ⏳ Partial | .env.testing.example ready |
| 6. CI/CD (GitHub Actions) | ✅ Complete | .github/workflows/test.yaml |
| 7. Update PROJECT-PROFILE | ✅ Complete | Commands substituted |

---

**Next Command**: 
```bash
composer install
```

**Then**:
```bash
/update-agents
```  
to finalize placeholder substitution in agent guardrails.

---

*Go-live date: After `composer install` ✅+ `composer quality` passes ✅*
