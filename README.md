# 🎮 TECH FEUD — Complete Setup Guide
## BAMC x ISTE's Paradox

---

## 📦 WHAT'S IN THIS PACKAGE

| File/Folder | Purpose |
|---|---|
| `index.php` | Home page with links to all screens |
| `admin.php` | Admin dashboard (host controls everything here) |
| `presenter.php` | Projector/stage screen (big screen) |
| `buzzer.php` | Contestant buzzer (open on phones) |
| `config.php` | Database settings |
| `api/api.php` | Backend API |
| `api/events.php` | Real-time SSE stream |
| `css/style.css` | All styling |
| `js/admin.js` | Admin panel logic |
| `js/presenter.js` | Stage screen logic |
| `database.sql` | MySQL setup script |
| `sounds/` | Put your MP3 sound files here |
| `uploads/` | Logo uploads go here (auto-created) |

---

## 🚀 SETUP STEPS

### Step 1 — Install XAMPP
Download from: https://www.apachefriends.org/
Start **Apache** and **MySQL** in the XAMPP Control Panel.

### Step 2 — Copy files
Place the entire `techfeud` folder inside:
```
C:\xampp\htdocs\techfeud\
```

### Step 3 — Create database
1. Open browser → go to `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Choose `database.sql` from this folder
4. Click **Go**

### Step 4 — Configure database (if needed)
Open `config.php` and update if your MySQL has a password:
```php
define('DB_USER', 'root');   // your MySQL username
define('DB_PASS', '');       // your MySQL password (usually blank for XAMPP)
```

### Step 5 — Add sound files
Place these MP3 files inside the `sounds/` folder:

| Filename | When it plays |
|---|---|
| `intro.mp3` | Welcome screen loads |
| `question.mp3` | Question is shown (loops) |
| `buzzer.mp3` | Someone presses buzzer |
| `correct.mp3` | Correct answer revealed |
| `wrong.mp3` | Wrong answer |
| `timer-low.mp3` | Last 5 seconds (loops) |
| `timesup.mp3` | Timer hits 0 |
| `reveal.mp3` | Answer card flips |
| `winner.mp3` | Leaderboard shown |

🎵 **Free sounds**: https://mixkit.co/free-sound-effects/game-show/

### Step 6 — Open the app
Go to: `http://localhost/techfeud/`

---

## 🎮 HOW TO RUN THE EVENT

### Before the event:
1. Open `http://localhost/techfeud/admin.php` on your laptop
2. Open `http://localhost/techfeud/presenter.php` on the projector (or second screen)
3. Find your laptop IP: open Command Prompt → type `ipconfig` → look for **IPv4 Address** (e.g. `192.168.1.5`)
4. Tell contestants to open `http://192.168.1.5/techfeud/buzzer.php` on their phones

### During the event:
1. **Admin panel**: Enter contestant names → click **Start Game**
2. **Question Manager**: Load prebuilt questions or add your own
3. **Question Manager → Preloaded**: Click to load 10 ready-made tech questions
4. Select a question from dropdown → click **Show Question** to display on projector
5. Set timer and click **Start** → projector shows countdown
6. When someone presses their buzzer:
   - Top 3 who buzzed appear in the **Buzzer Control** panel
   - The winner's name flashes on the projector automatically
7. If answer is correct → click the answer in **Answer Controls** to reveal it and award points
8. If wrong → click **No Answer / Wrong** and call on 2nd buzzer
9. After each question → click **New Round** to reset
10. Click **Leaderboard** to show rankings on projector

---

## 📱 QUESTION TYPES

| Type | Description |
|---|---|
| **Family Feud** | Hidden answers (1-8) revealed one by one. Points shown per answer. |
| **KBC** | 4 options (A B C D). You mark correct answer when adding. Host reveals when ready. |
| **Open Answer** | No fixed answers. Host judges manually and awards custom points. |

---

## 🔊 SOUND GUIDE (Mixkit recommended links)

Search these on https://mixkit.co:
- "game show reveal" → question.mp3
- "correct answer bell" → correct.mp3  
- "wrong answer buzz" → wrong.mp3
- "game show buzzer" → buzzer.mp3
- "dramatic timer" → timer-low.mp3
- "fanfare trumpets" → winner.mp3

---

## ❓ TROUBLESHOOTING

| Problem | Solution |
|---|---|
| Blank page | Check XAMPP Apache & MySQL are running |
| Database error | Run `database.sql` in phpMyAdmin |
| Phone can't connect | Make sure phone and laptop on same Wi-Fi |
| Buzzer not working | Check browser allows connections to local network |
| Sounds not playing | Add MP3 files to `sounds/` folder |
| Logo not uploading | Make sure `uploads/` folder exists and is writable |

---

## 🏗️ BUILT WITH
- **PHP 8+** + **MySQL** (XAMPP)
- **Vanilla JavaScript** (no frameworks)
- **Server-Sent Events** for real-time updates
- **CSS3** animations and 3D card flips
- KBC-inspired UI with Family Feud mechanics

---

*Tech Feud © 2025 — BAMC x ISTE's Paradox*
