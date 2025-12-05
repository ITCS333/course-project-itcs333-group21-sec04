<?php
require_once 'auth.php';
require_admin();
$users = load_users();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Students - ITCS333</title>
<link rel="stylesheet" href="task1.css">
</head>
<body>
<header>
<h1>Students</h1>
<nav>
<a href="admin_portal.php">Back to Portal</a>
<a href="logout.php">Logout</a>
</nav>
</header>
<main>
<section>
<h2>All Students</h2>
<table>
<thead><tr><th>ID</th><th>Name</th><th>Student ID</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($users as $u): ?>
<tr>
<td><?=htmlspecialchars($u['id'])?></td>
<td><?=htmlspecialchars($u['name'])?></td>
<td><?=htmlspecialchars($u['student_id'] ?? 'N/A')?></td>
<td><?=htmlspecialchars($u['email'])?></td>
<td><?=htmlspecialchars($u['role'] ?? 'student')?></td>
<td>
<a href="edit_user.php?id=<?=$u['id']?>">Edit</a> |
<a href="delete_user.php?id=<?=$u['id']?>" onclick="return confirm('Delete user?');">Delete</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>
<section>
<h2>Create New Student</h2>
<form action="create_user.php" method="post">
<label>Name<br><input type="text" name="name" required></label>
<label>Student ID<br><input type="text" name="student_id" required></label>
<label>Email<br><input type="email" name="email" required></label>
<label>Default Password<br><input type="text" name="password" value="student123" required></label>
<button type="submit">Create</button>
</form>
</section>
</main>
</body>
</html>