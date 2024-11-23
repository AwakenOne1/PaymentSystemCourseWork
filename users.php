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

 // Подключение к базе данных и получение данных из таблицы users
 $result = $conn->query("SELECT users.*, paymentsystems.name AS payment_system_name FROM users
                        LEFT JOIN paymentsystems ON users.payment_system_id = paymentsystems.id");
 $users = $result->fetch_all(MYSQLI_ASSOC);


 $conn->close();
 ?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
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
        <div class="user-info"><?php echo htmlspecialchars($userHeader['login']); ?> (<?php echo htmlspecialchars($userHeader['name']); ?>)</div>
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
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo htmlspecialchars($user['login']); ?></td>
                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                <td><?php echo htmlspecialchars($user['role']); ?></td>
                <td><?php echo htmlspecialchars($user['payment_system_name']); ?></td> <!-- Отображение имени платежной системы -->
                <?php if ($user_role === 'admin' && htmlspecialchars($user['role']) !== UserRole::ADMIN): ?>
                    <td>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <button class="edit-button" onclick="openEditModal(
                                 <?php echo htmlspecialchars($user['id']); ?>,
                                '<?php echo htmlspecialchars($user['name']); ?>',
                                '<?php echo htmlspecialchars($user['login']); ?>',
                                '<?php echo htmlspecialchars($user['phone']); ?>',
                                '<?php echo htmlspecialchars($user['role']); ?>',
                                '<?php echo htmlspecialchars($user['payment_system_id']); ?>')">Редактировать</button>
                            <button class="delete-button" onclick="deleteUser(
                                <?php echo htmlspecialchars($user['id']); ?>
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
    document.getElementById('payment_system').value = payment_system_id; // Устанавливаем значение платежной системы
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