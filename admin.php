<?php
require_once __DIR__ . '/auth.php';
requireAuth('admin');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tech Feud — Control Room</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Barlow+Condensed:wght@400;600;700;900&family=Barlow:wght@300;400;500&display=swap');

:root {
  --gold:   #C9A84C;
  --gold-l: #F0C060;
  --gold-d: #8a6a20;
  --bg:     #080c12;
  --bg2:    #0d1320;
  --bg3:    #111928;
  --bg4:    #182030;
  --bdr:    rgba(255,255,255,0.07);
  --bdr-g:  rgba(201,168,76,0.3);
  --tx:     #e8edf5;
  --tx-d:   rgba(232,237,245,0.45);
  --tx-dd:  rgba(232,237,245,0.22);
  --green:  #22c55e;
  --red:    #ef4444;
  --blue:   #3b82f6;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  height: 100%; background: var(--bg);
  color: var(--tx); font-family: 'Barlow', sans-serif;
  font-size: 14px; overflow: hidden;
}

/* ── TOPBAR ─────────────────────────────── */
#topbar {
  position: fixed; top: 0; left: 0; right: 0; height: 46px;
  background: var(--bg2); border-bottom: 1px solid var(--bdr-g);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 14px; z-index: 100;
  box-shadow: 0 2px 24px rgba(0,0,0,0.7);
}
.tb-left { display: flex; align-items: center; gap: 10px; }
#admin-logo-img { max-height: 28px; max-width: 80px; object-fit: contain; display: none; }
.tb-title {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 1.2rem; font-weight: 900;
  letter-spacing: 3px; text-transform: uppercase; color: var(--gold);
}
.tb-sep { width: 1px; height: 22px; background: var(--bdr); }
.badge {
  padding: 2px 8px; border-radius: 3px;
  font-family: 'Share Tech Mono', monospace;
  font-size: 0.68rem; letter-spacing: 1px; text-transform: uppercase; border: 1px solid;
}
.b-off   { color: #444; border-color: #2a2a2a; background: rgba(0,0,0,0.3); }
.b-green { color: var(--green); border-color: var(--green); background: rgba(34,197,94,0.1); }
.b-gold  { color: var(--gold);  border-color: var(--gold);  background: rgba(201,168,76,0.1); }
.b-red   { color: var(--red);   border-color: var(--red);   background: rgba(239,68,68,0.1); }
.tb-right { display: flex; gap: 6px; }
.tb-link {
  padding: 4px 10px; border-radius: 4px;
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.75rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
  text-decoration: none; color: var(--tx-d);
  background: var(--bg3); border: 1px solid var(--bdr); transition: all 0.15s;
}
.tb-link:hover { border-color: var(--gold); color: var(--gold); }

/* ── THREE-COLUMN LAYOUT ────────────────── */
#layout {
  position: fixed; top: 46px; left: 0; right: 0; bottom: 0;
  display: grid; grid-template-columns: 264px 1fr 288px;
}
.col { overflow-y: auto; overflow-x: hidden; padding: 10px; border-right: 1px solid var(--bdr); }
.col:last-child { border-right: none; }
.col::-webkit-scrollbar { width: 3px; }
.col::-webkit-scrollbar-thumb { background: var(--gold-d); border-radius: 2px; }

/* ── SECTION CARD ───────────────────────── */
.sec { background: var(--bg3); border: 1px solid var(--bdr); border-radius: 8px; margin-bottom: 9px; overflow: hidden; }
.sec-h {
  padding: 7px 11px; background: var(--bg4); border-bottom: 1px solid var(--bdr);
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.68rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
  color: var(--tx-d); display: flex; align-items: center; gap: 5px;
}
.sec-h i { color: var(--gold); font-size: 0.6rem; }
.sec-b { padding: 11px; }

