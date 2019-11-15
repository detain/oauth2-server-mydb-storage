<?php

namespace Detain\OAuth2\Server\Storage\MyDb;


use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AuthCodeInterface;
use PDOException;

class AuthCodeStorage extends Storage implements AuthCodeInterface
{

	/**
	 * Get the auth code
	 *
	 * @param string $code
	 *
	 * @return AuthCodeEntity | null
	 * @throws PDOException
	 */
	public function get($code)
	{
		$this->db->query('SELECT * FROM oauth_auth_codes WHERE auth_code = "'.$this->db->real_escape($code).'"');
		if ($this->db->num_rows() === 1) {
			$this->db->next_record(MYSQL_ASSOC);
			$token = new AuthCodeEntity($this->server);
			$token->setId($this->db->Record['auth_code']);
			$token->setRedirectUri($this->db->Record['client_redirect_uri']);
			$token->setExpireTime($this->db->Record['expire_time']);
			return $token;
		}
		return null;
	}

	/**
	 * Create an auth code.
	 *
	 * @param string $token The token ID
	 * @param integer $expireTime Token expire time
	 * @param integer $sessionId Session identifier
	 * @param string $redirectUri Client redirect uri
	 *
	 * @return void
	 * @throws PDOException
	 */
	public function create($token, $expireTime, $sessionId, $redirectUri)
	{
		$this->db->query('INSERT INTO oauth_auth_codes (auth_code, expire_time, session_id, client_redirect_uri) VALUES ("'.$this->db->real_escape($token).'","'.$this->db->real_escape($expireTime).'","'.$this->db->real_escape($sessionId).'","'.$this->db->real_escape($redirectUri).'")');
	}

	/**
	 * Get the scopes for an access token
	 *
	 * @param AuthCodeEntity $token The auth code
	 *
	 * @return ScopeEntity[] Array of \League\OAuth2\Server\Entity\ScopeEntity
	 * @throws PDOException
	 */
	public function getScopes(AuthCodeEntity $token)
	{
		$results = $this->db->qr('SELECT scope.* FROM oauth_auth_codes as code JOIN oauth_auth_code_scopes AS acs ON(acs.auth_code=code.auth_code) JOIN oauth_scopes as scope ON(scope.id=acs.scope) WHERE code.auth_code = "'.$this->db->real_escape($token->getId()).'"');
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
	 * Associate a scope with an acess token
	 *
	 * @param AuthCodeEntity $token The auth code
	 * @param ScopeEntity $scope The scope
	 *
	 * @return void
	 * @throws PDOException
	 */
	public function associateScope(AuthCodeEntity $token, ScopeEntity $scope)
	{
		$this->db->query('INSERT INTO oauth_auth_code_scopes (auth_code, scope) VALUES ("'.$this->db->real_escape($token->getId()).'","'.$this->db->real_escape($scope->getId()).'")');
	}

	/**
	 * Delete an access token
	 *
	 * @param AuthCodeEntity $token The access token to delete
	 *
	 * @return void
	 */
	public function delete(AuthCodeEntity $token)
	{
		$this->db->query('DELETE FROM oauth_auth_codes WHERE auth_code = "'.$this->db->real_escape($token->getId()).'"');
	}
}
