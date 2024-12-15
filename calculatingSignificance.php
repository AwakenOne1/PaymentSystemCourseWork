<?php
include 'db.php';
session_start();


$fieldOrder = $_POST['fieldOrder'];

if (empty($fieldOrder)) {
    
    $fieldOrder = "cashback,execution_time,rating,commission";
}

$fields = explode(",", $fieldOrder);
var_dump($fields); 

$weights = array();
foreach ($fields as $key => $field) {
    switch ($key) {
        case 0:
            $weights[$field] = 0.4;
            break;
        case 1:
            $weights[$field] = 0.3;
            break;
        case 2:
            $weights[$field] = 0.2;
            break;
        case 3:
            $weights[$field] = 0.1;
            break;
    }
}


$sql = "SELECT MAX(execution_time) AS max_execution_time, MAX(cashback) AS max_cashback, MAX(rating) AS max_rating, MAX(commission) AS max_commission FROM paymentsystems";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$max_execution_time = $row['max_execution_time'];
$max_cashback = $row['max_cashback'];
$max_rating = $row['max_rating'];
$max_commission = $row['max_commission'];


$sql = "SELECT * FROM paymentsystems";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $execution_time_normalized = ($max_execution_time - $row['execution_time']) / $max_execution_time;
    $cashback_normalized = $row['cashback'] / $max_cashback;
    $rating_normalized = $row['rating'] / $max_rating;
    $commission_normalized = ($max_commission - $row['commission']) / $max_commission;

    $sum_significance = 0;
    foreach ($fields as $field) {
        if ($field == 'execution_time') {
            $sum_significance += $execution_time_normalized * $weights['execution_time'];
        } elseif ($field == 'cashback') {
            $sum_significance += $cashback_normalized * $weights['cashback'];
        } elseif ($field == 'rating') {
            $sum_significance += $rating_normalized * $weights['rating'];
        } elseif ($field == 'commission') {
            $sum_significance += $commission_normalized * $weights['commission'];
        }
    }

    $id = $row['id'];
    $sql_update = "UPDATE paymentsystems SET sum_significance = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("di", $sum_significance, $id);
    $stmt->execute();
}

$stmt->close();
$conn->close();

header('Location: paymentSystems.php'); 
?>