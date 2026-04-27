<?php require_once 'header.php'; ?>
<?php require_once 'db.php'; 
$user_id = $_SESSION['user_id'];
$edit_id = (int)($_GET['edit'] ?? 0);
$delete_id = (int)($_GET['delete'] ?? 0);
$project_id = (int)($_GET['project_id'] ?? 0);
$error = '';
if (isset($_GET['action']) && $_GET['action'] === 'status_ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $task_id = (int)$_POST['task_id'];
    $status = $_POST['status'] ?? '';
    if (in_array($status, ['новая','в работе','выполнена','отменена'])) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$status, $task_id]);
        $status_class = match($status) {
            'новая' => 'new',
            'в работе' => 'work',
            'выполнена' => 'done',
            'отменена' => 'cancelled'
        };
        echo json_encode(['success' => true, 'new_status_text' => $status, 'new_status_class' => $status_class]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Неверный статус']);
    }
    exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'comment_ajax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $task_id = (int)$_POST['task_id'];
    $text = trim($_POST['comment_text']);
    $pid = (int)$_POST['project_id'];
    if ($text) {
        $stmt = $pdo->prepare("INSERT INTO comments (text, task_id, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$text, $task_id, $user_id]);
        echo json_encode([
            'success' => true,
            'author' => $_SESSION['user_name'],
            'text' => nl2br(htmlspecialchars($text)),
            'date' => date('d.m.Y H:i')
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Пустой комментарий']);
    }
    exit;
}
if ($delete_id) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND (project_id IN (SELECT id FROM projects WHERE owner_id = ?) OR ? = 'admin')");
    $admin = ($_SESSION['role'] === 'admin') ? 'admin' : '';
    if ($stmt->execute([$delete_id, $user_id, $admin])) {
        header("Location: project.php?id=$project_id");
        exit;
    } else $error = 'Не удалось удалить задачу';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && isset($_POST['task_id']) && !isset($_GET['action'])) {
    $task_id = (int)$_POST['task_id'];
    $status = $_POST['status'];
    $pid = (int)$_POST['project_id'];
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->execute([$status, $task_id]);
    header("Location: project.php?id=$pid");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && isset($_POST['task_id']) && !isset($_GET['action'])) {
    $task_id = (int)$_POST['task_id'];
    $text = trim($_POST['comment_text']);
    $pid = (int)$_POST['project_id'];
    if ($text) {
        $stmt = $pdo->prepare("INSERT INTO comments (text, task_id, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$text, $task_id, $user_id]);
    }
    header("Location: project.php?id=$pid");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $project_id_form = (int)$_POST['project_id'];
    $executor_id = (int)$_POST['executor_id'];
    $deadline = $_POST['deadline'] . ' ' . $_POST['deadline_time'];
    $status = $_POST['status'];
    if (empty($title) || empty($project_id_form) || empty($executor_id) || empty($deadline)) {
        $error = 'Заполните название, проект, исполнителя и дедлайн';
    } else {
        if ($edit_id) {
            $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, executor_id=?, deadline=?, status=? WHERE id=?");
            $stmt->execute([$title, $description, $executor_id, $deadline, $status, $edit_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, project_id, executor_id, deadline, status) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$title, $description, $project_id_form, $executor_id, $deadline, $status]);
        }
        header("Location: project.php?id=$project_id_form");
        exit;
    }
}

$task = null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$edit_id]);
    $task = $stmt->fetch();
    if ($task) $project_id = $task['project_id'];
}

$users = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll();
$projects_sql = "SELECT id, title FROM projects WHERE owner_id = ?";
$stmt = $pdo->prepare($projects_sql);
$stmt->execute([$user_id]);
$projects_list = $stmt->fetchAll();
if ($_SESSION['role'] === 'admin') {
    $all_projects = $pdo->query("SELECT id, title FROM projects")->fetchAll();
    $projects_list = array_merge($projects_list, $all_projects);
}
?>
<h1><?= $edit_id ? 'Редактирование задачи' : 'Новая задача' ?></h1>
<div class="card">
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <div class="form-group"><label>Название *</label><input type="text" name="title" value="<?= htmlspecialchars($task['title'] ?? '') ?>" required></div>
        <div class="form-group"><label>Описание</label><textarea name="description"><?= htmlspecialchars($task['description'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Проект *</label>
            <select name="project_id" required>
                <?php foreach($projects_list as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($project_id == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Исполнитель *</label>
            <select name="executor_id" required>
                <?php foreach($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($task && $task['executor_id'] == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Дедлайн *</label>
            <input type="date" name="deadline" value="<?= $task ? date('Y-m-d', strtotime($task['deadline'])) : '' ?>" required>
            <input type="time" name="deadline_time" value="<?= $task ? date('H:i', strtotime($task['deadline'])) : '18:00' ?>" required>
        </div>
        <div class="form-group"><label>Статус</label>
            <select name="status">
                <option value="новая" <?= ($task && $task['status']=='новая')?'selected':'' ?>>Новая</option>
                <option value="в работе" <?= ($task && $task['status']=='в работе')?'selected':'' ?>>В работе</option>
                <option value="выполнена" <?= ($task && $task['status']=='выполнена')?'selected':'' ?>>Выполнена</option>
                <option value="отменена" <?= ($task && $task['status']=='отменена')?'selected':'' ?>>Отменена</option>
            </select>
        </div>
        <button type="submit" class="btn">Сохранить</button>
        <a href="project.php?id=<?= $project_id ?>" class="btn">Отмена</a>
    </form>
</div>
<?php require_once 'footer.php'; ?>