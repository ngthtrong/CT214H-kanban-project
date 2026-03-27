# Tech Debt Checkpoint — Test Automation Enablement

**Status**: 🟔 BLOCKER for full agent guardrails activation  
**Created**: From `/update-agents` command (fragile zone enforcement)  
**Priority**: 🔴 P1 — blocking all /implement-task workflow

---

## Context

After `/discover-codebase`, we identified **HIGH-RISK zones** in authorization and concurrency handling. Agent guardrails have been updated to enforce these constraints (see `.github-copilot/agents/developer.md`, `tester.md`, `techlead.md`).

However, **the guardrails are not yet functional** because PROJECT-PROFILE.md shows:
```yaml
Commands:
  test: "N/A - chưa có test automation"
  coverage: "N/A - chưa có test automation"  
  lint: "N/A - chưa có test automation"
```

This document outlines the **exact steps** to unblock guardrails.

---

## Checklist: Implement Test Automation

### Phase 1: Framwork Selection (Choose 1)

- [ ] **PHPUnit** (recommended for Enterprise PHP)
  - Setup: `composer require --dev phpunit/phpunit`
  - Config: Create `phpunit.xml` in project root
  - Command: `php vendor/bin/phpunit`
  - Coverage support: Native via PCOV/Xdebug

- [ ] **Pest** (Modern, Laravel-centric)
  - Setup: `composer require --dev pestphp/pest`
  - Command: `./vendor/bin/pest`
  - Coverage support: Via PCOV

**ACTION**: Team decides and commits choice to decision log.

### Phase 2: Code Coverage Setup (Choose 1)

- [ ] **PCOV** (Fast, modern)
  - Setup: `pecl install pcov` or PHP extension
  - No overhead, native PHP 7.1+
  - Recommended ✅

- [ ] **Xdebug** (Established, many tools)
  - Already likely installed for debugging
  - Slower but widespread support

**ACTION**: Install chosen extension, verify with `php -m | grep -E 'pcov|xdebug'`

### Phase 3: Coverage Reporting

- [ ] Setup coverage command in PROJECT-PROFILE.md:
  ```bash
  COVERAGE_COMMAND="php vendor/bin/phpunit --coverage-percentage"
  # or
  COVERAGE_COMMAND="./vendor/bin/pest --coverage"
  ```
- [ ] Create coverage threshold: 80% minimum
- [ ] Add CI/CD validation (GitHub Actions) to block merges when coverage < 80%

**Expected Result**:
```bash
# Before running any test
composer install

# Run coverage check  
php vendor/bin/phpunit --coverage-percentage
# Output: Code Coverage: 45.3%
```

### Phase 4: Linting Setup (Choose tools)

- [ ] **PHP_CodeSniffer** (PSR-12 standard)
  - Setup: `composer require --dev squizlabs/php_codesniffer`
  - Command: `./vendor/bin/phpcs`

- [ ] **PHPMD** (Mess detection)
  - Setup: `composer require --dev phpmd/phpmd`
  - Command: `./vendor/bin/phpmd src text cleancode,codesize,naming`

- [ ] **Psalm** (Type checking)
  - Setup: `composer require --dev vimeo/psalm`
  - Command: `./vendor/bin/psalm`

**Recommended Combo**: PHP_CodeSniffer + PHPMD  

**ACTION**: Add lint command to PROJECT-PROFILE.md:
```bash
LINT_COMMAND="./vendor/bin/phpcs src && ./vendor/bin/phpmd src text cleancode,codesize,naming"
```

### Phase 5: Environment Setup (Development Workflow)

- [ ] Create `docker-compose.yml` or dev startup script:
  ```bash
  docker-compose up -d     # Spin up MySQL, PHP-FPM
  php -S localhost:8000    # Start dev server
  ```
- [ ] Document in PROJECT-PROFILE.md:
  ```yaml
  start: "docker-compose up -d && php -S localhost:8000 -t ."
  stop: "docker-compose down"
  health_check: "curl -f http://localhost:8000 || exit 1"
  ```

