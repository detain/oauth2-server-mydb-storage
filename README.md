# oauth2-server-mydb-storage

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/detain/oauth2-server-mydb-storage.svg?style=flat-square)](https://packagist.org/packages/detain/oauth2-server-mydb-storage)

A [`MyDb\Generic`](https://packagist.org/packages/detain/db_abstraction)–backed storage adapter for
[`thephpleague/oauth2-server` **4.x**](https://github.com/thephpleague/oauth2-server/tree/4.1.7).

It implements the six storage interfaces from the league's 4.x API
(`AccessTokenInterface`, `AuthCodeInterface`, `ClientInterface`,
`RefreshTokenInterface`, `ScopeInterface`, `SessionInterface`) so that an
existing MyDb-driven application can plug an OAuth 2.0 server straight into
its current MySQL connection without going through PDO directly.

> **Compatibility note** &mdash; this package targets the 4.x line of
> `league/oauth2-server`. Versions 5.x and later from the league were a
> ground-up rewrite using a different namespace layout
> (`Entities\…RepositoryInterface` rather than `Entity\…Interface`). If you
> need a v8-era adapter, look elsewhere or open a PR.

## Requirements

| Component             | Version          |
| --------------------- | ---------------- |
| PHP                   | 7.0 or newer     |
| `ext-pdo`             | enabled          |
| `league/oauth2-server`| `^4.1`           |
| `detain/db_abstraction` | any             |
| PHPUnit (dev)         | `^9.0`           |

## Installation

```bash
composer require detain/oauth2-server-mydb-storage
```

## Schema

Apply [`sql/oauth2.sql`](sql/oauth2.sql) to create the ten tables the
adapters expect:

| Table                          | Purpose                                              |
| ------------------------------ | ---------------------------------------------------- |
| `oauth_clients`                | Registered OAuth2 client applications                |
| `oauth_client_redirect_uris`   | Allowed redirect URIs per client                     |
| `oauth_scopes`                 | Available scopes (e.g. `basic`, `email`)             |
| `oauth_sessions`               | Active sessions linking owner ↔ client               |
| `oauth_session_scopes`         | Scopes granted to each session                       |
| `oauth_access_tokens`          | Issued bearer access tokens                          |
| `oauth_access_token_scopes`    | Scopes attached to each access token                 |
| `oauth_auth_codes`             | Pending authorization codes                          |
| `oauth_auth_code_scopes`       | Scopes attached to each auth code                    |
| `oauth_refresh_tokens`         | Issued refresh tokens                                |

The schema FK-references the host project's `accounts` table for
`oauth_clients.account_id` and `oauth_sessions.account_id`. If you do not
have an `accounts` table, edit those constraints out before applying.

## Usage

```php
use Detain\OAuth2\Server\Repository\MyDb\AccessTokenRepository;
use Detain\OAuth2\Server\Repository\MyDb\AuthCodeRepository;
use Detain\OAuth2\Server\Repository\MyDb\ClientRepository;
use Detain\OAuth2\Server\Repository\MyDb\RefreshTokenRepository;
use Detain\OAuth2\Server\Repository\MyDb\ScopeRepository;
use Detain\OAuth2\Server\Repository\MyDb\SessionRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use MyDb\Mysqli\Db;

$db = new Db('mydb', 'myuser', 'mypassword');

// Resource Server (token introspection / protected APIs)
$resourceServer = new ResourceServer(
    new SessionRepository($db),
    new AccessTokenRepository($db),
    new ClientRepository($db),
    new ScopeRepository($db)
);

// Authorization Server (token issuance / grants)
$authorizationServer = new AuthorizationServer();
$authorizationServer->setSessionStorage(new SessionRepository($db));
$authorizationServer->setAccessTokenStorage(new AccessTokenRepository($db));
$authorizationServer->setClientStorage(new ClientRepository($db));
$authorizationServer->setScopeStorage(new ScopeRepository($db));
$authorizationServer->setAuthCodeStorage(new AuthCodeRepository($db));
$authorizationServer->setRefreshTokenStorage(new RefreshTokenRepository($db));
```

> **Heads-up** &mdash; the league's 4.x API uses **`set*Storage`** /
> **`get*Storage`** rather than the `set*Repository` naming used in 5.x and
> later. The class names in this package retain the `…Repository` suffix
> for historical reasons but the league interfaces they implement are
> `League\OAuth2\Server\Storage\*Interface`.

## Database query pattern

Repositories use the MyDb string-escape pattern, never PDO prepared
statements:

```php
// SELECT
$this->db->query('SELECT * FROM oauth_access_tokens WHERE access_token = "'.$this->db->real_escape($token).'"');
if ($this->db->num_rows() === 1) {
    $this->db->next_record(MYSQLI_ASSOC);
    $row = $this->db->Record;
}

// INSERT (helper preserves NULL semantics for nullable columns)
$values = [
    $this->sqlValue($token),
    $this->sqlValue($expireTime),
    $this->sqlValue($sessionId),
];
$this->db->query('INSERT INTO oauth_access_tokens (access_token, expire_time, session_id) VALUES ('.implode(',', $values).')');

// DELETE
$this->db->query('DELETE FROM oauth_access_tokens WHERE access_token = "'.$this->db->real_escape($token->getId()).'"');
```

Always:

* Pass user input through `$this->db->real_escape()` (or `$this->sqlValue()`
  for nullable values) — never interpolate raw `$_GET` / `$_POST`.
* Check `$this->db->num_rows() === 1` before calling
  `next_record(MYSQLI_ASSOC)`.
* Read columns through `$this->db->Record['column_name']`.
* Loop multi-row results with `while ($this->db->next_record(MYSQLI_ASSOC))`.

## Conventions

* All `src/` classes extend [`src/Repository.php`](src/Repository.php) and
  implement the matching `League\OAuth2\Server\Storage\*Interface`.
* Hydrate entities via
  `(new FooEntity($this->server))->hydrate(['id' => …, 'description' => …])`
  or with the `setId()` / `setExpireTime()` / `setRedirectUri()` setters.
* Return `null` (not `false`) when a record is not found.
* Test files in `tests/` are named `{Name}RepositoryTest.php` and extend
  `MyDbTest` (defined in `tests/bootstrap.php`).
* Cover, at minimum: `get()` success + failure, `create()`, `delete()`,
  `getScopes()`, `associateScope()`.
* Seed test data with `$this->db->exec("INSERT INTO …")` inside each test
  method.

## Running the test suite

```bash
composer install
./vendor/bin/phpunit
```

or, equivalently:

```bash
composer test
```

The suite ships with an in-memory SQLite stub
(`tests/bootstrap.php#TestDb`) that re-implements just enough of the MyDb
public surface (`query`, `next_record`, `num_rows`, `Record`,
`real_escape`, `qr`, `prepare`, `exec`, `lastInsertId`, ...) to exercise
the repositories without spinning up a real MySQL server.

## Live OAuth flow examples

The examples below assume you have already inserted a client into
`oauth_clients` and given it a redirect URI in `oauth_client_redirect_uris`.

### Client-credentials grant

```bash
curl -X POST 'https://example.com/oauth/server/client_credentials.php/access_token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H 'Accept: 1.0' \
  --data-urlencode 'grant_type=client_credentials' \
  --data-urlencode 'client_id=rocketchat_2773' \
  --data-urlencode 'client_secret=s3cr3t' \
  --data-urlencode 'scope=basic email'
```

### Password grant

```bash
curl -X POST 'https://example.com/oauth/server/password.php/access_token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H 'Accept: 1.0' \
  --data-urlencode 'grant_type=password' \
  --data-urlencode 'client_id=rocketchat_2773' \
  --data-urlencode 'client_secret=s3cr3t' \
  --data-urlencode 'username=alex' \
  --data-urlencode 'password=whisky' \
  --data-urlencode 'scope=basic email'
```

### Refresh-token grant

Replace `{{REFRESH_TOKEN}}` with a refresh token returned by one of the
grants above.

```bash
curl -X POST 'https://example.com/oauth/server/refresh_token.php/access_token' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H 'Accept: 1.0' \
  --data-urlencode 'grant_type=refresh_token' \
  --data-urlencode 'client_id=rocketchat_2773' \
  --data-urlencode 'client_secret=s3cr3t' \
  --data-urlencode 'refresh_token={{REFRESH_TOKEN}}'
```

## License

Released under the [MIT License](LICENSE).

## Origin

This package began as a port of
[`DavidWiesner/oauth2-server-pdo`](https://github.com/DavidWiesner/oauth2-server-pdo)
to the MyDb abstraction used by [InterServer](https://www.interserver.net)'s
internal billing / hosting platform.
