<?php
use Detain\OAuth2\Server\Repository\MyDb\AccessTokenRepository;
use Detain\OAuth2\Server\Repository\MyDb\RefreshTokenRepository;
use League\OAuth2\Server\AbstractServer;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\RefreshTokenEntity;

/**
 * Created by IntelliJ IDEA.
 * User: david
 * Date: 16.03.16
 * Time: 21:05
 */
class RefreshTokenRepositoryTest extends MyDbTest
{
	/**
	 * @var RefreshTokenRepository
	 */
	protected $token;
	/**
	 * @var AbstractServer
	 */
	protected $server;
	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
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
		$this->db->exec('INSERT INTO oauth_refresh_tokens VALUES ("10Refresh", ' . $time . ', "10Access");');
		$accessToken = new AccessTokenEntity($this->server);
		$this->accessRepository->expects($this->once())->method('get')->with('10Access')->willReturn($accessToken);

		$authCode = $this->token->get('10Refresh');

		$this->assertNotNull($authCode);
		$this->assertEquals('10Refresh', $authCode->getId());
		$this->assertEquals($time, $authCode->getExpireTime());
		$this->assertEquals($accessToken, $authCode->getAccessToken());
	}

	public function testCreate()
	{
		$time = time() + 60 * 60;
		$this->token->create('20NewToken', $time, "20Access");

		$stmt = $this->db->prepare('SELECT * FROM oauth_refresh_tokens WHERE refresh_token = "20NewToken"');
		$stmt->execute();
		$this->assertSame([
				'refresh_token' => '20NewToken',
				'expire_time' => (string) $time,
				'access_token' => '20Access'
		], $stmt->fetch(PDO::FETCH_ASSOC));
	}

	/**
	 * @expectedException PDOException
	 * @expectedExceptionMessageRegExp '.*constraint (failed|violation):.*access_token'
	 */
	public function testCreateFailedNoAccessToken()
	{
		$this->token->create('20NewToken', 1024, null);
	}

	/**
	 * @expectedException PDOException
	 * @expectedExceptionMessageRegExp '.*constraint (failed|violation):.*expire_time'
	 */
	public function testCreateFailedNoExpired()
	{
		$this->token->create('20NewToken', null, "20AccessToken");
	}

	/**
	 * @expectedException PDOException
	 * @expectedExceptionMessageRegExp '.*constraint (failed|violation):.*refresh_token'
	 */
	public function testCreateFailedNoCode()
	{
		$this->token->create(null, 1024, 1);
	}

	public function testDelete()
	{
		$this->db->exec('INSERT INTO oauth_refresh_tokens
					VALUES ("10Refresh", DATETIME("NOW", "+1 DAY"), "10Access");');
		$token = (new RefreshTokenEntity($this->server))->setId('10Refresh');

		$this->token->delete($token);

		$stmt = $this->db->prepare('SELECT * FROM oauth_refresh_tokens WHERE refresh_token = "10Refresh"');
		$stmt->execute();
		$this->assertSame([], $stmt->fetchAll(PDO::FETCH_ASSOC));
	}

	protected function setUp()
	{
		parent::setUp();
		$this->token = new RefreshTokenRepository($this->db);
		$this->server = $this->getMock(AbstractServer::class);
		$this->accessRepository = $this->getMockBuilder(AccessTokenRepository::class)->disableOriginalConstructor()->getMock();
		$this->server->method('getAccessTokenRepository')->willReturn($this->accessRepository);

		$this->token->setServer($this->server);
	}

}
