<?php
    $payment_systems = $_SESSION['paymentsystems'] ?? [];
    include 'TransactionStatus_enum.php';
?>
<!-- Модальное окно для создания транзакции -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeCreateModal()">&times;</span>
        <h2>Создать транзакцию</h2>
        <form id="createForm" method="POST" action="create_transaction.php">
            <input type="hidden" name="create_transaction" value="1">
            
            <label for="sum">Сумма:</label>
            <input type="number" step="0.01" name="sum" id="sum" required>
            
            <label for="destination">Кому:</label>
            <input type="text" name="destination" id="destination" required>
            
            <label for="comment">Комментарий:</label>
            <textarea name="comment" id="comment"></textarea>
            
            <label for="payment_system">Платежная система:</label>
            <select name="payment_system_id" id="payment_system" required>
                <?php foreach ($payment_systems as $system): ?>
                    <option value="<?php echo $system['Id']; ?>">
                        <?php echo htmlspecialchars($system['Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">Создать транзакцию</button>
        </form>
    </div>
</div>

<!-- Модальное окно для редактирования транзакции -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Редактировать транзакцию</h2>
        <form id="editForm" method="POST" action="edit_transaction.php" onsubmit="return validateForm(['edit_sum', 'edit_destination', 'edit_comment'])">
            <input type="hidden" name="edit_transaction" value="1">
            <input type="hidden" name="transaction_id" id="transaction_id" value="">

            <label for="edit_sum">Сумма:</label>
            <input type="number" step="0.01" name="sum" id="edit_sum" required>

            <label for="edit_destination">Кому:</label>
            <input type="text" name="destination" id="edit_destination" required>

            <label for="edit_comment">Комментарий:</label>
            <textarea name="comment" id="edit_comment"></textarea>
<?php if ($user_role === 'admin'): ?>
    <label for="payment_system">Платежная система:</label>
    <select name="payment_system_id" id="payment_system" required>
        <?php foreach ($payment_systems as $system): ?>
            <option value="<?php echo $system['Id']; ?>" id="payment_system_<?php echo $system['Id']; ?>">
                <?php echo htmlspecialchars($system['Name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
<?php endif; ?>


            <label for="edit_status">Статус:</label>
            <select name="status" id="edit_status" required>
                <option value="<?php echo TransactionStatus::IN_PROCESS; ?>">В процессе</option>
                <option value="<?php echo TransactionStatus::COMPLETED; ?>">Завершено</option>
                <option value="<?php echo TransactionStatus::CANCELLED; ?>">Отменено</option>
            </select>

            <button type="submit">Сохранить изменения</button>
        </form>
    </div>
</div>


<!-- Модальное окно для поиска -->
<div id="searchModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeSearchModal()">&times;</span>
        <h2>Поиск по критериям</h2>
        <form id="searchForm" method="POST" action="" onsubmit="return validateSearch()">
            <label for="minSum">Сумма от:</label>
            <input type="number" id="minSum" step="0.01" name="minSum" value="<?php echo htmlspecialchars($minSum); ?>" required>
            <label for="maxSum">Сумма до:</label>
            <input type="number" id="maxSum" step="0.01" name="maxSum" value="<?php echo htmlspecialchars($maxSum); ?>" required>
            <label for="destination">Куда отправлено (необязательное, до 50 символов):</label>
            <input type="text" id="destination" name="destination" value="<?php echo htmlspecialchars($destination); ?>" maxlength="50" autocomplete="off">
            <input type="hidden" id="resetSearch" name="resetSearch" value="0">
            <button type="submit" style="margin-bottom:5px">Поиск</button>
            <button type="button" onclick="clearSearch()">Очистить</button>
        </form>
    </div>
</div>

<script>
    const maxDecimalValue = 99999999999.99; 

    function validateForm(fields) {
        for (const field of fields) {
            const element = document.getElementById(field);
            const value = field === 'sum' ? parseFloat(element.value) : element.value;
            if (field === 'sum' || field === 'edit_sum') {
                if (value <= 0) {
                    alert('Сумма должна быть больше 0.');
                    return false; 
                }
                if (value > maxDecimalValue) {
                    alert('Сумма должна быть меньше ' + maxDecimalValue.toFixed(2) + '.');
                    return false; 
                }
            }

            if ((field === 'destination' || field === 'comment') && value.length > 150) {
                alert(`Длина полей не должна превышать 150 символов.`);
                return false;
            }
            if ((field === 'edit_destination' || field === 'edit_comment') && value.length > 150) {
                alert(`Длина полей не должна превышать 150 символов.`);
                return false;
            }
        }
        return true; 
    }

    function validateSearch() {
        const minSum = parseFloat(document.getElementById('minSum').value);
        const maxSum = parseFloat(document.getElementById('maxSum').value);

        if (minSum >= maxSum) {
            alert('Минимальная сумма должна быть меньше максимальной.');
            return false; 
        }

        if (minSum < 0 || maxSum < 0) {
            alert('Суммы не могут быть отрицательными.');
            return false;
        }

        if (minSum > maxDecimalValue || maxSum > maxDecimalValue) {
            alert('Суммы должны быть меньше ' + maxDecimalValue.toFixed(2) + '.');
            return false; 
        }
        if (field === 'destination'  && value.length > 150) {
                alert(`Длина поля "${field === 'destination'}" не должна превышать 150 символов.`);
                return false;
        }

        return true; 
    }

   function clearSearch() {
        document.getElementById('searchForm').reset(); 
        document.getElementById('minSum').value = 0;
        document.getElementById('maxSum').value = 99999999999.99;
        document.getElementById('destination').value = '';
    
        
        document.getElementById('resetSearch').value = '1';

        
        document.getElementById('searchForm').submit();
   }
</script>

<style>
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
</style>