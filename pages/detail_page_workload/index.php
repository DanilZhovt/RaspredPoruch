<?php
session_start();

if (!isset($_SESSION['1c_username']) || !isset($_SESSION['1c_password'])) {
    header('Location: /');
    exit;
}

require_once dirname(__DIR__) . '/../classes/ApiClient.php';
require_once dirname(__DIR__) . '/../config/constants.php';

$api = new ApiClient(BASE_URL_API_1C);
$errorMessage = null;
$tableRows = [];
$teachers = [];
$teacherHours = [];

$tableRows = $api->getWorkloadByNumber($_GET['number'] ?? '');

if (ApiClient::isConnectionError($tableRows)) {
    header('Location: /pages/connection_error/');
    exit;
}

if (isset($tableRows['error']) && $tableRows['error'] === true) {
    $errorMessage = $tableRows['message'] ?? 'Документ не найден';
} elseif (!is_array($tableRows) || empty($tableRows)) {
    $errorMessage = 'Документ не найден или пуст';
} else {
    $teachers = $api->getTeachers(urldecode($_GET['name'] ?? ''));

    if (ApiClient::isConnectionError($teachers)) {
        header('Location: /pages/connection_error/');
        exit;
    }

    if (isset($teachers['error']) && $teachers['error'] === true) {
        $errorMessage = $teachers['message'] ?? 'Ошибка получения списка преподавателей';
        $teachers = [];
    } elseif (!is_array($teachers)) {
        $teachers = [];
    }

    foreach ($tableRows as $row) {
        if (empty($row['Сотрудники'])) continue;

        foreach ($row['Сотрудники'] as $t) {
            $name = trim($t['Сотрудник'] ?? '');
            if (!$name) continue;

            $hours = (float)($t['Количество'] ?? 0);

            if (!isset($teacherHours[$name])) {
                $teacherHours[$name] = 0;
            }

            $teacherHours[$name] += $hours;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Расчеты по преподавателю</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>

<div id="overDistributionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px 32px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); text-align: center; max-width: 420px; width: 90%;">
        <h3 style="margin: 0 0 10px 0; color: #d32f2f;">Ошибка распределения</h3>
        <p style="margin: 0 0 20px 0; font-size: 15px; color: #333;">Невозможно сохранить: в некоторых строках распределённая нагрузка превышает общую. Проверьте выделенные ячейки.</p>
        <button id="closeOverModalBtn" style="padding: 8px 32px; background: #1976d2; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 15px;">ОК</button>
    </div>
</div>

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
    <div class="header">
        <label>Дисциплина:
            <select id="disciplineFilter">
                <option value="">Все</option>
                <?php
                $disciplines = array_unique(array_column($tableRows, 'Дисциплина'));
                foreach ($disciplines as $discipline):
                    if (!$discipline) continue;
                    ?>
                    <option value="<?= htmlspecialchars($discipline) ?>">
                        <?= htmlspecialchars($discipline) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Тип занятия:
            <select id="typeFilter">
                <option value="">Все</option>
                <?php
                $types = array_unique(array_column($tableRows, 'Нагрузка'));
                foreach ($types as $type):
                    if (!$type) continue;
                    ?>
                    <option value="<?= htmlspecialchars($type) ?>">
                        <?= htmlspecialchars($type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Период контроля:
            <select id="periodFilter">
                <option value="">Все</option>
                <?php
                $periods = array_unique(array_column($tableRows, 'ПериодКонтроля'));
                foreach ($periods as $period):
                    if (!$period) continue;
                    ?>
                    <option value="<?= htmlspecialchars($period) ?>">
                        <?= htmlspecialchars($period) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Направление:
            <select id="directionFilter">
                <option value="">Все</option>
                <?php
                $directions = array_unique(array_column($tableRows, 'КонтингентНагрузки'));
                foreach ($directions as $dir):
                    if (!$dir) continue;
                    ?>
                    <option value="<?= htmlspecialchars($dir) ?>">
                        <?= htmlspecialchars($dir) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <button id="applyFilterBtn">Применить фильтр</button>
    </div>

    <div class="main-content">
        <div class="container">
            <h2>УЧЕБНАЯ НАГРУЗКА</h2>

            <table>
                <tr>
                    <th>№</th>
                    <th>Дисциплина</th>
                    <th>Тип занятия</th>
                    <th>Период контроля</th>
                    <th>Направление</th>
                    <th>Нагрузка</th>
                    <th>Распределено</th>
                </tr>

                <?php
                $lineNumber = 1;
                foreach ($tableRows as $row): $teachersData = [];
                    if (!empty($row['Сотрудники'])) {
                        foreach ($row['Сотрудники'] as $t) {
                            $teachersData[] = [
                                'name' => trim($t['Сотрудник'] ?? ''),
                                'hours' => (float)($t['Количество'] ?? 0)
                            ];
                        }
                    }
                    ?>
                    <tr
                            data-id="<?= htmlspecialchars($row['УникальныйИдентификатор']) ?>"
                            data-discipline="<?= htmlspecialchars($row['Дисциплина'] ?? '') ?>"
                            data-type="<?= htmlspecialchars($row['Нагрузка'] ?? '') ?>"
                            data-period="<?= htmlspecialchars($row['ПериодКонтроля'] ?? '') ?>"
                            data-direction="<?= htmlspecialchars($row['КонтингентНагрузки'] ?? '') ?>"
                            data-teachers='<?= htmlspecialchars(json_encode(array_column($row["Сотрудники"] ?? [], "Сотрудник"))) ?>'
                            data-teachers-hours='<?= htmlspecialchars(json_encode($teachersData)) ?>'
                    >
                        <td class="row-number-cell"><?= $lineNumber++ ?></td>
                        <td><?= htmlspecialchars($row['Дисциплина'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['Нагрузка'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['ПериодКонтроля'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['КонтингентНагрузки'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['Количество'] ?? '') ?></td>
                        <td
                                class="distributed editable"
                                data-base="<?= htmlspecialchars($row['Распределено'] ?? 0) ?>"
                        >
                            <?= htmlspecialchars($row['Распределено'] ?? 0) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="sidebar">
            <input
                    type="text"
                    id="teacherSearch"
                    placeholder="Поиск преподавателя..."
                    style="width: 100%; margin-bottom: 10px;"
            >

            <?php if (!empty($teachers)): ?>
                <?php foreach ($teachers as $teacher): ?>
                    <?php if (!empty($teacher['Сотрудник'])): ?>
                        <button
                                type="button"
                                class="teacher-btn"
                                data-teacher="<?= htmlspecialchars($teacher['Сотрудник']) ?>"
                        >
                            <div>
                                <strong><?= htmlspecialchars($teacher['Сотрудник']) ?></strong><br>
                                <?= htmlspecialchars($teacher['Должность'] ?? '') ?><br>
                                <?= htmlspecialchars($teacher['Ставка'] ?? '') ?><br>
                                <div>МинКол = <?= $teacherHours[$teacher['Сотрудник']] ?? 0 ?> / <?= (float)($teacher['МинКол'] ?? 0) ?></div>
                                <div>МаксКол = <?= $teacherHours[$teacher['Сотрудник']] ?? 0 ?> / <?= (float)($teacher['МаксКол'] ?? 0) ?></div>
                            </div>
                        </button>
                        <br>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #666; text-align: center; margin-top: 20px;">
                    Нет доступных преподавателей
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer"
         data-number="<?= htmlspecialchars($_GET['number'] ?? '') ?>"
         data-name="<?= htmlspecialchars($_GET['name'] ?? '') ?>">

        <button id="saveBtn">Сохранить</button>

        <button id="toggleHeaderBtn">
            Скрыть фильтры
        </button>
        <button id="generateReportBtn" class="btn-link">
            Сформировать отчет
        </button>
        <a
                class="btn-link"
                href="/pages/list_workloads"
        >
            К списку нагрузок
        </a>
    </div>

    <script src="scripts.js"></script>
<?php endif; ?>

</body>
</html>