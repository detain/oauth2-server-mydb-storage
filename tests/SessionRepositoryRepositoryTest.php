<?php
/**
 * Tests for {@see \Detain\OAuth2\Server\Repository\MyDb\SessionRepository}.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 */

use Detain\OAuth2\Server\Repository\MyDb\ClientRepository;
use Detain\OAuth2\Server\Repository\MyDb\SessionRepository;
use League\Event\Emitter;
use League\OAuth2\Server\AbstractServer;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Exercises CRUD and scope-association behaviour of the SessionRepository.
 */
class SessionRepositoryTest extends MyDbTest
{
    /**
     * @var SessionRepository
     */
    protected $session;

    /**
     * @var AbstractServer|MockObject
     */
    protected $server;

    /**
     * @var ClientRepository|MockObject
     */
    protected $clientRepository;

    public function testGetByAccessTokenFail()
    {
        $accessToken = new AccessTokenEntity($this->server);
        $accessToken->setId('1234');

        $session = $this->session->getByAccessToken($accessToken);

        $this->assertNull($session);
    }

    public function testGetByAccessToken()
    {
        $this->db->exec("INSERT INTO oauth_sessions VALUES (19, 'user', '3', 'testclient', '/');");
        $this->db->exec("INSERT INTO oauth_access_tokens VALUES ('1234', 19,  DATETIME('NOW', '+1 DAY'));");
        $accessToken = new AccessTokenEntity($this->server);
        $accessToken->setId('1234');

        $session = $this->session->getByAccessToken($accessToken);

        $this->assertEquals(19, $session->getId());
        $this->assertEquals('user', $session->getOwnerType());
        $this->assertEquals(3, $session->getOwnerId());
    }

    public function testGetByAuthCodeFail()
    {
        /** @var AuthCodeEntity $authCode */
        $authCode = (new AuthCodeEntity($this->server))->setId('1234');

        $session = $this->session->getByAuthCode($authCode);

        $this->assertNull($session);
    }

    public function testGetByAuthCode()
    {
        $this->db->exec("INSERT INTO oauth_sessions	VALUES (19, 'user', '3', 'testclient', '/');");
        $this->db->exec("INSERT INTO oauth_auth_codes VALUES ('1234', 19,  DATETIME('NOW', '+1 DAY'), '/');");
        /** @var AuthCodeEntity $authCode */
        $authCode = (new AuthCodeEntity($this->server))->setId('1234');

        $session = $this->session->getByAuthCode($authCode);

        $this->assertEquals(19, $session->getId());
        $this->assertEquals('user', $session->getOwnerType());
        $this->assertEquals(3, $session->getOwnerId());
    }

    public function testCreate()
    {
        $id = $this->session->create('user', '-123', 'myClient', '/myRedirect');

        $this->assertNotNull($id);
        $stmt = $this->db->prepare('SELECT * FROM oauth_sessions WHERE id = ' . $id);
        $stmt->execute();
        $this->assertSame([(int) $id, 'user', '-123', 'myClient', '/myRedirect'], $stmt->fetch(PDO::FETCH_NUM));
    }

    public function testCreateNoRedirect()
    {
        $id = $this->session->create('user', 'myOwner', 'myClient');

        $this->assertNotNull($id);
        $stmt = $this->db->prepare('SELECT * FROM oauth_sessions WHERE id = ' . $id);
        $stmt->execute();
        $this->assertSame([
                'id' => (int) $id,
                'owner_type' => 'user',
                'owner_id' => 'myOwner',
                'client_id' => 'myClient',
                'client_redirect_uri' => null
        ], $stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function testCreateFailClientNull()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/constraint.*(failed|violation)/i');
        $this->session->create('user', 'myOwner', null);
    }

    public function testCreateFailOwnerNull()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/constraint.*(failed|violation)/i');
        $this->session->create('user', null, 'myClient');
    }

    public function testCreateFailTypeNull()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/constraint.*(failed|violation)/i');
        $this->session->create(null, 'myOwner', 'myClient');
    }

    public function testGetScopes()
    {
        $this->db->exec("INSERT INTO oauth_sessions	VALUES (29, 'user', '3', 'testclient', '/');");
        $this->db->exec("INSERT INTO oauth_scopes VALUES ('user.list', 'list users'), ('user.add', 'add user');");
        $this->db->exec("INSERT INTO oauth_session_scopes VALUES (10, 29, 'user.list');");
        $session = (new SessionEntity($this->server))->setId(29);

        $scopes = $this->session->getScopes($session);

        $this->assertEquals(1, count($scopes));
        $this->assertEquals('user.list', $scopes[0]->getId());
        $this->assertEquals('list users', $scopes[0]->getDescription());
    }

    public function testGetScopesEmpty()
    {
        $session = (new SessionEntity($this->server))->setId(29);

        $scopes = $this->session->getScopes($session);

        $this->assertEquals(0, count($scopes));
    }

    public function testAssociateScope()
    {
        $session = (new SessionEntity($this->server))->setId(29);
        /** @var ScopeEntity $scope */
        $scope = (new ScopeEntity($this->server))->hydrate(['id' => 'user.list', 'description' => 'list user']);

        $this->session->associateScope($session, $scope);

        $stmt = $this->db->prepare('SELECT session_id, scope FROM oauth_session_scopes WHERE session_id = 29');
        $stmt->execute();
        $this->assertSame(['29', 'user.list'], $stmt->fetch(PDO::FETCH_NUM));
    }

    /**
     * Boot the in-memory database, instantiate the SUT, and stub the server.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new SessionRepository($this->db);
        $this->server = $this->getMockBuilder(AbstractServer::class)->disableOriginalConstructor()->getMock();
        $this->server->method('getEventEmitter')->willReturn(new Emitter());
        $this->clientRepository = $this->getMockBuilder(ClientRepository::class)->disableOriginalConstructor()->getMock();
        $this->server->method('getClientStorage')->willReturn($this->clientRepository);

        $this->session->setServer($this->server);
    }
}
