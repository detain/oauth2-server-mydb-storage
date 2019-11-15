<?php

namespace Detain\OAuth2\Server\Repository\MyDb;


use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Repository\SessionInterface;

class SessionRepository extends Repository implements SessionInterface
{
	/**
	 * Get a session from an access token
	 *
	 * @param AccessTokenEntity $accessToken The access token
	 *
	 * @return SessionEntity | null
	 */
	public function getByAccessToken(AccessTokenEntity $accessToken)
	{
		$this->db->query('SELECT id, owner_type, owner_id, client_id, client_redirect_uri FROM oauth_sessions as oauth_sessions JOIN oauth_access_tokens as tokens ON(tokens.session_id = id) WHERE tokens.access_token = "'.$this->db->real_escape($accessToken->getId()).'"');
		if ($this->db->num_rows() === 1) {
			$this->db->next_record(MYSQL_ASSOC);
			$session = new SessionEntity($this->getServer());
			$session->setId($this->db->Record['id']);
			$session->setOwner($this->db->Record['owner_type'], $this->db->Record['owner_id']);
			return $session;
		}
		return null;
	}

	/**
	 * Get a session from an auth code
	 *
	 * @param AuthCodeEntity $authCode The auth code
	 *
	 * @return SessionEntity | null
	 */
	public function getByAuthCode(AuthCodeEntity $authCode)
	{
		$this->db->query('SELECT id, owner_type, owner_id FROM oauth_sessions as s JOIN oauth_auth_codes as codes ON(codes.session_id = id) WHERE codes.auth_code = "'.$this->db->real_escape($authCode->getId()).'"');
		if ($this->db->num_rows() === 1) {
			$this->db->next_record(MYSQL_ASSOC);
			$session = new SessionEntity($this->getServer());
			$session->setId($this->db->Record['id']);
			$session->setOwner($this->db->Record['owner_type'], $this->db->Record['owner_id']);
			return $session;
		}
		return null;
	}

	/**
	 * Create a new session
	 *
	 * @param string $ownerId SessionRepository owner's ID
	 * @param string $clientId Client ID
	 * @param string $clientRedirectUri Client redirect URI (default = null)
	 *
	 * @return integer The session's ID
	 */
	public function create($ownerId, $clientId, $clientRedirectUri = null)
	{
		if ($this->supportsReturning) {
			$stmt = $this->db->query(/** @lang PostgreSQL */
				'INSERT INTO oauth_sessions (owner_type, owner_id, client_id, client_redirect_uri) VALUES (?,?,?,?) RETURNING id', [$ownerId, $clientId, $clientRedirectUri], true, true);
			return $stmt->fetchColumn();
		} else {
			$this->db->query('INSERT INTO oauth_sessions (owner_id, client_id, client_redirect_uri) VALUES ("'.$this->db->real_esacape($ownerId).'","'.$this->db->real_esacape($clientId).'","'.$this->db->real_esacape($clientRedirectUri).'")');
			return $this->db->lastInsertId();
		}
	}

	/**
	 * Get a session's scopes
	 *
	 * @param SessionEntity $session
	 *
	 * @return ScopeEntity[] Array of \League\OAuth2\Server\Entity\ScopeEntity
	 */
	public function getScopes(SessionEntity $session)
	{
		$this->db->query('SELECT scope.* FROM oauth_sessions as sess JOIN oauth_session_scopes as ss ON(ss.session_id=sess.id) JOIN oauth_scopes as scope ON(scope.id=ss.scope) WHERE sess.id = "'.$this->db->real_escape($session->getId()).'"');
		$scopes = [];
		while($this->db->next_record(MYSQL_ASSOC)) {
			$scopes[] = (new ScopeEntity($this->server))->hydrate([
				'id' => $this->db->Record['id'],
				'description' => $this->db->Record['description'],
			]);
		}
		return $scopes;
	}

	/**
	 * Associate a scope with a session
	 *
	 * @param SessionEntity $session The session
	 * @param ScopeEntity $scope The scope
	 *
	 * @return void
	 */
	public function associateScope(SessionEntity $session, ScopeEntity $scope)
	{
		$this->db->query('INSERT INTO oauth_session_scopes (session_id, scope) VALUES ("'.$this->db->real_esacape($session->getId()).'","'.$this->db->real_esacape($scope->getId()).'")');
	}
}
