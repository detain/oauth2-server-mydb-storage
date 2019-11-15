<?php
/**
 * Created by IntelliJ IDEA.
 * User: david
 * Date: 16.03.16
 * Time: 22:57
 */

namespace Detain\OAuth2\Server\Storage\PDO;


use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\ScopeInterface;

class ScopeStorage extends Storage implements ScopeInterface
{

	/**
	 * Return information about a scope
	 *
	 * @param string $scope The scope
	 * @param string $grantType The grant type used in the request (default = "null")
	 * @param string $clientId The client sending the request (default = "null")
	 *
	 * @return ScopeEntity | null
	 */
	public function get($scope, $grantType = null, $clientId = null)
	{
		$result = $this->run('SELECT * FROM oauth_scopes WHERE id = ?', [$scope]);
		if (count($result) === 1) {
			$scope = new ScopeEntity($this->server);
			$scope->hydrate($result[0]);
			return $scope;
		}
		return null;
	}
}
