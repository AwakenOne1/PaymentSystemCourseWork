<?php
include 'db.php';
include 'userRole_enum.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$result = $conn->query("SELECT login, name FROM users WHERE Id = $user_id");
$userHeader = $result->fetch_assoc();

if ($user_role !== 'admin' && $user_role !== 'moderator') {
    header('Location: transactions.php');
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


$result = $conn->query("SELECT users.*, payment_systems.name AS payment_system_name FROM users
                       LEFT JOIN payment_systems ON users.payment_system_id = payment_systems.id");
$users = $result->fetch_all(MYSQLI_ASSOC);


$conn->close();

function h($str) {
    return htmlspecialchars($str ?? '');
}
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
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

        .button-container {
            display: flex;
            gap: 20px;
            margin: 1em 0;
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
            width: 400px;
            max-width: 90%;
        }

        .close {
            float: right;
            font-size: 1.2em;
            cursor: pointer;
        }

        button, 
        .button, 
        input[type="submit"] {
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
        input[type="submit"]:hover {
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

        h1 {
            margin-bottom: 20px;
            color: #333;
        }
    </style>
</head>
<body>
    <header>
    <div class="header-left">
        <div class="user-info"><?php echo h($userHeader['login'] ?? ''); ?> (<?php echo h($userHeader['name'] ?? ''); ?>)</div>
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
    <h1>Список пользователей</h1>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Имя</th>
        <th>Логин</th>
        <th>Телефон</th>
        <th>Роль</th>
        <th>Платежная система</th> <!-- Новая колонка -->
        <?php if ($user_role === 'admin'): ?>
            <th>Действия</th>
        <?php endif; ?>
    </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo h($user['id'] ?? ''); ?></td>
                <td><?php echo h($user['name'] ?? ''); ?></td>
                <td><?php echo h($user['login'] ?? ''); ?></td>
                <td><?php echo h($user['phone'] ?? ''); ?></td>
                <td><?php echo h($user['role'] ?? ''); ?></td>
                <td><?php echo h($user['payment_system_name'] ?? ''); ?></td> <!-- Отображение имени платежной системы -->
                <?php if ($user_role === 'admin' && h($user['role']) !== UserRole::ADMIN): ?>
                    <td>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <button class="edit-button" onclick="openEditModal(
                                 <?php echo h($user['id']); ?>,
                                '<?php echo h($user['name']); ?>',
                                '<?php echo h($user['login']); ?>',
                                '<?php echo h($user['phone']); ?>',
                                '<?php echo h($user['role']); ?>',
                                '<?php echo h($user['payment_system_id']); ?>')">Редактировать</button>
                            <button class="delete-button" onclick="deleteUser(
                                <?php echo h($user['id']); ?>
                            )">Удалить</button>
                        </div>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>

   
</table>
</main>

<?php include 'users_modal.php'; ?>

<script>
    function openEditModal(id, name, login, phone, role, payment_system_id) {
    document.getElementById('user_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_login').value = login;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_role').value = role;
    document.getElementById('payment_system').value = payment_system_id; 
    document.getElementById('editModal').style.display = 'flex';
}


    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function deleteUser(id) {
        if (confirm('Вы уверены, что хотите удалить пользователя?')) {
            window.location.href = 'delete_user.php?id=' + id;
        }
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('editModal')) {
            closeEditModal();
        }
    };
 
</script>
</body>
</html>
