<?php
use Detain\OAuth2\Server\Storage\PDO\ClientStorage;
use League\OAuth2\Server\AbstractServer;
use League\OAuth2\Server\Entity\SessionEntity;

/**
 * Created by IntelliJ IDEA.
 * User: david
 * Date: 16.03.16
 * Time: 18:11
 */
class ClientStorageTest extends PDOTest
{
	/**
	 * @var ClientStorage
	 */
	protected $client;
	/**
	 * @var AbstractServer
	 */
	protected $server;

	public function testGetClientId()
	{
		$client = $this->client->get('10Client');
		$this->assertNotNull($client);
		$this->assertEquals('10Client', $client->getId());
		$this->assertEquals('10Name', $client->getName());
	}

	public function testGetClientSecretWrong()
	{
		$client = $this->client->get('10Client', '11Secret');
		$this->assertNull($client);
	}

	public function testGetClientSecret()
	{
		$client = $this->client->get('10Client', '10Secret');
		$this->assertNotNull($client);
		$this->assertEquals('10Client', $client->getId());
		$this->assertEquals('10Name', $client->getName());
	}

	public function testGetClientRedirectWrong()
	{
		$client = $this->client->get('10Client', '10Secret', '/11Redirect');
		$this->assertNull($client);
	}

	public function testGetClientRedirect()
	{
		$client = $this->client->get('11Client', '11Secret', '/11Redirect');
		$this->assertNotNull($client);
		$this->assertEquals('11Client', $client->getId());
		$this->assertEquals('11Name', $client->getName());
		$this->assertEquals('11Secret', $client->getSecret());
	}

	public function testGetBySessionFailed()
	{
		$this->db->exec('INSERT INTO oauth_sessions
						VALUES (100,"10Client","client", "10Owner", NULL )');
		$session = (new SessionEntity($this->server))->setId(29);

		$client = $this->client->getBySession($session);

		$this->assertNull($client);
	}

	public function testGetBySession()
	{
		$exec = $this->db->exec('INSERT INTO oauth_sessions
						VALUES (100, "client", "10Owner", "10Client", NULL )');
		$session = (new SessionEntity($this->server))->setId(100);

		$client = $this->client->getBySession($session);

		$this->assertEquals(1, $exec);
		$this->assertNotNull($client);
		$this->assertEquals('10Client', $client->getId());
		$this->assertEquals('10Name', $client->getName());
	}


	protected function setUp()
	{
		parent::setUp();
		$this->client = new ClientStorage($this->db);
		$this->server = $this->getMock(AbstractServer::class);

		$this->client->setServer($this->server);
		$this->db->exec('INSERT INTO oauth_clients
						VALUES ("10Client","10Secret","10Name"), ("11Client","11Secret","11Name")');
		$this->db->exec('INSERT INTO oauth_client_redirect_uris VALUES (20, "11Client", "/11Redirect")');
	}


}
