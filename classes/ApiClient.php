<?php

require_once dirname(__DIR__) . '/config/constants.php';

class ApiClient
{
    private string $baseUrl;
    private string $username;
    private string $password;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->username = USERNAME_API_1C;
        $this->password = PASSWORD_API_1C;
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @return array|mixed
     */
    private function request(string $endpoint, array $params = []): mixed
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

        $response = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        return json_decode($response, true);
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
    public function getWorkloadByNumber(string $number): mixed
    {
        $data = $this->request('/GetRaspredPoruchByNum', ['number' => $number]);
        return $data['РасчетЧасов'] ?? [];
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

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));

        $response = curl_exec($ch);

        curl_close($ch);

        return json_decode($response, true);
    }
}