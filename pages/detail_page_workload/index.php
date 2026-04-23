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
    <h3>Детализация нагрузки № <?= htmlspecialchars($number) ?></h3>
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
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= $row['Дисциплина'] ?></td>
                    <td><?= $row['Нагрузка'] ?></td>
                    <td><?= $row['ПериодКонтроля'] ?></td>
                    <td><?= $row['КонтингентНагрузки'] ?></td>
                    <td><?= $row['Количество'] ?></td>
                    <td><?= $row['Распределено'] ?></td>
                </tr>
            <?php endforeach; ?>

        </table>
    </div>

    <div class="sidebar">

        <?php foreach ($rows as $row): ?>
            <?php if (!empty($row['Сотрудники'])): ?>
                <?php foreach ($row['Сотрудники'] as $t): ?>
                    <div class="yellow">
                        <?= $t['Сотрудник'] ?><br>
                        <?= $t['Должность'] ?><br>
                        <?= $t['Ставка'] ?><br>
                        Мин: <?= $t['МинКол'] ?> / Макс: <?= $t['МаксКол'] ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>

    </div>

</div>

<div class="footer">
    <button>Сохранить</button>
    <button>Сформировать отчет</button>
</div>

</body>
</html>