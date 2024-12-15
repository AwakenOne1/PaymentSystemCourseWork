<?php
include 'db.php'; 
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

    
    $stmt = $conn->prepare("DELETE FROM transaction_logs WHERE UserId = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close(); 

    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close(); 

} else {
    
    header("Location: users.php?error=ID пользователя не указан");
    exit();
}

$conn->close();
header('Location: users.php');
exit();
?>