<?php
session_start();
// Change 'admin123' to your desired password
$secret_password = "admin123"; 

if (isset($_POST['password'])) {
    if ($_POST['password'] === $secret_password) {
        $_SESSION['authenticated'] = true;
        $target = $_GET['redirect'] ?? 'index.php';
        header("Location: " . $target);
        exit;
    } else {
        $error = "Invalid Password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tech Feud - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #050a30; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: sans-serif; }
        .login-card { background: rgba(255,255,255,0.1); padding: 40px; border-radius: 15px; border: 1px solid var(--gold); text-align: center; }
        input { padding: 10px; border-radius: 5px; border: none; width: 200px; margin-bottom: 10px; }
        button { padding: 10px 20px; background: var(--gold); border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 style="color:white; margin-bottom:20px;">Restricted Access</h2>
        <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter Password" autofocus><br>
            <button type="submit">Unlock Screen</button>
        </form>
    </div>
</body>
</html>