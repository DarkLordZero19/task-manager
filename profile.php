<?php require_once 'header.php'; ?>
<?php require_once 'db.php'; 
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_name'])) {
        $new_name = trim($_POST['name']);
        if ($new_name) {
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$new_name, $user_id]);
            $_SESSION['user_name'] = $new_name;
            $success = 'Имя обновлено';
        } else {
            $error = 'Имя не может быть пустым';
        }
    } elseif (isset($_POST['update_password'])) {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (password_verify($old, $user['password'])) {
            if ($new === $confirm && strlen($new) >= 6) {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $user_id]);
                $success = 'Пароль изменён';
            } else {
                $error = 'Новый пароль должен быть не менее 6 символов и совпадать';
            }
        } else {
            $error = 'Неверный текущий пароль';
        }
    }
}

$stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<h1>Профиль пользователя</h1>

<?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <h3>Информация</h3>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Роль:</strong> <?= htmlspecialchars($user['role']) ?></p>
</div>
<div class="card">
    <h3>Изменить имя</h3>
    <form method="post">
        <div class="form-group"><label>Новое имя</label><input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required></div>
        <button type="submit" name="update_name" class="btn">Обновить имя</button>
    </form>
</div>
<div class="card">
    <h3>Изменить пароль</h3>
    <form method="post">
        <div class="form-group"><label>Текущий пароль</label><input type="password" name="old_password" required></div>
        <div class="form-group"><label>Новый пароль (мин. 6 символов)</label><input type="password" name="new_password" required></div>
        <div class="form-group"><label>Подтверждение нового пароля</label><input type="password" name="confirm_password" required></div>
        <button type="submit" name="update_password" class="btn">Сменить пароль</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>