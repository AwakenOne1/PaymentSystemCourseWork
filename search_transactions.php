<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Получение данных из формы
$user_id = $_SESSION['user_id'];
$minSum = $_POST['minSum'] ?? 0; // Значение по умолчанию
$maxSum = $_POST['maxSum'] ?? 1000; // Значение по умолчанию
$destination = $_POST['destination'] ?? '';

// Вызов процедуры
$stmt = $conn->prepare("CALL search_transactions(?, ?, ?, ?)");
$stmt->bind_param("idds", $user_id, $minSum, $maxSum, $destination);
$stmt->execute();
$result = $stmt->get_result();

// Обработка результатов
$transactions = $result->fetch_all(MYSQLI_ASSOC);

// Генерация HTML для таблицы транзакций
if (count($transactions) === 0) {
  echo '<tr><td colspan="5">Нет транзакций, соответствующих критериям поиска.</td></tr>';
} else {
  foreach ($transactions as $transaction) {
    // Вывод транзакций
  }
}

$conn->close();