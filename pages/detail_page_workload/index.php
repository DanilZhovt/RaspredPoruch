<?php

$number = $_GET['number'] ?? '';

$url = "http://10.128.240.232/university_volgmu_test/ru/hs/api/GetRaspredPoruchByNum?number=" . $number;

$username = "danil.zhovtobryuh";
$password = "9jgejj42";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

$response = curl_exec($ch);

curl_close($ch);

$data = json_decode($response, true);
$rows = $data['РасчетЧасов'] ?? [];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Расчеты по преподавателю</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            font-size: 18px;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #ffffff;
            padding: 15px 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            z-index: 1000;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 15px;
        }

        .header label {
            display: flex;
            flex-direction: column;
            font-size: 18px;
        }

        .header select {
            margin-top: 5px;
            padding: 8px 12px;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .header button {
            padding: 12px 25px;
            font-size: 18px;
            border-radius: 6px;
            border: none;
            background-color: #2196F3;
            color: white;
            cursor: pointer;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #ffffff;
            padding: 15px 20px;
            box-shadow: 0 -2px 6px rgba(0,0,0,0.15);
            text-align: center;
            z-index: 1000;
        }

        .footer button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            margin: 0 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
        }

        .main-content {
            display: flex;
            width: 95%;
            margin: 140px auto 80px;
            gap: 20px;
        }

        .container {
            flex: 1;
            background-color: #e8e8e8;
            padding: 20px;
            border-radius: 10px;
        }

        h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            font-size: 18px;
        }

        th, td {
            border: 1px solid #999;
            padding: 12px;
            text-align: center;
        }

        th {
            background-color: #d3d3d3;
            font-size: 20px;
        }

        .sidebar {
            min-width: 340px;
            width: fit-content;
            background-color: #f2f2f2;
            padding: 20px;
            border-radius: 10px;
            font-size: 18px;
            height: fit-content;
        }

        .sidebar .yellow {
            background-color: #fff176;
            padding: 10px;
            margin-bottom: 15px;
            white-space: nowrap;
        }
    </style>
</head>

<body>

<!-- HEADER (оставлен как есть) -->
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