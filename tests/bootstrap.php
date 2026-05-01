<?php
/**
 * PHPUnit bootstrap file for the MyDb OAuth2 storage adapter test suite.
 *
 * Wires up Composer's autoloader, defines a `TestDb` shim that exposes both
 * the PDO surface used by the tests (`exec`, `prepare`, `setAttribute`,
 * `lastInsertId`, ...) and the `MyDb\Generic` surface used by the production
 * repositories (`query`, `next_record`, `num_rows`, `real_escape`, `qr`,
 * `Record`), and provides a `MyDbTest` base class that loads a SQLite
 * schema for each test method.
 *
 * The SQLite schema mirrors `sql/oauth2.sql` but is rewritten in
 * SQLite-compatible DDL so that the suite can run entirely in memory.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 */

require __DIR__ . '/../vendor/autoload.php';

if (!defined('MYSQLI_ASSOC')) {
    define('MYSQLI_ASSOC', 1);
}
if (!defined('MYSQL_ASSOC')) {
    define('MYSQL_ASSOC', MYSQLI_ASSOC);
}

/**
 * Lightweight in-memory `MyDb\Generic`-compatible test double.
 *
 * Wraps a real PDO connection (SQLite in-memory) and re-exposes the subset
 * of the MyDb API that the repositories under test rely on. Tests can also
 * call PDO methods directly (`exec`, `prepare`, ...) to seed fixtures.
 */
class TestDb extends \MyDb\Generic
{
    /**
     * Underlying PDO link.
     *
     * @var \PDO
     */
    public $linkId;

    /**
     * Last executed PDO statement, used by `next_record()` and `num_rows()`.
     *
     * @var \PDOStatement|false
     */
    public $queryId = false;

    /**
     * Materialised result set from the most recent `query()` call.
     *
     * @var array
     */
    protected $rows = [];

    /**
     * Current 0-based cursor position into `$rows`.
     *
     * @var int
     */
    protected $cursor = -1;

    /**
     * Build a new TestDb.
     *
     * @param string $dsn      A PDO DSN (defaults to in-memory SQLite).
     * @param string $user     PDO username (unused for SQLite).
     * @param string $password PDO password (unused for SQLite).
     */
    public function __construct($dsn = 'sqlite::memory:', $user = '', $password = '')
    {
        $this->linkId = new \PDO($dsn, $user, $password);
        $this->linkId->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // SQLite defaults FK enforcement OFF — matching MySQL's behaviour
        // when these repositories run in production (the application
        // performs its own ordering / cleanup) and letting the failure
        // tests focus on NOT NULL violations rather than FK ordering.
    }

    /**
     * Pass-through to PDO::setAttribute.
     *
     * @param int   $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function setAttribute($attribute, $value)
    {
        return $this->linkId->setAttribute($attribute, $value);
    }

    /**
     * Pass-through to PDO::getAttribute.
     *
     * @param int $attribute
     *
     * @return mixed
     */
    public function getAttribute($attribute)
    {
        return $this->linkId->getAttribute($attribute);
    }

    /**
     * Pass-through to PDO::exec.
     *
     * @param string $sql
     *
     * @return int|false
     */
    public function exec($sql)
    {
        return $this->linkId->exec($sql);
    }

    /**
     * Pass-through to PDO::prepare.
     *
     * @param string $sql
     * @param string $line  unused, kept for MyDb signature compatibility
     * @param string $file  unused, kept for MyDb signature compatibility
     *
     * @return \PDOStatement|false
     */
    public function prepare($sql, $line = '', $file = '')
    {
        return $this->linkId->prepare($sql);
    }

    /**
     * Pass-through to PDO::lastInsertId.
     *
     * @param string|null $name
     *
     * @return string
     */
    public function lastInsertId($name = null)
    {
        return $this->linkId->lastInsertId($name);
    }

    /**
     * Run a SELECT/INSERT/UPDATE/DELETE and capture its result set.
     *
     * @param string $sql
     * @param string $line unused, kept for MyDb signature compatibility
     * @param string $file unused, kept for MyDb signature compatibility
     *
     * @return \PDOStatement
     */
    public function query($sql, $line = '', $file = '')
    {
        $this->queryId = $this->linkId->prepare($sql);
        $this->queryId->execute();
        $this->rows = $this->queryId->fetchAll(\PDO::FETCH_ASSOC);
        $this->cursor = -1;
        $this->Record = [];
        return $this->queryId;
    }

