<?php

use Detain\OAuth2\Server\Repository\MyDb\ScopeRepository;
use League\OAuth2\Server\AbstractServer;

/**
 * Created by IntelliJ IDEA.
 * User: david
 * Date: 16.03.16
 * Time: 23:00
 */
class ScopeRepositoryTest extends MyDbTest
{
    /**
     * @var ScopeRepository
     */
    protected $scope;
    /**
     * @var AbstractServer
     */
    protected $server;

    public function testGetFailed()
    {
        $authCode = $this->scope->get('unknwon');

        $this->assertNull($authCode);
    }

    public function testGet()
    {
        $this->db->exec('INSERT INTO oauth_scopes VALUES ("user.list", "list users"), ("user.add", "add user");');

        $scope = $this->scope->get('user.list');

        $this->assertNotNull($scope);
        $this->assertEquals('user.list', $scope->getId());
        $this->assertEquals('list users', $scope->getDescription());
    }


    protected function setUp()
    {
        parent::setUp();
        $this->scope = new ScopeRepository($this->db);
        $this->server = $this->getMock(AbstractServer::class);

        $this->scope->setServer($this->server);
    }
}
