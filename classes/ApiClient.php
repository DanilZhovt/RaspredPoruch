<?php

require_once dirname(__DIR__) . '/config/constants.php';

class ApiClient
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $timeout;
    private int $connectTimeout;

    public function __construct($baseUrl, $username = null, $password = null, $timeout = 10, $connectTimeout = 5)
    {
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($username !== null && $password !== null) {
            $this->username = $username;
            $this->password = $password;
        } else {
            $this->username = $_SESSION['1c_username'] ?? '';
            $this->password = $_SESSION['1c_password'] ?? '';
        }
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @return array|mixed
     */
    protected function request(string $endpoint, array $params = []): mixed
    {
        $url = $this->baseUrl . $endpoint;

        if (isset($params['name'])) {
            $params['name'] = str_replace(' ', '%20', $params['name']);
        }

        if (!empty($params)) {
            $queryParts = [];

            foreach ($params as $key => $value) {
                $queryParts[] = $key . '=' . $value;
            }

            $url .= '?' . implode('&', $queryParts);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_VERBOSE, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errorNumber = curl_errno($ch);

        curl_close($ch);

        if ($error) {
            $errorInfo = [
                'error' => $error,
                'error_number' => $errorNumber,
                'http_code' => $httpCode
            ];

            if ($errorNumber === CURLE_OPERATION_TIMEOUTED) {
                $errorInfo['error_type'] = 'timeout';
                $errorInfo['error'] = 'Request timed out';
            } elseif ($errorNumber === CURLE_COULDNT_CONNECT ||
                $errorNumber === CURLE_COULDNT_RESOLVE_HOST) {
                $errorInfo['error_type'] = 'connection';
                $errorInfo['error'] = 'Could not connect to server';
            }

            error_log("API Request Error: " . print_r($errorInfo, true));

            return $errorInfo;
        }

        if ($httpCode >= 400) {
            $errorData = [
                'error' => "HTTP Error: {$httpCode}",
                'http_code' => $httpCode,
                'response' => $response
            ];

            $decodedResponse = json_decode($response, true);
            if ($decodedResponse !== null) {
                $errorData['response_decoded'] = $decodedResponse;
            }

            return $errorData;
        }

        $decodedResponse = json_decode($response, true);

        if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => 'Invalid JSON response',
                'raw_response' => substr($response, 0, 500)
            ];
        }

        return $decodedResponse;
    }

    /**
     * Проверяет, является ли ошибка ошибкой подключения
     * @param array $response
     * @return bool
     */
    public static function isConnectionError(array $response): bool
    {
        return isset($response['error_type']) &&
            in_array($response['error_type'], ['timeout', 'connection']);
    }

    /**
     * Проверяет валидность учетных данных
     * @param string $baseUrl
     * @param string $username
     * @param string $password
     * @return array [bool, string]
     */
    public static function validateCredentials(string $baseUrl, string $username, string $password): array
    {
        $client = new self($baseUrl, $username, $password);

        $response = $client->getAllWorkloads();

        if (self::isConnectionError($response)) {
            return [false, 'connection_error'];
        }

        if (isset($response['http_code']) && $response['http_code'] === 401) {
            return [false, 'Неверный логин или пароль'];
        }

        if (isset($response['error'])) {
            return [false, "Ошибка: {$response['error']}"];
        }

        if (is_array($response) && !isset($response['http_code'])) {
            return [true, ''];
        }

        return [false, 'Неизвестная ошибка авторизации'];
    }

    /**
     * @return array|mixed
     */
    public function getAllWorkloads(): mixed
    {
        return $this->request('/GetRaspredPoruch');
    }

    /**
     * @param string $number
     * @return array|mixed
     */
    /**
     * @param string $number
     * @return array|mixed
     */
    public function getWorkloadByNumber(string $number): mixed
    {
        $data = $this->request('/GetRaspredPoruchByNum', ['number' => $number]);

        if (isset($data['error'])) {
            if (isset($data['response']) && is_string($data['response'])) {
                $responseText = $data['response'];

                if (str_contains($responseText, 'Элемент не выбран') ||
                    str_contains($responseText, 'ПолучитьОбъект')) {
                    return [
                        'error' => true,
                        'message' => 'Документ с номером ' . $number . ' не найден',
                        'not_found' => true
                    ];
                }

                if (str_contains($responseText, 'Ошибка')) {
                    preg_match('/Ошибка.*:(.*)/i', $responseText, $matches);
                    $errorDescription = $matches[1] ?? $responseText;

                    return [
                        'error' => true,
                        'message' => 'Ошибка 1С: ' . trim($errorDescription),
                        'details' => $responseText
                    ];
                }
            }

            if (isset($data['response_decoded']) && is_array($data['response_decoded'])) {
                $decodedError = $data['response_decoded'];

                if (isset($decodedError['error'])) {
                    return [
                        'error' => true,
                        'message' => $decodedError['error'],
                        'details' => $decodedError
                    ];
                }

                if (isset($decodedError['message'])) {
                    return [
                        'error' => true,
                        'message' => $decodedError['message'],
                        'details' => $decodedError
                    ];
                }
            }

            return [
                'error' => true,
                'message' => $data['error'] ?? 'Неизвестная ошибка сервера',
                'details' => $data
            ];
        }

        if (!isset($data['РасчетЧасов'])) {
            return [
                'error' => true,
                'message' => 'Некорректный формат ответа от сервера',
                'not_found' => true
            ];
        }

        return $data['РасчетЧасов'];
    }

    /**
     * @param string $department
     * @return array
     * @throws DateMalformedStringException
     */
    public function getTeachers(string $department): array
    {
        $response = $this->request('/GetTeachers', [
            'name' => $department
        ]);

        if (isset($response['error'])) {
            return [
                'error' => $response['error']
            ];
        }

        if (empty($response['data']) || !is_array($response['data'])) {
            return [];
        }

        $teachers = [];

        foreach ($response['data'] as $item) {

            $employee = $item['Сотрудник'] ?? '';
            $event = $item['ВидСобытия'] ?? '';
            $startRaw = $item['ДатаНачала'] ?? null;

            if (($item['ПометкаУдаления'] ?? 'Нет') === 'Да') {
                continue;
            }

            if (empty($employee) || empty($startRaw)) {
                continue;
            }

            if ((new DateTimeImmutable('today')) < (new DateTimeImmutable($startRaw))) {
                continue;
            }

            if ($event === 'Прием') {

                if (!isset($teachers[$employee])) {
                    $teachers[$employee] = $item;
                }

            } elseif ($event === 'Перемещение') {

                $teachers[$employee] = $item;

            } elseif ($event === 'Увольнение') {

                if (isset($teachers[$employee])) {
                    unset($teachers[$employee]);
                }
            }
        }

        $allowedPositions = [
            'Преподаватель',
            'Старший преподаватель',
            'Доцент'
        ];

        $result = array_filter($teachers, function ($teacher) use ($allowedPositions) {
            return in_array(
                $teacher['Должность'] ?? '',
                $allowedPositions,
                true
            );
        });

        usort($result, function ($a, $b) {
            return strcmp(
                $a['Сотрудник'] ?? '',
                $b['Сотрудник'] ?? ''
            );
        });

        return array_values($result);
    }

    public function postAddEmployeeToRaspredPoruch(array $data)
    {
        $ch = curl_init($this->baseUrl . '/PostAddEmployeeToRaspredPoruch');

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

        $response = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return ['error' => $error, 'error_type' => 'connection'];
        }

        return json_decode($response, true);
    }
}