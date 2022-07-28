<?php

require __DIR__ . '/../vendor/autoload.php';

class MyDbTest extends PHPUnit_Framework_TestCase
{
    private static $sql;
    /**
     * @var MyAdb\Generic
     */
    protected $db;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$sql = file_get_contents(__DIR__ . '/../sql/oauth2.sql');
    }

    protected function setUp()
    {
        parent::setUp();
        $this->db = new MyDb\Generic('sqlite::memory:', '', '');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setupTable();
    }

    protected function setupTable()
    {
        $this->db->exec(self::$sql);
    }
}

class ServerTest extends MyDbTest
{
    protected function setUp()
    {
        parent::setUp();
    }
}
