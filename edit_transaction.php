<?php
include 'db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_transaction'])) {
    $transactionId = $_POST['transaction_id'];
    $sum = $_POST['sum'];
    $destination = $_POST['destination'];
    $comment = $_POST['comment'] ?? '';

    // Проверка валидации
    if (!is_numeric($sum) || empty($destination) || strlen($destination) > 150 || strlen($comment) > 150 || $sum <= 0) {
        $_SESSION['error_message'] = "Некорректные данные.";
        header('Location: transactions.php');
        exit();
    }

    // Выполнение запроса
    $stmt = $conn->prepare("UPDATE transactions SET Sum = ?, Destination = ?, Comment = ? WHERE Id = ?");
    $stmt->bind_param('dsdi', $sum, $destination, $comment, $transactionId);

    if ($stmt->execute()) {
        $stmt->close(); // Закрываем соединение только при успешном выполнении запроса
        header('Location: transactions.php');
        exit();
    } else {
        $stmt->close(); // Закрываем соединение даже при ошибке выполнения запроса
        $_SESSION['error_message'] = "Ошибка выполнения запроса: " . $stmt->error;
        header('Location: transactions.php');
        exit();
    }
}
$conn->close();
?>