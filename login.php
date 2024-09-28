<?php
include 'includes/db.php';

// Проверка, если пользователь уже вошел в систему, перенаправляем на главную страницу
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];

    // Подключение к базе данных и проверка учетных данных
    $result = $conn->query("SELECT * FROM users WHERE login = '$login'");
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Вход успешен, сохраняем ID пользователя в сессии и перенаправляем на главную страницу
        $_SESSION['user_id'] = $user['id'];
        header('Location: index.php');
        exit();
    } else {
        // Неверные учетные данные, отображаем сообщение об ошибке
        $error = 'Неверный логин или пароль';
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="static.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: left; /* Выравнивание текста по левому краю */
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="password"] {
            width: calc(100% - 20px); /* Учитываем отступы */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #45a049;
        }

        .register-link {
            display: block;
            text-align: center;
            color: #007bff; /* Цвет текста для ссылки */
            text-decoration: none; /* Убираем подчеркивание */
            margin-top: 10px; /* Отступ сверху */
        }

        .register-link:hover {
            text-decoration: underline; /* Подчеркивание при наведении */
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 400px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Вход</h1>
        <form method="POST">
            <label for="login">Логин:</label>
            <input type="text" id="login" name="login" required>

            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Войти</button>
            <a href="registration.php" class="register-link">Зарегистрироваться</a> <!-- Ссылка на регистрацию -->
        </form>
    </div>

    <div id="errorModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p id="errorMessage"><?php echo isset($error) ? $error : ''; ?></p>
        </div>
    </div>
    <script>
        window.onload = function() {
            var error = "<?php echo isset($error) ? $error : ''; ?>";
            if (error) {
                document.getElementById('errorModal').style.display = 'block';
            }
        };

        document.getElementsByClassName('close')[0].onclick = function() {
            document.getElementById('errorModal').style.display = 'none';
        };
    </script>
</body>
</html>