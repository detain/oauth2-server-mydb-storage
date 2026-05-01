<?php
/**
 * Refresh Token Repository for the MyDb storage adapter.
 *
 * Implements the `RefreshTokenInterface` from `league/oauth2-server` against
 * the `oauth_refresh_tokens` MySQL table.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 * @package   Detain\OAuth2\Server\Repository\MyDb
 */

namespace Detain\OAuth2\Server\Repository\MyDb;

use League\OAuth2\Server\Entity\RefreshTokenEntity;
use League\OAuth2\Server\Storage\RefreshTokenInterface;

/**
 * Persists OAuth2 refresh tokens in MyDb.
 */
class RefreshTokenRepository extends Repository implements RefreshTokenInterface
{
    /**
     * Look up a refresh token by its ID.
     *
     * @param string $token The refresh token string.
     *
     * @return RefreshTokenEntity|null The hydrated entity, or `null` if no match.
     */
    public function get($token)
    {
        $this->db->query('SELECT * FROM oauth_refresh_tokens WHERE refresh_token = "'.$this->db->real_escape($token).'"');
        if ($this->db->num_rows() === 1) {
            $this->db->next_record(MYSQLI_ASSOC);
            $entity = new RefreshTokenEntity($this->server);
            $entity->setId($this->db->Record['refresh_token']);
            $entity->setExpireTime($this->db->Record['expire_time']);
            $entity->setAccessTokenId($this->db->Record['access_token']);
            return $entity;
        }
        return null;
    }

    /**
     * Create a new refresh token row.
     *
     * @param string $token       The refresh token string.
     * @param int    $expireTime  Expiry as a unix timestamp.
     * @param string $accessToken ID of the linked access token.
     *
     * @return RefreshTokenEntity The hydrated entity for the newly created row.
     */
    public function create($token, $expireTime, $accessToken)
    {
        $values = [
            $this->sqlValue($token),
            $this->sqlValue($expireTime),
            $this->sqlValue($accessToken),
        ];
        $this->db->query('INSERT INTO oauth_refresh_tokens (refresh_token, expire_time, access_token) VALUES ('.implode(',', $values).')');
        $entity = new RefreshTokenEntity($this->server);
        $entity->setId($token);
        $entity->setExpireTime($expireTime);
        $entity->setAccessTokenId($accessToken);
        return $entity;
    }

    /**
     * Delete a refresh token row.
     *
     * @param RefreshTokenEntity $token The refresh token entity to delete.
     *
     * @return void
     */
    public function delete(RefreshTokenEntity $token)
    {
        $this->db->query('DELETE FROM oauth_refresh_tokens WHERE refresh_token = "'.$this->db->real_escape($token->getId()).'"');
    }
}
