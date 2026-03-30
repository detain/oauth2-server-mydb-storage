---
name: add-repository-test
description: Creates a PHPUnit test class in `tests/` for a MyDb OAuth2 repository, extending `MyDbTest` from `tests/bootstrap.php`. Seeds the SQLite in-memory DB with `$this->db->exec()`, covers get/create/delete/getScopes/associateScope methods, and follows the `{Name}RepositoryTest.php` naming pattern. Use when user says 'add tests', 'write test', 'test repository', or adds a new file to `tests/`. Do NOT use for integration tests against a real MySQL DB or for non-repository classes.
---
# add-repository-test

## Critical

- Test class MUST extend `MyDbTest` (defined in `tests/bootstrap.php`) — never extend `PHPUnit_Framework_TestCase` directly
- Test file MUST be named after the repository class (e.g. `tests/AccessTokenRepositoryTest.php`, `tests/RefreshTokenRepositoryTest.php`)
- Seed ALL test data with `$this->db->exec("INSERT INTO ...")` inside each individual test method — never in `setUp()`
- Verify DB state after writes using `$this->db->prepare(...); $stmt->execute(); $stmt->fetch(PDO::FETCH_ASSOC)` — not via repository methods
- Always assert `$this->assertNull($result)` for not-found cases — never `assertFalse`
- Use `$this->getMock(AbstractServer::class)` (not `createMock`) — this is PHPUnit 4/5 API

## Instructions

1. **Identify the repository interface and entity classes.**
   Read the repository source file in `src/` (e.g. `src/AccessTokenRepository.php`) to find which `League\OAuth2\Server\Repository\*Interface` it implements and which `League\OAuth2\Server\Entity\*Entity` it uses.
   Verify the interface methods: typically `get()`, `create()`, `delete()`, `getScopes()`, `associateScope()`.

2. **Create the test file** in `tests/` with this skeleton:
   ```php
   <?php

   use Detain\OAuth2\Server\Repository\MyDb\{Name}Repository;
   use League\OAuth2\Server\AbstractServer;
   use League\OAuth2\Server\Entity\{Name}Entity;
   use League\OAuth2\Server\Entity\ScopeEntity;

   class {Name}RepositoryTest extends MyDbTest
   {
       /** @var {Name}Repository */
       protected ${camel};
       /** @var AbstractServer */
       protected $server;

       protected function setUp()
       {
           parent::setUp();
           $this->{camel} = new {Name}Repository($this->db);
           $this->server = $this->getMock(AbstractServer::class);
           $this->{camel}->setServer($this->server);
       }
   }
   ```
   Verify `parent::setUp()` is called first — it initializes `$this->db` as SQLite in-memory and loads `sql/oauth2.sql`.

3. **Add `testGetFailed()` and `testGet()`:**
   ```php
   public function testGetFailed()
   {
       $result = $this->{camel}->get('unknown');
       $this->assertNull($result);
   }

   public function testGet()
   {
       $this->db->exec("INSERT INTO oauth_{table} VALUES ('myId', ...)");
       $result = $this->{camel}->get('myId');
       $this->assertNotNull($result);
       $this->assertEquals('myId', $result->getId());
   }
   ```

4. **Add `testCreate()` and constraint-violation tests:**
   ```php
   public function testCreate()
   {
       $this->{camel}->create('newId', ...);
       $stmt = $this->db->prepare("SELECT * FROM oauth_{table} WHERE {pk} = 'newId'");
       $stmt->execute();
       $this->assertSame(['{pk}' => 'newId', ...], $stmt->fetch(PDO::FETCH_ASSOC));
   }

   /**
    * @expectedException PDOException
    * @expectedExceptionMessageRegExp '.*constraint (failed|violation):.*{column}'
    */
   public function testCreateFailed{Column}Null()
   {
       $this->{camel}->create(null, ...);
   }
   ```
   Add one `@expectedException` test per NOT NULL column.

