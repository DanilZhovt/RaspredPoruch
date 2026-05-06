<?php

require_once dirname(__DIR__) . '/config/constants.php';

class ApiClient
{
    private $baseUrl;
    private $username;
    private $password;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->username = USERNAME_API_1C;
        $this->password = PASSWORD_API_1C;
    }

    private function request($endpoint, $params = [])
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

    public function getAllWorkloads()
    {
        return $this->request('/GetRaspredPoruch');
    }

    public function getWorkloadByNumber($number)
    {
        $data = $this->request('/GetRaspredPoruchByNum', ['number' => $number]);
        return $data['РасчетЧасов'] ?? [];
    }

    public function getTeachers(string $department)
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

        $today = new DateTimeImmutable('today');

        foreach ($response['data'] as $item) {

            $employee = $item['Сотрудник'] ?? '';
            $event    = $item['ВидСобытия'] ?? '';
            $startRaw = $item['ДатаНачала'] ?? null;

            /**
             * 🔥 НОВОЕ: игнорируем помеченные на удаление документы
             */
            $deleted = $item['ПометкаУдаления'] ?? 'Нет';

            if ($deleted === 'Да') {
                continue;
            }

            if (empty($employee) || empty($startRaw)) {
                continue;
            }

            try {
                $startDate = new DateTimeImmutable($startRaw);
            } catch (Exception $e) {
                continue;
            }

            /**
             * 🔥 Не применяем будущие записи
             */
            if ($today < $startDate) {
                continue;
            }

            // =========================
            // ЛОГИКА СОБЫТИЙ
            // =========================

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
        $url = $this->baseUrl . '/PostAddEmployeeToRaspredPoruch';

        $ch = curl_init($url);

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