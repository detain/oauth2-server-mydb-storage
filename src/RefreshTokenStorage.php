<?php
/**
 * Created by IntelliJ IDEA.
 * User: david
 * Date: 16.03.16
 * Time: 21:04
 */

namespace Detain\OAuth2\Server\Storage\MyDb;


use League\OAuth2\Server\Entity\RefreshTokenEntity;
use League\OAuth2\Server\Storage\RefreshTokenInterface;

class RefreshTokenStorage extends Storage implements RefreshTokenInterface
{

	/**
	 * Return a new instance of \League\OAuth2\Server\Entity\RefreshTokenEntity
	 *
	 * @param string $token
	 *
	 * @return \League\OAuth2\Server\Entity\RefreshTokenEntity | null
	 */
	public function get($token)
	{
		$result = $this->run('SELECT * FROM oauth_refresh_tokens WHERE refresh_token = ?', [$token]);
		if (count($result) === 1) {
			$token = new RefreshTokenEntity($this->server);
			$token->setId($result[0]['refresh_token']);
			$token->setExpireTime($result[0]['expire_time']);
			$token->setAccessTokenId($result[0]['access_token']);
			return $token;
		}
		return null;
	}

	/**
	 * Create a new refresh token_name
	 *
	 * @param string $token
	 * @param integer $expireTime
	 * @param string $accessToken
	 *
	 * @return \League\OAuth2\Server\Entity\RefreshTokenEntity
	 */
	public function create($token, $expireTime, $accessToken)
	{
		$this->run('INSERT INTO oauth_refresh_tokens (refresh_token, expire_time, access_token)
							VALUES (?,?,?)', [$token, $expireTime, $accessToken]);
		$token = new RefreshTokenEntity($this->server);
		$token->setId($token);
		$token->setExpireTime($expireTime);
		$token->setAccessTokenId($accessToken);
		return $token;
	}

	/**
	 * Delete the refresh token
	 *
	 * @param \League\OAuth2\Server\Entity\RefreshTokenEntity $token
	 *
	 * @return void
	 */
	public function delete(RefreshTokenEntity $token)
	{
		$this->run('DELETE FROM oauth_refresh_tokens WHERE refresh_token = ?', [$token->getId()]);
	}
}
