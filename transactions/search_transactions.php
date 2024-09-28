<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$minSum = $_POST['minSum'] ?? 0; // Значение по умолчанию
$maxSum = $_POST['maxSum'] ?? 1000; // Значение по умолчанию
$destination = $_POST['destination'] ?? '';

// Подготовка SQL-запроса
$sql = "SELECT * FROM transactions WHERE UserId = ? AND Sum BETWEEN ? AND ?";
$params = [$user_id, $minSum, $maxSum];

if (!empty($destination)) {
    $sql .= " AND Destination REGEXP ?";
    $params[] = $destination;
}

// Подготовка и выполнение запроса
$stmt = $conn->prepare($sql);
$types = str_repeat('i', count($params));
if (!empty($destination)) {
    $types .= 's';
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$transactions = $result->fetch_all(MYSQLI_ASSOC);

// Генерация HTML для таблицы транзакций
if (count($transactions) === 0) {
    echo '<tr><td colspan="5">Нет транзакций, соответствующих критериям поиска.</td></tr>';
} else {
    foreach ($transactions as $transaction) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($transaction['Id']) . '</td>';
        echo '<td>' . htmlspecialchars($transaction['Sum']) . '</td>';
        echo '<td>' . htmlspecialchars($transaction['Destination']) . '</td>';
        echo '<td>' . htmlspecialchars($transaction['Comment']) . '</td>';
        echo '<td>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <button class="edit-button" onclick="openEditModal(' . $transaction['Id'] . ', \'' . htmlspecialchars($transaction['Sum']) . '\', \'' . htmlspecialchars($transaction['Destination']) . '\', \'' . htmlspecialchars($transaction['Comment']) . '\')">Редактировать</button>
                    <button class="delete-button" onclick="deleteTransaction(' . $transaction['Id'] . ')">Удалить</button>
                </div>
              </td>';
        echo '</tr>';
    }
}

$conn->close();
?>