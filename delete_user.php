<?php
include 'includes/db.php'; // Подключаем базу данных

// Проверяем, передан ли ID пользователя
if (isset($_GET['id'])) {
    $userId = intval($_GET['id']); // Получаем ID и преобразуем в целое число

    // Запрос на удаление пользователя
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        // Успешно удалено, перенаправляем обратно на страницу управления пользователями
        header("Location: users.php?message=Пользователь успешно удален");
    } else {
        // Ошибка при удалении
        header("Location: users.php?error=Ошибка при удалении пользователя");
    }

    $stmt->close();
} else {
    // Если ID не передан, перенаправляем обратно с сообщением об ошибке
    header("Location: users.php?error=ID пользователя не указан");
}

$conn->close();
?>