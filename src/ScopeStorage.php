<?php

namespace Detain\OAuth2\Server\Storage\MyDb;


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
		if ($this->db->num_rows() === 1) {
			$this->db->next_record(MYSQL_ASSOC);
			$scope = new ScopeEntity($this->server);
			$scope->hydrate($this->db->Record);
			return $scope;
		}
		return null;
	}
}
