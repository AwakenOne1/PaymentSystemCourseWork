<?php
include 'db.php';
session_start();

// Проверка, если пользователь уже вошел в систему, перенаправляем на главную страницу
if (isset($_SESSION['user_id'])) {
    header('Location: users.php');
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
        header('Location: users.php');
        exit();
    } else {
        // Неверные учетные данные, отображаем сообщение об ошибке
        $error = 'Неверный логин или пароль';
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Вход</title>
    <style>
        .error {
            color: red;
        }
    </style>
</head>
<body>
<h1>Вход</h1>
<?php if (isset($error)) { ?>
    <p class="error"><?php echo $error; ?></p>
<?php } ?>
<form method="POST">
    <label for="login">Логин:</label>
    <input type="text" id="login" name="login" required><br>

    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required><br>

    <button type="submit">Войти</button>
</form>
</body>
</html>