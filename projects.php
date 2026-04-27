<?php require_once 'header.php'; ?>
<?php require_once 'db.php'; 
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$project_id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($action === 'delete' && $project_id) {
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND owner_id = ?");
    if ($stmt->execute([$project_id, $user_id])) {
        header('Location: dashboard.php?msg=deleted');
        exit;
    } else {
        $error = 'Не удалось удалить проект';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    if (empty($title)) {
        $error = 'Название проекта обязательно';
    } else {
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO projects (title, description, owner_id) VALUES (?, ?, ?)");
            if ($stmt->execute([$title, $description, $user_id])) {
                header('Location: dashboard.php');
                exit;
            } else $error = 'Ошибка создания';
        } elseif ($action === 'edit' && $project_id) {
            $stmt = $pdo->prepare("UPDATE projects SET title=?, description=? WHERE id=? AND owner_id=?");
            if ($stmt->execute([$title, $description, $project_id, $user_id])) {
                header('Location: dashboard.php');
                exit;
            } else $error = 'Ошибка обновления';
        }
    }
}

$project = null;
if ($action === 'edit' && $project_id) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND owner_id = ?");
    $stmt->execute([$project_id, $user_id]);
    $project = $stmt->fetch();
    if (!$project) {
        header('Location: dashboard.php');
        exit;
    }
}
?>
<h1><?= $action === 'create' ? 'Новый проект' : 'Редактирование проекта' ?></h1>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<div class="card">
    <form method="post">
        <div class="form-group"><label>Название *</label><input type="text" name="title" value="<?= htmlspecialchars($project['title'] ?? '') ?>" required></div>
        <div class="form-group"><label>Описание</label><textarea name="description"><?= htmlspecialchars($project['description'] ?? '') ?></textarea></div>
        <button type="submit" class="btn">Сохранить</button>
        <a href="dashboard.php" class="btn">Отмена</a>
    </form>
</div>
<?php require_once 'footer.php'; ?>