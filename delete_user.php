<?php
include 'db.php'; // Подключаем базу данных
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Проверяем, передан ли ID пользователя
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    // Удаление всех записей из transaction_logs, связанных с пользователем
    $stmt = $conn->prepare("DELETE FROM transaction_logs WHERE UserId = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close(); // Закрываем первый запрос

    // Теперь можно удалить пользователя
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close(); // Закрываем второй запрос

} else {
    // Если ID не передан, перенаправляем обратно с сообщением об ошибке
    header("Location: users.php?error=ID пользователя не указан");
    exit();
}

$conn->close();
header('Location: users.php');
exit();
?>