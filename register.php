<?php
require 'config.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $e = trim($_POST['email'] ?? '');
    $p = $_POST['password'] ?? '';
    $c = $_POST['confirm_password'] ?? '';

    if (!$u || !$e || !$p) {
        $errors[] = "All fields are required.";
    } elseif ($p !== $c) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // ensure username/email unique
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->bind_param("ss", $u, $e);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows) {
            $errors[] = "Username or email already taken.";
        } else {
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (username,email,password_hash) VALUES (?,?,?)");
            $ins->bind_param("sss", $u, $e, $hash);
            $ins->execute();
            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Register</title></head><body>
  <h2>Register</h2>
  <?php foreach($errors as $err): ?>
    <p style="color:red;"><?=htmlspecialchars($err)?></p>
  <?php endforeach; ?>
  <form method="post">
    <label>Username:<br><input name="username" required></label><br>
    <label>Email:<br><input type="email" name="email" required></label><br>
    <label>Password:<br><input type="password" name="password" required></label><br>
    <label>Confirm Password:<br><input type="password" name="confirm_password" required></label><br>
    <button type="submit">Sign Up</button>
  </form>
  <p>Already have an account? <a href="login.php">Log in</a></p>
</body></html>