<?php
/**
 * Session Repository for the MyDb storage adapter.
 *
 * Implements the `SessionInterface` from `league/oauth2-server` against the
 * `oauth_sessions` and `oauth_session_scopes` MySQL tables.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 * @package   Detain\OAuth2\Server\Repository\MyDb
 */

namespace Detain\OAuth2\Server\Repository\MyDb;

use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Storage\SessionInterface;

/**
 * Persists OAuth2 sessions and their scope associations in MyDb.
 */
class SessionRepository extends Repository implements SessionInterface
{
    /**
     * Get a session from an access token.
     *
     * @param AccessTokenEntity $accessToken The access token entity.
     *
     * @return SessionEntity|null The hydrated session entity, or `null` if no match.
     */
    public function getByAccessToken(AccessTokenEntity $accessToken)
    {
        $this->db->query('SELECT id, owner_type, owner_id, client_id, client_redirect_uri FROM oauth_sessions as oauth_sessions JOIN oauth_access_tokens as tokens ON(tokens.session_id = id) WHERE tokens.access_token = "'.$this->db->real_escape($accessToken->getId()).'"');
        if ($this->db->num_rows() === 1) {
            $this->db->next_record(MYSQLI_ASSOC);
            $session = new SessionEntity($this->getServer());
            $session->setId($this->db->Record['id']);
            $session->setOwner($this->db->Record['owner_type'], $this->db->Record['owner_id']);
            return $session;
        }
        return null;
    }

    /**
     * Get a session from an auth code.
     *
     * @param AuthCodeEntity $authCode The auth code entity.
     *
     * @return SessionEntity|null The hydrated session entity, or `null` if no match.
     */
    public function getByAuthCode(AuthCodeEntity $authCode)
    {
        $this->db->query('SELECT id, owner_type, owner_id FROM oauth_sessions as s JOIN oauth_auth_codes as codes ON(codes.session_id = id) WHERE codes.auth_code = "'.$this->db->real_escape($authCode->getId()).'"');
        if ($this->db->num_rows() === 1) {
            $this->db->next_record(MYSQLI_ASSOC);
            $session = new SessionEntity($this->getServer());
            $session->setId($this->db->Record['id']);
            $session->setOwner($this->db->Record['owner_type'], $this->db->Record['owner_id']);
            return $session;
        }
        return null;
    }

    /**
     * Create a new session row.
     *
     * On PostgreSQL the new row's `id` is read back via `RETURNING`. On every
     * other supported driver `lastInsertId()` is used.
     *
     * @param string      $ownerType         Session owner type (e.g. `user`, `client`).
     * @param string      $ownerId           Session owner's ID.
     * @param string      $clientId          Client ID.
     * @param string|null $clientRedirectUri Client redirect URI (optional).
     *
     * @return int The newly inserted session's ID.
     */
    public function create($ownerType, $ownerId, $clientId, $clientRedirectUri = null)
    {
        if ($this->supportsReturning) {
            $stmt = $this->db->query(/** @lang PostgreSQL */
                'INSERT INTO oauth_sessions (owner_type, owner_id, client_id, client_redirect_uri) VALUES (?,?,?,?) RETURNING id',
                [$ownerType, $ownerId, $clientId, $clientRedirectUri],
                true,
                true
            );
            return $stmt->fetchColumn();
        } else {
            // Preserve NULL semantics for caller-supplied nulls so the
            // database can enforce its own NOT NULL / nullable column
            // contracts rather than silently coercing to empty strings.
            $values = [
                $this->sqlValue($ownerType),
                $this->sqlValue($ownerId),
                $this->sqlValue($clientId),
                $this->sqlValue($clientRedirectUri),
            ];
            $this->db->query('INSERT INTO oauth_sessions (owner_type, owner_id, client_id, client_redirect_uri) VALUES ('.implode(',', $values).')');
            return $this->db->lastInsertId();
        }
    }

    /**
     * Get a session's scopes.
     *
     * @param SessionEntity $session The session entity.
     *
     * @return ScopeEntity[] Array of `\League\OAuth2\Server\Entity\ScopeEntity`.
     */
    public function getScopes(SessionEntity $session)
    {
        $this->db->query('SELECT scope.* FROM oauth_sessions as sess JOIN oauth_session_scopes as ss ON(ss.session_id=sess.id) JOIN oauth_scopes as scope ON(scope.id=ss.scope) WHERE sess.id = "'.$this->db->real_escape($session->getId()).'"');
        $scopes = [];
        while ($this->db->next_record(MYSQLI_ASSOC)) {
            $scopes[] = (new ScopeEntity($this->server))->hydrate([
                'id' => $this->db->Record['id'],
                'description' => $this->db->Record['description'],
            ]);
        }
        return $scopes;
    }

    /**
     * Associate a scope with a session.
     *
     * @param SessionEntity $session The session entity.
     * @param ScopeEntity   $scope   The scope entity to associate.
     *
     * @return void
     */
    public function associateScope(SessionEntity $session, ScopeEntity $scope)
    {
        $this->db->query('INSERT INTO oauth_session_scopes (session_id, scope) VALUES ("'.$this->db->real_escape($session->getId()).'","'.$this->db->real_escape($scope->getId()).'")');
    }
}
