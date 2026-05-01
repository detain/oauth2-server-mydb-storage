<?php
/**
 * Base Repository for the MyDb storage adapter.
 *
 * Holds the shared `$db` handle and provides a convenience `run()` method that
 * wraps prepared-statement execution against the underlying MyDb driver.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 * @package   Detain\OAuth2\Server\Repository\MyDb
 */

namespace Detain\OAuth2\Server\Repository\MyDb;

use League\OAuth2\Server\Storage\AbstractStorage;
use MyDb\Generic;
use PDO;
use PDOException;

/**
 * Base class extended by every concrete MyDb-backed repository.
 *
 * This class wires the `MyDb\Generic` database handle, detects whether the
 * underlying driver supports `INSERT ... RETURNING` (PostgreSQL), and exposes
 * a small `run()` helper for executing prepared statements with optional
 * automatic exception throwing.
 */
class Repository extends AbstractStorage
{
    /**
     * Database connection handle.
     *
     * @var Generic
     */
    protected $db;

    /**
     * True when the underlying driver supports the `RETURNING` clause
     * (currently only PostgreSQL).
     *
     * @var bool
     */
    protected $supportsReturning;

    /**
     * Repository constructor.
     *
     * @param Generic $db An initialised `MyDb\Generic` (or subclass) database handle.
     */
    public function __construct(Generic $db)
    {
        $this->db = $db;
        $this->supportsReturning = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
    }

    /**
     * Quote a value for direct embedding inside a VALUES (...) list.
     *
     * Returns the literal `NULL` for `null` inputs so that the database can
     * enforce its own NOT NULL / nullable column contracts; otherwise wraps
     * the result of `real_escape()` in double quotes.
     *
     * @param mixed $value
     *
     * @return string The SQL fragment ready for splicing into a query.
     */
    protected function sqlValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        return '"' . $this->db->real_escape($value) . '"';
    }

    /**
     * Prepare and execute a SQL statement on the underlying PDO connection.
     *
     * For SELECT / DESCRIBE / PRAGMA statements, the full result set is
     * returned as an associative array. For DELETE / INSERT / UPDATE / REPLACE
     * the row count is returned. For everything else the executed
     * `PDOStatement` is returned, unless `$returnStatement` is true in which
     * case the statement is always returned.
     *
     * @param string $sql             A valid SQL statement for the target database server.
     * @param array  $bind            Optional array of values bound to the prepared parameters.
     * @param bool   $shouldThrow     When true (default) a `PDOException` is thrown if prepare/execute fails.
     * @param bool   $returnStatement When true the executed `PDOStatement` is always returned.
     *
     * @return array|false|int|\PDOStatement
     *                     - associative array for SELECT/DESCRIBE/PRAGMA
     *                     - row count for DELETE/INSERT/UPDATE/REPLACE
     *                     - `PDOStatement` otherwise
     *                     - `false` if execution failed and `$shouldThrow` is false
     *
     * @throws PDOException When `$shouldThrow` is true and prepare or execute fails.
     */
    public function run($sql, $bind = [], $shouldThrow = true, $returnStatement = false)
    {
        $sql = trim($sql);
        $statement = $this->db->prepare($sql);
        if ($statement !== false && ($statement->execute($bind) !== false)) {
            if ($returnStatement) {
                return $statement;
            } elseif (preg_match('/^(select|describe|pragma) /i', $sql)) {
                return $statement->fetchAll(PDO::FETCH_ASSOC);
            } elseif (preg_match('/^(delete|insert|update|replace) /i', $sql)) {
                return $statement->rowCount();
            } else {
                return $statement;
            }
        }
        if ($shouldThrow) {
            throw new PDOException($this->db->errorCode() . ' ' . ($statement === false ? 'prepare' : 'execute') . ' failed');
        }
        return false;
    }
}
