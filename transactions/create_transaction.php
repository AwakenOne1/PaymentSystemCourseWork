<?php
include 'includes/includes/db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_transaction'])) {
    $sum = $_POST['sum'];
    $destination = $_POST['destination'];
    $comment = $_POST['comment'] ?? '';
    $user_id = $_SESSION['user_id']; // Получаем UserId из сессии

    // Проверка валидации
    if (!is_numeric($sum) || empty($destination) || strlen($destination) > 150 || strlen($comment) > 150) {
        $_SESSION['error_message'] = "Некорректные данные.";
        header('Location: ../index.php');
        exit();
    }

    // Выполнение запроса
    $stmt = $conn->prepare("INSERT INTO transactions (Sum, Destination, Comment, UserId) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('dssi', $sum, $destination, $comment, $user_id);

    if ($stmt->execute()) {
        header("Location: ../index.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Ошибка выполнения запроса: " . $stmt->error;
        header('Location: ../index.php');
        exit();
    }

    $stmt->close();
}
$conn->close();
?>