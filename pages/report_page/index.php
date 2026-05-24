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
$reportBuilder = null;

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
        $reportBuilder->build();
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
    <div class="error-container">
        <div class="error-message">
            <h2>Ошибка</h2>
            <p><?= htmlspecialchars($errorMessage) ?></p>
            <div class="error-actions">
                <a href="/pages/list_workloads" class="btn-back">
                    К списку нагрузок
                </a>
                <button onclick="window.location.reload()" class="btn-retry">
                    Попробовать снова
                </button>
            </div>
        </div>
    </div>
<?php else: ?>
    <h2 class="page-title">Отчет по учебной нагрузке</h2>

    <table>
        <tr>
            <th>№</th>
            <th>ФИО</th>
            <th>Ставка</th>
            <th>Должность</th>

            <?php foreach ($reportBuilder->getLessonTypes() as $type): ?>
                <th><?= htmlspecialchars($type) ?></th>
            <?php endforeach; ?>

            <th>Итого</th>
        </tr>

        <?php $lineNumber = 1; ?>
        <?php foreach ($reportBuilder->getReport() as $teacher): ?>
            <tr>
                <td><?= $lineNumber++ ?></td>
                <td class="name"><?= htmlspecialchars($teacher['ФИО']) ?></td>
                <td><?= htmlspecialchars($teacher['Ставка']) ?></td>
                <td><?= htmlspecialchars($teacher['Должность']) ?></td>

                <?php foreach ($reportBuilder->getLessonTypes() as $type): ?>
                    <td><?= $teacher[$type] ?: '' ?></td>
                <?php endforeach; ?>

                <td><strong><?= $teacher['Итого'] ?></strong></td>
            </tr>
        <?php endforeach; ?>

        <tr class="total-row">
            <td colspan="4"><strong>Распределено:</strong></td>
            <?php foreach ($reportBuilder->getLessonTypes() as $type): ?>
                <td><strong><?= $reportBuilder->getDistributedByType($type) ?: '' ?></strong></td>
            <?php endforeach; ?>
            <td><strong><?= $reportBuilder->getDistributedTotal() ?></strong></td>
        </tr>

        <tr class="total-row">
            <td colspan="4"><strong>Общая нагрузка:</strong></td>
            <?php foreach ($reportBuilder->getLessonTypes() as $type): ?>
                <td><strong><?= $reportBuilder->getLoadByType($type) ?: '' ?></strong></td>
            <?php endforeach; ?>
            <td><strong><?= $reportBuilder->getLoadTotal() ?></strong></td>
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