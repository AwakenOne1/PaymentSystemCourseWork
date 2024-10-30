<?php
include 'db.php';
include 'log_transaction.php';  // Подключаем файл с функцией логирования

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_transaction'])) {
    $sum = $_POST['sum'];
    $destination = trim($_POST['destination']);
    $comment = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];

    // Проверка валидации
    if (!is_numeric($sum) || empty($destination) || strlen($destination) > 150 || strlen($comment) > 150 || $sum <= 0) {
        $_SESSION['error_message'] = "Некорректные данные.";
        header('Location: transactions.php');
        exit();
    }

    // Выполнение запроса
    $stmt = $conn->prepare("INSERT INTO transactions (Sum, Destination, Comment, UserId) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('dssi', $sum, $destination, $comment, $user_id);

    if ($stmt->execute()) {
        $transactionId = $stmt->insert_id;
        $stmt->close();

        // Логирование создания транзакции
        $changes = json_encode([
            'sum' => $sum,
            'destination' => $destination,
            'comment' => $comment
        ]);

        // Вызов функции логирования
        logTransaction($conn, $transactionId, $user_id, 'Create', $changes);

        header('Location: transactions.php');
        exit();
    } else {
        $stmt->close();
        $_SESSION['error_message'] = "Ошибка выполнения запроса: " . $stmt->error;
        header('Location: transactions.php');
        exit();
    }
}

$conn->close();
?>