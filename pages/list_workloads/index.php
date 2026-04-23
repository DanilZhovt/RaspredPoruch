<?php
require_once dirname(__DIR__) . '/../classes/ApiClient.php';
require_once dirname(__DIR__) . '/../config/constants.php';

$api = new ApiClient(BASE_URL_API_1C);
$data = $api->getAllWorkloads();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Таблица кафедр</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>

<div class="header">
    <label>Кафедра:
        <select id="kafedraFilter">
            <option value="">Все</option>
            <?php
            $kafedras = array_unique(array_column($data, 'Кафедра'));
            foreach ($kafedras as $k):
                if (!$k) continue;
                ?>
                <option value="<?= htmlspecialchars($k) ?>">
                    <?= htmlspecialchars($k) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Учебный год:
        <select id="yearFilter">
            <option value="">Все</option>
            <?php
            $years = array_unique(array_column($data, 'УчебныйГод'));
            foreach ($years as $y):
                if (!$y) continue;
                ?>
                <option value="<?= htmlspecialchars($y) ?>">
                    <?= htmlspecialchars($y) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <button id="applyFilterBtn">Применить фильтр</button>
</div>

<div class="main-content">
    <h2>Список нагрузок по кафедрам</h2>

    <table>
        <thead>
        <tr>
            <th>Номер</th>
            <th>Кафедра</th>
            <th>Учебный год</th>
        </tr>
        </thead>

        <tbody id="table-body">
        <?php foreach ($data as $item): ?>
            <tr
                    data-kafedra="<?= htmlspecialchars($item['Кафедра'] ?? '') ?>"
                    data-year="<?= htmlspecialchars($item['УчебныйГод'] ?? '') ?>"
                    onclick="location.href='/pages/detail_page_workload/?number=<?= urlencode($item['Номер']) ?>'"
                    style="cursor: pointer;"
            >
                <td><?= htmlspecialchars($item['Номер'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['Кафедра'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['УчебныйГод'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="scripts.js"></script>
</body>
</html>