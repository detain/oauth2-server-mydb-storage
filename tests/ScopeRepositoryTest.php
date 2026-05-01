<?php
/**
 * Tests for {@see \Detain\OAuth2\Server\Repository\MyDb\ScopeRepository}.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 */

use Detain\OAuth2\Server\Repository\MyDb\ScopeRepository;
use League\OAuth2\Server\AbstractServer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Exercises lookup behaviour of the ScopeRepository.
 */
class ScopeRepositoryTest extends MyDbTest
{
    /**
     * @var ScopeRepository
     */
    protected $scope;

    /**
     * @var AbstractServer|MockObject
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

    /**
     * Boot the in-memory database and instantiate the SUT.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->scope = new ScopeRepository($this->db);
        $this->server = $this->getMockBuilder(AbstractServer::class)->disableOriginalConstructor()->getMock();

        $this->scope->setServer($this->server);
    }
}
