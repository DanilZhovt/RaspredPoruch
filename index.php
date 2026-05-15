<?php
session_start();

if (isset($_SESSION['1c_username']) && isset($_SESSION['1c_password'])) {
    header('Location: /pages/list_workloads/');
    exit;
}

require_once dirname(__DIR__) . '/my-module.local/classes/ApiClient.php';
require_once dirname(__DIR__) . '/my-module.local/config/constants.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        list($success, $message) = ApiClient::validateCredentials(BASE_URL_API_1C, $username, $password);

        if ($success) {
            $_SESSION['1c_username'] = $username;
            $_SESSION['1c_password'] = $password;

            header('Location: /pages/list_workloads/');
            exit;
        } else {
            if ($message === 'connection_error') {
                header('Location: /pages/connection_error/');
                exit;
            }

            $error = $message ?: 'Неверный логин или пароль. Проверьте правильность ввода.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация 1С</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
<div class="login-container">
    <h1 class="login-title">Войдите в свой аккаунт 1С</h1>
    <p class="login-subtitle">Введите учетные данные для доступа к системе</p>

    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Логин</label>
            <input type="text" id="username" name="username" required
                   placeholder="Введите логин 1С"
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required
                   placeholder="Введите пароль 1С">
        </div>

        <button type="submit" class="login-button">Войти</button>
    </form>
</div>
</body>
</html>