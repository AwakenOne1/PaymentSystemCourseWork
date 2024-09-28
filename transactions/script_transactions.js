
    function openCreateModal() {
        document.getElementById('createModal').style.display = 'flex';
    }

    function closeCreateModal() {
        document.getElementById('createModal').style.display = 'none';
    }

    function openSearchModal() {
        document.getElementById('searchModal').style.display = 'flex';
    }

    function closeSearchModal() {
        document.getElementById('searchModal').style.display = 'none';
    }

    function openEditModal(id, sum, destination, comment) {
        document.getElementById('transaction_id').value = id;
    document.getElementById('edit_sum').value = sum;
    document.getElementById('edit_destination').value = destination;
    document.getElementById('edit_comment').value = comment;
    document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function deleteTransaction(id) {
        if (confirm('Вы уверены, что хотите удалить эту транзакцию?')) {
        window.location.href = 'delete_transaction.php?id=' + id;
        }
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('createModal')) {
        closeCreateModal();
        } else if (event.target == document.getElementById('searchModal')) {
        closeSearchModal();
        } else if (event.target == document.getElementById('editModal')) {
        closeEditModal();
        }
    };
