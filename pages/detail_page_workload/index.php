<?php
require_once dirname(__DIR__) . '/../classes/ApiClient.php';
require_once dirname(__DIR__) . '/../config/constants.php';

$api = new ApiClient(BASE_URL_API_1C);

$rows = $api->getWorkloadByNumber($_GET['number']);

$teachers = $api->getTeachers(urldecode($_GET['name']));
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
                        data-discipline="<?= htmlspecialchars($row['Дисциплина'] ?? '') ?>"
                        data-type="<?= htmlspecialchars($row['Нагрузка'] ?? '') ?>"
                        data-period="<?= htmlspecialchars($row['ПериодКонтроля'] ?? '') ?>"
                        data-direction="<?= htmlspecialchars($row['КонтингентНагрузки'] ?? '') ?>"
                        data-teachers='<?= json_encode(array_filter(array_map(function ($t) {
                            return $t["Сотрудник"];
                        }, $row["Сотрудники"] ?? []))) ?>'
                >
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['Дисциплина'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['Нагрузка'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['ПериодКонтроля'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['КонтингентНагрузки'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['Количество'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['Распределено'] ?? '') ?></td>
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
                        <div>МинКол = <?= htmlspecialchars($teacher['МинКол'] ?? '') ?></div>
                        <div>МаксКол = <?= htmlspecialchars($teacher['МаксКол'] ?? '') ?></div>
                    </div>
                </button>
                <br>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="footer">
    <button>Сохранить</button>
    <button>Сформировать отчет</button>
</div>

<script src="scripts.js"></script>
</body>
</html>