5. **Add `testGetScopes()`, `testGetScopesEmpty()`, `testAssociateScope()`** (if the interface includes scope methods):
   ```php
   public function testGetScopes()
   {
       $this->db->exec("INSERT INTO oauth_{table} VALUES (...);
           INSERT INTO oauth_scopes VALUES ('user.list', 'list users');
           INSERT INTO oauth_{table}_scopes VALUES (10, 'myId', 'user.list');");
       $entity = (new {Name}Entity($this->server))->setId('myId');
       $scopes = $this->{camel}->getScopes($entity);
       $this->assertEquals(1, count($scopes));
       $this->assertEquals('user.list', $scopes[0]->getId());
       $this->assertEquals('list users', $scopes[0]->getDescription());
   }

   public function testAssociateScope()
   {
       $entity = (new {Name}Entity($this->server))->setId('myId');
       $scope = (new ScopeEntity($this->server))->hydrate(['id' => 'user.list', 'description' => 'list user']);
       $this->{camel}->associateScope($entity, $scope);
       $stmt = $this->db->prepare("SELECT {fk}, scope FROM oauth_{table}_scopes WHERE {fk} = 'myId'");
       $stmt->execute();
       $this->assertSame(['myId', 'user.list'], $stmt->fetch(PDO::FETCH_NUM));
   }
   ```

6. **Add `testDelete()`:**
   ```php
   public function testDelete()
   {
       $this->db->exec("INSERT INTO oauth_{table} VALUES ('myId', ...)");
       $entity = (new {Name}Entity($this->server))->setId('myId');
       $this->{camel}->delete($entity);
       $stmt = $this->db->prepare("SELECT * FROM oauth_{table} WHERE {pk} = 'myId'");
       $stmt->execute();
       $this->assertSame([], $stmt->fetchAll(PDO::FETCH_ASSOC));
   }
   ```

7. **Run tests to verify:**
   ```bash
   ./vendor/bin/phpunit
   ```
   All tests must pass before committing.

## Examples

**User says:** "Add tests for RefreshTokenRepository"

**Actions taken:**
1. Read `src/RefreshTokenRepository.php` — implements `RefreshTokenInterface`, uses `RefreshTokenEntity`, table `oauth_refresh_tokens`
2. Create the test file in `tests/` extending `MyDbTest`
3. `setUp()` instantiates the repository with `$this->db` and `$this->getMock(AbstractServer::class)`
4. `testGetFailed()` calls `get('unknown')`, asserts `assertNull`
5. `testGet()` seeds `oauth_refresh_tokens` via `$this->db->exec("INSERT INTO oauth_refresh_tokens VALUES ('tok1', 1, DATETIME('NOW', '+1 DAY'));")`, asserts `getId()` and `getExpireTime()`
6. `testCreate()` calls `create('tok2', 2048, 1)`, verifies row via `$stmt->fetch(PDO::FETCH_ASSOC)`
7. `testDelete()` seeds a row, calls `delete($entity)`, verifies `fetchAll()` returns `[]`
8. Runs `./vendor/bin/phpunit` — all green

## Common Issues

- **`Call to undefined method getMock()`**: You are using PHPUnit 6+. This project targets PHPUnit 4/5 API. Keep `getMock()` — do not replace with `createMock()`.
- **`Class 'MyDbTest' not found`**: `phpunit.xml` must reference `tests/bootstrap.php` as the bootstrap file. Confirm with `cat phpunit.xml | grep bootstrap`.
- **SQLite constraint error on multi-statement `exec()`**: SQLite requires semicolons between statements in a single `exec()` call. Use one string with `;` separators as shown in existing tests.
- **`MYSQL_ASSOC` undefined constant**: This is defined by the `MyDb` library — it must be installed via `composer install`. Run `composer install` if the constant is missing.
- **`@expectedExceptionMessageRegExp` not matching**: SQLite says `constraint failed: table.column`; MySQL says `constraint violation`. The pattern `'.*constraint (failed|violation):.*{column}'` covers both — copy this regex exactly.
