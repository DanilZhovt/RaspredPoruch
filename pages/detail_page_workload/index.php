<?php
require_once dirname(__DIR__) . '/../classes/ApiClient.php';
require_once dirname(__DIR__) . '/../config/constants.php';

$number = $_GET['number'] ?? '';

$api = new ApiClient(BASE_URL_API_1C);

$rows = $api->getWorkloadByNumber($number);
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
        <?php /*foreach ($rows as $row): */?><!--
            <?php /*if (!empty($row['Сотрудники'])): */?>
                <?php /*foreach ($row['Сотрудники'] as $t): */?>
                    <div class="yellow">
                        <?php /*= $t['Сотрудник'] */?><br>
                        <?php /*= $t['Должность'] */?><br>
                        <?php /*= $t['Ставка'] */?><br>
                        Мин: <?php /*= $t['МинКол'] */?> / Макс: <?php /*= $t['МаксКол'] */?>
                    </div>
                <?php /*endforeach; */?>
            <?php /*endif; */?>
        --><?php /*endforeach; */?>
    </div>
</div>

<div class="footer">
    <button>Сохранить</button>
    <button>Сформировать отчет</button>
</div>

<script src="scripts.js"></script>
</body>
</html>