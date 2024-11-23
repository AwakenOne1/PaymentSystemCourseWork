<?php
include 'db.php';
include 'log_transaction.php';  // Подключаем файл с функцией логирования
include 'TransactionStatus_enum.php';  // Подключаем класс для статусов транзакций

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_transaction'])) {
    $transactionId = trim($_POST['transaction_id']);
    $sum = trim($_POST['sum']);
    $destination = trim($_POST['destination']);
    $comment = trim($_POST['comment'] ?? '');
    $payment_system_id = trim($_POST['payment_system_id']);
    $status = trim($_POST['status']);  // Получаем новый статус из формы

    // Проверка валидации
    if (!is_numeric($sum) || empty($destination) || $destination == "" || strlen($destination) > 150 || strlen($comment) > 150 || $sum <= 0 || !is_numeric($payment_system_id) || !TransactionStatus::isValidStatus($status)) {
        $_SESSION['error_message'] = "Некорректные данные.";
        header('Location: transactions.php');
        exit();
    }

    // Получаем старые данные транзакции
    $stmt = $conn->prepare("SELECT Sum, Destination, Comment, payment_system_id, Status FROM transactions WHERE Id = ?");
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldData = $result->fetch_assoc();
    $stmt->close();

    // Выполнение запроса на обновление
    $stmt = $conn->prepare("UPDATE transactions SET Sum = ?, Destination = ?, Comment = ?, payment_system_id = ?, Status = ? WHERE Id = ?");
    $stmt->bind_param('dssisi', $sum, $destination, $comment, $payment_system_id, $status, $transactionId);

    if ($stmt->execute()) {
        $stmt->close();

        // Подготовка данных для логирования
        $changes = [];
        if ($oldData['Sum'] != $sum)
            $changes['sum'] = ['old' => $oldData['Sum'], 'new' => $sum];
        if ($oldData['Destination'] != $destination)
            $changes['destination'] = ['old' => $oldData['Destination'], 'new' => $destination];
        if ($oldData['Comment'] != $comment)
            $changes['comment'] = ['old' => $oldData['Comment'], 'new' => $comment];
        if ($oldData['payment_system_id'] != $payment_system_id)
            $changes['payment_system_id'] = ['old' => $oldData['payment_system_id'], 'new' => $payment_system_id];
        if ($oldData['Status'] != $status)
            $changes['status'] = ['old' => $oldData['Status'], 'new' => $status];

        // Логирование изменений
        if (!empty($changes)) {
            logTransaction($conn, $transactionId, $_SESSION['user_id'], 'Edit', json_encode($changes));
        }

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
