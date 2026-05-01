<?php
/**
 * Access Token Repository for the MyDb storage adapter.
 *
 * Implements the `AccessTokenInterface` from `league/oauth2-server` against
 * the `oauth_access_tokens` and `oauth_access_token_scopes` MySQL tables.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 * @package   Detain\OAuth2\Server\Repository\MyDb
 */

namespace Detain\OAuth2\Server\Repository\MyDb;

use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AccessTokenInterface;
use PDOException;

/**
 * Persists OAuth2 access tokens and their scope associations in MyDb.
 */
class AccessTokenRepository extends Repository implements AccessTokenInterface
{
    /**
     * Look up an access token by its ID.
     *
     * @param string $token The access token string.
     *
     * @return AccessTokenEntity|null The hydrated entity, or `null` if no match.
     */
    public function get($token)
    {
        $this->db->query('SELECT * FROM oauth_access_tokens WHERE access_token = "'.$this->db->real_escape($token).'"');
        if ($this->db->num_rows() === 1) {
            $this->db->next_record(MYSQLI_ASSOC);
            $entity = new AccessTokenEntity($this->server);
            $entity->setId($this->db->Record['access_token']);
            $entity->setExpireTime($this->db->Record['expire_time']);
            return $entity;
        }
        return null;
    }

    /**
     * Get all scopes attached to a given access token.
     *
     * @param AccessTokenEntity $token The access token entity.
     *
     * @return ScopeEntity[] Array of `\League\OAuth2\Server\Entity\ScopeEntity`.
     *
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
     * Create a new access token row.
     *
     * @param string         $token      The access token string.
     * @param int            $expireTime Expiry as a unix timestamp.
     * @param string|int     $sessionId  The owning session's ID.
     *
     * @return void
     */
    public function create($token, $expireTime, $sessionId)
    {
        $values = [
            $this->sqlValue($token),
            $this->sqlValue($expireTime),
            $this->sqlValue($sessionId),
        ];
        $this->db->query('INSERT INTO oauth_access_tokens (access_token, expire_time, session_id) VALUES ('.implode(',', $values).')');
    }

    /**
     * Associate a scope with an access token.
     *
     * @param AccessTokenEntity $token The access token entity.
     * @param ScopeEntity       $scope The scope entity to associate.
     *
     * @return void
     */
    public function associateScope(AccessTokenEntity $token, ScopeEntity $scope)
    {
        $this->db->query('INSERT INTO oauth_access_token_scopes (access_token, scope) VALUES ("'.$this->db->real_escape($token->getId()).'","'.$this->db->real_escape($scope->getId()).'")');
    }

    /**
     * Delete an access token row.
     *
     * @param AccessTokenEntity $token The access token entity to delete.
     *
     * @return void
     */
    public function delete(AccessTokenEntity $token)
    {
        $this->db->query('DELETE FROM oauth_access_tokens WHERE access_token = "'.$this->db->real_escape($token->getId()).'"');
    }
}
