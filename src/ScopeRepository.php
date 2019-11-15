<?php

namespace Detain\OAuth2\Server\Repository\MyDb;


use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Repository\ScopeInterface;

class ScopeRepository extends Repository implements ScopeInterface
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
		$this->db->query('SELECT * FROM oauth_scopes WHERE id = "'.$this->db->real_escape($scope).'"');
		if ($this->db->num_rows() === 1) {
			$this->db->next_record(MYSQL_ASSOC);
			$scope = new ScopeEntity($this->server);
			$scope->hydrate($this->db->Record);
			return $scope;
		}
		return null;
	}
}
