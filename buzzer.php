<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#1A237E">
<title>Tech Feud — Buzzer</title>
<link rel="stylesheet" href="css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;600;700&family=Roboto:wght@300;400;700&display=swap">
<style>
html, body {
    height: 100%;
    overflow: hidden;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
}

/* ── LOGIN SCREEN ── */
#login-screen {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    height: 100vh;
    height: 100dvh;
    padding: 30px 20px;
    background: radial-gradient(ellipse at top, #1a237e 0%, #050a30 100%);
    text-align: center;
}

.login-title {
    font-family: 'Anton', sans-serif;
    font-size: 2.4rem;
    color: #C9A84C;
    letter-spacing: 3px;
}

.login-subtitle {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.5);
    margin-bottom: 8px;
}

.login-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(201,168,76,0.3);
    border-radius: 20px;
    padding: 28px 20px;
    width: 100%;
    max-width: 360px;
}

.login-card-title {
    font-family: 'Oswald', sans-serif;
    color: #C9A84C;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
}

.contestant-option {
    display: block;
    width: 100%;
    background: rgba(26,35,126,0.6);
    border: 2px solid rgba(201,168,76,0.3);
    border-radius: 12px;
    padding: 14px 16px;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Oswald', sans-serif;
    font-size: 1.1rem;
    color: #fff;
    text-align: center;
    margin-bottom: 8px;
    -webkit-tap-highlight-color: transparent;
}

.contestant-option:active { transform: scale(0.97); }

.contestant-option.selected {
    background: rgba(201,168,76,0.25);
    border-color: #C9A84C;
    color: #C9A84C;
    font-weight: 700;
}

.join-btn {
    display: block;
    width: 100%;
    background: linear-gradient(135deg, #A07830, #C9A84C);
    color: #000;
    border: none;
    border-radius: 14px;
    padding: 16px;
    font-family: 'Anton', sans-serif;
    font-size: 1.3rem;
    letter-spacing: 2px;
    cursor: pointer;
    margin-top: 16px;
    transition: all 0.2s;
    display: none;
}

.join-btn:active { transform: scale(0.97); }

.waiting-msg {
    color: rgba(255,255,255,0.4);
    font-size: 0.9rem;
    padding: 20px;
}

/* ── BUZZER SCREEN ── */
#buzzer-screen {
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    height: 100vh;
    height: 100dvh;
    padding: 20px 16px 30px;
    background: radial-gradient(ellipse at top, #1a237e 0%, #050a30 100%);
}

.buzzer-header {
    text-align: center;
    width: 100%;
}

.buzzer-logo-img {
    max-height: 45px;
    max-width: 120px;
    object-fit: contain;
    display: none;
    margin: 0 auto 6px;
}

.buzzer-game-title {
    font-family: 'Anton', sans-serif;
    font-size: 1.5rem;
    color: #C9A84C;
    letter-spacing: 2px;
}

/* Center buzzer area */
.buzzer-center {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    flex: 1;
    justify-content: center;
    width: 100%;
}

.player-name-display {
    font-family: 'Oswald', sans-serif;
    font-size: 1.4rem;
    color: #C9A84C;
    text-align: center;
    letter-spacing: 1px;
}

.player-score-display {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.5);
    text-align: center;
}

