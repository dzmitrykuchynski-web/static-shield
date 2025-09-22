<?php
namespace StaticShield;

/**
 * Client for interacting with the Static Shield Cloudflare Worker.
 *
 * Provides helper methods for making authenticated requests
 * to the Worker API (DNS list/add/delete).
 *
 * @package StaticShield
 */
class StaticShieldWorkerClient {

    /**
     * @var string|null $workerUrl Worker endpoint base URL.
     */
    private $workerUrl;

    /**
     * @var string|null $authToken Cloudflare API Token used for authorization.
     */
    private $authToken;

    /**
     * Constructor.
     *
     * Initializes Worker URL and API Token from WordPress options.
     */
    public function __construct() {
        $this->workerUrl = get_option('static_shield_cf_worker');
        $this->authToken = get_option('static_shield_cf_api_key');
    }

    /**
     * Send HTTP request to the Worker API.
     *
     * @param string     $endpoint API endpoint relative to worker base URL.
     * @param string     $method   HTTP method (GET, POST, DELETE).
     * @param array|null $body     Optional request body for POST/PUT requests.
     *
     * @return array|\WP_Error Decoded JSON response on success, or WP_Error on failure.
     */
    private function request($endpoint, $method = 'GET', $body = null) {
        if (empty($this->workerUrl)) {
            return new \WP_Error('missing_worker_url', 'Worker URL not configured');
        }

        $args = [
            'method'  => $method,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->authToken,
            ],
            'timeout' => 20,
        ];

        if ($body) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request(
            trailingslashit($this->workerUrl) . ltrim($endpoint, '/'),
            $args
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300) {
            return $data;
        }

        return new \WP_Error('worker_error', 'Worker returned error', $data);
    }

    /**
     * Retrieve DNS records from Worker API.
     *
     * @return array|\WP_Error List of DNS records or WP_Error on failure.
     */
    public function listDnsRecords() {
        return $this->request('dns/list', 'GET');
    }

    /**
     * Add a new DNS record via Worker API.
     *
     * @param array $record DNS record data (type, name, content, ttl, proxied).
     *
     * @return array|\WP_Error Response data or WP_Error on failure.
     */
    public function addDnsRecord($record) {
        return $this->request('dns/add', 'POST', $record);
    }

    /**
     * Delete a DNS record via Worker API.
     *
     * @param string $id DNS record identifier.
     *
     * @return array|\WP_Error Response data or WP_Error on failure.
     */
    public function deleteDnsRecord($id) {
        return $this->request('dns/delete/' . $id, 'DELETE');
    }
}
