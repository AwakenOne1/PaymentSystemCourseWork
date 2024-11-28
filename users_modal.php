<?php
$payment_systems = $_SESSION['paymentsystems'] ?? [];

$user_role = $_SESSION['user_role'];
?>
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Редактировать пользователя</h2>
        <form id="editForm" method="POST" action="edit_user.php" onsubmit="return validateForm(['edit_name', 'edit_login', 'edit_phone', 'edit_role'])">
            <input type="hidden" name="edit_user" value="1">
            <input type="hidden" name="user_id" id="user_id" value="">
            <label for="edit_name">Имя:</label>
            <input type="text" name="name" id="edit_name" required>
            <label for="edit_login">Логин:</label>
            <input type="email" name="login" id="edit_login" required>
            <label for="edit_phone">Телефон:</label>
            <input type="text" name="phone" id="edit_phone" required>
            <label for="edit_role">Роль:</label>
            <select name="role" id="edit_role" required onchange="togglePaymentSystem()">
                <option value="admin">Администратор</option>
                <option value="moderator">Модератор</option>
                <option value="user" <?php if ($user_role === 'user')
                    echo 'selected'; ?>>Пользователь</option>
            </select>
            <label for="payment_system">Платежная система:</label>
            <select name="payment_system_id" id="payment_system" <?php if ($user_role === 'user')
                echo 'disabled'; ?>>
                <option value="">Не выбрано</option> <!-- Опция для сброса значения -->
                <?php foreach ($payment_systems as $system): ?>
                    <option value="<?php echo $system['Id']; ?>"><?php echo htmlspecialchars($system['Name']); ?></option>
                <?php endforeach; ?>
            </select>

            <div><h1></h1></div>
            <button type="submit">Сохранить изменения</button>
        </form>
    </div>
</div>

<script>
    function validateForm(fields) {
        let isValid = true;
        fields.forEach(function (field) {
            const input = document.getElementById(field);
            if (!input.value.trim() && field !== "payment_system") {
                alert("Пожалуйста, заполните все обязательные поля.");
                isValid = false;
                return;
            }
        });
        return isValid;
    }

    function togglePaymentSystem() {
        var roleSelect = document.getElementById("edit_role");
        var paymentSystemSelect = document.getElementById("payment_system");

        // Проверяем, выбран ли 'user'
        if (roleSelect.value === 'user' || roleSelect.value === 'admin') {
            paymentSystemSelect.disabled = true; // Отключаем поле
            paymentSystemSelect.value = ""; // Сбрасываем выбор
        } else {
            paymentSystemSelect.disabled = false; // Включаем поле
        }
    }

    // Вызов функции при загрузке, чтобы установить начальное состояние
    document.addEventListener("DOMContentLoaded", function() {
        togglePaymentSystem();
    });
</script>