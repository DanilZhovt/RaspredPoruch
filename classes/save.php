<?php

require_once dirname(__DIR__) . '/classes/ApiClient.php';
require_once dirname(__DIR__) . '/config/constants.php';

$api = new ApiClient(BASE_URL_API_1C);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'empty input']);
    exit;
}

$number = $_GET['number'] ?? null;

if (!$number) {
    echo json_encode(['status' => 'error', 'message' => 'no number']);
    exit;
}

$rows = $api->getWorkloadByNumber($number);

$normalizedRows = [];

foreach ($rows as $row) {
    $rowId = $row['УникальныйИдентификатор'] ?? null;
    if (!$rowId) {
        continue;
    }

    $normalizedRows[$rowId] = [];

    if (empty($row['Сотрудники'])) {
        continue;
    }

    foreach ($row['Сотрудники'] as $t) {
        $name = trim($t['Сотрудник'] ?? '');
        if (!$name) {
            continue;
        }

        $normalizedRows[$rowId][$name] = (float)($t['Количество'] ?? 0);
    }
}

$teachers = $api->getTeachers($_GET['name']);

$teachersByName = [];

foreach ($teachers as $t) {
    $name = trim($t['Сотрудник'] ?? '');
    if (!$name) continue;

    $teachersByName[$name] = $t;
}

$payload = [];

foreach ($input as $rowId => $teachers) {

    $oldTeachers = $normalizedRows[$rowId] ?? [];
    $meta = $rowsById[$rowId] ?? [];

    foreach ($teachers as $teacher => $value) {

        if ($teacher === '_base') continue;

        $value = (float)$value;
        $oldValue = (float)($oldTeachers[$teacher] ?? 0);

        if ($value === $oldValue) {
            continue;
        }

        $teacherMeta = $teachersByName[$teacher] ?? [];

        $payload[] = [
            "НомерДок" => $number,
            "УникальныйИдентификатор" => $rowId,
            "Сотрудник" => $teacher,
            "Должность" => $teacherMeta['Должность'] ?? '',
            "Ставка" => $teacherMeta['Ставка'] ?? '',
            "Количество" => $value
        ];
    }
}

if (!empty($payload)) {
    $result = $api->postAddEmployeeToRaspredPoruch($payload);
} else {
    $result = ['status' => 'ok', 'message' => 'no changes'];
}

echo json_encode($result);