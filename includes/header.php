<?php
// Получаем данные пользователя
$result = $conn->query("SELECT login, name FROM users WHERE Id = $user_id");
$user = $result->fetch_assoc();
?>

<header>
    <div><?php echo htmlspecialchars($user['login']); ?> (<?php echo htmlspecialchars($user['name']); ?>)</div>
    <div><a href="?action=logout">Выйти</a></div>
</header>