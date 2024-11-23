<?php
include 'db.php';
include 'log_transaction.php';  // Подключаем файл с функцией логирования
include 'TransactionStatus_enum.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_transaction'])) {
    // Получаем данные из формы
    $sum = $_POST['sum'];
    $destination = trim($_POST['destination']);
    $comment = trim($_POST['comment'] ?? '');
    $payment_system_id = $_POST['payment_system_id']; // ID платежной системы
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];

    // Проверка валидации
    if (!is_numeric($sum) || empty($destination) || $destination === '' || strlen($destination) > 150 || strlen($comment) > 150 || $sum <= 0 || !is_numeric($payment_system_id)) {
        $_SESSION['error_message'] = "Некорректные данные.";
        header('Location: transactions.php');
        exit();
    }


    $status = TransactionStatus::IN_PROCESS;

    // Выполнение запроса
    $stmt = $conn->prepare("INSERT INTO transactions (Sum, Destination, Comment, UserId, payment_system_id, Status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('dssiss', $sum, $destination, $comment, $user_id, $payment_system_id, $status);

    if ($stmt->execute()) {
        $transactionId = $stmt->insert_id;
        $stmt->close();

        // Логирование создания транзакции
        $changes = json_encode([
            'sum' => $sum,
            'destination' => $destination,
            'comment' => $comment,
            'payment_system_id' => $payment_system_id,
            'status' => $status
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
