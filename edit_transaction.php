<?php
include 'db.php';
include 'log_transaction.php';  // Подключаем файл с функцией логирования

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_transaction'])) {
    $transactionId = $_POST['transaction_id'];
    $sum = $_POST['sum'];
    $destination = trim($_POST['destination']);
    $comment = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['user_id'];

    // Проверка валидации
    if (!is_numeric($sum) || empty($destination) || strlen($destination) > 150 || strlen($comment) > 150 || $sum <= 0) {
        $_SESSION['error_message'] = "Некорректные данные.";
        header('Location: transactions.php');
        exit();
    }

    // Получаем старые данные транзакции
    $stmt = $conn->prepare("SELECT Sum, Destination, Comment FROM transactions WHERE Id = ?");
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldData = $result->fetch_assoc();
    $stmt->close();

    // Выполнение запроса на обновление
    $stmt = $conn->prepare("UPDATE transactions SET Sum = ?, Destination = ?, Comment = ? WHERE Id = ?");
    $stmt->bind_param('dssi', $sum, $destination, $comment, $transactionId);

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

        // Логирование изменений
        if (!empty($changes)) {
            logTransaction($conn, $transactionId, $user_id, 'Edit', json_encode($changes));
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