<?php
/**
 * Tests for {@see \Detain\OAuth2\Server\Repository\MyDb\ClientRepository}.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 */

use Detain\OAuth2\Server\Repository\MyDb\ClientRepository;
use League\OAuth2\Server\AbstractServer;
use League\OAuth2\Server\Entity\SessionEntity;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Exercises lookup behaviour of the ClientRepository, including secret /
 * redirect-URI validation and session-based fetches.
 */
class ClientRepositoryTest extends MyDbTest
{
    /**
     * @var ClientRepository
     */
    protected $client;

    /**
     * @var AbstractServer|MockObject
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
        $this->db->exec('INSERT INTO oauth_sessions VALUES (100, "client", "10Owner", "10Client", NULL )');
        $session = (new SessionEntity($this->server))->setId(29);

        $client = $this->client->getBySession($session);

        $this->assertNull($client);
    }

    public function testGetBySession()
    {
        $exec = $this->db->exec('INSERT INTO oauth_sessions VALUES (100, "client", "10Owner", "10Client", NULL )');
        $session = (new SessionEntity($this->server))->setId(100);

        $client = $this->client->getBySession($session);

        $this->assertEquals(1, $exec);
        $this->assertNotNull($client);
        $this->assertEquals('10Client', $client->getId());
        $this->assertEquals('10Name', $client->getName());
    }

    /**
     * Seed two clients and a redirect URI before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new ClientRepository($this->db);
        $this->server = $this->getMockBuilder(AbstractServer::class)->disableOriginalConstructor()->getMock();

        $this->client->setServer($this->server);
        $this->db->exec('INSERT INTO oauth_clients (id, secret, name) VALUES ("10Client","10Secret","10Name"), ("11Client","11Secret","11Name")');
        $this->db->exec('INSERT INTO oauth_client_redirect_uris (id, client_id, redirect_uri) VALUES (20, "11Client", "/11Redirect")');
    }
}
