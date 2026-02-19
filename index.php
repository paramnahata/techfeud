<?php
// Logout handler
if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header('Location: index.php');
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tech Feud — Home</title>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;600;700&family=Roboto:wght@300;400;700&display=swap">
<style>
body {
    display: flex; align-items: center; justify-content: center;
    min-height: 100vh;
    background: radial-gradient(ellipse at top, #1a237e 0%, #050a30 100%);
    font-family: 'Roboto', sans-serif;
}
.home-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 24px; padding: 44px 40px;
    text-align: center; max-width: 480px; width: 90%;
}
.home-title {
    font-family: 'Anton', sans-serif; font-size: 3.5rem;
    background: linear-gradient(135deg, #C9A84C, #F0C060, #C9A84C);
    background-size: 200% auto;
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: goldShimmer 3s linear infinite;
    letter-spacing: 3px; margin-bottom: 4px;
}
.home-sub   { color: var(--gold); font-family: 'Oswald', sans-serif; font-size: 1rem; margin-bottom: 4px; }
.home-tagline { color: rgba(255,255,255,0.45); font-size: 0.85rem; margin-bottom: 32px; }
.home-btns  { display: grid; gap: 10px; }
.home-btn {
    display: block; padding: 15px 24px; border-radius: 12px;
    text-decoration: none; font-family: 'Oswald', sans-serif;
    font-size: 1rem; letter-spacing: 1px; transition: all 0.18s;
}
.home-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.4); }
.hbtn-admin    { background: linear-gradient(135deg, #8a6a20, #C9A84C); color: #000; font-weight: 700; }
.hbtn-projector{ background: linear-gradient(135deg, #1565C0, #1E88E5); color: #fff; }
.hbtn-buzzer   { background: linear-gradient(135deg, #B71C1C, #D32F2F); color: #fff; }
.divider { margin: 10px 0 2px; color: rgba(255,255,255,0.25); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; }
.info-box {
    margin-top: 24px; background: rgba(0,0,0,0.3);
    border-radius: 12px; padding: 14px 16px;
    font-size: 0.82rem; color: rgba(255,255,255,0.45); text-align: left;
    line-height: 1.7;
}
.info-box strong { color: var(--gold); }
code {
    background: rgba(255,255,255,0.08); padding: 1px 6px;
    border-radius: 4px; font-size: 0.8rem;
}
.logout-link {
    margin-top: 16px; font-size: 0.75rem;
    color: rgba(255,255,255,0.2);
}
.logout-link a { color: inherit; text-decoration: underline; }
.logout-link a:hover { color: rgba(255,255,255,0.5); }

/* Passwords hint box */
.pw-hint {
    margin-top: 10px; padding: 12px 14px;
    background: rgba(201,168,76,0.06);
    border: 1px solid rgba(201,168,76,0.2);
    border-radius: 10px; font-size: 0.8rem;
    color: rgba(255,255,255,0.5); text-align: left; line-height: 1.8;
}
.pw-hint strong { color: var(--gold); }
</style>
</head>
<body>

<div class="home-card">
    <div class="home-title">TECH FEUD</div>
    <div class="home-sub">presented by Swayambhu 2026</div>
    <div class="home-tagline">The Ultimate Technology Game Show</div>

    <div class="home-btns">
        <a class="home-btn hbtn-admin" href="admin.php">⚙️ ADMIN DASHBOARD</a>

        <div class="divider">Open on projector / laptop</div>
        <a class="home-btn hbtn-projector" href="presenter.php" target="_blank">📺 STAGE SCREEN (Projector)</a>

        <div class="divider">Open on contestant phones</div>
        <a class="home-btn hbtn-buzzer" href="buzzer.php" target="_blank">🔔 BUZZER (Phone)</a>
    </div>

    <div class="pw-hint">
        🔐 <strong>Password protected:</strong><br>
        Admin: <code>techfeud_admin</code><br>
        Stage Screen: <code>techfeud_stage</code><br>
        <span style="font-size:0.73rem;opacity:.7;">Change these in <code>auth.php</code></span>
    </div>

    <div class="info-box">
        <strong>📡 How to connect:</strong><br>
        1. Connect all devices to the <strong>same Wi-Fi</strong><br>
        2. Find your laptop IP: <code>ipconfig</code> (Win) / <code>ifconfig</code> (Mac)<br>
        3. Contestants open: <strong>http://YOUR-IP/techfeud/buzzer.php</strong>
    </div>

    <div class="logout-link"><a href="?logout=1">Clear sessions / logout</a></div>
</div>

</body>
</html>
