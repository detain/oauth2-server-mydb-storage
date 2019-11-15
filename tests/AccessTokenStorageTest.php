<?php
use Detain\OAuth2\Server\Storage\MyDb\AccessTokenStorage;
use League\OAuth2\Server\AbstractServer;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;

/**
 * Created by IntelliJ IDEA.
 * User: david
 * Date: 16.03.16
 * Time: 20:44
 */
class AccessTokenStorageTest extends MyDbTest
{
	/**
	 * @var AccessTokenStorage
	 */
	protected $accessToken;
	/**
	 * @var AbstractServer
	 */
	protected $server;
	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $accessStorage;

	public function testGetFailed()
	{
		$authCode = $this->accessToken->get('unknwon');

		$this->assertNull($authCode);
	}

	public function testGet()
	{
		$time = time() + 60 * 60;
		$this->db->exec("INSERT INTO oauth_access_tokens VALUES ('10authCode', 1, " . $time . ');');
		$scope = new ScopeEntity($this->server);

		$token = $this->accessToken->get('10authCode');

		$this->accessStorage->expects($this->once())->method('getScopes')->with($token)->willReturn([''=>$scope]);
		$this->assertNotNull($token);
		$this->assertEquals('10authCode', $token->getId());
		$this->assertEquals($time, $token->getExpireTime());
		$this->assertEquals($scope, $token->getScopes()['']);
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

	/**
	 * @expectedException PDOException
	 * @expectedExceptionMessageRegExp '.*constraint (failed|violation):.*session_id'
	 */
	public function testCreateFailedNoSession()
	{
		$this->accessToken->create('20NewToken', 1024, null);
	}

	/**
	 * @expectedException PDOException
	 * @expectedExceptionMessageRegExp '.*constraint (failed|violation):.*expire_time'
	 */
	public function testCreateFailedNoExpired()
	{
		$this->accessToken->create('20NewToken', null, 1);
	}

	/**
	 * @expectedException PDOException
	 * @expectedExceptionMessageRegExp '.*constraint (failed|violation):.*access_token'
	 */
	public function testCreateFailedNoCode()
	{
		$this->accessToken->create(null, 1024, 1);
	}


	public function testGetScopes()
	{
		$this->db->exec("INSERT INTO oauth_access_tokens
						VALUES ('10authCode', 1, DATETIME('NOW', '+1 DAY'));
						INSERT INTO oauth_scopes VALUES ('user.list', 'list users'), ('user.add', 'add user');
						INSERT INTO oauth_access_token_scopes VALUES (10, '10authCode', 'user.list');");
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
		$this->db->exec("INSERT INTO oauth_access_tokens
					VALUES ('10authCode', 1, DATETIME('NOW', '+1 DAY'));");
		$token = (new AccessTokenEntity($this->server))->setId('10authCode');

		$this->accessToken->delete($token);

		$stmt = $this->db->prepare("SELECT * FROM oauth_access_tokens WHERE access_token = '10authCode'");
		$stmt->execute();
		$this->assertSame([], $stmt->fetchAll(PDO::FETCH_ASSOC));
	}


	protected function setUp()
	{
		parent::setUp();
		$this->accessToken = new AccessTokenStorage($this->db);
		$this->server = $this->getMock(AbstractServer::class);
		$this->accessStorage = $this->getMockBuilder(AccessTokenStorage::class)->disableOriginalConstructor()->getMock();
		$this->server->method('getAccessTokenStorage')->willReturn($this->accessStorage);

		$this->accessToken->setServer($this->server);
	}


}
