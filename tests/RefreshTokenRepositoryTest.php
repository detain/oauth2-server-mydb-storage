<?php
/**
 * Tests for {@see \Detain\OAuth2\Server\Repository\MyDb\RefreshTokenRepository}.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 */

use Detain\OAuth2\Server\Repository\MyDb\AccessTokenRepository;
use Detain\OAuth2\Server\Repository\MyDb\RefreshTokenRepository;
use League\OAuth2\Server\AbstractServer;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\RefreshTokenEntity;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Exercises CRUD behaviour of the RefreshTokenRepository.
 */
class RefreshTokenRepositoryTest extends MyDbTest
{
    /**
     * @var RefreshTokenRepository
     */
    protected $token;

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
        $authCode = $this->token->get('unknwon');

        $this->assertNull($authCode);
    }

    public function testGet()
    {
        $time = time() + 60 * 60;
        $this->db->exec('INSERT INTO oauth_access_tokens VALUES ("10Access", 1, ' . $time . ');');
        $this->db->exec('INSERT INTO oauth_refresh_tokens VALUES ("10Refresh", ' . $time . ', "10Access");');

        $authCode = $this->token->get('10Refresh');

        $this->assertNotNull($authCode);
        $this->assertEquals('10Refresh', $authCode->getId());
        $this->assertEquals($time, $authCode->getExpireTime());
        // The entity stores the access-token ID privately; round-trip
        // via reflection so we don't depend on AbstractServer wiring.
        $reflection = new ReflectionProperty($authCode, 'accessTokenId');
        $reflection->setAccessible(true);
        $this->assertEquals('10Access', $reflection->getValue($authCode));
    }

    public function testCreate()
    {
        $time = time() + 60 * 60;
        $this->db->exec('INSERT INTO oauth_access_tokens VALUES ("20Access", 1, ' . $time . ');');
        $this->token->create('20NewToken', $time, '20Access');

        $stmt = $this->db->prepare('SELECT * FROM oauth_refresh_tokens WHERE refresh_token = "20NewToken"');
        $stmt->execute();
        $this->assertSame([
                'refresh_token' => '20NewToken',
                'expire_time' => (string) $time,
                'access_token' => '20Access'
        ], $stmt->fetch(PDO::FETCH_ASSOC));
    }

    public function testCreateFailedNoAccessToken()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/constraint.*(failed|violation)/i');
        $this->token->create('20NewToken', 1024, null);
    }

    public function testCreateFailedNoExpired()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/constraint.*(failed|violation)/i');
        $this->token->create('20NewToken', null, '20AccessToken');
    }

    public function testCreateFailedNoCode()
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/constraint.*(failed|violation)/i');
        $this->token->create(null, 1024, 1);
    }

    public function testDelete()
    {
        $this->db->exec('INSERT INTO oauth_access_tokens VALUES ("10Access", 1, ' . (time() + 60) . ');');
        $this->db->exec('INSERT INTO oauth_refresh_tokens VALUES ("10Refresh", ' . (time() + 60) . ', "10Access");');
        $token = (new RefreshTokenEntity($this->server))->setId('10Refresh');

        $this->token->delete($token);

        $stmt = $this->db->prepare('SELECT * FROM oauth_refresh_tokens WHERE refresh_token = "10Refresh"');
        $stmt->execute();
        $this->assertSame([], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Boot the in-memory database, instantiate the SUT, and stub the server.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->token = new RefreshTokenRepository($this->db);
        $this->server = $this->getMockBuilder(AbstractServer::class)->disableOriginalConstructor()->getMock();
        $this->accessRepository = $this->getMockBuilder(AccessTokenRepository::class)->disableOriginalConstructor()->getMock();
        $this->server->method('getAccessTokenStorage')->willReturn($this->accessRepository);

        $this->token->setServer($this->server);
    }
}
