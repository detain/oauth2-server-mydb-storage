# oauth2-server-mydb-storage

MyDb storage adapter implementing [thephpleague/oauth2-server](https://github.com/thephpleague/oauth2-server/) repository interfaces. Namespace: `Detain\OAuth2\Server\Repository\MyDb\`.

## Commands

```bash
composer install           # install deps
./vendor/bin/phpunit       # run all tests (config: phpunit.xml)
```

```bash
# Syntax-check a repository class
php -l src/AccessTokenRepository.php
# Run a single test file
./vendor/bin/phpunit tests/AccessTokenRepositoryTest.php
```

## Architecture

**Base**: `src/Repository.php` extends `League\OAuth2\Server\Repository\AbstractRepository` · holds `$this->db` (`MyDb\Generic`) and `$this->supportsReturning`

**Repositories** (`src/`):
- `AccessTokenRepository.php` — `AccessTokenInterface` · tables: `oauth_access_tokens`, `oauth_access_token_scopes`
- `AuthCodeRepository.php` — `AuthCodeInterface` · tables: `oauth_auth_codes`, `oauth_auth_code_scopes`
- `ClientRepository.php` — `ClientInterface` · tables: `oauth_clients`, `oauth_client_redirect_uris`
- `RefreshTokenRepository.php` — `RefreshTokenInterface` · table: `oauth_refresh_tokens`
- `ScopeRepository.php` — `ScopeInterface` · table: `oauth_scopes`
- `SessionRepository.php` — `SessionInterface` · tables: `oauth_sessions`, `oauth_session_scopes`

**Schema**: `sql/oauth2.sql` — 10 tables with FK constraints, seed data for `oauth_scopes` and `oauth_clients`

**Tests** (`tests/`): each extends `MyDbTest` from `tests/bootstrap.php` · SQLite in-memory · schema loaded from `sql/oauth2.sql` · one test file per repository

## DB Query Pattern

```php
// SELECT
$this->db->query('SELECT * FROM oauth_access_tokens WHERE access_token = "'.$this->db->real_escape($token)'"');
if ($this->db->num_rows() === 1) {
    $this->db->next_record(MYSQL_ASSOC);
    $row = $this->db->Record;
}

// INSERT
$this->db->query('INSERT INTO oauth_access_tokens (access_token, expire_time, session_id) VALUES ("'.$this->db->real_escape($token).'","'.$this->db->real_escape($expireTime).'","'.$this->db->real_escape($sessionId).'")'));

// DELETE
$this->db->query('DELETE FROM oauth_access_tokens WHERE access_token = "'.$this->db->real_escape($token->getId()).'"');
```

- Always `$this->db->real_escape()` on every value — no raw `$_GET`/`$_POST` interpolation
- Check `$this->db->num_rows() === 1` before calling `next_record(MYSQL_ASSOC)`
- Access results via `$this->db->Record['column_name']`
- Multi-row results: loop with `while ($this->db->next_record(MYSQL_ASSOC))`

## Conventions

- All `src/` classes extend `src/Repository.php` and implement the matching `League\OAuth2\Server\Repository\*Interface`
- Hydrate entities: `(new FooEntity($this->server))->hydrate(['id' => ..., 'description' => ...])` or use setters like `->setId()`, `->setExpireTime()`
- Return `null` (not `false`) when a record is not found
- Test files in `tests/` named `{Name}RepositoryTest.php` extending `MyDbTest`
- Always cover: `get()` success + failure, `create()`, `delete()`, `getScopes()`, `associateScope()`
- Seed test data via `$this->db->exec("INSERT INTO ...")` inside each test method

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
