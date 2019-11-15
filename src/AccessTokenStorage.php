<?php
/**
 * Created by IntelliJ IDEA.
 * User: david
 * Date: 16.03.16
 * Time: 20:43
 */

namespace Detain\OAuth2\Server\Storage\PDO;


use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AccessTokenInterface;
use PDOException;

class AccessTokenStorage extends Storage implements AccessTokenInterface
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
		$result = $this->run('SELECT * FROM oauth_access_tokens WHERE access_token = ?',[$token]);
		if (count($result) === 1) {
			$token = new AccessTokenEntity($this->server);
			$token->setId($result[0]['access_token']);
			$token->setExpireTime($result[0]['expire_time']);
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
		$results = $this->run('SELECT scope.* FROM oauth_access_tokens as token
							 JOIN oauth_access_token_scopes AS acs ON(acs.access_token=token.access_token)
							 JOIN oauth_scopes as scope ON(scope.id=acs.scope)
							 WHERE token.access_token = :token',
				[':token'=> $token->getId()]);
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
		$this->run('INSERT INTO oauth_access_tokens (access_token, expire_time, session_id)
							VALUES (?,?,?)', [$token, $expireTime, $sessionId]);
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
		$this->run('INSERT INTO oauth_access_token_scopes (access_token, scope) VALUES (?,?)'
				,[$token->getId(), $scope->getId()]);
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
		$this->run('DELETE FROM oauth_access_tokens WHERE access_token = :token',
				[ ':token'=>$token->getId()]);
	}
}
