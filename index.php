<?php
session_start();

if (isset($_SESSION['1c_username']) && isset($_SESSION['1c_password'])) {
    header('Location: /pages/list_workloads/');
}

require_once dirname(__DIR__) . '/my-module.local/classes/ApiClient.php';
require_once dirname(__DIR__) . '/my-module.local/config/constants.php';

$api = new ApiClient(BASE_URL_API_1C);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($api->validateCredentials(BASE_URL_API_1C, $_POST['username'], $_POST['password'])) {
        $_SESSION['1c_username'] = $username;
        $_SESSION['1c_password'] = $password;

        header('Location: /pages/list_workloads/');
    } else {
        $error = 'Неверный логин или пароль. Проверьте правильность ввода.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация 1С</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-title {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
        }

        .login-subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .login-button:active {
            transform: translateY(0);
        }
    </style>
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