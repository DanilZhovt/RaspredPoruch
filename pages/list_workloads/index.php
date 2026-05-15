<?php
session_start();

if (!isset($_SESSION['1c_username']) || !isset($_SESSION['1c_password'])) {
    header('Location: /');
    exit;
}

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
        <select id="cathedraFilter">
            <option value="">Все</option>
            <?php
            foreach (array_unique(array_column($data, 'Кафедра')) as $cathedra):
                if (!$cathedra) continue;
                ?>
                <option value="<?= htmlspecialchars($cathedra) ?>">
                    <?= htmlspecialchars($cathedra) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Учебный год:
        <select id="yearFilter">
            <option value="">Все</option>
            <?php
            foreach (array_unique(array_column($data, 'УчебныйГод')) as $year):
                if (!$year) continue;
                ?>
                <option value="<?= htmlspecialchars($year) ?>">
                    <?= htmlspecialchars($year) ?>
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
                    data-cathedra="<?= htmlspecialchars($item['Кафедра'] ?? '') ?>"
                    data-year="<?= htmlspecialchars($item['УчебныйГод'] ?? '') ?>"
                    onclick="location.href='/pages/detail_page_workload/?number=<?= urlencode($item['Номер']) ?>&name=<?= urlencode($item['Кафедра']) ?>'"
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