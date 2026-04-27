<?php
session_start();
$public_pages = ['login.php', 'register.php'];
if (!in_array(basename($_SERVER['SCRIPT_NAME']), $public_pages)) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="container navbar">
        <div class="logo">Task Manager</div>
        <div class="nav-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">Проекты</a>
                <a href="profile.php">Профиль</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php">Админка</a>
                <?php endif; ?>
                <a href="logout.php">Выход (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<div class="container">