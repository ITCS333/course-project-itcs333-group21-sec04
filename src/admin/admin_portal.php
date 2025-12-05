<?php
require_once 'auth.php';
require_admin();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Portal - ITCS333</title>
    <link rel="stylesheet" href="task1.css">
</head>
<body>
    <header>
        <h1>Admin Portal</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="logout.php">Logout (<?php echo $_SESSION['user']['email']; ?>)</a>
        </nav>
    </header>
    <div class="container">
        <div class="admin-box">
            <h2>Welcome, Admin</h2>
            <p>Choose a management option below:</p>
            <div class="admin-links">
                <a href="manage_users.php">Manage Students</a>
                <a href="create_user.php">Add New Student</a>
                <a href="change_password.php">Change Admin Password</a>
            </div>
        </div>
    </div>
</body>
</html>