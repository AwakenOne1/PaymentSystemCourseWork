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