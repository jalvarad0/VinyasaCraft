<?php
require 'config.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if ($u && $p) {
        $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username=? OR email=?");
        $stmt->bind_param("ss", $u, $u);
        $stmt->execute();
        $stmt->bind_result($id, $hash);
        if ($stmt->fetch() && password_verify($p, $hash)) {
            // success
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $u;
            header("Location: home.php");
            exit;
        }
    }
    $errors[] = "Invalid credentials.";
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Log In</title></head><body>
  <h2>Log In</h2>
  <?php if (!empty($_GET['registered'])): ?>
    <p style="color:green">Registration successful. Please log in.</p>
  <?php endif; ?>
  <?php foreach($errors as $err): ?>
    <p style="color:red;"><?=htmlspecialchars($err)?></p>
  <?php endforeach; ?>
  <form method="post">
    <label>Username or Email:<br><input name="username" required></label><br>
    <label>Password:<br><input type="password" name="password" required></label><br>
    <button type="submit">Log In</button>
  </form>
  <p>No account yet? <a href="register.php">Register here</a></p>
</body></html>