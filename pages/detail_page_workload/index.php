<?php
require_once dirname(__DIR__) . '/../classes/ApiClient.php';
require_once dirname(__DIR__) . '/../config/constants.php';

$api = new ApiClient(BASE_URL_API_1C);

$rows = $api->getWorkloadByNumber($_GET['number']);

$teachers = $api->getTeachers(urldecode($_GET['name']));

$teacherHours = [];

foreach ($rows as $row) {

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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Расчеты по преподавателю</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>

<div class="header">
    <label>Дисциплина:
        <select id="disciplineFilter">
            <option value="">Все</option>
            <?php
            $disciplines = array_unique(array_column($rows, 'Дисциплина'));
            foreach ($disciplines as $d):
                if (!$d) continue;
                ?>
                <option value="<?= htmlspecialchars($d) ?>">
                    <?= htmlspecialchars($d) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Тип занятия:
        <select id="typeFilter">
            <option value="">Все</option>
            <?php
            $types = array_unique(array_column($rows, 'Нагрузка'));
            foreach ($types as $t):
                if (!$t) continue;
                ?>
                <option value="<?= htmlspecialchars($t) ?>">
                    <?= htmlspecialchars($t) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Период контроля:
        <select id="periodFilter">
            <option value="">Все</option>
            <?php
            $periods = array_unique(array_column($rows, 'ПериодКонтроля'));
            foreach ($periods as $p):
                if (!$p) continue;
                ?>
                <option value="<?= htmlspecialchars($p) ?>">
                    <?= htmlspecialchars($p) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Направление:
        <select id="directionFilter">
            <option value="">Все</option>
            <?php
            $dirs = array_unique(array_column($rows, 'КонтингентНагрузки'));
            foreach ($dirs as $d):
                if (!$d) continue;
                ?>
                <option value="<?= htmlspecialchars($d) ?>">
                    <?= htmlspecialchars($d) ?>
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

            <?php $i = 1; foreach ($rows as $row): ?>
                <tr
                        data-id="<?= htmlspecialchars($row['УникальныйИдентификатор']) ?>"
                        data-discipline="<?= htmlspecialchars($row['Дисциплина'] ?? '') ?>"
                        data-type="<?= htmlspecialchars($row['Нагрузка'] ?? '') ?>"
                        data-period="<?= htmlspecialchars($row['ПериодКонтроля'] ?? '') ?>"
                        data-direction="<?= htmlspecialchars($row['КонтингентНагрузки'] ?? '') ?>"
                        data-teachers='<?= htmlspecialchars(json_encode(array_column($row["Сотрудники"], "Сотрудник"))) ?>'
                >
                    <td class="row-number-cell"><?= $i++ ?></td>
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
    </div>
</div>

<div class="footer"
     data-number="<?= htmlspecialchars($_GET['number'] ?? '') ?>"
     data-name="<?= htmlspecialchars($_GET['name'] ?? '') ?>">

    <button id="saveBtn">Сохранить</button>
    <button>Сформировать отчет</button>
</div>

<script src="scripts.js"></script>
</body>
</html>