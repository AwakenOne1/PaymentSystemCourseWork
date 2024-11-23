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
    $status = trim($_POST['status']);

    $user_role = $_SESSION['user_role'];
    if ($user_role === 'admin') {
        // Администратор может редактировать все поля
        $payment_system_id = trim($_POST['payment_system_id']);
        if (!is_numeric($sum) || empty($destination) || strlen($destination) > 150 || strlen($comment) > 150 || $sum <= 0 || empty($status) || empty($payment_system_id)) {
            $_SESSION['error_message'] = "Некорректные данные.";
            header('Location: transactions.php');
            exit();
        }

        $stmt = $conn->prepare("UPDATE transactions SET Sum = ?, Destination = ?, Comment = ?, payment_system_id = ?, Status = ? WHERE Id = ?");
        $stmt->bind_param('dssisi', $sum, $destination, $comment, $payment_system_id, $status, $transactionId);
    } elseif ($user_role === 'moderator') {
        // Модератор не может редактировать payment_system_id
        if (!is_numeric($sum) || empty($destination) || strlen($destination) > 150 || strlen($comment) > 150 || $sum <= 0 || empty($status)) {
            $_SESSION['error_message'] = "Некорректные данные.";
            header('Location: transactions.php');
            exit();
        }

        $stmt = $conn->prepare("UPDATE transactions SET Sum = ?, Destination = ?, Comment = ?, Status = ? WHERE Id = ?");
        $stmt->bind_param('dsssi', $sum, $destination, $comment, $status, $transactionId);
    } else {
        $_SESSION['error_message'] = "У вас нет прав на редактирование.";
        header('Location: transactions.php');
        exit();
    }

    // Выполняем запрос
    if ($stmt->execute()) {
        $stmt->close();

        // Логирование изменений
        $changes = [];
        if ($oldData['Sum'] != $sum)
            $changes['sum'] = ['old' => $oldData['Sum'], 'new' => $sum];
        if ($oldData['Destination'] != $destination)
            $changes['destination'] = ['old' => $oldData['Destination'], 'new' => $destination];
        if ($oldData['Comment'] != $comment)
            $changes['comment'] = ['old' => $oldData['Comment'], 'new' => $comment];
        if ($user_role === 'admin' && $oldData['payment_system_id'] != $payment_system_id)
            $changes['payment_system_id'] = ['old' => $oldData['payment_system_id'], 'new' => $payment_system_id];
        if ($oldData['Status'] != $status)
            $changes['status'] = ['old' => $oldData['Status'], 'new' => $status];

        if (!empty($changes)) {
            logTransaction($conn, $transactionId, $_SESSION['user_id'], 'Edit', json_encode($changes));
        }

        header('Location: transactions.php');
        exit();
    } else {
        $_SESSION['error_message'] = "Ошибка выполнения запроса: " . $stmt->error;
        header('Location: transactions.php');
        exit();
    }
}


$conn->close();
?>
