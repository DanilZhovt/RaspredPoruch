<?php

class DistributionSaver
{
    private ApiClient $api;
    private string $documentNumber;
    private string $documentName;
    private array $newData;
    private array $oldData;
    private array $teachers;
    private array $normalizedOldRows;
    private array $teachersByName;

    public function __construct()
    {
        require_once dirname(__DIR__) . '/config/constants.php';
        require_once __DIR__ . '/ApiClient.php';

        $this->api = new ApiClient(BASE_URL_API_1C);
        $this->normalizedOldRows = [];
        $this->teachersByName = [];
    }

    public function handleRequest(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['1c_username']) || !isset($_SESSION['1c_password'])) {
            $this->sendResponse(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $this->newData = json_decode($rawInput, true);

        if (!$this->newData) {
            $this->sendResponse(['status' => 'error', 'message' => 'empty input']);
            return;
        }

        $this->documentNumber = $_GET['number'] ?? null;
        $this->documentName = $_GET['name'] ?? '';

        if (!$this->documentNumber) {
            $this->sendResponse(['status' => 'error', 'message' => 'no number']);
            return;
        }

        try {
            $result = $this->save();
            $this->sendResponse($result);
        } catch (Exception $e) {
            $this->sendResponse([
                'status' => 'error',
                'message' => 'Внутренняя ошибка сервера'
            ]);
        }
    }

    public function save(): array
    {
        $this->oldData = $this->api->getWorkloadByNumber($this->documentNumber);

        if (ApiClient::isConnectionError($this->oldData)) {
            return ['status' => 'error', 'message' => 'Ошибка получения данных из 1С'];
        }

        $this->normalizeOldData();

        $this->teachers = $this->api->getTeachers($this->documentName);

        if (ApiClient::isConnectionError($this->teachers)) {
            return ['status' => 'error', 'message' => 'Ошибка получения списка преподавателей'];
        }

        $this->indexTeachersByName();

        $payload = $this->buildPayload();

        if (!empty($payload)) {
            $result = $this->api->postAddEmployeeToRaspredPoruch($payload);

            if (ApiClient::isConnectionError($result)) {
                return ['status' => 'error', 'message' => 'Ошибка сохранения в 1С'];
            }

            return $result;
        }

        return ['status' => 'ok', 'message' => 'no changes'];
    }

    private function normalizeOldData(): void
    {
        foreach ($this->oldData as $row) {
            $rowId = $row['УникальныйИдентификатор'] ?? null;

            if (!$rowId) {
                continue;
            }

            $this->normalizedOldRows[$rowId] = [];

            if (empty($row['Сотрудники'])) {
                continue;
            }

            foreach ($row['Сотрудники'] as $teacher) {
                $name = trim($teacher['Сотрудник'] ?? '');

                if (!$name) {
                    continue;
                }

                $this->normalizedOldRows[$rowId][$name] = (float)($teacher['Количество'] ?? 0);
            }
        }
    }

    private function indexTeachersByName(): void
    {
        foreach ($this->teachers as $teacher) {
            $name = trim($teacher['Сотрудник'] ?? '');

            if (!$name) {
                continue;
            }

            $this->teachersByName[$name] = $teacher;
        }
    }

    private function buildPayload(): array
    {
        $payload = [];

        foreach ($this->newData as $rowId => $teachers) {
            $oldTeachers = $this->normalizedOldRows[$rowId] ?? [];

            foreach ($teachers as $teacher => $value) {
                if ($teacher === '_base') {
                    continue;
                }

                $value = (float)$value;
                $oldValue = (float)($oldTeachers[$teacher] ?? 0);

                if ($value === $oldValue) {
                    continue;
                }

                $teacherMeta = $this->teachersByName[$teacher] ?? [];

                $payload[] = [
                    "НомерДок" => $this->documentNumber,
                    "УникальныйИдентификатор" => $rowId,
                    "Сотрудник" => $teacher,
                    "Должность" => $teacherMeta['Должность'] ?? '',
                    "Ставка" => $teacherMeta['Ставка'] ?? '',
                    "Количество" => $value
                ];
            }
        }

        return $payload;
    }

    private function sendResponse(array $data): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode($data);
        exit;
    }
}

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'DistributionSaver.php') {
    $saver = new DistributionSaver();
    $saver->handleRequest();
}