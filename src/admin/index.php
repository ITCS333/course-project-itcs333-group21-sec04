<?php
require_once 'auth.php';
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
?>
<!doctype html>
<html lang="en">
<head>
    <link rel="stylesheet" href="task1.css">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Course Page - ITCS333</title>
    <link rel="stylesheet" href="task1.css">
</head>
<body>
    <header>
        <h1>ITCS333 — Course Page</h1>
        <nav>
            <a href="index.php">Home</a>
            <?php if (!$user): ?>
                <a href="login.php">Login</a>
                <a href="signup.php">Sign Up</a>
            <?php else: ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="admin_portal.php">Admin Portal</a>
                <?php endif; ?>
                <a href="logout.php">Logout (<?php echo htmlspecialchars($user['email']); ?>)</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">
        <section class="hero">
            <h2>Welcome to ITCS333</h2>
            <p>This is the course landing page. Use the navigation to login and manage users (admins only).</p>
        </section>
        <section>
            <h3>Course Info</h3>
            <p>Semester: Fall 2025 — Instructor: Dr. Abdulla subah</p>
        </section>
    </main>
    <footer>
        <p>&copy; 2025 ITCS333</p>
    </footer>
</body>
</html>