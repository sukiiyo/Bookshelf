<?php
session_start();
require_once 'koneksi.php';
require_once 'language.php';

// Sending users to homepage directly after logging in
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validating inputs
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = txt('err_empty');
    } else {
        // Prepared statement (prevents SQL Injection)
        $stmt = mysqli_prepare($koneksi, "SELECT id, username, password FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Password verify (plaintext)
            if ($password === $row['password']) {
                session_regenerate_id(true);
                $_SESSION['login']    = true;
                $_SESSION['user_id']  = $row['id'];
                $_SESSION['username'] = $row['username'];

                header("Location: index.php");
                exit();
            } else {
                $error = txt('err_pass');
            }
        } else {
            $error = txt('err_user');
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Bookshelf</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth">

<div class="auth-card">
    <div class="brand">Books</div>
    <h2 style="margin:12px 0 4px;font-size:18px;"><?= txt('login_title'); ?></h2>
    <p class="auth-sub"><?= txt('login_sub'); ?></p>

    <?php if (!empty($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username"><?= txt('username'); ?></label>
            <input type="text" name="username" id="username" required autocomplete="off"
                   value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="password"><?= txt('password'); ?></label>
            <input type="password" name="password" id="password" required>
        </div>
        <button type="submit" class="btn"><?= txt('login_btn'); ?></button>
    </form>

    <div class="auth-lang">
        <a href="?lang=id" class="<?= $_SESSION['lang'] === 'id' ? 'active' : ''; ?>">ID</a> |
        <a href="?lang=en" class="<?= $_SESSION['lang'] === 'en' ? 'active' : ''; ?>">EN</a>
    </div>
</div>

</body>
</html>