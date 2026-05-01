<?php
/**
 * Scope Repository for the MyDb storage adapter.
 *
 * Implements the `ScopeInterface` from `league/oauth2-server` against the
 * `oauth_scopes` MySQL table.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 * @package   Detain\OAuth2\Server\Repository\MyDb
 */

namespace Detain\OAuth2\Server\Repository\MyDb;

use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\ScopeInterface;

/**
 * Looks up OAuth2 scopes from MyDb.
 */
class ScopeRepository extends Repository implements ScopeInterface
{
    /**
     * Return information about a scope.
     *
     * @param string      $scope     The scope name.
     * @param string|null $grantType The grant type used in the request (optional, currently unused).
     * @param string|null $clientId  The client sending the request (optional, currently unused).
     *
     * @return ScopeEntity|null The hydrated entity, or `null` if no match.
     */
    public function get($scope, $grantType = null, $clientId = null)
    {
        $this->db->query('SELECT * FROM oauth_scopes WHERE id = "'.$this->db->real_escape($scope).'"');
        if ($this->db->num_rows() === 1) {
            $this->db->next_record(MYSQLI_ASSOC);
            $entity = new ScopeEntity($this->server);
            $entity->hydrate($this->db->Record);
            return $entity;
        }
        return null;
    }
}
