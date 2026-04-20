<?php
//
//$url = "http://10.128.240.232/university_volgmu_test/ru/hs/api/test";
//
//$username = "danil.zhovtobryuh";
//$password = "9jgejj42";
//
//$ch = curl_init();
//
//curl_setopt($ch, CURLOPT_URL, $url);
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
//curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
//
//$response = curl_exec($ch);
//
//$error = null;
//$data = null;
//
//if (curl_errno($ch)) {
//    $error = curl_error($ch);
//} else {
//    $data = json_decode($response, true);
//}
//
//curl_close($ch);
//
//?>
<!---->
<!--<!DOCTYPE html>-->
<!--<html lang="ru">-->
<!--<head>-->
<!--    <meta charset="UTF-8">-->
<!--    <title>1С API Test</title>-->
<!--    <style>-->
<!--        body {-->
<!--            font-family: Arial, serif;-->
<!--            padding: 20px;-->
<!--        }-->
<!--        .box {-->
<!--            border: 1px solid #ccc;-->
<!--            padding: 15px;-->
<!--            border-radius: 8px;-->
<!--        }-->
<!--        .error {-->
<!--            color: red;-->
<!--        }-->
<!--        pre {-->
<!--            background: #f4f4f4;-->
<!--            padding: 10px;-->
<!--        }-->
<!--    </style>-->
<!--</head>-->
<!--<body>-->
<!---->
<!--<h2>Ответ от 1С API</h2>-->
<!---->
<!--<div class="box">-->
<!---->
<!--    --><?php //if ($error): ?>
<!--        <div class="error">-->
<!--            Ошибка: --><?php //= htmlspecialchars($error) ?>
<!--        </div>-->
<!---->
<!--    --><?php //elseif ($data): ?>
<!--        <div><b>Статус:</b> --><?php //= htmlspecialchars($data['status'] ?? 'нет') ?><!--</div>-->
<!--        <div><b>Сообщение:</b> --><?php //= htmlspecialchars($data['message'] ?? 'нет') ?><!--</div>-->
<!---->
<!--        <h4>Полный JSON:</h4>-->
<!--        <pre>--><?php //= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?><!--</pre>-->
<!---->
<!--    --><?php //else: ?>
<!--        <div>-->
<!--            <b>Сырой ответ:</b>-->
<!--            <pre>--><?php //= htmlspecialchars($response) ?><!--</pre>-->
<!--        </div>-->
<!--    --><?php //endif; ?>
<!---->
<!--</div>-->
<!---->
<!--</body>-->
<!--</html>-->