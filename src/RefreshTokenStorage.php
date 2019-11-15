<?php

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
		$this->db->query('SELECT * FROM oauth_refresh_tokens WHERE refresh_token = "'.$this->db->real_escape($token).'"');
		if ($this->db->num_rows() === 1) {
			$this->db->next_record(MYSQL_ASSOC);
			$token = new RefreshTokenEntity($this->server);
			$token->setId($this->db->Record['refresh_token']);
			$token->setExpireTime($this->db->Record['expire_time']);
			$token->setAccessTokenId($this->db->Record['access_token']);
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
		$this->db->query('INSERT INTO oauth_refresh_tokens (refresh_token, expire_time, access_token) VALUES ("'.$this->db->real_escape($token).'","'.$this->db->real_escape($expireTime).'","'.$this->db->real_escape($accessToken).'")');
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
		$this->db->query('DELETE FROM oauth_refresh_tokens WHERE refresh_token = "'.$this->db->real_escape($token->getId()).'"');
	}
}