/* Phone timer */
.phone-timer {
    font-family: 'Anton', sans-serif;
    font-size: 3rem;
    color: #C9A84C;
    text-align: center;
    display: none;
    line-height: 1;
}
.phone-timer.urgent { color: #ff4444; animation: timerPulse 0.5s ease infinite; }

/* THE BUZZER BUTTON */
.buzzer-btn {
    width: min(65vw, 230px);
    height: min(65vw, 230px);
    border-radius: 50%;
    border: none;
    cursor: pointer;
    font-family: 'Anton', sans-serif;
    font-size: 1.8rem;
    letter-spacing: 2px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 6px;
    -webkit-tap-highlight-color: transparent;
    transition: transform 0.1s ease, box-shadow 0.1s ease;
}

.buzzer-btn.state-ready {
    background: radial-gradient(circle at 35% 35%, #ff6666, #cc0000, #880000);
    color: white;
    box-shadow: 0 8px 0 #440000, 0 10px 30px rgba(255,0,0,0.5), inset 0 2px 4px rgba(255,255,255,0.3);
}

.buzzer-btn.state-ready:active {
    transform: translateY(6px);
    box-shadow: 0 2px 0 #440000, 0 4px 10px rgba(255,0,0,0.3);
}

.buzzer-btn.state-locked {
    background: radial-gradient(circle at 35% 35%, #555, #333, #111);
    color: #666;
    box-shadow: 0 4px 0 #000, 0 6px 15px rgba(0,0,0,0.5);
    cursor: not-allowed;
}

.buzzer-btn.state-buzzed {
    background: radial-gradient(circle at 35% 35%, #66ff66, #00cc00, #008800);
    color: white;
    box-shadow: 0 8px 0 #004400, 0 10px 30px rgba(0,255,0,0.4);
}

.buzzer-btn.state-waiting {
    background: radial-gradient(circle at 35% 35%, #aaaaaa, #777777, #444444);
    color: #ccc;
    box-shadow: 0 4px 0 #222, 0 6px 15px rgba(0,0,0,0.4);
    cursor: not-allowed;
}

/* Status box */
.status-box {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 12px 20px;
    text-align: center;
    width: 100%;
    max-width: 300px;
    min-height: 48px;
    font-family: 'Oswald', sans-serif;
    font-size: 1rem;
    color: rgba(255,255,255,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Rank badge */
.rank-badge {
    display: none;
    background: #C9A84C;
    color: #000;
    font-family: 'Anton', sans-serif;
    font-size: 1.8rem;
    padding: 8px 28px;
    border-radius: 30px;
    letter-spacing: 2px;
}

.buzzer-footer {
    font-size: 0.7rem;
    color: rgba(255,255,255,0.2);
    text-align: center;
}

@keyframes timerPulse {
    0%,100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>
</head>
<body>

<!-- ══ LOGIN SCREEN ══ -->
<div id="login-screen">
    <div>
        <img id="login-logo" src="" alt="" style="max-height:55px;max-width:160px;object-fit:contain;margin-bottom:10px;display:none;">
        <div class="login-title">TECH FEUD</div>
        <div class="login-subtitle">Tap your name to join the game</div>
    </div>

    <div class="login-card">
        <div class="login-card-title">Select Your Name</div>
        <div id="contestant-grid">
            <div class="waiting-msg">Waiting for host to start the game...</div>
        </div>
        <button class="join-btn" id="join-btn">🎮 JOIN GAME</button>
    </div>
</div>

<!-- ══ BUZZER SCREEN ══ -->
<div id="buzzer-screen">
    <div class="buzzer-header">
        <img id="buzzer-logo" src="" alt="" class="buzzer-logo-img">
        <div class="buzzer-game-title">TECH FEUD</div>
    </div>

    <div class="buzzer-center">
        <div class="player-name-display" id="player-display-name">—</div>
        <div class="player-score-display" id="player-display-score">Score: 0</div>

        <div class="phone-timer" id="phone-timer">30</div>

        <button class="buzzer-btn state-waiting" id="buzzer-btn" onclick="pressBuzzer()">
            <span style="font-size:2rem;">🔔</span>
            <span id="btn-label">WAIT</span>
        </button>

        <div class="rank-badge" id="rank-badge">1st!</div>
        <div class="status-box" id="status-box">Connecting...</div>
    </div>

    <div class="buzzer-footer">Tech Feud • presented by Swayambhu 2026</div>
</div>

<audio id="buzz-snd" preload="auto">
    <source src="sounds/buzzer.mp3" type="audio/mpeg">
</audio>

<script>
// ═══════════════════════════════════════
// STATE — all buzzer state in one object
// ═══════════════════════════════════════
let myContestant = null;
let selectedContestantId = null;
let pollInterval = null;
let timerInterval = null;

// Per-question state (reset when question changes)
let qState = {
    questionId: null,
    hasBuzzed: false,
    myRank: null,
};

// Last known server state for comparison
let lastKnown = {
    buzzersLocked: false,
    gameActive: false,
    questionId: null,
    timerRunning: false,
    timerSeconds: 0,
    timerStartedAt: 0,
};

// ── Restore session
const stored = sessionStorage.getItem('tf_contestant');
if (stored) {
    try { myContestant = JSON.parse(stored); } catch(e) {}
}

// ═══════════════════════════════════════
// POLLING
// ═══════════════════════════════════════
function startPolling() {
    if (pollInterval) clearInterval(pollInterval);
    fetchState(); // immediately
    pollInterval = setInterval(fetchState, 1500);
}

function fetchState() {
    fetch('api/api.php?action=get_state')
        .then(r => r.json())
        .then(data => handleState(data))
        .catch(err => setStatus('Connection error...', 'waiting'));
}

function handleState(data) {
    const game = data.game;
    const contestants = data.contestants || [];

    // ── FIX: Always parse as int — MySQL returns strings
    const isActive    = parseInt(game.is_active) === 1;
    const buzzLocked  = parseInt(game.buzzers_locked) === 1;
    const timerRunning = parseInt(game.timer_running) === 1;
    const timerSeconds = parseInt(game.timer_seconds) || 30;
    const timerStartAt = parseInt(game.timer_started_at) || 0;
    const questionId  = parseInt(game.current_question_id) || null;

    // ── Logo
    if (game.logo_path) {
        ['login-logo','buzzer-logo'].forEach(id => {
            const el = document.getElementById(id);
            if (el.getAttribute('data-src') !== game.logo_path) {
                el.src = game.logo_path;
                el.style.display = 'block';
                el.setAttribute('data-src', game.logo_path);
            }
        });
    }

    // ── If not yet joined — show login
    if (!myContestant) {
        renderLoginScreen(isActive, contestants);
        return;
    }

    // ── Sync my score
    const me = contestants.find(c => String(c.id) === String(myContestant.id));
    if (me) {
        document.getElementById('player-display-score').textContent = 'Score: ' + me.score;
        myContestant.score = me.score;
    }

    // Show buzzer screen
    document.getElementById('login-screen').style.display = 'none';
    document.getElementById('buzzer-screen').style.display = 'flex';
    document.getElementById('player-display-name').textContent = myContestant.name;

    // ── FIX: Detect question change → reset per-question state
    if (questionId !== qState.questionId) {
        qState.questionId = questionId;
        qState.hasBuzzed = false;
        qState.myRank = null;
        document.getElementById('rank-badge').style.display = 'none';
        clearInterval(timerInterval);
        document.getElementById('phone-timer').style.display = 'none';
    }

    // ── FIX: Detect buzzer unlock → reset hasBuzzed so button becomes active again
    if (lastKnown.buzzersLocked && !buzzLocked) {
        // Admin just unlocked — clear our buzzer state too
        qState.hasBuzzed = false;
        qState.myRank = null;
        document.getElementById('rank-badge').style.display = 'none';
    }

    // Save for next comparison
    lastKnown = { buzzersLocked: buzzLocked, gameActive: isActive, questionId, timerRunning, timerSeconds, timerStartAt };

    // ── Timer
    const phoneTimer = document.getElementById('phone-timer');
    if (timerRunning && timerStartAt > 0) {
        phoneTimer.style.display = 'block';
        startPhoneTimer(timerSeconds, timerStartAt);
    } else if (!timerRunning) {
        phoneTimer.style.display = 'none';
        clearInterval(timerInterval);
    }

    // ── Check if I'm in buzzer queue
    const bq = data.buzzer_queue || [];
    const myBuzz = bq.find(b => String(b.contestant_id) === String(myContestant.id));
    if (myBuzz && !qState.hasBuzzed) {
        qState.hasBuzzed = true;
        qState.myRank = parseInt(myBuzz.rank_position);
    }

    // ── Update buzzer UI
    updateBuzzerUI(isActive, buzzLocked, questionId, data);
}

function updateBuzzerUI(isActive, buzzLocked, questionId, data) {
    const btn = document.getElementById('buzzer-btn');
    const btnLabel = document.getElementById('btn-label');
    const statusBox = document.getElementById('status-box');
    const rankBadge = document.getElementById('rank-badge');

    if (!isActive) {
        setBtnState('waiting');
        statusBox.textContent = 'Waiting for game to start...';
        return;
    }

    if (!questionId) {
        setBtnState('waiting');
        statusBox.textContent = '⏳ Waiting for question...';
        return;
    }

    if (qState.hasBuzzed) {
        setBtnState('buzzed');
        const rankLabels = ['🥇 You were FIRST!', '✌️ You were 2nd!', '👍 You were 3rd'];
        const rankLabel = qState.myRank ? (rankLabels[qState.myRank - 1] || `You were #${qState.myRank}`) : '✅ Buzzed!';
        statusBox.textContent = rankLabel;
        if (qState.myRank) {
            rankBadge.textContent = qState.myRank === 1 ? '1st!' : qState.myRank === 2 ? '2nd' : '3rd';
            rankBadge.style.display = 'block';
        }
        return;
    }

    // FIX: Only show locked if buzzers EXPLICITLY locked by admin, not by default
    if (buzzLocked) {
        setBtnState('locked');
        statusBox.textContent = '🔒 Buzzers locked by host';
        return;
    }

    // Ready to buzz!
    setBtnState('ready');
    const isRapidFire = (data && data.game && data.game.game_mode === 'rapid_fire');
    statusBox.textContent = isRapidFire
        ? '⚡ RAPID-FIRE — buzz first to answer!'
        : '❓ Question active — buzz if you know!';
}

function setBtnState(state) {
    const btn = document.getElementById('buzzer-btn');
    const label = document.getElementById('btn-label');
    btn.className = 'buzzer-btn state-' + state;
    const labels = { ready: 'BUZZ!', locked: 'LOCKED', buzzed: 'BUZZED', waiting: 'WAIT' };
    label.textContent = labels[state] || 'WAIT';
}

// ═══════════════════════════════════════
// LOGIN SCREEN
// ═══════════════════════════════════════
function renderLoginScreen(isActive, contestants) {
    document.getElementById('login-screen').style.display = 'flex';
    document.getElementById('buzzer-screen').style.display = 'none';

    const grid = document.getElementById('contestant-grid');
    const joinBtn = document.getElementById('join-btn');

    if (isActive && contestants.length > 0) {
        grid.innerHTML = '';
        contestants.forEach(c => {
            const btn = document.createElement('button');
            btn.className = 'contestant-option' + (String(selectedContestantId) === String(c.id) ? ' selected' : '');
            btn.textContent = c.name;
            btn.onclick = () => selectContestant(c, btn);
            grid.appendChild(btn);
        });
        joinBtn.style.display = 'block';
    } else {
        grid.innerHTML = '<div class="waiting-msg">Waiting for host to start the game...<br><br>Ask the host to start the game, then refresh this page.</div>';
        joinBtn.style.display = 'none';
    }
}

function selectContestant(c, btn) {
    selectedContestantId = c.id;
    document.querySelectorAll('.contestant-option').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
}

function joinGame() {
    if (!selectedContestantId) {
        alert('Please tap your name first!');
        return;
    }
    fetch('api/api.php?action=get_state')
        .then(r => r.json())
        .then(data => {
            const c = (data.contestants || []).find(c => String(c.id) === String(selectedContestantId));
            if (c) {
                myContestant = c;
                sessionStorage.setItem('tf_contestant', JSON.stringify(c));
                document.getElementById('login-screen').style.display = 'none';
                document.getElementById('buzzer-screen').style.display = 'flex';
                document.getElementById('player-display-name').textContent = c.name;
                // Reset question state for this new join
                qState = { questionId: null, hasBuzzed: false, myRank: null };
            } else {
                alert('Could not find that contestant. Try refreshing!');
            }
        });
}

// ═══════════════════════════════════════
// BUZZER PRESS
// ═══════════════════════════════════════
function pressBuzzer() {
    // FIX: Only allow press if state is ready
    const btn = document.getElementById('buzzer-btn');
    if (!btn.classList.contains('state-ready')) return;
    if (qState.hasBuzzed) return;
    if (!myContestant) return;

    // Play sound immediately for responsiveness
    document.getElementById('buzz-snd').play().catch(() => {});

    // Optimistic update
    setBtnState('buzzed');
    document.getElementById('status-box').textContent = '⏳ Sending buzz...';

    fetch('api/api.php?action=buzz', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            contestant_id: myContestant.id,
            contestant_name: myContestant.name
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            qState.hasBuzzed = true;
            qState.myRank = data.rank;
            const rankLabels = ['🥇 You were FIRST!', '✌️ You were 2nd!', '👍 You were 3rd'];
            document.getElementById('status-box').textContent = rankLabels[data.rank - 1] || `You buzzed #${data.rank}`;
            if (data.rank <= 3) {
                const rankBadge = document.getElementById('rank-badge');
                rankBadge.textContent = data.rank === 1 ? '1st!' : data.rank === 2 ? '2nd' : '3rd';
                rankBadge.style.display = 'block';
            }
        } else {
            // Failed — revert
            qState.hasBuzzed = false;
            setBtnState(lastKnown.buzzersLocked ? 'locked' : 'ready');
            document.getElementById('status-box').textContent = data.message || 'Already buzzed or buzzers locked!';
        }
    })
    .catch(() => {
        qState.hasBuzzed = false;
        setBtnState('ready');
        document.getElementById('status-box').textContent = 'Network error — try again!';
    });
}

// ═══════════════════════════════════════
// TIMER
// ═══════════════════════════════════════
let _timerStartedAt = 0;
let _timerTotal = 0;

function startPhoneTimer(totalSeconds, startedAt) {
    // Don't restart if same timer
    if (_timerStartedAt === startedAt && _timerTotal === totalSeconds) return;
    _timerStartedAt = startedAt;
    _timerTotal = totalSeconds;

    clearInterval(timerInterval);
    const el = document.getElementById('phone-timer');
    const update = () => {
        const elapsed = (Date.now() - startedAt) / 1000;
        const remaining = Math.max(0, Math.ceil(totalSeconds - elapsed));
        el.textContent = remaining;
        el.className = 'phone-timer' + (remaining <= 5 ? ' urgent' : '');
        if (remaining <= 0) clearInterval(timerInterval);
    };
    update();
    timerInterval = setInterval(update, 500);
}

function setStatus(msg) {
    const el = document.getElementById('status-box');
    if (el) el.textContent = msg;
}

// ═══════════════════════════════════════
// BIND & START
// ═══════════════════════════════════════
document.getElementById('join-btn').addEventListener('click', joinGame);

startPolling();
</script>
</body>
</html>
