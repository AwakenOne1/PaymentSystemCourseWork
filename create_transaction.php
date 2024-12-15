<?php
include 'db.php';
include 'log_transaction.php';
include 'TransactionStatus_enum.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_transaction'])) {
    $sum = $_POST['sum'];
    $destination = trim($_POST['destination']);
    $comment = trim($_POST['comment'] ?? '');
    $payment_system_id = $_POST['payment_system_id'];
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    var_dump($payment_system_id);

    if (!is_numeric($sum) || empty($destination) || $destination === '' || strlen($destination) > 150 || strlen($comment) > 150 || $sum <= 0) {
        $_SESSION['error_message'] = "Некорректные данные.";
        header('Location: transactions.php');
        exit();
    }

    $status = TransactionStatus::IN_PROCESS;

    $stmt = $conn->prepare("INSERT INTO transactions (Sum, Destination, Comment, UserId, payment_system_id, Status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('dssiss', $sum, $destination, $comment, $user_id, $payment_system_id, $status);

    if ($stmt->execute()) {
        $transactionId = $stmt->insert_id;
        $stmt->close();

        $changes = json_encode([
            'sum' => $sum,
            'destination' => $destination,
            'comment' => $comment,
            'payment_system_id' => $payment_system_id,
            'status' => $status
        ]);

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
