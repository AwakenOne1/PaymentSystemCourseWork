<?php
// submit_registration.php
include 'db.php';

// Валидация данных
$name = trim($_POST['name']);
$login = trim($_POST['email']);
$password = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);
$phone = trim($_POST['phone']);

// Проверка на пустые поля
if (empty($name) && empty($login)&&  empty($password)&&  empty($confirm_password)&&  empty($phone)) {
$error = "Заполните все поля.";
header("Location: registration.php?error=" . urlencode($error) . "&name=" . urlencode($name) . "&email=" . urlencode($login) . "&phone=" . urlencode($phone));
exit();
}

// Проверка совпадения паролей
if ($password !== $confirm_password) {
$error = "Пароли не совпадают.";
header("Location: registration.php?error=" . urlencode($error) . "&name=" . urlencode($name) . "&email=" . urlencode($login) . "&phone=" . urlencode($phone));
exit();
}

// Проверка существования пользователя с таким email
$stmt = $conn->prepare("SELECT * FROM users WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
// Пользователь с таким email уже существует
$error = "Пользователь с таким email уже существует.";

    echo "<script>alert('$error');</script>";
exit();
}

// Хеширование пароля
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Подготовленный запрос для предотвращения SQL-инъекций
$stmt = $conn->prepare("INSERT INTO users (name, login, password, phone) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $login, $hashed_password, $phone);

// Выполнение запроса
if ($stmt->execute()) {
echo "Пользователь успешно зарегистрирован.";
header('Location: login.php');
exit();
} else {
echo "Ошибка: " . $stmt->error;
}

// Закрытие соединения
$stmt->close();
$conn->close();
?>