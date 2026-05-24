<?php
session_start();

if (!isset($_SESSION['1c_username']) || !isset($_SESSION['1c_password'])) {
    header('Location: /');
    exit;
}

require_once dirname(__DIR__) . '/../classes/ApiClient.php';
require_once dirname(__DIR__) . '/../config/constants.php';
require_once 'TeacherWorkloadReport.php';

$api = new ApiClient(BASE_URL_API_1C);
$errorMessage = null;
$data = [];
$rows = [];
$teachers = [];

$rows = $api->getWorkloadByNumber($_GET['number'] ?? '');

if (ApiClient::isConnectionError($rows)) {
    header('Location: /pages/connection_error/');
    exit;
}

if (isset($rows['error']) && $rows['error'] === true) {
    $errorMessage = $rows['message'] ?? 'Документ не найден';
} elseif (!is_array($rows) || empty($rows)) {
    $errorMessage = 'Документ не найден или пуст';
} else {
    $teachers = $api->getTeachers(urldecode($_GET['name'] ?? ''));

    if (ApiClient::isConnectionError($teachers)) {
        header('Location: /pages/connection_error/');
        exit;
    }

    if (isset($teachers['error']) && $teachers['error'] === true) {
        $errorMessage = $teachers['message'] ?? 'Ошибка получения списка преподавателей';
    } elseif (!is_array($teachers)) {
        $errorMessage = 'Некорректные данные преподавателей';
    } else {
        $reportBuilder = new TeacherWorkloadReport($rows, $teachers);
        $data = $reportBuilder->build();
    }
}

$totalDistributed = 0;
$totalLoad = 0;

if (!empty($data['report'])) {
    foreach ($data['report'] as $teacher) {
        $totalDistributed += $teacher['Итого'];
    }

    foreach ($rows as $row) {
        if (!empty($row['Сотрудники'])) {
            foreach ($row['Сотрудники'] as $employee) {
                $totalLoad += (float)($employee['Количество'] ?? 0);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчет по нагрузке</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>

<?php if ($errorMessage): ?>
    <div class="error-container" style="max-width: 800px; margin: 100px auto; padding: 20px; text-align: center;">
        <div class="error-message" style="background: #fff3f3; border: 1px solid #ffcaca; border-radius: 8px; padding: 30px;">
            <h2 style="color: #d32f2f; margin-bottom: 15px;">Ошибка</h2>
            <p style="font-size: 16px; color: #333; margin-bottom: 20px;">
                <?= htmlspecialchars($errorMessage) ?>
            </p>
            <div style="margin-top: 30px;">
                <a href="/pages/list_workloads" class="btn-link" style="display: inline-block; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 4px;">
                    ← К списку нагрузок
                </a>
                <button onclick="window.location.reload()" style="display: inline-block; padding: 10px 20px; background: #4caf50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
                    Попробовать снова
                </button>
            </div>
        </div>
    </div>
<?php else: ?>
    <h2>Отчет по учебной нагрузке</h2>

    <table>
        <tr>
            <th>№</th>
            <th>ФИО</th>
            <th>Ставка</th>
            <th>Должность</th>

            <?php foreach ($data['lessonTypes'] as $type): ?>
                <th><?= htmlspecialchars($type) ?></th>
            <?php endforeach; ?>

            <th>Итого</th>
        </tr>

        <?php
        $lineNumber = 1;
        $columnSums = [];
        foreach ($data['lessonTypes'] as $type) {
            $columnSums[$type] = 0;
        }
        ?>

        <?php foreach ($data['report'] as $teacher): ?>
            <tr>
                <td><?= $lineNumber++ ?></td>
                <td class="name"><?= htmlspecialchars($teacher['ФИО']) ?></td>
                <td><?= htmlspecialchars($teacher['Ставка']) ?></td>
                <td><?= htmlspecialchars($teacher['Должность']) ?></td>

                <?php foreach ($data['lessonTypes'] as $type): ?>
                    <td>
                        <?php
                        $value = $teacher[$type] ?: '';
                        echo $value;
                        if ($value !== '') {
                            $columnSums[$type] += (float)$value;
                        }
                        ?>
                    </td>
                <?php endforeach; ?>

                <td><strong><?= $teacher['Итого'] ?></strong></td>
            </tr>
        <?php endforeach; ?>

        <tr class="total-row">
            <td colspan="4"><strong>Распределено:</strong></td>
            <?php foreach ($data['lessonTypes'] as $type): ?>
                <td><strong><?= $columnSums[$type] > 0 ? $columnSums[$type] : '' ?></strong></td>
            <?php endforeach; ?>
            <td><strong><?= $totalDistributed ?></strong></td>
        </tr>

        <tr class="total-row">
            <td colspan="4"><strong>Общая нагрузка:</strong></td>
            <?php
            $totalLoadByType = [];
            foreach ($data['lessonTypes'] as $type) {
                $totalLoadByType[$type] = 0;
            }

            foreach ($rows as $row) {
                $rowType = trim($row['Нагрузка'] ?? '');
                if (in_array($rowType, $data['lessonTypes'])) {
                    $totalLoadByType[$rowType] += (float)($row['Количество'] ?? 0);
                }
            }
            ?>
            <?php foreach ($data['lessonTypes'] as $type): ?>
                <td><strong><?= $totalLoadByType[$type] > 0 ? $totalLoadByType[$type] : '' ?></strong></td>
            <?php endforeach; ?>
            <td><strong><?= $totalLoad ?></strong></td>
        </tr>
    </table>

    <div class="footer">
        <a class="btn-link" href="/pages/detail_page_workload/?number=<?= urlencode($_GET['number']) ?>&name=<?= urlencode($_GET['name']) ?>">
            К расчетам
        </a>
        <a class="btn-link" href="/pages/list_workloads">
            К списку нагрузок
        </a>
    </div>
<?php endif; ?>

</body>
</html>