    /**
     * Run a SELECT and return all rows as an associative array.
     *
     * @param string $sql
     * @param string $line unused
     * @param string $file unused
     *
     * @return array
     */
    public function qr($sql, $line = '', $file = '')
    {
        $stmt = $this->linkId->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Number of rows in the most recent query() result.
     *
     * @return int
     */
    public function num_rows()
    {
        return count($this->rows);
    }

    /**
     * Advance to the next row, populating `$this->Record`.
     *
     * @param int $resultType ignored — we always fetch associative.
     *
     * @return bool true if a row was loaded, false at end-of-set.
     */
    public function next_record($resultType = MYSQLI_ASSOC)
    {
        $this->cursor++;
        if (isset($this->rows[$this->cursor])) {
            $this->Record = $this->rows[$this->cursor];
            return true;
        }
        $this->Record = [];
        return false;
    }

    /**
     * Quote a string for safe inclusion in a SQL literal.
     *
     * @param string $string
     *
     * @return string
     */
    public function real_escape($string = '')
    {
        if ($string === null) {
            return '';
        }
        $quoted = $this->linkId->quote((string) $string);
        // PDO::quote wraps in single quotes; strip them to match MyDb semantics.
        if (strlen($quoted) >= 2 && $quoted[0] === "'" && substr($quoted, -1) === "'") {
            return substr($quoted, 1, -1);
        }
        return $quoted;
    }
}

/**
 * Base class for every repository test case.
 *
 * Boots a fresh in-memory SQLite database for each test, applies an
 * SQLite-compatible mirror of `sql/oauth2.sql`, and exposes the
 * connection as `$this->db`.
 */
abstract class MyDbTest extends \PHPUnit\Framework\TestCase
{
    /**
     * SQLite-compatible schema mirroring `sql/oauth2.sql`.
     *
     * Differences from production schema:
     *  - INTEGER PRIMARY KEY AUTOINCREMENT instead of `int unsigned AUTO_INCREMENT`
     *  - No CHARSET / COLLATE / ENGINE / COMMENT clauses
     *  - No KEY/INDEX clauses (created with CREATE INDEX where needed)
     *  - No FK to host project tables (`accounts`)
     *
     * @var string
     */
    private static $sql = <<<'SQL'
CREATE TABLE oauth_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_type VARCHAR(255) NOT NULL,
    owner_id VARCHAR(255) NOT NULL,
    client_id VARCHAR(255) NOT NULL,
    client_redirect_uri VARCHAR(255) DEFAULT NULL
);

CREATE TABLE oauth_clients (
    id VARCHAR(255) NOT NULL PRIMARY KEY,
    secret VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL
);

CREATE TABLE oauth_client_redirect_uris (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id VARCHAR(255) NOT NULL,
    redirect_uri VARCHAR(255) NOT NULL
);

CREATE TABLE oauth_scopes (
    id VARCHAR(255) NOT NULL PRIMARY KEY,
    description VARCHAR(255) NOT NULL
);

CREATE TABLE oauth_access_tokens (
    access_token VARCHAR(255) NOT NULL PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    expire_time VARCHAR(255) NOT NULL,
    FOREIGN KEY (session_id) REFERENCES oauth_sessions (id) ON DELETE CASCADE
);

CREATE TABLE oauth_access_token_scopes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    access_token VARCHAR(255) NOT NULL,
    scope VARCHAR(255) NOT NULL,
    FOREIGN KEY (access_token) REFERENCES oauth_access_tokens (access_token) ON DELETE CASCADE,
    FOREIGN KEY (scope) REFERENCES oauth_scopes (id) ON DELETE CASCADE
);

CREATE TABLE oauth_refresh_tokens (
    refresh_token VARCHAR(255) NOT NULL PRIMARY KEY,
    expire_time VARCHAR(255) NOT NULL,
    access_token VARCHAR(255) NOT NULL,
    FOREIGN KEY (access_token) REFERENCES oauth_access_tokens (access_token) ON DELETE CASCADE
);

CREATE TABLE oauth_auth_codes (
    auth_code VARCHAR(255) NOT NULL PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    expire_time VARCHAR(255) NOT NULL,
    client_redirect_uri VARCHAR(255) NOT NULL,
    FOREIGN KEY (session_id) REFERENCES oauth_sessions (id) ON DELETE CASCADE
);

CREATE TABLE oauth_auth_code_scopes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    auth_code VARCHAR(255) NOT NULL,
    scope VARCHAR(255) NOT NULL,
    FOREIGN KEY (auth_code) REFERENCES oauth_auth_codes (auth_code) ON DELETE CASCADE,
    FOREIGN KEY (scope) REFERENCES oauth_scopes (id) ON DELETE CASCADE
);

CREATE TABLE oauth_session_scopes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id VARCHAR(255) NOT NULL,
    scope VARCHAR(255) NOT NULL,
    FOREIGN KEY (session_id) REFERENCES oauth_sessions (id) ON DELETE CASCADE,
    FOREIGN KEY (scope) REFERENCES oauth_scopes (id) ON DELETE CASCADE
);
SQL;

    /**
     * Test database connection.
     *
     * @var TestDb
     */
    protected $db;

    /**
     * Spin up a fresh in-memory database before every test method.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new TestDb('sqlite::memory:', '', '');
        $this->setupTable();
    }

    /**
     * Apply the cached schema to the current test database.
     */
    protected function setupTable()
    {
        $this->db->exec(self::$sql);
    }
}

/**
 * Marker subclass kept for backwards compatibility with tests that
 * previously extended `ServerTest`.
 */
abstract class ServerTest extends MyDbTest
{
    /**
     * Inherit MyDbTest::setUp.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }
}
