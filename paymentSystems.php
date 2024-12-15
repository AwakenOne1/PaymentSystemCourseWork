<?php
include 'db.php';
session_start();


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


$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$result = $conn->query("SELECT login, name FROM users WHERE Id = $user_id");
$user = $result->fetch_assoc();



$fieldOrder = isset($_POST['fieldOrder']) ? $_POST['fieldOrder'] : null;

if (empty($fieldOrder)) {
    
    $fieldOrder = "cashback,execution_time,rating,commission";
}


$fields = explode(",", $fieldOrder);


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


$sql = "SELECT MAX(execution_time) AS max_execution_time, MAX(cashback) AS max_cashback, MAX(rating) AS max_rating, MAX(commission) AS max_commission FROM payment_systems";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$max_execution_time = $row['max_execution_time'];
$max_cashback = $row['max_cashback'];
$max_rating = $row['max_rating'];
$max_commission = $row['max_commission'];
$fieldOrder = null;

$sql = "SELECT * FROM payment_systems";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $execution_time_normalized = ($max_execution_time - $row['execution_time']) / $max_execution_time;
    $cashback_normalized = $row['cashback'] / $max_cashback;
    $rating_normalized = $row['Rating'] / $max_rating;
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

    $id = $row['Id'];
    $sql_update = "UPDATE payment_systems SET sum_significance = ? WHERE Id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("di", $sum_significance, $id);
    $stmt->execute();
}

$stmt->close();


$paymentSystemsResult = $conn->query("SELECT * FROM payment_systems ORDER BY sum_significance DESC");
$paymentSystems = $paymentSystemsResult->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Платежные системы</title>
    <link rel="stylesheet" href="static.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
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

        .create-button {
            background-color: #00bcd4;
            color: white;
            padding: 0.8em 0.8em;
            border: none;
            border-radius: 0.25em;
            cursor: pointer;
            margin-right: 10px;
        }

        .create-button:hover {
            background-color: #008ba3;
        }

        /* Специфичные стили для сортировки */
        #fieldOrder {
            list-style-type: none;
            padding: 0;
            width: 300px;
            margin-bottom: 20px;
        }

        #fieldOrder li {
            padding: 10px;
            margin: 5px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: move;
        }

        .field-order-form {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-left">
            <div class="user-info">
                <?php echo htmlspecialchars($user['login'] ?? ''); ?> 
                (<?php echo htmlspecialchars($user['name'] ?? ''); ?>)
            </div>
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
        <h1>Платежные системы</h1>
        
        <?php if ($user_role === 'admin'): ?>
            <div class="field-order-form">
                <h2>Настройка значимости полей</h2>
                <form id="fieldOrderForm" method="POST" action="">
                    <label for="fieldOrder">Выберите порядок значимости (перетащите элементы):</label>
                    <ul id="fieldOrder">
                        <li data-value="cashback">Кэшбек</li>
                        <li data-value="execution_time">Время выполнения</li>
                        <li data-value="rating">Рейтинг</li>
                        <li data-value="commission">Комиссия</li>
                    </ul>
                    <input type="hidden" name="fieldOrder" id="fieldOrderInput" value="<?php echo htmlspecialchars($fieldOrder); ?>">
                    <button type="submit" class="create-button">Сохранить порядок</button>
                </form>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Кэшбек</th>
                    <th>Комиссия</th>
                    <th>Время выполнения</th>
                    <th>Рейтинг</th>
                    <th>Количество оценок</th>
                    <th>Значимость</th>
                    <?php if ($user_role === 'user'): ?>
                        <th>Действия</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paymentSystems as $system): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($system['Id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($system['Name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars(number_format($system['cashback'] ?? 0, 2)); ?></td>
                        <td><?php echo htmlspecialchars($system['commission'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($system['execution_time'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars(number_format($system['rating'] ?? 0, 2)); ?></td>
                        <td><?php echo htmlspecialchars($system['RatingCount'] ?? '0'); ?></td>
                        <td><?php echo htmlspecialchars(number_format($system['sum_significance'] ?? 0, 2)); ?></td>
                        <?php if ($user_role === 'user'): ?>
                            <td>
                                <button class="create-button" onclick="openCreateModal('<?php echo htmlspecialchars($system['Id'] ?? ''); ?>', '<?php echo htmlspecialchars($system['Name'] ?? ''); ?>')">Создать транзакцию</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <?php include 'transactions_modal.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(function() {
            
            $("#fieldOrder").sortable({
                update: function() {
                    
                    const orderArray = $(this).sortable('toArray', { attribute: 'data-value' });
                    $('#fieldOrderInput').val(orderArray.join(','));
                }
            });
            $("#fieldOrder").disableSelection();
        });
        function openCreateModal(paymentSystemId, paymentSystemName) {
            console.log('openCreateModal вызвано');
            document.getElementById('payment_system_id').value = paymentSystemId; 
            document.getElementById('payment_system').value = paymentSystemName; 
           
            document.getElementById('payment_system').disabled = true; 
            document.getElementById('createModal').style.display = 'flex';
        }

        function closeCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }
    </script>
</body>
</html>