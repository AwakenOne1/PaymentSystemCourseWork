<?php
include 'db.php';
session_start();

// Проверка, если пользователь вошел в систему
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Удаление транзакции
if (isset($_GET['id'])) {
    $transactionId = intval($_GET['id']);
    $conn->query("DELETE FROM transactions WHERE Id = $transactionId");
}

header('Location: transactions.php'); // Перенаправление обратно на страницу транзакций
exit();
?>