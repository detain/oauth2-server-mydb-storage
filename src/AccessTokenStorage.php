<?php

namespace Detain\OAuth2\Server\Repository\MyDb;


use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Repository\AccessTokenInterface;
use PDOException;

class AccessTokenRepository extends Repository implements AccessTokenInterface
{

	/**
	 * Get an instance of Entity\AccessTokenEntity
	 *
	 * @param string $token The access token
	 *
	 * @return AccessTokenEntity | null
	 */
	public function get($token)
	{
		$this->db->query('SELECT * FROM oauth_access_tokens WHERE access_token = "'.$this->db->real_escape($token).'"');
		if ($this->db->num_rows() === 1) {
			$this->db->next_record(MYSQL_ASSOC);
			$token = new AccessTokenEntity($this->server);
			$token->setId($this->db->Record['access_token']);
			$token->setExpireTime($this->db->Record['expire_time']);
			return $token;
		}
		return null;
	}

	/**
	 * Get the scopes for an access token
	 *
	 * @param AccessTokenEntity $token The access token
	 *
	 * @return ScopeEntity[] Array of \League\OAuth2\Server\Entity\ScopeEntity
	 * @throws PDOException
	 */
	public function getScopes(AccessTokenEntity $token)
	{
		$results = $this->db->qr('SELECT scope.* FROM oauth_access_tokens as token JOIN oauth_access_token_scopes AS acs ON(acs.access_token=token.access_token) JOIN oauth_scopes as scope ON(scope.id=acs.scope) WHERE token.access_token = "'.$this->db->real_escape($token->getId()).'"');
		$scopes = [];
		foreach ($results as $scope) {
			$scopes[] = (new ScopeEntity($this->server))->hydrate([
					'id' => $scope['id'],
					'description' => $scope['description'],
			]);
		}
		return $scopes;

	}

	/**
	 * Creates a new access token
	 *
	 * @param string $token The access token
	 * @param integer $expireTime The expire time expressed as a unix timestamp
	 * @param string|integer $sessionId The session ID
	 *
	 * @return void
	 */
	public function create($token, $expireTime, $sessionId)
	{
		$this->db->query('INSERT INTO oauth_access_tokens (access_token, expire_time, session_id) VALUES ("'.$this->db->real_escape($token).'","'.$this->db->real_escape($expireTime).'","'.$this->db->real_escape($sessionId).'")');
	}

	/**
	 * Associate a scope with an acess token
	 *
	 * @param AccessTokenEntity $token The access token
	 * @param ScopeEntity $scope The scope
	 *
	 * @return void
	 */
	public function associateScope(AccessTokenEntity $token, ScopeEntity $scope)
	{
		$this->db->query('INSERT INTO oauth_access_token_scopes (access_token, scope) VALUES ("'.$this->db->real_escape($token->getId()).'","'.$this->db->real_escape($scope->getId()).'")');
	}

	/**
	 * Delete an access token
	 *
	 * @param AccessTokenEntity $token The access token to delete
	 *
	 * @return void
	 */
	public function delete(AccessTokenEntity $token)
	{
		$this->db->query('DELETE FROM oauth_access_tokens WHERE access_token = "'.$this->db->real_escape($token->getId()).'"');
	}
}
