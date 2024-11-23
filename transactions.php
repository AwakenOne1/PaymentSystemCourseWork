<?php
include 'db.php';
session_start();
function getTransactionChanges($transactionId, $conn)
{
    $sql = "SELECT Action, Changes, Timestamp FROM transaction_logs WHERE TransactionId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $changes = $result->fetch_all(MYSQLI_ASSOC);

    $output = '';
    foreach ($changes as $change) {
        $output .= $change['Action'] . ': ' . $change['Changes'] . ' (' . $change['Timestamp'] . ')<br>';
    }

    return $output;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

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
$user_role = $_SESSION['user_role'];

$result = $conn->query("SELECT login, name, payment_system_id FROM users WHERE Id = $user_id");
$user = $result->fetch_assoc();

// Получаем список платежных систем
$systems_result = $conn->query("SELECT Id, Name FROM paymentsystems");
$payment_systems = [];
while ($system = $systems_result->fetch_assoc()) {
    $payment_systems[] = $system;
}

$_SESSION['paymentsystems'] = $payment_systems;

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
    $sql = "SELECT t.*, 
               ps.Name AS PaymentSystems, 
               t.Status, 
               GROUP_CONCAT(CONCAT(tl.Action, ': ', tl.Changes, ' (', tl.Timestamp, ')') SEPARATOR '<br>') AS Changes
        FROM transactions t
        LEFT JOIN transaction_logs tl ON t.Id = tl.TransactionId
        LEFT JOIN payment_systems ps ON t.Payment_System_Id = ps.Id
        WHERE t.Sum BETWEEN ? AND ?";

    $params = [$minSum, $maxSum];

    if (!empty($destination)) {
        $sql .= " AND t.Destination REGEXP ?";
        $params[] = $destination;
    }

    // Проверка роли для выбора транзакций
    if ($user_role === 'user') {
        $sql .= " AND t.UserId = ?";
        $params[] = $user_id;
    }

    // Если модератор, фильтруем транзакции по платежной системе
    if ($user_role === 'moderator') {
        $sql .= " AND t.Payment_System_Id = ?";
        $params[] = $user['payment_system_id'];  // Используем payment_system_id текущего пользователя
    }

    $sql .= " GROUP BY t.Id";

    // Подготовка и выполнение запроса
    $stmt = $conn->prepare($sql);
    $types = 'dd'; // типы для minSum и maxSum
    if (!empty($destination)) {
        $types .= 's'; // тип для destination
    }
    if ($user_role !== 'admin') {
        $types .= 'i'; // тип для UserId
    }
    if ($user_role === 'moderator') {
        $types .= 'i'; // тип для PaymentSystemId
    }

    // Привязка параметров
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Получение всех транзакций, если поиск не выполнялся
    if ($user_role === 'admin') {
        $sql = "SELECT t.*, 
                       GROUP_CONCAT(CONCAT(u.Id, ' (', u.Role, ') ', tl.Action, ' - ', tl.Timestamp, ': ', tl.Changes) SEPARATOR '<br>') as Changes
                FROM transactions t
                LEFT JOIN transaction_logs tl ON t.Id = tl.TransactionId
                LEFT JOIN users u ON tl.UserId = u.Id
                GROUP BY t.Id";
        $transactions_result = $conn->query($sql);
    } elseif ($user_role === 'moderator') {
        $sql = "SELECT t.*, 
                       GROUP_CONCAT(CONCAT(u.Id, ' (', u.Role, ') ', tl.Action, ' - ', tl.Timestamp, ': ', tl.Changes) SEPARATOR '<br>') as Changes
                FROM transactions t
                LEFT JOIN transaction_logs tl ON t.Id = tl.TransactionId
                LEFT JOIN users u ON tl.UserId = u.Id
                WHERE t.Payment_System_Id = ?
                GROUP BY t.Id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user['payment_system_id']);  // Используем payment_system_id текущего пользователя
        $stmt->execute();
        $transactions_result = $stmt->get_result();
    } else {
        $sql = "SELECT t.*, 
                       GROUP_CONCAT(CONCAT(u.Id, ' (', u.Role, ') ', tl.Action, ' - ', tl.Timestamp, ': ', tl.Changes) SEPARATOR '<br>') as Changes
                FROM transactions t
                LEFT JOIN transaction_logs tl ON t.Id = tl.TransactionId
                LEFT JOIN users u ON tl.UserId = u.Id
                WHERE t.UserId = ?
                GROUP BY t.Id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $transactions_result = $stmt->get_result();
    }
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
        /* Стили для страницы */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        header {
            display: flex;
            justify-content: space-between; /* Это оставит пространство между тремя основными блоками */
            align-items: center;
            padding: 1.5em;
            background-color: #4CAF50;
            color: white;
        }

        .header-left, .header-right {
            display: flex;
            align-items: center;
        }

        .user-info {
            /* Уберите margin-right: auto; */
        }

        .nav-tabs {
            display: flex;
            justify-content: center; /* Центрирование вкладок */
            flex-grow: 1; /* Позволяет занять оставшееся пространство, но мы это уберем для строгого центрирования */
            position: absolute; /* Позиционирование относительно header */
            left: 50%; /* Сдвиг на 50% ширины родителя */
            transform: translateX(-50%); /* Коррекция позиции на 50% своей ширины */
        }

        .logout a {
            text-decoration: none;
            color: white;
        }

            .logout a:hover {
                text-decoration: underline;
                color: white;
            }

        .nav-tabs a {
            color: white;
            text-decoration: none;
            padding: 0 1em;
        }

            .nav-tabs a:hover {
                text-decoration: underline;
            }
        main {
            padding: 2em;
            display: flex;
            flex-direction: column;
            align-items: flex-start; /* Выравнивание по левому краю */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2em;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 1em;
            text-align: left;
            word-wrap: break-word;
        }
        th:nth-child(1), td:nth-child(1) {
            width: 10%;
        }
        th:nth-child(2), td:nth-child(2) {
            width: 15%;
        }
        th:nth-child(3), td:nth-child(3) {
            width: 25%;
        }
        th:nth-child(4), td:nth-child(4) {
            width: 30%;
        }
        th:nth-child(5), td:nth-child(5) {
            width: 10%;
        }
        .edit-button, .delete-button {
            padding: 0.8em 0.8em;
            font-size: 1em;
            border: none;
            border-radius: 0.25em;
            cursor: pointer;
        }
        .edit-button {
            background-color: #4CAF50;
            color: white;
            margin-right: 0.5em;
        }
        .edit-button:hover {
            background-color: #45a049;
        }
        .delete-button {
            background-color: #f44336;
            color: white;
        }
        .delete-button:hover {
            background-color: #e53935;
        }
        .button-container {
            display: flex;
            align-items: center;
            width: 100%;
            margin: 1em 0;
        }
        .search-button {
            margin-right: 0.5em; /* Уменьшено расстояние между кнопками */
        }
        .create-button {
            /* Никаких дополнительных маргинов для центрирования */
        }
        .create-button, .search-button {
            background-color: #4CAF50;
            color: white;
            padding: 1em 2em;
            font-size: 1.2em;
            border: none;
            border-radius: 0.25em;
            cursor: pointer;
            width: auto;
        }
        .create-button:hover, .search-button:hover {
            background-color: #45a049;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            padding: 2em;
            border-radius: 0.5em;
            width: 25%;
            max-width: 600px;
        }
        .close {
            float: right;
            font-size: 1.2em;
            cursor: pointer;
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <div class="user-info"><?php echo htmlspecialchars($user['login']); ?> (<?php echo htmlspecialchars($user['name']); ?>)</div>
    </div>
    <nav class="nav-tabs">
        <a href="transactions.php">Транзакции</a>
        <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
            <a href="users.php">Пользователи</a>
        <?php endif; ?>
    </nav>
    <div class="header-right">
        <div class="logout"><a href="?action=logout">Выйти</a></div>
    </div>
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
                <th>Платежная система</th>
                <th>Статус</th>
                <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
                    <th>Действия</th>
                    <?php if ($user_role === 'admin'): ?>
                        <th>Изменения</th>
                    <?php endif; ?>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['Id']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['Sum']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['Destination']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['Comment']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['payment_system_id']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['status']); ?></td>
                    <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
                        <td>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <button class="edit-button" onclick="openEditModal(<?php echo $transaction['Id']; ?>, '<?php echo htmlspecialchars($transaction['Sum']); ?>', '<?php echo htmlspecialchars($transaction['Destination']); ?>', '<?php echo htmlspecialchars($transaction['Comment']); ?>')">Редактировать</button>
                                <button class="delete-button" onclick="deleteTransaction(<?php echo $transaction['Id']; ?>)">Удалить</button>
                            </div>
                        </td>
                    <?php endif; ?>
                    <?php if ($user_role === 'admin'): ?>
                    <td>
                        <?php if (!empty($transaction['Changes'])): ?>
                            <details>
                                <summary>История изменений</summary>
                                <div><?php echo $transaction['Changes']; ?></div>
                            </details>
                        <?php else: ?>
                            Нет изменений
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>

            <?php endforeach; ?>
        </tbody>

