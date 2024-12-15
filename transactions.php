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
    if (isset($_POST['resetSearch']) && $_POST['resetSearch'] === '1') {
        $minSum = 0.00;
        $maxSum = 99999999999.99;
        $destination = '';
    } else {
        $minSum = number_format((float) $_POST['minSum'], 2, '.', '');
        $maxSum = number_format((float) $_POST['maxSum'], 2, '.', '');
        $destination = $_POST['destination'];
    }

    $sql = "SELECT t.*, 
               ps.Name AS PaymentSystems, 
               ps.Rating AS PaymentSystemRating,
               t.Status, 
               GROUP_CONCAT(CONCAT(tl.Action, ': ', tl.Changes, ' (', tl.Timestamp, ')') SEPARATOR '<br>') AS Changes
        FROM transactions t
        LEFT JOIN transaction_logs tl ON t.Id = tl.TransactionId
        LEFT JOIN paymentsystems ps ON t.Payment_System_Id = ps.Id
        WHERE t.Sum BETWEEN ? AND ?";

    $params = [$minSum, $maxSum];
    $types = 'dd'; // Типы для $minSum и $maxSum

    if (!empty($destination)) {
        $sql .= " AND t.Destination REGEXP ?";
        $params[] = $destination;
        $types .= 's'; // Тип для $destination
    }

    if ($user_role === 'user') {
        $sql .= " AND t.UserId = ?";
        $params[] = $user_id;
        $types .= 'i'; // Тип для $user_id
    }

    if ($user_role === 'moderator') {
        $sql .= " AND t.Payment_System_Id = ?";
        $params[] = $user['payment_system_id'];
        $types .= 'i'; // Тип для $payment_system_id
    }

    $sql .= " GROUP BY t.Id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Получение всех транзакций, если поиск не выполнялся
    if ($user_role === 'admin') {
        $sql = "SELECT t.*, 
                ps.Rating AS PaymentSystemRating,
                t.Status,
                       GROUP_CONCAT(CONCAT(u.Id, ' (', u.Role, ') ', tl.Action, ' - ', tl.Timestamp, ': ', tl.Changes) SEPARATOR '<br>') as Changes
                FROM transactions t
                LEFT JOIN transaction_logs tl ON t.Id = tl.TransactionId
                LEFT JOIN users u ON tl.UserId = u.Id
                LEFT JOIN paymentsystems ps ON t.Payment_System_Id = ps.Id
                GROUP BY t.Id";
        $transactions_result = $conn->query($sql);
    } elseif ($user_role === 'moderator') {
        $sql = "SELECT t.*, 
                ps.Rating AS PaymentSystemRating,
                t.Status,
                       GROUP_CONCAT(CONCAT(u.Id, ' (', u.Role, ') ', tl.Action, ' - ', tl.Timestamp, ': ', tl.Changes) SEPARATOR '<br>') as Changes
                FROM transactions t
                LEFT JOIN transaction_logs tl ON t.Id = tl.TransactionId
                LEFT JOIN users u ON tl.UserId = u.Id
                LEFT JOIN paymentsystems ps ON t.Payment_System_Id = ps.Id
                WHERE t.Payment_System_Id = ?
                GROUP BY t.Id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user['payment_system_id']);  // Используем payment_system_id текущего пользователя
        $stmt->execute();
        $transactions_result = $stmt->get_result();
    } else {
        $sql = "SELECT t.*, 
                ps.Rating AS PaymentSystemRating,
                t.Status,
                       GROUP_CONCAT(CONCAT(u.Id, ' (', u.Role, ') ', tl.Action, ' - ', tl.Timestamp, ': ', tl.Changes) SEPARATOR '<br>') as Changes
                FROM transactions t
                LEFT JOIN transaction_logs tl ON t.Id = tl.TransactionId
                LEFT JOIN users u ON tl.UserId = u.Id
                LEFT JOIN paymentsystems ps ON t.Payment_System_Id = ps.Id
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
            overflow-x:auto;
            flex-direction: column;
            align-items: flex-start; /* Выравнивание по левому краю */
        }
        table {
            width: 100%;
            max-width: 1500PX;
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
        .changes {
            max-width: 150px;
            max-height: 100px;
            overflow: auto;
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
        <a href="paymentSystems.php">Платежные системы</a>
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
                <th>Рейтинг платежной системы</th>
                <?php if($user_role === 'user'): ?>
                    <th>Оценка платежной системы</th>
                <?php endif; ?>
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
                    <td>
                        <div style="display: flex; align-items: center;">
                                <span style="margin-left: 10px;"><?php echo htmlspecialchars($transaction['PaymentSystemRating']); ?></span>
                        </div>
                     <?php if($user_role === 'user'): ?>
                    <td>
                        <?php if ($transaction['Status'] === 'cancelled' || $transaction['Status'] === 'completed'): ?>
                            <div style="display: flex; align-items: center;">
                                <?php if ($transaction['UserRated'] == 0): ?>
                                    <select name="rating_<?php echo $transaction['Id']; ?>" id="rating_<?php echo $transaction['Id']; ?>">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <button class="edit-button" onclick="updateRating(<?php echo $transaction['Id']; ?>)">Оценить</button>
                                <?php else: ?>
                                    <span>Вы уже оценили эту транзакцию</span>
                                <?php endif; ?>
                                
                            </div>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
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
                        <div class="changes">
                            <details>
                                <summary>История изменений</summary>
                                <div><?php echo $transaction['Changes']; ?></div>
                            </details>
                            </div>
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
        <?php if ($user_role === 'user'): ?>
            <a href="paymentSystems.php" class="create-button">Создать транзакцию</a>
        <?php endif; ?>    
    </div>
</main>

    <?php include 'transactions_modal.php'; ?>
    
    <script>
        function openCreateModal() {
            console.log('openCreateModal вызвано');
            document.getElementById('createModal').style.display = 'flex';
        }

        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        function openSearchModal() {
             console.log('openSearchModal вызвано');
            document.getElementById('searchModal').style.display = 'flex';
        }

        function closeSearchModal() {
            document.getElementById('searchModal').style.display = 'none';
        }
        function openEditModal(transactionId, sum, destination, comment, status, paymentSystemId) {
            console.log('openEditModal вызвано');
            document.getElementById('transaction_id').value = transactionId;
            document.getElementById('edit_sum').value = sum;
            document.getElementById('edit_destination').value = destination;
            document.getElementById('edit_comment').value = comment;
            document.getElementById('edit_status').value = status;

            const paymentSystemField = document.getElementById('payment_system');
            if (paymentSystemField) {
                paymentSystemField.value = paymentSystemId;
            }

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
        function updateRating(transactionId) {
            var rating = document.getElementById('rating_' + transactionId).value;

            // Отправка AJAX-запроса для обновления рейтинга банка
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_rating.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Обработка успешного обновления рейтинга
                    console.log(xhr.responseText);
                    // Обновление страницы после успешного обновления рейтинга
                    location.reload();
                }
            };
            xhr.send('transaction_id=' + transactionId + '&rating=' + rating);
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