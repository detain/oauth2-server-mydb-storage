<?php
/**
 * Tests for {@see \Detain\OAuth2\Server\Repository\MyDb\AccessTokenRepository}.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 */

use Detain\OAuth2\Server\Repository\MyDb\AccessTokenRepository;
use League\OAuth2\Server\AbstractServer;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Exercises CRUD and scope-association behaviour of the AccessTokenRepository.
 */
class AccessTokenRepositoryTest extends MyDbTest
{
    /**
     * @var AccessTokenRepository
     */
    protected $accessToken;

    /**
     * @var AbstractServer|MockObject
     */
    protected $server;

    /**
     * @var AccessTokenRepository|MockObject
     */
    protected $accessRepository;

    public function testGetFailed()
    {
        $authCode = $this->accessToken->get('unknwon');

        $this->assertNull($authCode);
    }

    public function testGet()
    {
        $time = time() + 60 * 60;
        $this->db->exec("INSERT INTO oauth_access_tokens VALUES ('10authCode', 1, " . $time . ');');

        $token = $this->accessToken->get('10authCode');

        $this->assertNotNull($token);
        $this->assertEquals('10authCode', $token->getId());
        $this->assertEquals($time, $token->getExpireTime());
    }

    public function testCreate()
    {
        $this->accessToken->create('20NewToken', 1024, 1);

        $stmt = $this->db->prepare("SELECT * FROM oauth_access_tokens WHERE access_token = '20NewToken'");
        $stmt->execute();
        $this->assertSame([
                'access_token' => '20NewToken',
                'session_id' => '1',
                'expire_time' => '1024',
        ], $stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function testCreateFailedNoSession()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/constraint.*(failed|violation)/i');
        $this->accessToken->create('20NewToken', 1024, null);
    }

    public function testCreateFailedNoExpired()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/constraint.*(failed|violation)/i');
        $this->accessToken->create('20NewToken', null, 1);
    }

    public function testCreateFailedNoCode()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/constraint.*(failed|violation)/i');
        $this->accessToken->create(null, 1024, 1);
    }

    public function testGetScopes()
    {
        $this->db->exec("INSERT INTO oauth_access_tokens VALUES ('10authCode', 1, DATETIME('NOW', '+1 DAY'));");
        $this->db->exec("INSERT INTO oauth_scopes VALUES ('user.list', 'list users'), ('user.add', 'add user');");
        $this->db->exec("INSERT INTO oauth_access_token_scopes VALUES (10, '10authCode', 'user.list');");
        $token = (new AccessTokenEntity($this->server))->setId('10authCode');

        $scopes = $this->accessToken->getScopes($token);

        $this->assertEquals(1, count($scopes));
        $this->assertEquals('user.list', $scopes[0]->getId());
        $this->assertEquals('list users', $scopes[0]->getDescription());
    }

    public function testGetScopesEmpty()
    {
        $token = (new AccessTokenEntity($this->server))->setId('10authCode');

        $scopes = $this->accessToken->getScopes($token);

        $this->assertEquals(0, count($scopes));
    }

    public function testAssociateScope()
    {
        $token = (new AccessTokenEntity($this->server))->setId('10authCode');
        /** @var ScopeEntity $scope */
        $scope = (new ScopeEntity($this->server))->hydrate(['id' => 'user.list', 'description' => 'list user']);

        $this->accessToken->associateScope($token, $scope);

        $stmt = $this->db->prepare("SELECT access_token, scope FROM oauth_access_token_scopes WHERE access_token = '10authCode'");
        $stmt->execute();
        $this->assertSame(['10authCode', 'user.list'], $stmt->fetch(PDO::FETCH_NUM));
    }

    public function testDelete()
    {
        $this->db->exec("INSERT INTO oauth_access_tokens VALUES ('10authCode', 1, DATETIME('NOW', '+1 DAY'));");
        $token = (new AccessTokenEntity($this->server))->setId('10authCode');

        $this->accessToken->delete($token);

        $stmt = $this->db->prepare("SELECT * FROM oauth_access_tokens WHERE access_token = '10authCode'");
        $stmt->execute();
        $this->assertSame([], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Boot the in-memory database, instantiate the SUT, and stub the server.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->accessToken = new AccessTokenRepository($this->db);
        $this->server = $this->getMockBuilder(AbstractServer::class)->disableOriginalConstructor()->getMock();
        $this->accessRepository = $this->getMockBuilder(AccessTokenRepository::class)->disableOriginalConstructor()->getMock();
        $this->server->method('getAccessTokenStorage')->willReturn($this->accessRepository);

        $this->accessToken->setServer($this->server);
    }
}
