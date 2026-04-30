<?php require_once 'header.php'; ?>
<?php require_once 'db.php'; 
$project_id = (int)($_GET['id'] ?? 0);
if (!$project_id) { header('Location: dashboard.php'); exit; }
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND owner_id = ?");
$stmt->execute([$project_id, $user_id]);
$project = $stmt->fetch();
if (!$project && $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}
if (!$project) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    if (!$project) { header('Location: dashboard.php'); exit; }
}

$status_filter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$where = ['project_id = ?'];
$params = [$project_id];
if ($status_filter && in_array($status_filter, ['новая','в работе','выполнена','отменена'])) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}
if ($search) {
    $where[] = "(title ILIKE ? OR description ILIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql = "SELECT t.*, u.name as executor_name
        FROM tasks t
        LEFT JOIN users u ON t.executor_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY t.deadline ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();
?>
<h1><a href="dashboard.php">Проекты</a> / <?= htmlspecialchars($project['title']) ?></h1>
<div class="card">
    <form method="get" class="filter-bar">
        <input type="hidden" name="id" value="<?= $project_id ?>">
        <input type="text" name="search" placeholder="Поиск по названию/описанию" value="<?= htmlspecialchars($search) ?>">
        <select name="status">
            <option value="">Все статусы</option>
            <option value="новая" <?= $status_filter=='новая'?'selected':'' ?>>Новая</option>
            <option value="в работе" <?= $status_filter=='в работе'?'selected':'' ?>>В работе</option>
            <option value="выполнена" <?= $status_filter=='выполнена'?'selected':'' ?>>Выполнена</option>
            <option value="отменена" <?= $status_filter=='отменена'?'selected':'' ?>>Отменена</option>
        </select>
        <button type="submit" class="btn">Применить</button>
        <a href="project.php?id=<?= $project_id ?>" class="btn">Сбросить</a>
        <a href="task_form.php?project_id=<?= $project_id ?>" class="btn btn-success">+ Новая задача</a>
    </form>
</div>
<div class="card">
    <table>
        <thead>
            <tr><th>Задача</th><th>Описание</th><th>Исполнитель</th><th>Дедлайн</th><th>Статус</th><th>Действия</th></tr>
        </thead>
        <tbody>
        <?php foreach($tasks as $task): 
            $overdue = ($task['deadline'] < date('Y-m-d H:i:s') && !in_array($task['status'], ['выполнена','отменена']));
        ?>
            <tr>
                <td data-label="Задача"><?= htmlspecialchars($task['title']) ?></td>
                <td data-label="Описание"><?= nl2br(htmlspecialchars($task['description'])) ?></td>
                <td data-label="Исполнитель"><?= htmlspecialchars($task['executor_name']) ?></td>
                <td data-label="Дедлайн" class="<?= $overdue ? 'overdue' : '' ?>"><?= date('d.m.Y H:i', strtotime($task['deadline'])) ?></td>
                <td data-label="Статус" class="status-cell">
                    <form method="post" action="task_form.php" style="display:inline;">
                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                        <input type="hidden" name="project_id" value="<?= $project_id ?>">
                        <select name="status" onchange="this.form.submit()">
                            <option value="новая" <?= $task['status']=='новая'?'selected':'' ?>>Новая</option>
                            <option value="в работе" <?= $task['status']=='в работе'?'selected':'' ?>>В работе</option>
                            <option value="выполнена" <?= $task['status']=='выполнена'?'selected':'' ?>>Выполнена</option>
                            <option value="отменена" <?= $task['status']=='отменена'?'selected':'' ?>>Отменена</option>
                        </select>
                    </form>
                </td>
                <td data-label="Действия">
                    <a href="task_form.php?edit=<?= $task['id'] ?>&project_id=<?= $project_id ?>" class="btn">✏️</a>
                    <a href="task_form.php?delete=<?= $task['id'] ?>&project_id=<?= $project_id ?>" class="btn btn-danger" onclick="return confirm('Удалить задачу?')">🗑️</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if(empty($tasks)) echo "<tr><td colspan='6'>Задач не найдено</td></tr>"; ?>
        </tbody>
    </table>
</div>
<?php foreach($tasks as $task): 
    $stmt = $pdo->prepare("SELECT c.*, u.name as author_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC");
    $stmt->execute([$task['id']]);
    $comments = $stmt->fetchAll();
?>
<div class="card">
    <h4>Комментарии к задаче «<?= htmlspecialchars($task['title']) ?>»</h4>
    <div class="comments-list">
        <?php foreach($comments as $comment): ?>
        <div class="comment">
            <div class="comment-author"><?= htmlspecialchars($comment['author_name']) ?></div>
            <div><?= nl2br(htmlspecialchars($comment['text'])) ?></div>
            <div class="comment-date"><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Обычная синхронная форма без AJAX -->
    <form method="post" action="task_form.php">
        <input type="hidden" name="add_comment" value="1">
        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
        <input type="hidden" name="project_id" value="<?= $project_id ?>">
        <textarea name="comment_text" rows="2" placeholder="Добавить комментарий..." required></textarea>
        <button type="submit" class="btn">Добавить</button>
    </form>
</div>
<?php endforeach; ?>

<?php require_once 'footer.php'; ?>