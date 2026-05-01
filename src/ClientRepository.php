<?php
/**
 * Client Repository for the MyDb storage adapter.
 *
 * Implements the `ClientInterface` from `league/oauth2-server` against the
 * `oauth_clients` and `oauth_client_redirect_uris` MySQL tables.
 *
 * @author    Joe Huss <detain@interserver.net>
 * @copyright 2020 Interserver, Inc.
 * @license   MIT
 * @link      https://github.com/detain/oauth2-server-mydb-storage
 * @package   Detain\OAuth2\Server\Repository\MyDb
 */

namespace Detain\OAuth2\Server\Repository\MyDb;

use League\OAuth2\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Storage\ClientInterface;

/**
 * Validates OAuth2 client credentials and looks up clients by session.
 */
class ClientRepository extends Repository implements ClientInterface
{
    /**
     * Validate a client by ID and (optionally) secret and redirect URI.
     *
     * If `$clientSecret` and/or `$redirectUri` are supplied they must match
     * the stored values for the client to be returned.
     *
     * @param string      $clientId     The client's ID.
     * @param string|null $clientSecret The client's secret (optional).
     * @param string|null $redirectUri  The client's redirect URI (optional).
     * @param string|null $grantType    The grant type used (optional, currently unused).
     *
     * @return ClientEntity|null The hydrated entity, or `null` if validation fails.
     */
    public function get($clientId, $clientSecret = null, $redirectUri = null, $grantType = null)
    {
        $sql = 'SELECT clients.* FROM oauth_clients as clients ';
        $where = [];
        $where[] = 'WHERE clients.id = "'.$this->db->real_escape($clientId).'" ';
        if ($clientSecret != null) {
            $where[] = ' clients.secret = "'.$this->db->real_escape($clientSecret).'" ';
        }
        if ($redirectUri != null) {
            $sql .= ' LEFT JOIN oauth_client_redirect_uris as redirect ON (redirect.client_id = clients.id) ';
            $where[] = ' redirect.redirect_uri = "'.$this->db->real_escape($redirectUri).'" ';
        }
        $this->db->query($sql . implode(' AND ', $where));
        if ($this->db->num_rows() === 1) {
            $this->db->next_record(MYSQLI_ASSOC);
            $client = new ClientEntity($this->getServer());
            $client->hydrate($this->db->Record);
            return $client;
        }

        return null;
    }

    /**
     * Get the client associated with a session.
     *
     * @param SessionEntity $session The session entity.
     *
     * @return ClientEntity|null The hydrated entity, or `null` if no match.
     */
    public function getBySession(SessionEntity $session)
    {
        $this->db->query('SELECT client.id, client.name FROM oauth_clients as client LEFT JOIN oauth_sessions as sess  ON(sess.client_id = client.id) WHERE sess.id = "'.$this->db->real_escape($session->getId()).'"');
        if ($this->db->num_rows() === 1) {
            $this->db->next_record(MYSQLI_ASSOC);
            $client = new ClientEntity($this->getServer());
            $client->hydrate([
                    'id' => $this->db->Record['id'],
                    'name' => $this->db->Record['name']
            ]);
            return $client;
        }
        return null;
    }
}
