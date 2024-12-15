<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Обработка формы поиска
if (isset($_POST['search'])) {
    $search = $_POST['search'];
    $sql = "SELECT * FROM users WHERE name LIKE '%$search%'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Имя: " . $row['name'] . "<br>";
            echo "Логин: " . $row['login'] . "<br>";
            echo "Роль: " . $row['role'] . "<br><br>";
        }
    } else {
        echo "Пользователь не найден";
    }
}

// Закрытие подключения
$conn->close();
?>

<form action="" method="post">
    <input type="text" name="search" placeholder="Введите имя">
    <button type="submit">Поиск</button>
</form>