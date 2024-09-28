<?php
//include __DIR__ . '/../includes/auth.php';
//include __DIR__ . '/../includes/includes/db.php';

// Получение всех транзакций
$transactions_result = $conn->query("SELECT * FROM transactions WHERE UserId = $user_id");
$transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);

// Закрытие соединения после выполнения всех запросов

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main>
    <h1>Ваши транзакции</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Сумма</th>
                <th>Кому</th>
                <th>Комментарий</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['Id']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['Sum']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['Destination']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['Comment']); ?></td>
                    <td>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <button class="edit-button" onclick="openEditModal(<?php echo $transaction['Id']; ?>, '<?php echo htmlspecialchars($transaction['Sum']); ?>', '<?php echo htmlspecialchars($transaction['Destination']); ?>', '<?php echo htmlspecialchars($transaction['Comment']); ?>')">Редактировать</button>
                            <button class="delete-button" onclick="deleteTransaction(<?php echo $transaction['Id']; ?>)">Удалить</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="button-container">
        <button class="search-button" onclick="openSearchModal()">Поиск по критериям</button>
        <button class="create-button" onclick="openCreateModal()">Создать транзакцию</button>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
