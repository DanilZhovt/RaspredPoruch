<?php
require_once dirname(__DIR__) . '/config/constants.php';

class ApiClient {
    private $baseUrl;
    private $username;
    private $password;

    public function __construct($baseUrl) {
        $this->baseUrl = $baseUrl;
        $this->username = USERNAME_API_1C;
        $this->password = PASSWORD_API_1C;
    }

    private function request($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");

        $response = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        return json_decode($response, true);
    }

    public function getAllWorkloads() {
        return $this->request('/GetRaspredPoruch');
    }

    public function getWorkloadByNumber($number) {
        $data = $this->request('/GetRaspredPoruchByNum', ['number' => $number]);
        return $data['РасчетЧасов'] ?? [];
    }
}