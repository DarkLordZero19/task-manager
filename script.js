document.addEventListener('DOMContentLoaded', function() {
    const statusForms = document.querySelectorAll('.status-form');
    statusForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('task_form.php?action=status_ajax', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const taskRow = this.closest('tr');
                    const statusCell = taskRow.querySelector('.status-cell');
                    statusCell.innerText = data.new_status_text;
                    const badge = statusCell.querySelector('.status-badge');
                    if (badge) {
                        badge.className = `status-badge status-${data.new_status_class}`;
                    }
                    showNotification('Статус обновлён', 'success');
                } else {
                    showNotification(data.error || 'Ошибка', 'error');
                }
            })
            .catch(err => showNotification('Ошибка сети', 'error'));
        });
    });
    const commentForms = document.querySelectorAll('.comment-form');
    commentForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('task_form.php?action=comment_ajax', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentsContainer = this.parentElement.querySelector('.comments-list');
                    const newComment = document.createElement('div');
                    newComment.className = 'comment';
                    newComment.innerHTML = `
                        <div class="comment-author">${data.author}</div>
                        <div>${data.text}</div>
                        <div class="comment-date">${data.date}</div>
                    `;
                    commentsContainer.appendChild(newComment);
                    this.reset();
                    showNotification('Комментарий добавлен', 'success');
                } else {
                    showNotification(data.error || 'Ошибка', 'error');
                }
            })
            .catch(err => showNotification('Ошибка сети', 'error'));
        });
    });
});

function showNotification(message, type) {
    const notif = document.createElement('div');
    notif.className = `notification ${type}`;
    notif.innerText = message;
    notif.style.position = 'fixed';
    notif.style.bottom = '20px';
    notif.style.right = '20px';
    notif.style.backgroundColor = type === 'success' ? '#2ecc71' : '#e74c3c';
    notif.style.color = 'white';
    notif.style.padding = '10px 20px';
    notif.style.borderRadius = '4px';
    notif.style.zIndex = '9999';
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}