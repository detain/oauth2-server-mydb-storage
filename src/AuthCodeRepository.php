<?php
/**
 * Authorization Code Repository for the MyDb storage adapter.
 *
 * Implements the `AuthCodeInterface` from `league/oauth2-server` against the
 * `oauth_auth_codes` and `oauth_auth_code_scopes` MySQL tables.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 * @package   Detain\OAuth2\Server\Repository\MyDb
 */

namespace Detain\OAuth2\Server\Repository\MyDb;

use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AuthCodeInterface;
use PDOException;

/**
 * Persists OAuth2 authorization codes and their scope associations in MyDb.
 */
class AuthCodeRepository extends Repository implements AuthCodeInterface
{
    /**
     * Look up an authorization code by its ID.
     *
     * @param string $code The auth code string.
     *
     * @return AuthCodeEntity|null The hydrated entity, or `null` if no match.
     *
     * @throws PDOException
     */
    public function get($code)
    {
        $this->db->query('SELECT * FROM oauth_auth_codes WHERE auth_code = "'.$this->db->real_escape($code).'"');
        if ($this->db->num_rows() === 1) {
            $this->db->next_record(MYSQLI_ASSOC);
            $entity = new AuthCodeEntity($this->server);
            $entity->setId($this->db->Record['auth_code']);
            $entity->setRedirectUri($this->db->Record['client_redirect_uri']);
            $entity->setExpireTime($this->db->Record['expire_time']);
            return $entity;
        }
        return null;
    }

    /**
     * Create a new authorization code row.
     *
     * @param string $token       The auth code string.
     * @param int    $expireTime  Expiry as a unix timestamp.
     * @param int    $sessionId   Owning session ID.
     * @param string $redirectUri Client redirect URI.
     *
     * @return void
     *
     * @throws PDOException
     */
    public function create($token, $expireTime, $sessionId, $redirectUri)
    {
        $values = [
            $this->sqlValue($token),
            $this->sqlValue($expireTime),
            $this->sqlValue($sessionId),
            $this->sqlValue($redirectUri),
        ];
        $this->db->query('INSERT INTO oauth_auth_codes (auth_code, expire_time, session_id, client_redirect_uri) VALUES ('.implode(',', $values).')');
    }

    /**
     * Get all scopes attached to a given authorization code.
     *
     * @param AuthCodeEntity $token The auth code entity.
     *
     * @return ScopeEntity[] Array of `\League\OAuth2\Server\Entity\ScopeEntity`.
     *
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
     * Associate a scope with an authorization code.
     *
     * @param AuthCodeEntity $token The auth code entity.
     * @param ScopeEntity    $scope The scope entity to associate.
     *
     * @return void
     *
     * @throws PDOException
     */
    public function associateScope(AuthCodeEntity $token, ScopeEntity $scope)
    {
        $this->db->query('INSERT INTO oauth_auth_code_scopes (auth_code, scope) VALUES ("'.$this->db->real_escape($token->getId()).'","'.$this->db->real_escape($scope->getId()).'")');
    }

    /**
     * Delete an authorization code row.
     *
     * @param AuthCodeEntity $token The auth code entity to delete.
     *
     * @return void
     */
    public function delete(AuthCodeEntity $token)
    {
        $this->db->query('DELETE FROM oauth_auth_codes WHERE auth_code = "'.$this->db->real_escape($token->getId()).'"');
    }
}
