
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/auth.php';
include 'includes/db.php';

// Выход из системы
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="error-message">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT login, name FROM users WHERE Id = $user_id");
$user = $result->fetch_assoc();

// Обработка поиска
$minSum = 0.00;
$maxSum = 99999999999.99;
$destination = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Проверка на сброс формы
    if (isset($_POST['resetSearch']) && $_POST['resetSearch'] === '1') {
        // Если форма сброшена, устанавливаем значения по умолчанию
        $minSum = 0.00;
        $maxSum = 99999999999.99;
        $destination = '';
    } else {
        // Иначе обрабатываем значения с формы
        $minSum = number_format((float) $_POST['minSum'], 2, '.', '');
        $maxSum = number_format((float) $_POST['maxSum'], 2, '.', '');
        $destination = $_POST['destination'];
    }

    // Подготовка SQL-запроса
    $sql = "SELECT * FROM transactions WHERE UserId = ? AND Sum BETWEEN ? AND ?";
    $params = [$user_id, $minSum, $maxSum];

    if (!empty($destination)) {
        $sql .= " AND Destination REGEXP ?";
        $params[] = $destination;
    }

    // Подготовка и выполнение запроса
    $stmt = $conn->prepare($sql);

    // Генерация строки типов для bind_param
    $types = 'i'; // тип для user_id
    $types .= 'dd'; // типы для minSum и maxSum
    if (!empty($destination)) {
        $types .= 's'; // тип для destination
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Получение всех транзакций, если поиск не выполнялся
    $transactions_result = $conn->query("SELECT * FROM transactions WHERE UserId = $user_id");
    $transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);
}

// Закрытие соединения после выполнения всех запросов
$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Транзакции</title>
    <link rel="stylesheet" href="static.css">
    <style>
       
    </style>
</head>
<body>
<header>
    <div><?php echo htmlspecialchars($user['login']); ?> (<?php echo htmlspecialchars($user['name']); ?>)</div>
    <div><a href="?action=logout">Выйти</a></div>
</header>
<main>
    <h1>Ваши транзакции</h1>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Сумма</th>
                <th>Кому</th>
                <th>Комментарий</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['Id']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['Sum']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['Destination']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['Comment']); ?></td>
                    <td>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <button class="edit-button" onclick="openEditModal(<?php echo $transaction['Id']; ?>, '<?php echo htmlspecialchars($transaction['Sum']); ?>', '<?php echo htmlspecialchars($transaction['Destination']); ?>', '<?php echo htmlspecialchars($transaction['Comment']); ?>')">Редактировать</button>
                            <button class="delete-button" onclick="deleteTransaction(<?php echo $transaction['Id']; ?>)">Удалить</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="button-container">
        <button class="search-button" onclick="openSearchModal()">Поиск по критериям</button>
        <button class="create-button" onclick="openCreateModal()">Создать транзакцию</button>
    </div>
</main>

<?php include 'transactions/transactions_modal.php'; ?>
<script src="transactions/script_transactions.js"></script>

</body>
</html>
