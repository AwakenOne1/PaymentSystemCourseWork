<?php

include 'db.php';
session_start();


$name = trim($_POST['name']);
$login = trim($_POST['email']);
$password = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);
$phone = trim($_POST['phone']);


if ((empty($name) || $name === "") || empty($login) || empty($password) || empty($confirm_password) || (empty($phone) || $phone == "")) {
    $_SESSION['error'] = "Заполните все поля.";
    header("Location: registration.php");
    exit();
}


if (strlen($password) < 8) {
    $_SESSION['error'] = "Пароль должен содержать не менее 8 символов.";
    header("Location: registration.php");
    exit();
}


if ($password !== $confirm_password) {
    $_SESSION['error'] = "Пароли не совпадают.";
    header("Location: registration.php");
    exit();
}


$stmt = $conn->prepare("SELECT * FROM users WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Пользователь с таким email уже существует.";
    header("Location: registration.php");
    exit();
}


$hashed_password = password_hash($password, PASSWORD_DEFAULT);


$stmt = $conn->prepare("INSERT INTO users (name, login, password, phone, role) VALUES (?, ?, ?, ?, 'user')");
$stmt->bind_param("ssss", $name, $login, $hashed_password, $phone);


if ($stmt->execute()) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
} else {
    echo "Ошибка: " . $stmt->error;
}


$stmt->close();
$conn->close();
?>