### Phase 6: CI/CD Integration

- [ ] Create `.github/workflows/test.yaml`:
  ```yaml
  name: Test & Coverage
  on: [push, pull_request]
  jobs:
    test:
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v3
        - uses: shivammathur/setup-php@v2
          with:
            php-version: '8.0'
            coverage: pcov
        - run: composer install
        - run: php vendor/bin/phpunit --coverage-percentage
        - run: ./vendor/bin/phpcs src
  ```
- [ ] Verify workflow passes before enabling branch protection rule

### Phase 7: Update PROJECT-PROFILE.md  

Once all above steps complete, update:
```yaml
Stack: "PHP 8.x + MySQL 8.x"
TestRunner: "PHPUnit"
CoverageDriver: "PCOV" 
LintTools: "PHP_CodeSniffer + PHPMD"

Commands:
  start: "docker-compose up -d && php -S localhost:8000 -t ."
  stop: "docker-compose down"  
  test: "php vendor/bin/phpunit"
  coverage: "php vendor/bin/phpunit --coverage-percentage"
  lint: "./vendor/bin/phpcs src && ./vendor/bin/phpmd src text cleancode,codesize,naming"
```

**Then run**: `/update-agents` again to substitute placeholders in developer.md, tester.md.

---

## Current Guardrail Status After Updates

### ✅ Developer Agent (Updated)
- **Authorization enforcement**: Centralized pattern required, multiple guards per endpoint
- **Concurrency (task claim)**: SELECT FOR UPDATE + concurrent test required
- **N+1 prevention**: Query profiling required before push
- **Coverage > 80%**: Enforced (blocked if test command resolves from "N/A")

### ✅ Tester Agent (Updated)
- **Authorization smoke test**: 5 scenarios covering permission matrix
- **Concurrency validation**: Documented as deferred to load test phase
- **Smoke test scenarios ≤ 10**: Always enforced

### ✅ Tech Lead Agent (Updated)
- **ADR requirements**: Auth pattern + concurrency strategy ADRs mandated
- **PR review checklist**: Authorization, concurrency, query optimization checks added

---

## Unblocking Timeline

| Phase | Task | Owner | Est. Time | Unblocks |
|-------|------|-------|-----------|----------|
| 1 | Choose test framework | Team | 30 min | Phases 2-3 |
| 2 | Install coverage (PCOV) | DevOps/Dev Lead | 30 min | Phase 3 |
| 3 | Coverage threshold (80%) | Dev Lead | 1 hour | Coverage checks |
| 4 | Linting (CodeSniffer + PHPMD) | Dev Lead | 1 hour | Lint checks |
| 5 | Docker/Dev env | DevOps | 2 hours | Local dev workflow |
| 6 | CI/CD workflow (GitHub Actions) | DevOps | 1.5 hours | Pre-push validation |
| 7 | Update PROJECT-PROFILE | Bot (auto after 1-6) | 5 min | Agent re-initialization |
| **Σ** | **All Phases** | **Team** | **~7 hours** | **Full guardrail activation** |

---

## Success Criteria

✅ All checklist items in Phase 1-7 complete  
✅ PROJECT-PROFILE.md has no "N/A" in test/coverage/lint commands  
✅ `/update-agents` re-run shows guardrails now reference actual commands  
✅ Developer can push code → CI/CD validates coverage ≥ 80% → test pass block merge if < 80%  
✅ Authorization + concurrency guardrails now bindable in PR review (not just advisory)

---

## Next Action

1. Team lead selects framework + coverage + lint tools
2. Submit for review: `/review-tech-stack`  
3. Once approved, create subtasks for Phases 1-7
4. Upon completion, re-run `/update-agents` to finalize

**Dependency**: Cannot start `/design-sprint SPRINT-1` until test automation is enabled (Phases 1-6 complete).

---

**Document Created**: 2024 (From `/update-agents` guardrail enforcement)  
**Last Updated**: Upon agent guardrail updates  
**Owner**: Agent Tech Lead + Team Lead
