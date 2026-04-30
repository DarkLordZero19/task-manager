<?php require_once 'header.php'; ?>
<?php require_once 'db.php'; 
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $uid = (int)$_POST['user_id'];
    $role = $_POST['role'];
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$role, $uid]);
}
if (isset($_GET['delete_user'])) {
    $uid = (int)$_GET['delete_user'];
    if ($uid != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
    }
    header('Location: admin.php');
    exit;
}
$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY id")->fetchAll();
?>
<h1>Управление пользователями</h1>
<div class="card">
    <table class="admin-table">
        <thead><tr><th>ID</th><th>Имя</th><th>Email</th><th>Роль</th><th>Дата регистрации</th><th>Действия</th></tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <select name="role" onchange="this.form.submit()">
                        <option value="user" <?= $u['role']=='user'?'selected':'' ?>>user</option>
                        <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>admin</option>
                    </select>
                    <input type="hidden" name="change_role" value="1">
                </form>
             </td>
            <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
            <td>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                <a href="?delete_user=<?= $u['id'] ?>" onclick="return confirm('Удалить пользователя?')" class="btn btn-danger">Удалить</a>
                <?php endif; ?>
             </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once 'footer.php'; ?>