<?php
function logTransaction($conn, $transactionId, $userId, $action, $changes) {
    $stmt = $conn->prepare("INSERT INTO transaction_logs (TransactionId, UserId, Action, Changes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiss', $transactionId, $userId, $action, $changes);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
?>