</table>

    <div class="button-container">
        <button class="search-button" onclick="openSearchModal()">Поиск по критериям</button>
         <?php if ($user_role === 'admin' || $user_role === 'user'): ?>
            <button class="create-button" onclick="openCreateModal()">Создать транзакцию</button>
         <?php endif; ?> 
    </div>
</main>

    <?php include 'transactions_modal.php'; ?>

    <script>
        function openCreateModal() {
             console.log('openCreateModal вызвана');
            document.getElementById('createModal').style.display = 'flex';
        }

        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        function openSearchModal() {
            document.getElementById('searchModal').style.display = 'flex';
        }

        function closeSearchModal() {
            document.getElementById('searchModal').style.display = 'none';
        }

        function openEditModal(id, sum, destination, comment) {
            document.getElementById('transaction_id').value = id;
            document.getElementById('edit_sum').value = sum;
            document.getElementById('edit_destination').value = destination;
            document.getElementById('edit_comment').value = comment;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function deleteTransaction(id) {
            if (confirm('Вы уверены, что хотите удалить эту транзакцию?')) {
                window.location.href = 'delete_transaction.php?id=' + id;
            }
        }
    
        window.onclick = function(event) {
            if (event.target == document.getElementById('createModal')) {
                closeCreateModal();
            } else if (event.target == document.getElementById('searchModal')) {
                closeSearchModal();
            } else if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        };
    </script>

</body>
</html>