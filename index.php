<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистраци FREFERя</title>
    <link rel="stylesheet" href="static.css">
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
</body>
</html>






}