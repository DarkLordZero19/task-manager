<?php require_once 'header.php'; ?>
<?php require_once 'db.php'; 
$user_id = $_SESSION['user_id'];
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'выполнена') as completed_tasks
        FROM projects p
        WHERE p.owner_id = ?
        ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll();
?>
<h1>Мои проекты</h1>
<a href="projects.php?action=create" class="btn" style="margin-bottom:20px;">+ Новый проект</a>
<div class="projects-grid">
    <?php foreach ($projects as $proj): 
        $percent = ($proj['total_tasks'] > 0) ? round(($proj['completed_tasks'] / $proj['total_tasks']) * 100) : 0;
    ?>
    <div class="card project-card">
        <h3><a href="project.php?id=<?= $proj['id'] ?>"><?= htmlspecialchars($proj['title']) ?></a></h3>
        <p><?= nl2br(htmlspecialchars($proj['description'])) ?></p>
        <p>Задач: <?= $proj['completed_tasks'] ?>/<?= $proj['total_tasks'] ?></p>
        <div class="progress-bar"><div class="progress-fill" style="width: <?= $percent ?>%;"></div></div>
        <p>Выполнено: <?= $percent ?>%</p>
        <a href="projects.php?action=edit&id=<?= $proj['id'] ?>" class="btn">Редактировать</a>
        <a href="projects.php?action=delete&id=<?= $proj['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить проект со всеми задачами?')">Удалить</a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($projects)) echo "<p>У вас пока нет проектов. Создайте первый проект!</p>"; ?>
</div>
<?php require_once 'footer.php'; ?>