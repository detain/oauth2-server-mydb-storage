<?php
/**
 * Created by IntelliJ IDEA.
 * User: david
 * Date: 16.03.16
 * Time: 10:44
 */
require __DIR__ . '/../vendor/autoload.php';

class PDOTest extends PHPUnit_Framework_TestCase
{
	private static $sql;
	/**
	 * @var PDO
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
		$this->db = new PDO('sqlite::memory:', '', '');
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->setupTable();
	}

	protected function setupTable()
	{
		$this->db->exec(self::$sql);
	}
}

class ServerTest extends PDOTest{
	protected function setUp()
	{
		parent::setUp();
	}

}
