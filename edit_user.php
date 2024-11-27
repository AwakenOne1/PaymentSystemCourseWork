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
    $phone = trim($_POST['phone'], "+");
    $role = trim($_POST['role']);
    $payment_system_id = isset($_POST['payment_system_id']) && $_POST['payment_system_id'] !== ''
        ? intval($_POST['payment_system_id'])
        : null; // Если пустое, установим NULL
    $userId = intval($_POST['user_id']); // Получаем ID пользователя из формы

    // Проверка валидации
    if (empty($phone) || empty($name) || empty($login) || empty($role)) {
        $_SESSION['error_message'] = "Некорректные данные.";
        header('Location: transactions.php');
        exit();
    }

    // Проверка прав администратора
    if ($user_role === UserRole::ADMIN) {
        $stmt = $conn->prepare(
            "UPDATE users SET name = ?, login = ?, phone = ?, role = ?, payment_system_id = ? WHERE id = ?"
        );
        $stmt->bind_param(
            "ssssii",
            $name,
            $login,
            $phone,
            $role,
            $payment_system_id, // Если NULL, будет автоматически преобразовано
            $userId
        );
    } elseif ($user_role === UserRole::MODERATOR && $role === UserRole::USER) {
        // Модератор не может менять payment_system_id
        $stmt = $conn->prepare(
            "UPDATE users SET name = ?, login = ?, phone = ?, role = ? WHERE id = ?"
        );
        $stmt->bind_param(
            "sssii",
            $name,
            $login,
            $phone,
            $role,
            $userId
        );
    } else {
        $_SESSION['error_message'] = "У вас нет прав на изменение данных.";
        header('Location: transactions.php');
        exit();
    }

    // Выполняем запрос
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: users.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Ошибка выполнения запроса.";
        header('Location: transactions.php');
        exit();
    }
}


$conn->close();
?>
