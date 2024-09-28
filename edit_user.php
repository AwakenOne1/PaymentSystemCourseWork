<?php
include 'includes/db.php';
// Получение ID пользователя из параметра запроса
$userId = $_GET['id'];

// Подключение к базе данных и получение данных пользователя

$result = $conn->query("SELECT * FROM users WHERE id = $userId");
$user = $result->fetch_assoc();

// Обработка формы редактирования пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $login = $_POST['login'];
    $phone = $_POST['phone'];

    // Обновление данных пользователя в базе данных
    $conn->query("UPDATE users SET name = '$name', login = '$login', phone = '$phone' WHERE id = $userId");

    // Перенаправление на главную страницу после обновления
    header('Location: users.php');
    exit();
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Редактирование пользователя</title>
</head>
<body>
<h1>Редактирование пользователя</h1>
<form method="POST">
    <label for="name">Имя:</label>
    <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required><br>

    <label for="login">Логин:</label>
    <input type="text" id="login" name="login" value="<?php echo $user['login']; ?>" required><br>

    <label for="phone">Телефон:</label>
    <input type="text" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required><br>

    <button type="submit">Сохранить</button>
</form>
</body>
</html>