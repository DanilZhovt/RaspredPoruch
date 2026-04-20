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

// если ошибка — пустой массив
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

        .main-content {
            width: 95%;
            margin: 100px auto;
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
        }
    </style>
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
    const data = <?php echo json_encode($data, JSON_UNESCAPED_UNICODE); ?>;

    const tbody = document.getElementById('table-body');
    const kafedraFilter = document.getElementById('kafedraFilter');
    const yearFilter = document.getElementById('yearFilter');

    function renderTable(filteredData) {
        tbody.innerHTML = '';

        filteredData.forEach(item => {
            const row = document.createElement('tr');

            row.innerHTML = `
                <td>${item.Номер ?? ''}</td>
                <td>${item.Кафедра ?? ''}</td>
                <td>${item.УчебныйГод ?? ''}</td>
            `;

            tbody.appendChild(row);
        });
    }

    function fillFilters() {
        const kafedras = [...new Set(data.map(i => i.Кафедра))];
        kafedras.forEach(k => {
            const opt = document.createElement('option');
            opt.value = k;
            opt.textContent = k;
            kafedraFilter.appendChild(opt);
        });

        const years = [...new Set(data.map(i => i.УчебныйГод))];
        years.forEach(y => {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            yearFilter.appendChild(opt);
        });
    }

    document.getElementById("applyFilterBtn").addEventListener("click", function () {
        const k = kafedraFilter.value.toLowerCase();
        const y = yearFilter.value.toLowerCase();

        const filtered = data.filter(item => {
            const okK = !k || (item.Кафедра || '').toLowerCase().includes(k);
            const okY = !y || (item.УчебныйГод || '').toLowerCase().includes(y);
            return okK && okY;
        });

        renderTable(filtered);
    });

    fillFilters();
    renderTable(data);
</script>

</body>
</html>