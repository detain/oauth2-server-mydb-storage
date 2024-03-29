<?php

namespace Detain\OAuth2\Server\Repository\MyDb;

use League\OAuth2\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Repository\ClientInterface;

class ClientRepository extends Repository implements ClientInterface
{
    /**
     * Validate a client
     *
     * @param string $clientId The client's ID
     * @param string $clientSecret The client's secret (default = "null")
     * @param string $redirectUri The client's redirect URI (default = "null")
     * @param string $grantType The grant type used (default = "null")
     *
     * @return ClientEntity | null
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
            $this->db->next_record(MYSQL_ASSOC);
            $client = new ClientEntity($this->getServer());
            $client->hydrate($this->db->Record);
            return $client;
        }

        return null;
    }

    /**
     * Get the client associated with a session
     *
     * @param SessionEntity $session The session
     *
     * @return ClientEntity | null
     */
    public function getBySession(SessionEntity $session)
    {
        $this->db->query('SELECT client.id, client.name FROM oauth_clients as client LEFT JOIN oauth_sessions as sess  ON(sess.client_id = client.id) WHERE sess.id = "'.$this->db->real_escape($session->getId()).'"');
        if ($this->db->num_rows() === 1) {
            $this->db->next_record(MYSQL_ASSOC);
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
