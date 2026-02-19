<?php
// ═══════════════════════════════════════════════════════════
// Tech Feud — Auth Helper
// Include this at the top of admin.php and presenter.php
// ═══════════════════════════════════════════════════════════

session_start();

// ── CONFIGURE YOUR PASSWORDS HERE ───────────────────────────
define('ADMIN_PASSWORD',     'techfeud_admin');
define('PRESENTER_PASSWORD', 'techfeud_stage');
// ────────────────────────────────────────────────────────────

function requireAuth(string $role): void {
    $key = 'techfeud_auth_' . $role;

    // Already logged in?
    if (!empty($_SESSION[$key])) return;

    // Logging in?
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        $correct = ($role === 'admin') ? ADMIN_PASSWORD : PRESENTER_PASSWORD;
        if ($_POST['password'] === $correct) {
            $_SESSION[$key] = true;
            // Redirect to self to clear POST data
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
        $GLOBALS['auth_error'] = 'Incorrect password. Try again.';
    }

    // Show login form and stop
    $title = ($role === 'admin') ? '⚙️ Admin Login' : '📺 Stage Screen Login';
    echo '<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tech Feud — ' . htmlspecialchars($title) . '</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;600;700&family=Roboto:wght@300;400;700&display=swap">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{
    font-family:"Roboto",sans-serif;
    background: radial-gradient(ellipse at top, #1a237e 0%, #050a30 100%);
    min-height:100vh; display:flex; align-items:center; justify-content:center;
    color:#fff;
}
.card{
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(201,168,76,0.3);
    border-radius:20px; padding:44px 40px;
    text-align:center; max-width:380px; width:90%;
}
.lock{ font-size:3rem; margin-bottom:12px; }
.ttl{
    font-family:"Anton",sans-serif; font-size:1.8rem;
    color:#C9A84C; letter-spacing:2px; margin-bottom:4px;
}
.sub{ color:rgba(255,255,255,0.45); font-size:0.85rem; margin-bottom:28px; }
input[type=password]{
    width:100%; padding:12px 16px;
    background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15);
    border-radius:10px; color:#fff;
    font-family:"Roboto",sans-serif; font-size:1rem;
    margin-bottom:12px; outline:none;
    transition:border-color .2s;
}
input[type=password]:focus{ border-color:#C9A84C; }
button{
    width:100%; padding:13px;
    background:linear-gradient(135deg,#8a6a20,#C9A84C);
    border:none; border-radius:10px; color:#000;
    font-family:"Oswald",sans-serif; font-size:1rem;
    font-weight:700; letter-spacing:1px; text-transform:uppercase;
    cursor:pointer; transition:filter .15s;
}
button:hover{ filter:brightness(1.1); }
.err{
    color:#ff6b6b; background:rgba(255,50,50,0.1);
    border:1px solid rgba(255,50,50,0.3);
    border-radius:8px; padding:9px 14px;
    font-size:0.85rem; margin-bottom:12px;
}
.back{ margin-top:18px; }
.back a{ color:rgba(255,255,255,0.35); font-size:0.8rem; text-decoration:none; }
.back a:hover{ color:#C9A84C; }
</style>
</head><body>
<div class="card">
    <div class="lock">🔐</div>
    <div class="ttl">TECH FEUD</div>
    <div class="sub">' . htmlspecialchars($title) . '</div>';

    if (!empty($GLOBALS['auth_error'])) {
        echo '<div class="err">' . htmlspecialchars($GLOBALS['auth_error']) . '</div>';
    }

    echo '<form method="POST">
        <input type="password" name="password" placeholder="Enter password" autofocus autocomplete="current-password">
        <button type="submit">🔓 Enter</button>
    </form>
    <div class="back"><a href="index.php">← Back to Home</a></div>
</div>
</body></html>';
    exit;
}
