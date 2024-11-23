<?php
    $payment_systems = $_SESSION['paymentsystems'] ?? [];
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
            <input type="text" name="login" id="edit_login" required>
            <label for="edit_phone">Телефон:</label>
            <input type="text" name="phone" id="edit_phone" required>
            <label for="edit_role">Роль:</label>
            <select name="role" id="edit_role" required>
                <option value="admin">Администратор</option>
                <option value="moderator">Модератор</option>
                <option value="user">Пользователь</option>
            </select>
            <label for="payment_system">Платежная система:</label>
            <select name="payment_system_id" id="payment_system" required>
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
        fields.forEach(function(field) {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                alert('Пожалуйста, заполните все поля.');
                isValid = false;
                return;
            }
        });
        return isValid;
    }

</script>