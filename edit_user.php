<?php
include 'db.php';
include 'userRole_enum.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($user_role !== 'admin' && $user_role !== 'moderator') {
    header('Location: transactions.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $name = trim($_POST['name']);
    $login = trim($_POST['login']);
    $phone = trim($_POST['phone'], "+"); // Удаление плюса, если он есть
    $role = trim($_POST['role']);
    $payment_system_id = intval($_POST['payment_system_id']); // Получение payment_system_id из формы
    $userId = intval($_POST['user_id']); // Получаем ID пользователя из формы

    // Проверка валидации
    if (empty($phone) || !is_numeric($phone) || empty($name) || empty($login) || empty($role) || empty($payment_system_id)) {
        $_SESSION['error_message'] = "Некорректные данные.";
        header('Location: transactions.php');
        exit();
    }

    // Проверка прав администратора
    if (
        ($user_role === UserRole::ADMIN && ($role === UserRole::MODERATOR || $role === UserRole::USER))
        || ($user_role === UserRole::ADMIN && $role === UserRole::ADMIN)
    ) {

        // Обновление данных пользователя в базе данных
        $stmt = $conn->prepare("UPDATE users SET name = ?, login = ?, phone = ?, role = ?, payment_system_id = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $name, $login, $phone, $role, $payment_system_id, $userId);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: users.php');
    exit();
}

$conn->close();
?>
