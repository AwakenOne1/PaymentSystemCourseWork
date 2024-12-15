<?php
include 'db.php';
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


if (isset($_GET['id'])) {
    $transactionId = intval($_GET['id']);
    $conn->query("DELETE FROM transactions WHERE Id = $transactionId");
}

header('Location: transactions.php'); 
exit();
?>
