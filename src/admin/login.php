<?php
require_once 'auth.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $user = find_user_by_email($email);
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - ITCS333</title>
    <link rel="stylesheet" href="task1.css">
</head>
<body>
    <main class="auth">
        <h2>Login</h2>
        <?php if ($errors): ?>
            <div class="errors">
                <?php foreach ($errors as $e) echo '<p>'.htmlspecialchars($e).'</p>'; ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <label>Email<br>
                <input type="email" name="email" required>
            </label>
            <label>Password<br>
                <input type="password" name="password" required>
            </label>
            <button type="submit">Login</button>
        </form>
        <p>Default admin: <strong>admin@example.com / admin123</strong></p>
        <p style="text-align: center; margin-top: 20px;">
            Don't have an account? <a href="signup.php" style="color: #0056b3; text-decoration: none; font-weight: 600;">Sign up here</a>
        </p>
    </main>
</body>
</html>