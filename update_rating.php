<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = $_POST['transaction_id'];
    $rating = $_POST['rating'];
    $user_id = $_SESSION['user_id'];

    
    $check = $conn->prepare("SELECT * FROM transaction_ratings WHERE TransactionId = ? AND UserId = ?");
    $check->bind_param('ii', $transaction_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        
        $stmt = $conn->prepare("INSERT INTO transaction_ratings (TransactionId, UserId, Rating) VALUES (?, ?, ?)");
        $stmt->bind_param('iii', $transaction_id, $user_id, $rating);
        
        if ($stmt->execute()) {
            
            $update = $conn->prepare("
                UPDATE payment_systems ps
                SET Rating = (
                    SELECT AVG(tr.Rating)
                    FROM transaction_ratings tr
                    JOIN transactions t ON tr.TransactionId = t.Id
                    WHERE t.Payment_System_Id = ps.Id
                )
                WHERE Id = (
                    SELECT Payment_System_Id 
                    FROM transactions 
                    WHERE Id = ?
                )
            ");
            $update->bind_param('i', $transaction_id);
            $update->execute();

            
            $updateRated = $conn->prepare("UPDATE transactions SET UserRated = 1 WHERE Id = ?");
            $updateRated->bind_param('i', $transaction_id);
            $updateRated->execute();

            echo "Оценка успешно добавлена";
        } else {
            echo "Ошибка при добавлении оценки";
        }
    } else {
        echo "Вы уже оценили эту транзакцию";
    }
}

$conn->close();
?> 