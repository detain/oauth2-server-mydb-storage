---
name: add-repository
description: Creates a new OAuth2 repository class in `src/` following the project's MyDb pattern. Generates the class extending `src/Repository.php`, implements the correct `League\OAuth2\Server\Repository\*Interface`, uses `$this->db->query()` with `$this->db->real_escape()`, and returns hydrated entities or null on miss. Use when user says 'add repository', 'new storage class', 'implement interface', or needs a new file in `src/`. Do NOT use for modifying existing repositories.
---
# add-repository

## Critical

- **Never** interpolate raw user input — every value passed to SQL must go through `$this->db->real_escape()`.
- **Always** check `$this->db->num_rows() === 1` before calling `$this->db->next_record(MYSQL_ASSOC)`.
- **Always** return `null` (not `false`) when a record is not found.
- The class **must** extend `Detain\OAuth2\Server\Repository\MyDb\Repository` and implement the matching `League\OAuth2\Server\Repository\*Interface`.
- Do **not** use PDO directly or the `run()` helper — use `$this->db->query()` for all writes and single-result reads; use `$this->db->qr()` only for multi-row JOINs.

## Instructions

1. **Identify the interface.** Confirm which `League\OAuth2\Server\Repository\*Interface` to implement (e.g. `RefreshTokenInterface`). Available interfaces are in the `league/oauth2-server` package under the `League\OAuth2\Server\Repository\` namespace — see existing implementations like `src/AccessTokenRepository.php` for reference.

2. **Create the new `src/{Name}Repository.php`** with this skeleton:

```php
<?php

namespace Detain\OAuth2\Server\Repository\MyDb;

use League\OAuth2\Server\Entity\{Name}Entity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Repository\{Name}Interface;

class {Name}Repository extends Repository implements {Name}Interface
{
    public function get($id)
    {
        $this->db->query('SELECT * FROM oauth_{table} WHERE {pk} = "'.$this->db->real_escape($id)'"');
        if ($this->db->num_rows() === 1) {
            $this->db->next_record(MYSQL_ASSOC);
            $entity = new {Name}Entity($this->server);
            $entity->setId($this->db->Record['{pk}']);
            // map remaining columns via setters
            return $entity;
        }
        return null;
    }

    public function create(/* interface params */)
    {
        $this->db->query('INSERT INTO oauth_{table} ({col1}, {col2}) VALUES ("'.$this->db->real_escape($val1).'","'.$this->db->real_escape($val2).'")'));
    }

    public function delete({Name}Entity $entity)
    {
        $this->db->query('DELETE FROM oauth_{table} WHERE {pk} = "'.$this->db->real_escape($entity->getId()).'"');
    }
}
```

   Verify the new file compiles with:
   ```bash
   php -l src/AccessTokenRepository.php
   ```

3. **Add `getScopes()` and `associateScope()` if the interface requires them.** Follow `src/AccessTokenRepository.php` — use `$this->db->qr()` for the JOIN, iterate results, and hydrate each `ScopeEntity` via `->hydrate(['id' => ..., 'description' => ...])`.

4. **Create the matching test file** in `tests/` extending `MyDbTest`:

```php
<?php
use Detain\OAuth2\Server\Repository\MyDb\{Name}Repository;
use League\OAuth2\Server\AbstractServer;
use League\OAuth2\Server\Entity\{Name}Entity;

class {Name}RepositoryTest extends MyDbTest
{
    protected ${camel};
    protected $server;

    public function testGetFailed() { $this->assertNull($this->{camel}->get('unknown')); }
    public function testGet() { /* seed, call get(), assert getId() and fields */ }
    public function testCreate() { /* call create(), SELECT via $this->db->prepare(), assertSame */ }
    public function testDelete() { /* seed, call delete(), SELECT, assertSame([]) */ }
    // add testGetScopes / testAssociateScope if interface requires them

    protected function setUp()
    {
        parent::setUp();
        $this->server = $this->getMock(AbstractServer::class);
        $this->{camel} = new {Name}Repository($this->db);
        $this->{camel}->setServer($this->server);
    }
}
```

   Seed test data inline with `$this->db->exec("INSERT INTO ...")` — no shared fixtures.

5. **Run tests** to confirm green:
   ```bash
   ./vendor/bin/phpunit
   ```

## Examples

**User says:** "Add a refresh token repository"

**Actions:**
- Creates `src/RefreshTokenRepository.php` extending `Repository`, implementing `RefreshTokenInterface`
- `get($token)` queries `oauth_refresh_tokens`, checks `num_rows() === 1`, calls `next_record(MYSQL_ASSOC)`, sets `->setId()` and `->setExpireTime()` on a new `RefreshTokenEntity`, returns it or `null`
- `create($token, $expireTime, $accessToken)` INSERTs with three `real_escape()` calls
- `delete(RefreshTokenEntity $token)` DELETEs by `real_escape($token->getId())`
- Creates a test file in `tests/` with `testGetFailed`, `testGet`, `testCreate`, `testDelete`

**Result:** `./vendor/bin/phpunit` passes all four tests.

## Common Issues

- **`Call to undefined method MyDb\Generic::real_escape()`** — you're running tests against a real MySQL connection, not the SQLite in-memory instance from `tests/bootstrap.php`. Ensure `setUp()` calls `parent::setUp()` so `$this->db` is the SQLite `MyDb\Generic` instance.
- **`num_rows() returns 0` but row exists** — the query ran before `exec()` committed in the same test. Add `;` between statements in multi-statement `exec()` calls (SQLite requires them).
- **`Class 'League\OAuth2\Server\Entity\{Name}Entity' not found`** — check the exact class name in the `league/oauth2-server` package; some entities omit the module word (e.g. `SessionEntity` not `OauthSessionEntity`).
- **`PHPUnit_Framework_TestCase not found`** — the bootstrap extends `PHPUnit_Framework_TestCase` (PHPUnit 4/5 alias). Run `./vendor/bin/phpunit` (project-local binary), not a globally installed PHPUnit 9+.
- **INSERT fails with FK constraint violation in tests** — SQLite enforces FK constraints only when `PRAGMA foreign_keys = ON`. The schema in `sql/oauth2.sql` does not enable it by default; seed parent tables first or omit the FK-constrained columns in unit-focused tests.
