<?php

namespace Detain\OAuth2\Server\Repository\MyDb;

use League\OAuth2\Server\Repository\AbstractRepository;
use MyDb\Generic;
use PDOException;

class Repository extends AbstractRepository
{
    /**
     * @var MyDb\Generic
     */
    protected $db;
    /**
     * @var bool
     */
    protected $supportsReturning;


    /**
     * Repository constructor.
     * @param MyDb\Generic $db
     */
    public function __construct(MyDb\Generic $db)
    {
        $this->db = $db;
        $this->supportsReturning = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
    }

    /**
     * prepare and execute sql statement on the pdo. Run MyDb\Generic::fetchAll on select, describe or pragma statements
     *
     * @param string $sql This must be a valid SQL statement for the target database server.
     * @param array $bind [optional]
     *                     An array of values with as many elements as there are bound parameters in the SQL statement
     *                     being executed
     * @param bool $shouldThrow if throw MyDb\GenericException if prepare or execute failed otherwise return false (default true )
     * @param bool $returnStatement if true always return \PDOStatement
     * @return array|false|int|\PDOStatement <ul>
     *                     <li> associative array of results if sql statement is select, describe or pragma
     *                     <li> the number of rows affected by a delete, insert, update or replace statement
     *                     <li> the executed PDOStatement otherwise</ul>
     *                     <li> false only if execution failed and the PDO::ERRMODE_EXCEPTION was unset</ul>
     * @see MyDb\Generic::execute
     * @see MyDb\Generic::prepare
     */
    public function run($sql, $bind = [], $shouldThrow = true, $returnStatement = false)
    {
        $sql = trim($sql);
        $statement = $this->db->prepare($sql);
        if ($statement !== false and ($statement->execute($bind) !== false)) {
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
