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


$systems_result = $conn->query("SELECT Id, Name FROM payment_systems");
$payment_systems = [];
while ($system = $systems_result->fetch_assoc()) {
    $payment_systems[] = $system;
}

$_SESSION['payment_systems'] = $payment_systems;


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
        LEFT JOIN payment_systems ps ON t.Payment_System_Id = ps.Id
        WHERE t.Sum BETWEEN ? AND ?";

    $params = [$minSum, $maxSum];
    $types = 'dd'; 

    if (!empty($destination)) {
        $sql .= " AND t.Destination REGEXP ?";
        $params[] = $destination;
        $types .= 's'; 
    }

    if ($user_role === 'user') {
        $sql .= " AND t.UserId = ?";
        $params[] = $user_id;
        $types .= 'i'; 
    }

    if ($user_role === 'moderator') {
        $sql .= " AND t.Payment_System_Id = ?";
        $params[] = $user['payment_system_id'];
        $types .= 'i'; 
    }

    $sql .= " GROUP BY t.Id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
} else {
    
    if ($user_role === 'admin') {
        $sql = "SELECT * FROM transactions_with_ratings";
        $transactions_result = $conn->query($sql);
    } elseif ($user_role === 'moderator') {
        $sql = "SELECT * FROM transactions_with_ratings WHERE Payment_System_Id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user['payment_system_id']);
        $stmt->execute();
        $transactions_result = $stmt->get_result();
    } else {
        $sql = "SELECT * FROM transactions_with_ratings WHERE UserId = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $transactions_result = $stmt->get_result();
    }
    $transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);
}


$systems_result = $conn->query("SELECT Id, Name FROM payment_systems");
$payment_systems = [];
if ($systems_result) {
    while ($system = $systems_result->fetch_assoc()) {
        $payment_systems[] = $system;
    }
}
$_SESSION['paymentsystems'] = $payment_systems;

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
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        header {
            display: flex;
            align-items: center;
            padding: 1.5em;
            background-color: #00bcd4;
            color: white;
        }

        .header-left, .header-right {
            display: flex;
            align-items: center;
        }

        .user-info {
        }

        .nav-tabs {
            display: flex;
            margin: 0;
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
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
            margin-top: 20px;
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
            background-color: #00bcd4;
            color: white;
            margin-right: 10px;
        }
        .edit-button:hover {
            background-color: #008ba3;
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
            gap: 20px;
            margin: 1em 0;
        }
        .search-button {
        }
        .create-button {
        }
        .create-button, .search-button {
            background-color: #00bcd4;
            color: white;
            padding: 1em 2em;
            font-size: 1.2em;
            border: none;
            border-radius: 0.25em;
            cursor: pointer;
            width: auto;
        }
        .create-button:hover, .search-button:hover {
            background-color: #008ba3;
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

        button, 
        .button, 
        input[type="submit"],
        .create-button, 
        .search-button,
        .edit-button,
        .delete-button {
            background-color: #00bcd4;
            color: white;
            padding: 0.5em 1em;
            border: none;
            border-radius: 0.25em;
            cursor: pointer;
            font-size: 1em;
        }

        button:hover, 
        .button:hover, 
        input[type="submit"]:hover,
        .create-button:hover, 
        .search-button:hover,
        .edit-button:hover,
        .delete-button:hover {
            background-color: #008ba3;
        }

        select,
        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="password"],
        textarea {
            border: 1px solid #00bcd4;
            border-radius: 0.25em;
            padding: 0.5em;
        }

        select:focus,
        input:focus,
        textarea:focus {
            outline: 2px solid #00bcd4;
            border-color: #00bcd4;
        }

        .button-container {
            display: flex;
            gap: 20px;
            margin: 1em 0;
        }

        main {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
            margin-top: 20px;
        }

        a {
            color: #00bcd4;
            text-decoration: none;
        }

        a:hover {
            color: #008ba3;
        }

        .nav-tabs a {
            color: white;
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
                    <td><?php echo htmlspecialchars($transaction['PaymentSystems']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['Status']); ?></td>
                    <td>
                        <div style="display: flex; align-items: center;">
                                <span style="margin-left: 10px;"><?php echo htmlspecialchars($transaction['PaymentSystemRating']); ?></span>
                        </div>
                     <?php if($user_role === 'user'): ?>
                    <td>
                        <?php if ($transaction['Status'] === 'cancelled' || $transaction['Status'] === 'completed'): ?>
                            <div style="display: flex; align-items: center;">
                                <?php if ($transaction['HasUserRating'] == 0): ?>
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
            <button class="create-button" onclick="openCreateModal()">Создать транзакцию</button>
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
            const rating = document.getElementById('rating_' + transactionId).value;
            
            fetch('update_rating.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'transaction_id=' + transactionId + '&rating=' + rating
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                if (result === "Оценка успешно добавлена") {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при отправке оценки');
            });
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