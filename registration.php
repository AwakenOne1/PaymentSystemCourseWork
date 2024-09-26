 <?php
session_start();
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
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
            width: 100%; /* Адаптивная ширина */
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
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
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
            width: 100%; /* Полная ширина кнопки */
            margin-bottom: 10px; /* Отступ между кнопками */
        }

        button:hover {
            background-color: #45a049;
        }

        .login-link {
            display: block;
            text-align: center;
            color: #007bff; /* Цвет текста для ссылки */
            text-decoration: none; /* Убираем подчеркивание */
            margin-top: 10px; /* Отступ сверху */
        }

        .login-link:hover {
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
        <h1>Регистрация</h1>
        <form action="submit_registration.php" method="POST">
            <label for="name">ФИО:</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Подтверждение пароля:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <label for="phone">Телефон:</label>
            <input type="tel" id="phone" name="phone" required>

            <button type="submit">Зарегистрироваться</button>
            <a href="login.php" class="login-link">Уже есть аккаунт? Войти</a> <!-- Кнопка для перехода на страницу входа -->
        </form>
    </div>

<div id="errorModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p id="errorMessage"></p>
        </div>
    </div>

    <script>
        window.onload = function() {
            var error = "<?php echo $error; ?>";
            if (error) {
                document.getElementById('errorMessage').innerHTML = error;
                document.getElementById('errorModal').style.display = 'block';
            }
        };

        document.getElementsByClassName('close')[0].onclick = function() {
            document.getElementById('errorModal').style.display = 'none';
        };
    </script>
</body>
</html>