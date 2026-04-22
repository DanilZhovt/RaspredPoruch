<?php
$url = "http://10.128.240.232/university_volgmu_test/ru/hs/api/GetRaspredPoruch";

$username = "danil.zhovtobryuh";
$password = "9jgejj42";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

$response = curl_exec($ch);

$error = curl_error($ch);

curl_close($ch);

$data = [];

if (!$error) {
    $decoded = json_decode($response, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}
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
        </select>
    </label>

    <label>Учебный год:
        <select id="yearFilter">
            <option value="">Все</option>
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

        <tbody id="table-body"></tbody>
    </table>
</div>

<script>
    window.phpData = <?php echo json_encode($data, JSON_UNESCAPED_UNICODE); ?>;
</script>

<script src="scripts.js"></script>

</body>
</html>