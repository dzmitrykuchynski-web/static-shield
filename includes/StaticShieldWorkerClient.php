<?php
namespace StaticShield;

class StaticShieldWorkerClient {

    private $workerUrl;
    private $authToken;

    public function __construct() {
        $this->workerUrl = get_option('static_shield_cf_worker');
        $this->authToken = get_option('static_shield_cf_api_key');
    }

    /**
     * Make request to Worker API
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

        $response = wp_remote_request(trailingslashit($this->workerUrl) . ltrim($endpoint, '/'), $args);

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

    public function listDnsRecords() {
        return $this->request('dns/list', 'GET');
    }

    public function addDnsRecord($record) {
        return $this->request('dns/add', 'POST', $record);
    }

    public function deleteDnsRecord($id) {
        return $this->request('dns/delete/' . $id, 'DELETE');
    }
}
