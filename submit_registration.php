<?php
// submit_registration.php
include 'db.php';
session_start();

// Валидация данных
$name = trim($_POST['name']);
$login = trim($_POST['email']);
$password = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);
$phone = trim($_POST['phone']);

// Проверка на пустые поля
if ((empty($name) || $name === "") || empty($login) || empty($password) || empty($confirm_password) || (empty($phone) || $phone == "")) {
    $_SESSION['error'] = "Заполните все поля.";
    header("Location: registration.php");
    exit();
}

// Проверка длины пароля
if (strlen($password) < 8) {
    $_SESSION['error'] = "Пароль должен содержать не менее 8 символов.";
    header("Location: registration.php");
    exit();
}

// Проверка совпадения паролей
if ($password !== $confirm_password) {
    $_SESSION['error'] = "Пароли не совпадают.";
    header("Location: registration.php");
    exit();
}

// Проверка существования пользователя с таким email
$stmt = $conn->prepare("SELECT * FROM users WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Пользователь с таким email уже существует.";
    header("Location: registration.php");
    exit();
}

// Хеширование пароля
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Подготовленный запрос для предотвращения SQL-инъекций
$stmt = $conn->prepare("INSERT INTO users (name, login, password, phone, role) VALUES (?, ?, ?, ?, 'user')");
$stmt->bind_param("ssss", $name, $login, $hashed_password, $phone);

// Выполнение запроса
if ($stmt->execute()) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
} else {
    echo "Ошибка: " . $stmt->error;
}

// Закрытие соединения
$stmt->close();
$conn->close();
?>