<?php
require_once dirname(__DIR__) . '/../classes/ApiClient.php';
require_once dirname(__DIR__) . '/../config/constants.php';
require_once 'TeacherWorkloadReport.php';

$api = new ApiClient(BASE_URL_API_1C);

$rows = $api->getWorkloadByNumber($_GET['number']);
$teachers = $api->getTeachers(urldecode($_GET['name']));

$reportBuilder = new TeacherWorkloadReport($rows, $teachers);

$data = $reportBuilder->build();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчет по нагрузке</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>

<h2>Отчет по учебной нагрузке</h2>

<table>
    <tr>
        <th>№</th>
        <th>ФИО</th>
        <th>Ставка</th>
        <th>Должность</th>

        <?php
        foreach ($data['lessonTypes'] as $type): ?>
            <th><?= htmlspecialchars($type) ?></th>
        <?php
        endforeach; ?>

        <th>Итого</th>
    </tr>

    <?php
    $lineNumber = 1;
    foreach ($data['report'] as $teacher): ?>

        <tr>

            <td><?= $lineNumber++ ?></td>

            <td class="name">
                <?= htmlspecialchars($teacher['ФИО']) ?>
            </td>

            <td>
                <?= htmlspecialchars($teacher['Ставка']) ?>
            </td>

            <td>
                <?= htmlspecialchars($teacher['Должность']) ?>
            </td>

            <?php
            foreach ($data['lessonTypes'] as $type): ?>

                <td>
                    <?= $teacher[$type] ?: '' ?>
                </td>

            <?php
            endforeach; ?>

            <td>
                <strong><?= $teacher['Итого'] ?></strong>
            </td>

        </tr>

    <?php
    endforeach; ?>

</table>

</body>
</html>