/* ── BUTTONS ────────────────────────────── */
.btn {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.82rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
  padding: 7px 12px; border-radius: 5px; border: none; cursor: pointer;
  transition: all 0.12s; display: inline-flex; align-items: center; justify-content: center; gap: 5px;
}
.btn:disabled { opacity: 0.28; cursor: not-allowed; pointer-events: none; }
.btn:not(:disabled):hover { filter: brightness(1.18); transform: translateY(-1px); }
.btn:not(:disabled):active { transform: translateY(0); filter: brightness(0.95); }
.btn-full { width: 100%; }
.btn-sm { padding: 5px 9px; font-size: 0.75rem; }
.btn-gold   { background: linear-gradient(135deg, var(--gold-d), var(--gold)); color: #000; }
.btn-green  { background: #15803d; color: #fff; }
.btn-red    { background: #991b1b; color: #fff; }
.btn-blue   { background: #1d4ed8; color: #fff; }
.btn-purple { background: #7e22ce; color: #fff; }
.btn-ghost  { background: var(--bg4); color: var(--tx-d); border: 1px solid var(--bdr); }
.btn-ghost:not(:disabled):hover { border-color: var(--gold); color: var(--gold); }

/* ── INPUTS ─────────────────────────────── */
input, select, textarea {
  background: var(--bg); color: var(--tx);
  border: 1px solid var(--bdr); border-radius: 5px;
  padding: 6px 9px; font-family: 'Barlow', sans-serif;
  font-size: 0.83rem; width: 100%; transition: border-color 0.15s;
}
input:focus, select:focus, textarea:focus {
  outline: none; border-color: var(--gold);
  box-shadow: 0 0 0 2px rgba(201,168,76,0.14);
}
select option { background: var(--bg2); }
label { font-size: 0.7rem; color: var(--tx-d); letter-spacing: 0.5px; text-transform: uppercase; display: block; margin-bottom: 4px; }

/* ── PROJECTOR BUTTONS ──────────────────── */
.proj-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.proj-btn {
  padding: 11px 5px; border-radius: 6px; border: 1px solid var(--bdr);
  background: var(--bg); color: var(--tx-d);
  font-family: 'Barlow Condensed', sans-serif; font-size: 0.78rem;
  font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
  cursor: pointer; transition: all 0.12s;
  display: flex; flex-direction: column; align-items: center; gap: 4px;
}
.proj-btn .ico { font-size: 1.25rem; }
.proj-btn:hover { border-color: rgba(255,255,255,0.2); color: var(--tx); }
.proj-btn.active {
  background: linear-gradient(135deg, var(--gold-d), var(--gold));
  color: #000; border-color: var(--gold); font-weight: 900;
  box-shadow: 0 0 14px rgba(201,168,76,0.35);
}

/* ── TIMER ──────────────────────────────── */
.timer-num {
  font-family: 'Share Tech Mono', monospace;
  font-size: 3.8rem; text-align: center; color: var(--gold);
  line-height: 1; margin: 6px 0;
  text-shadow: 0 0 24px rgba(201,168,76,0.45);
  letter-spacing: -2px;
}
.timer-num.urgent { color: var(--red); text-shadow: 0 0 20px rgba(239,68,68,0.5); animation: blink 0.5s ease infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.55} }

.timer-presets { display: grid; grid-template-columns: repeat(6,1fr); gap: 3px; margin-bottom: 7px; }
.timer-presets button {
  padding: 4px 2px; border-radius: 3px; border: 1px solid var(--bdr);
  background: var(--bg); color: var(--tx-dd);
  font-family: 'Share Tech Mono', monospace; font-size: 0.7rem;
  cursor: pointer; transition: all 0.12s;
}
.timer-presets button:hover { border-color: var(--gold); color: var(--gold); }

.timer-adj { display: flex; align-items: center; gap: 3px; margin-bottom: 7px; }
.timer-adj input {
  text-align: center; font-family: 'Share Tech Mono', monospace;
  font-size: 1rem; padding: 5px; min-width: 0;
}

/* ── BUZZER QUEUE ───────────────────────── */
.bq { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 5px; margin-bottom: 7px; }
.bq-card {
  border-radius: 5px; padding: 7px 5px; text-align: center;
  border: 1px dashed rgba(255,255,255,0.12); background: var(--bg);
}
.bq-card.filled.r1 { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.45); }
.bq-card.filled.r2 { background: rgba(120,120,120,0.12); border: 1px solid #555; }
.bq-card.filled.r3 { background: rgba(180,100,30,0.12); border: 1px solid #92400e; }
.bq-rnk { font-size: 0.6rem; color: var(--tx-dd); margin-bottom: 2px; }
.bq-nm  { font-family: 'Barlow Condensed', sans-serif; font-size: 0.82rem; font-weight: 700; color: var(--tx-d); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bq-card.filled .bq-nm { color: var(--tx); }

/* ── ANSWER BUTTONS ─────────────────────── */
.ans-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
.ans-btn {
  padding: 13px 8px; border-radius: 6px;
  border: 1px solid var(--bdr); background: var(--bg4); color: var(--tx);
  cursor: pointer; font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.85rem; font-weight: 700;
  transition: all 0.12s; text-align: center;
  display: flex; flex-direction: column; align-items: center; gap: 3px;
}
.ans-btn .al { font-size: 1.3rem; font-family: 'Share Tech Mono', monospace; color: var(--gold); }
.ans-btn .at { font-size: 0.73rem; color: var(--tx-d); line-height: 1.2; word-break: break-word; }
.ans-btn .ac { font-size: 0.6rem; color: var(--green); }
.ans-btn:not(:disabled):hover { border-color: var(--gold); background: rgba(201,168,76,0.07); }
.ans-btn.revealed { background: rgba(34,197,94,0.1); border-color: var(--green); }
.ans-btn.revealed .al { color: var(--green); }

/* ── ACTIVE QUESTION BOX ────────────────── */
#aq-box {
  background: var(--bg); border: 1px solid var(--bdr-g);
  border-radius: 5px; padding: 9px 11px;
  margin-bottom: 9px; display: none;
}
#aq-box .aql {
  font-size: 0.62rem; color: var(--gold);
  font-family: 'Barlow Condensed', sans-serif;
  font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
  margin-bottom: 3px;
}
#active-q-text {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.95rem; font-weight: 600; line-height: 1.3;
}

/* ── AWARD ROW ──────────────────────────── */
.award-row { display: grid; grid-template-columns: 1fr 58px 36px; gap: 5px; align-items: center; }

/* ── SCORE ROWS ─────────────────────────── */
.sc-row { display: grid; grid-template-columns: 1fr 56px 30px; gap: 4px; align-items: center; margin-bottom: 5px; }
.sc-name { font-family: 'Barlow Condensed', sans-serif; font-size: 0.88rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── CONTESTANT INPUTS ──────────────────── */
.c-row { display: grid; grid-template-columns: 16px 1fr; gap: 5px; align-items: center; margin-bottom: 4px; }
.c-num { font-family: 'Share Tech Mono', monospace; font-size: 0.7rem; color: var(--gold); text-align: center; }

/* ── KBC ANSWER FORM ────────────────────── */
.kbc-row { display: grid; grid-template-columns: 24px 1fr auto; gap: 5px; align-items: center; margin-bottom: 5px; }
.kbc-lbl {
  width: 24px; height: 24px; border-radius: 50%;
  background: var(--gold-d); color: #000;
  font-family: 'Barlow Condensed', sans-serif; font-size: 0.8rem; font-weight: 900;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.cr-radio { display: flex; align-items: center; gap: 3px; white-space: nowrap; }
.cr-radio input { width: auto; }
.cr-radio span { font-size: 0.68rem; color: var(--tx-d); }

/* ── QUESTION LIST ──────────────────────── */
.q-item {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 9px; border-radius: 5px;
  border: 1px solid var(--bdr); background: var(--bg);
  margin-bottom: 5px; cursor: pointer; transition: all 0.12s;
}
.q-item:hover { border-color: rgba(255,255,255,0.18); }
.q-item.active-q { border-color: var(--gold); background: rgba(201,168,76,0.05); }
.q-pill {
  padding: 1px 6px; border-radius: 3px;
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.62rem; font-weight: 700; letter-spacing: 1px; flex-shrink: 0;
}
.p-kbc  { background: rgba(168,85,247,0.18); color: #c084fc; border: 1px solid #6b21a8; }
.p-open { background: rgba(34,197,94,0.14); color: #86efac; border: 1px solid #166534; }
.q-txt  { font-size: 0.8rem; color: var(--tx-d); flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.q-item.active-q .q-txt { color: var(--tx); }
.q-acts { display: flex; gap: 3px; flex-shrink: 0; }

/* ── TABS ───────────────────────────────── */
.tab-bar { display: flex; gap: 3px; margin-bottom: 9px; }
.tab-btn {
  padding: 4px 10px; border-radius: 4px; border: none; cursor: pointer;
  font-family: 'Barlow Condensed', sans-serif; font-size: 0.72rem;
  font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
  background: var(--bg4); color: var(--tx-d); transition: all 0.12s;
}
.tab-btn.active { background: var(--gold); color: #000; }
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* ── SOUND GRID ─────────────────────────── */
.snd-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 3px; }

/* ── LOGO UPLOAD ────────────────────────── */
.logo-drop {
  border: 1px dashed var(--bdr-g); border-radius: 5px;
  padding: 13px; text-align: center; cursor: pointer;
  transition: all 0.15s; font-size: 0.78rem; color: var(--tx-d);
}
.logo-drop:hover { border-color: var(--gold); color: var(--gold); }

/* ── TOAST MESSAGES ─────────────────────── */
#toasts {
  position: fixed; bottom: 14px; right: 14px;
  z-index: 9999; max-width: 290px;
  display: flex; flex-direction: column; gap: 5px;
}
.toast {
  padding: 9px 13px; border-radius: 5px; font-size: 0.8rem;
  border: 1px solid; animation: tup 0.18s ease;
}
@keyframes tup { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
.t-ok  { background: rgba(21,128,61,0.92);  border-color: var(--green); color:#fff; }
.t-err { background: rgba(153,27,27,0.92);  border-color: var(--red);   color:#fff; }
.t-inf { background: rgba(29,78,216,0.92);  border-color: var(--blue);  color:#fff; }

/* ── UTILS ──────────────────────────────── */
.row  { display: flex; gap: 6px; align-items: center; }
.row2 { display: flex; gap: 5px; }
.hr   { height: 1px; background: var(--bdr); margin: 9px 0; }
.muted { font-size: 0.72rem; color: var(--tx-dd); }
.json-hint {
  background: var(--bg); border: 1px solid var(--bdr); border-radius: 4px;
  padding: 7px 9px; font-family: 'Share Tech Mono', monospace;
  font-size: 0.63rem; color: var(--tx-dd); line-height: 1.7;
  margin-bottom: 7px; white-space: pre-wrap; word-break: break-all;
}
</style>
</head>
<body>

<!-- ════ TOPBAR ════ -->
<div id="topbar">
  <div class="tb-left">
    <img id="admin-logo-img" src="" alt="">
    <span class="tb-title">⚡ Tech Feud</span>
    <div class="tb-sep"></div>
    <span class="badge b-off"  id="badge-game">INACTIVE</span>
    <span class="badge b-off"  id="badge-timer">TIMER OFF</span>
    <span class="badge b-off"  id="badge-buzzer">BUZZERS OPEN</span>
  </div>
  <div class="tb-right">
    <a href="presenter.php" target="_blank" class="tb-link">📺 Projector</a>
    <a href="buzzer.php"    target="_blank" class="tb-link">📱 Buzzer</a>
  </div>
</div>

<div id="layout">

  <!-- ═══════════════════════════════
       LEFT — LIVE CONTROLS
       Projector · Timer · Buzzer · Scores · Sound
       ═══════════════════════════════ -->
  <div class="col">

    <!-- PROJECTOR TOGGLES -->
    <div class="sec">
      <div class="sec-h"><i class="fas fa-tv"></i> Projector Screen</div>
      <div class="sec-b">
        <div class="proj-grid">
          <button class="proj-btn" data-type="show_welcome"     id="tog-welcome">
            <span class="ico">🏠</span>Welcome
          </button>
          <button class="proj-btn" data-type="show_question"    id="tog-question">
            <span class="ico">❓</span>Question
          </button>
          <button class="proj-btn" data-type="show_answers"     id="tog-answers">
            <span class="ico">📋</span>Options
          </button>
          <button class="proj-btn" data-type="show_leaderboard" id="tog-leaderboard">
            <span class="ico">🏆</span>Leaderboard
          </button>
        </div>
        <p class="muted" style="margin-top:6px;">⚡ Options auto-starts timer</p>
      </div>
    </div>

    <!-- TIMER -->
    <div class="sec">
      <div class="sec-h"><i class="fas fa-clock"></i> Timer</div>
      <div class="sec-b">
        <div class="timer-num" id="timer-admin-display">—</div>
        <div class="timer-presets">
          <button onclick="setTimer(15)">15s</button>
          <button onclick="setTimer(20)">20s</button>
          <button onclick="setTimer(30)">30s</button>
          <button onclick="setTimer(45)">45s</button>
          <button onclick="setTimer(60)">60s</button>
          <button onclick="setTimer(90)">90s</button>
        </div>
        <div class="timer-adj">
          <button class="btn btn-ghost btn-sm" onclick="adjustTimer(-10)" style="flex-shrink:0;">−10</button>
          <button class="btn btn-ghost btn-sm" onclick="adjustTimer(-5)"  style="flex-shrink:0;">−5</button>
          <input type="number" id="timer-input" value="30" min="5" max="300">
          <button class="btn btn-ghost btn-sm" onclick="adjustTimer(+5)"  style="flex-shrink:0;">+5</button>
          <button class="btn btn-ghost btn-sm" onclick="adjustTimer(+10)" style="flex-shrink:0;">+10</button>
        </div>
        <div class="row">
          <button class="btn btn-green btn-full" id="timer-start-btn" disabled>
            <i class="fas fa-play"></i> Start
          </button>
          <button class="btn btn-red btn-full" id="timer-stop-btn" disabled>
            <i class="fas fa-stop"></i> Stop
          </button>
        </div>
      </div>
    </div>

    <!-- BUZZER -->
    <div class="sec">
      <div class="sec-h"><i class="fas fa-bolt"></i> Buzzer Queue</div>
      <div class="sec-b">
        <div class="bq" id="buzzer-rank-display">
          <div class="bq-card"><div class="bq-rnk">🥇 1st</div><div class="bq-nm">—</div></div>
          <div class="bq-card"><div class="bq-rnk">🥈 2nd</div><div class="bq-nm">—</div></div>
          <div class="bq-card"><div class="bq-rnk">🥉 3rd</div><div class="bq-nm">—</div></div>
        </div>
        <div class="row">
          <button class="btn btn-red   btn-sm btn-full" id="lock-buzzers-btn"   disabled><i class="fas fa-lock"></i> Lock</button>
          <button class="btn btn-green btn-sm btn-full" id="unlock-buzzers-btn" disabled><i class="fas fa-unlock"></i> Unlock</button>
        </div>
      </div>
    </div>

    <!-- SCORE EDITOR -->
    <div class="sec">
      <div class="sec-h"><i class="fas fa-edit"></i> Scores</div>
      <div class="sec-b">
        <div id="score-editor-list"><div class="muted">Start game to edit.</div></div>
      </div>
    </div>

    <!-- SOUND TEST -->
    <div class="sec">
      <div class="sec-h"><i class="fas fa-music"></i> Sound Test</div>
      <div class="sec-b">
        <div class="snd-grid">
          <button class="btn btn-ghost btn-sm" onclick="testSound('intro')">🎬 intro</button>
          <button class="btn btn-ghost btn-sm" onclick="testSound('question')">❓ q</button>
          <button class="btn btn-ghost btn-sm" onclick="testSound('buzzer')">🔔 buzz</button>
          <button class="btn btn-ghost btn-sm" onclick="testSound('correct')">✅ ok</button>
          <button class="btn btn-ghost btn-sm" onclick="testSound('wrong')">❌ wrong</button>
          <button class="btn btn-ghost btn-sm" onclick="testSound('timer-low')">⏰ low</button>
          <button class="btn btn-ghost btn-sm" onclick="testSound('timesup')">🚨 end</button>
          <button class="btn btn-ghost btn-sm" onclick="testSound('reveal')">🎁 reveal</button>
          <button class="btn btn-ghost btn-sm" onclick="testSound('winner')">🏆 win</button>
        </div>
      </div>
    </div>

  </div><!-- /left col -->


  <!-- ═══════════════════════════════
       CENTRE — ACTIVE QUESTION
       Biggest col: question + answers + award
       ═══════════════════════════════ -->
  <div class="col">

    <div class="sec">
      <div class="sec-h"><i class="fas fa-list-check"></i> Active Question &amp; Answers</div>
      <div class="sec-b">

        <!-- Question selector + new round -->
        <div class="row" style="margin-bottom:8px;">
          <select id="question-select" disabled style="flex:1;">
            <option value="">— Pick a question to go live —</option>
          </select>
          <button class="btn btn-ghost btn-sm" id="new-round-btn" disabled title="Clear answers, keep question">
            <i class="fas fa-rotate"></i> Reset Answers
          </button>
        </div>

        <!-- Active Q text -->
        <div id="aq-box">
          <div class="aql">Now Live ▸</div>
          <div id="active-q-text"></div>
        </div>

        <!-- Answer reveal buttons (A B C D) -->
        <div id="answer-ctrl-grid" class="ans-grid">
          <div class="muted" style="grid-column:1/-1;padding:10px 0;">Select a question above to see answer options.</div>
        </div>

        <!-- Award Points -->
        <div id="kbc-award-section" style="display:none;">
          <div class="hr"></div>
          <label>Award Points to Contestant</label>
          <div class="award-row">
            <select id="award-contestant-select"><option value="">Select contestant...</option></select>
            <input type="number" id="award-points-input" value="20" min="0" max="10000" style="text-align:center;">
            <button class="btn btn-gold" id="award-points-btn" title="Award points">
              <i class="fas fa-star"></i>
            </button>
          </div>
        </div>

        <!-- Wrong / no answer -->
        <button class="btn btn-red btn-full" id="no-answer-btn" style="margin-top:9px;" disabled>
          <i class="fas fa-times"></i> Wrong / No Answer
        </button>

      </div>
    </div>

  </div><!-- /centre col -->


  <!-- ═══════════════════════════════
       RIGHT — SETUP & MANAGEMENT
       Contestants · Q Manager · Logo
       ═══════════════════════════════ -->
  <div class="col">

    <!-- CONTESTANTS -->
    <div class="sec">
      <div class="sec-h"><i class="fas fa-users"></i> Contestants</div>
      <div class="sec-b">
        <div id="contestant-inputs"></div>
        <div class="row" style="margin-top:7px;">
          <button class="btn btn-green btn-full" id="start-game-btn">
            <i class="fas fa-play"></i> Start Game
          </button>
          <button class="btn btn-red btn-sm" id="reset-game-btn" title="Reset everything">
            <i class="fas fa-rotate-left"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- QUESTION MANAGER -->
    <div class="sec">
      <div class="sec-h"><i class="fas fa-database"></i> Question Manager</div>
      <div class="sec-b">
        <div class="tab-bar">
          <button class="tab-btn active" onclick="switchTab('tab-add',this)">➕ Add</button>
          <button class="tab-btn"        onclick="switchTab('tab-list',this)">📋 List</button>
          <button class="tab-btn"        onclick="switchTab('tab-import',this)">📦 Import</button>
        </div>

        <!-- ADD -->
        <div class="tab-panel active" id="tab-add">
          <div style="display:grid;gap:7px;">
            <div>
              <label>Question Text</label>
              <textarea id="new-q-text" rows="2" placeholder="Type question..." style="resize:vertical;"></textarea>
            </div>
            <div class="row2">
              <div style="flex:1;">
                <label>Type</label>
                <select id="new-q-type" onchange="updateAnswerForm()">
                  <option value="kbc">KBC (A/B/C/D)</option>
                  <option value="open">Open Answer</option>
                </select>
              </div>
              <div style="width:65px;">
                <label>Points</label>
                <input type="number" id="new-q-points" value="20" min="1">
              </div>
            </div>
            <div id="kbc-answers-form">
              <label>Options — tick the correct one</label>
              <div id="kbc-ans-list"></div>
            </div>
            <div id="open-answers-form" style="display:none;">
              <div style="background:rgba(34,197,94,0.07);border:1px solid rgba(34,197,94,0.22);border-radius:4px;padding:9px;font-size:0.78rem;color:var(--tx-d);">
                ✅ Judge verbally → award points manually.
              </div>
            </div>
            <button class="btn btn-gold btn-full" onclick="addQuestion()">
              <i class="fas fa-save"></i> Save Question
            </button>
          </div>
        </div>

        <!-- LIST -->
        <div class="tab-panel" id="tab-list">
          <div id="q-list-container">
            <div class="muted" style="text-align:center;padding:14px;">Loading...</div>
          </div>
        </div>

        <!-- IMPORT -->
        <div class="tab-panel" id="tab-import">
          <label style="margin-bottom:5px;">📦 Prebuilt Questions</label>
          <button class="btn btn-purple btn-full" onclick="loadPrebuiltQuestions()" style="margin-bottom:5px;">
            <i class="fas fa-download"></i> Load 10 Prebuilt
          </button>
          <div id="preload-status"></div>
          <div class="hr"></div>
          <label style="margin-bottom:5px;">📋 JSON Bulk Import</label>
          <div class="json-hint">[{"question_text":"Q?","question_type":"kbc",
 "points":20,"answers":[
  {"text":"Ans","label":"A","correct":1}
]}]</div>
          <textarea id="json-import-input" rows="4" placeholder="Paste JSON array..." style="font-family:'Share Tech Mono',monospace;font-size:0.7rem;resize:vertical;margin-bottom:6px;"></textarea>
          <div class="row2">
            <button class="btn btn-gold" onclick="importJSON()"><i class="fas fa-file-import"></i> Import</button>
            <button class="btn btn-ghost btn-sm" onclick="showJSONExample()">Example</button>
          </div>
          <div id="json-import-status" style="margin-top:5px;"></div>
        </div>

      </div>
    </div>

    <!-- LOGO -->
    <div class="sec">
      <div class="sec-h"><i class="fas fa-image"></i> Logo</div>
      <div class="sec-b">
        <div class="logo-drop" onclick="document.getElementById('logo-file-input').click()">
          <input type="file" id="logo-file-input" accept="image/*" style="display:none;" onchange="uploadLogo(this)">
          <img id="logo-preview-admin" src="" alt="" style="display:none;max-height:44px;max-width:150px;object-fit:contain;margin:0 auto 5px;display:none;">
          <div id="logo-preview-placeholder">🖼️ Click to upload<br><span style="font-size:0.68rem;opacity:.6;">PNG · JPG · SVG</span></div>
        </div>
      </div>
    </div>

  </div><!-- /right col -->

</div><!-- /layout -->

<div id="toasts"></div>
<script src="js/admin.js"></script>
</body>
</html>
