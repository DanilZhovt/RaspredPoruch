<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка подключения</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
<div class="error-container">
    <div class="error-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
    </div>

    <h1 class="error-title">Не удалось получить данные</h1>

    <p class="error-message">
        Проверьте подключение к интернету/локальной сети и повторите попытку.
    </p>

    <div class="error-details">
        <strong>Возможные причины:</strong>
        • Отсутствует подключение к сети Интернет<br>
        • Сервер 1С временно недоступен<br>
        • Блокировка запроса брандмауэром или антивирусом<br>
        • Неправильный адрес сервера в настройках
    </div>

    <div class="button-group">
        <a href="/pages/list_workloads/" class="btn btn-primary">
            Повторить попытку
        </a>
    </div>

    <p class="timer" id="timer"></p>
</div>

<script src="scripts.js"></script>
</body>
</html>