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
<html>
<head>
<meta charset="UTF-8">
<title>Log In</title>
<style>
  body {
    background: url('./images/goat_yoga.jpg') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
  }

  .login-container {
    max-width: 400px;
    margin: 80px auto;
    background: rgba(255, 255, 255, 0.8);
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    text-align: center;
  }

  h2 {
    margin-bottom: 20px;
    color: #333;
  }

  label {
    display: block;
    margin-bottom: 15px;
    text-align: left;
    font-weight: bold;
    color: #555;
  }

  input[type="text"],
  input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
  }

  button {
    width: 100%;
    padding: 12px;
    background-color: #6c63ff;
    border: none;
    border-radius: 25px;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
  }

  button:hover {
    background-color: #5751d9;
  }

  p {
    margin-top: 20px;
    color: #333;
  }

  a {
    color: #6c63ff;
    text-decoration: none;
    font-weight: bold;
  }

  a:hover {
    text-decoration: underline;
  }

  .success-message {
    color: green;
    margin-bottom: 15px;
  }

  .error-message {
    color: red;
    margin-bottom: 15px;
  }
</style>
</head>
<body>

<div class="login-container">
  <h2>Log In</h2>

  <?php if (!empty($_GET['registered'])): ?>
    <p class="success-message">Registration successful. Please log in.</p>
  <?php endif; ?>

  <?php foreach($errors as $err): ?>
    <p class="error-message"><?=htmlspecialchars($err)?></p>
  <?php endforeach; ?>

  <form method="post">
    <label>Email:
      <input type="text" name="username" required>
    </label>

    <label>Password:
      <input type="password" name="password" required>
    </label>

    <button type="submit">Log In</button>
  </form>

  <p>No account yet? <a href="register.php">Register here</a></p>
</div>

</body>
</html>