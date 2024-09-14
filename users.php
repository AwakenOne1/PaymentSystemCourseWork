<!DOCTYPE html>
<html>
<head>
    <title>Управление пользователями</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            color: white;
            cursor: pointer;
        }
        .edit-btn {
            background-color: #4CAF50;
        }
        .delete-btn {
            background-color: #f44336;
        }
    </style>
</head>
<body>
<h1>Управление пользователями</h1>
<table>
    <tr>
        <th>ID</th>
        <th>Имя</th>
        <th>Логин</th>
        <th>Телефон</th>
        <th>Действия</th>
    </tr>
    <?php
    include 'db.php';
    // Подключение к базе данных и получение данных из таблицы users
    $result = $conn->query("SELECT * FROM users");

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['login'] . "</td>";
        echo "<td>" . $row['phone'] . "</td>";
        echo "<td>
                    <button class='edit-btn' onclick='editUser(" . $row['id'] . ")'>Редактировать</button>
                    <button class='delete-btn' onclick='deleteUser(" . $row['id'] . ")'>Удалить</button>
                  </td>";
        echo "</tr>";
    }
    $conn->close();
    ?>
</table>

<script>
    function editUser(userId) {
        // Перенаправление на страницу редактирования пользователя
        window.location.href = 'edit_user.php?id=' + userId;
    }

    function deleteUser(userId) {
        // Запрос на удаление пользователя
        if (confirm('Вы уверены, что хотите удалить этого пользователя?')) {
            window.location.href = 'delete_user.php?id=' + userId;
        }
    }
</script>
</body>
</html>