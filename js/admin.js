// ═══════════════════════════════════════════════════════════
// Tech Feud — Admin Dashboard JS
// techfeud/js/admin.js
// ═══════════════════════════════════════════════════════════
'use strict';

const API = 'api/api.php';
let state         = null;
let timerInterval = null;
let prevTimerOn   = false;
let _toggling       = false; // prevent poll from overwriting active toggle click
let _awardFocused   = false; // true while user has award dropdown or points input focused
let _awardContestant= '';    // remember selected contestant across polls

// ─────────────────────────────────────────────────────────
// BOOT
// ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    buildContestantInputs();
    buildKbcAnswerRows();
    bindEvents();
    fetchState();
    setInterval(fetchState, 2000);
});

function buildContestantInputs() {
    const cont = document.getElementById('contestant-inputs');
    if (!cont) return;
    cont.innerHTML = '';
    for (let i = 0; i < 8; i++) {
        cont.innerHTML += `
        <div class="c-row">
            <div class="c-num">${i+1}</div>
            <input type="text" class="contestant-input" placeholder="Contestant ${i+1}">
        </div>`;
    }
}

function bindEvents() {
    document.getElementById('start-game-btn').addEventListener('click', startGame);
    document.getElementById('reset-game-btn').addEventListener('click', resetGame);
    document.getElementById('new-round-btn').addEventListener('click', newRound);
    document.getElementById('no-answer-btn').addEventListener('click', noAnswer);
    document.getElementById('question-select').addEventListener('change', e => {
        const v = parseInt(e.target.value);
        if (v) setQuestion(v);
    });
    document.getElementById('timer-start-btn').addEventListener('click', startTimer);
    document.getElementById('timer-stop-btn').addEventListener('click', stopTimer);
    document.getElementById('lock-buzzers-btn').addEventListener('click', lockBuzzers);
    document.getElementById('unlock-buzzers-btn').addEventListener('click', unlockBuzzers);
    document.getElementById('award-points-btn').addEventListener('click', awardPoints);

    document.querySelectorAll('.proj-btn').forEach(btn => {
        btn.addEventListener('click', () => toggleDisplay(btn.dataset.type, btn));
    });

    // CRITICAL FIX: Stop poll from resetting award fields while user is using them
    ['award-contestant-select', 'award-points-input'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('focus',  () => { _awardFocused = true;  });
        el.addEventListener('blur',   () => { _awardFocused = false; });
        el.addEventListener('change', () => {
            if (id === 'award-contestant-select') _awardContestant = el.value;
        });
    });
}

// ─────────────────────────────────────────────────────────
// POLLING
// ─────────────────────────────────────────────────────────
async function fetchState() {
    try {
        const r    = await fetch(API + '?action=get_state');
        const text = await r.text();
        if (text.trim().startsWith('<')) {
            console.error('PHP error:', text.substring(0, 300));
            showMsg('⚠️ Server error — check PHP/MySQL in XAMPP', 'error');
            return;
        }
        const data = JSON.parse(text);
        if (!data.error) updateUI(data);
    } catch(e) {
        console.warn('Poll error:', e.message);
    }
}

// ─────────────────────────────────────────────────────────
// UPDATE UI
// ─────────────────────────────────────────────────────────
function updateUI(data) {
    state             = data;
    const game        = data.game;
    const contestants = data.contestants  || [];
    const bq          = data.buzzer_queue || [];
    const questions   = data.questions    || [];

    // All MySQL booleans come back as strings — always parseInt
    const isActive   = parseInt(game.is_active)       === 1;
    const timerOn    = parseInt(game.timer_running)    === 1;
    const buzzLocked = parseInt(game.buzzers_locked)   === 1;
    const showWelc   = parseInt(game.show_welcome)     === 1;
    const showQ      = parseInt(game.show_question)    === 1;
    const showA      = parseInt(game.show_answers)     === 1;
    const showLB     = parseInt(game.show_leaderboard) === 1;

    // ── Logo ───────────────────────────────────────────
    if (game.logo_path) {
        const img = document.getElementById('admin-logo-img');
        const prv = document.getElementById('logo-preview-admin');
        const ph  = document.getElementById('logo-preview-placeholder');
        if (img) { img.src = game.logo_path; img.style.display = 'block'; }
        if (prv) { prv.src = game.logo_path; prv.style.display = 'block'; }
        if (ph)  ph.style.display = 'none';
    }

    // ── Status badges ──────────────────────────────────
    setbadge('badge-game',   isActive   ? 'ACTIVE'   : 'INACTIVE',  isActive   ? 'b-green' : 'b-off');
    setbadge('badge-timer',  timerOn    ? 'TIMER ON' : 'TIMER OFF', timerOn    ? 'b-gold'  : 'b-off');
    setbadge('badge-buzzer', buzzLocked ? '🔒 LOCKED': 'BUZZERS OPEN',   buzzLocked ? 'b-red' : 'b-off');

    // ── Enable controls ────────────────────────────────
    ['question-select','timer-start-btn','timer-stop-btn',
     'lock-buzzers-btn','unlock-buzzers-btn','new-round-btn','no-answer-btn'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !isActive;
    });

    // ── Question dropdown ──────────────────────────────
    const sel = document.getElementById('question-select');
    sel.innerHTML = '<option value="">— Select a question to set it live —</option>';
    questions.forEach(q => {
        const opt = document.createElement('option');
        opt.value = q.id;
        opt.textContent = `[${q.question_type.toUpperCase()}] ${q.question_text.substring(0,65)}${q.question_text.length>65?'...':''}`;
        sel.appendChild(opt);
    });
    if (game.current_question_id) sel.value = game.current_question_id;

    // ── Active question text ───────────────────────────
    const aqBox  = document.getElementById('aq-box');
    const aqText = document.getElementById('active-q-text');
    if (data.current_question) {
        if (aqBox)  aqBox.style.display = 'block';
        if (aqText) aqText.textContent  = data.current_question.question_text;
    } else {
        if (aqBox) aqBox.style.display = 'none';
    }

    // ── Answer controls ────────────────────────────────
    if (data.current_question) {
        renderAnswerControls(data.current_question, game.revealed_answers || []);
        const qt = data.current_question.question_type;
        const awdSec = document.getElementById('kbc-award-section');
        if (awdSec) awdSec.style.display = (qt === 'kbc' || qt === 'open') ? 'block' : 'none';
    } else {
        const grid = document.getElementById('answer-ctrl-grid');
        if (grid) grid.innerHTML = '<div style="color:rgba(255,255,255,0.3);font-size:0.85rem;grid-column:1/-1;">Select a question above to see answer options.</div>';
        const awdSec = document.getElementById('kbc-award-section');
        if (awdSec) awdSec.style.display = 'none';
    }

    // ── Award contestant dropdown ──────────────────────
    // FIX: Don't rebuild while user is focused on the dropdown (would close it)
    const awdSel = document.getElementById('award-contestant-select');
    if (awdSel && !_awardFocused) {
        // Remember what was selected before rebuild
        const prevVal = _awardContestant || awdSel.value;
        awdSel.innerHTML = '<option value="">Select contestant...</option>';
        contestants.forEach(c => {
            const o = document.createElement('option');
            o.value = c.id;
            o.textContent = `${c.name} (${c.score} pts)`;
            awdSel.appendChild(o);
        });
        // Restore previously selected contestant after rebuild
        if (prevVal) {
            awdSel.value = prevVal;
            if (awdSel.value) _awardContestant = prevVal; // still valid
        }
    }

    // ── Buzzer queue ───────────────────────────────────
    renderBuzzerQueue(bq);

    // ── Timer display ──────────────────────────────────
    const timerStart = parseInt(game.timer_started_at) || 0;
    const timerSecs  = parseInt(game.timer_seconds)    || 30;
    if (timerOn && timerStart > 0) {
        if (!prevTimerOn) startAdminTimer(timerSecs, timerStart);
    } else if (prevTimerOn) {
        clearInterval(timerInterval);
        const dEl = document.getElementById('timer-admin-display');
        if (dEl) { dEl.textContent = '—'; dEl.className = 'timer-num'; }
    }
    prevTimerOn = timerOn;

    // ── Toggle button sync (only when not mid-click) ───
    if (!_toggling) {
        syncToggle('tog-welcome',     showWelc);
        syncToggle('tog-question',    showQ);
        syncToggle('tog-answers',     showA);
        syncToggle('tog-leaderboard', showLB);
    }

    // ── Score editor ───────────────────────────────────
    renderScoreEditor(contestants);

    // ── Question list tab ──────────────────────────────
    const listTab = document.getElementById('tab-list');
    if (listTab?.classList.contains('active')) renderQuestionList(questions, game.current_question_id);
}

// ─────────────────────────────────────────────────────────
// RENDER HELPERS
// ─────────────────────────────────────────────────────────
function renderAnswerControls(question, revealed) {
    const grid = document.getElementById('answer-ctrl-grid');
    if (!grid) return;
    grid.innerHTML = '';
    const answers = question.answers || [];
    const qt      = question.question_type;

    if (qt === 'kbc') {
        grid.style.gridTemplateColumns = '1fr 1fr';
        answers.forEach(ans => {
            const isRev     = revealed.some(r => String(r) === String(ans.id));
            const isCorrect = parseInt(ans.is_correct) === 1;
            const btn = document.createElement('button');
            btn.className = 'ans-btn' + (isRev ? ' revealed' : '');
            btn.innerHTML = `
                <div class="al">${ans.option_label || String.fromCharCode(65)}</div>
                <div class="at">${ans.answer_text}</div>
                ${isCorrect ? '<div class="ac">✓ CORRECT</div>' : ''}
                ${isRev     ? '<div class="ac">✓ Revealed</div>' : ''}`;
            btn.addEventListener('click', () => revealKbcAnswer(ans.id, isCorrect, question.points));
            grid.appendChild(btn);
        });
    } else {
        // Open answer
        grid.style.gridTemplateColumns = '1fr';
        grid.innerHTML = '<div class="muted" style="grid-column:1/-1;padding:10px 0;">Open question — judge verbally, award points below.</div>';
    }
}

function renderBuzzerQueue(bq) {
    const cont = document.getElementById('buzzer-rank-display');
    if (!cont) return;
    cont.innerHTML = '';
    const rnks   = ['🥇 1st','🥈 2nd','🥉 3rd'];
    const rcls   = ['r1','r2','r3'];
    for (let i = 0; i < 3; i++) {
        const item = bq[i];
        const div  = document.createElement('div');
        div.className = 'bq-card' + (item ? ' filled ' + rcls[i] : '');
        div.innerHTML = `<div class="bq-rnk">${rnks[i]}</div><div class="bq-nm">${item ? item.contestant_name : '—'}</div>`;
        cont.appendChild(div);
    }
}

function renderScoreEditor(contestants) {
    const cont = document.getElementById('score-editor-list');
    if (!cont) return;
    if (!contestants.length) {
        cont.innerHTML = '<div style="color:rgba(255,255,255,0.3);font-size:0.85rem;">Start the game to edit scores.</div>';
        return;
    }
    cont.innerHTML = '';
    contestants.forEach(c => {
        const row = document.createElement('div');
        row.className = 'sc-row';
        row.innerHTML = `
            <div class="sc-name">${c.name}</div>
            <input type="number" id="score-input-${c.id}" value="${c.score}" style="text-align:center;">
            <button class="btn btn-gold btn-sm" onclick="updateScore(${c.id})" title="Set score"><i class="fas fa-check"></i></button>`;
        cont.appendChild(row);
    });
}

function renderQuestionList(questions, activeId) {
    const cont = document.getElementById('q-list-container');
    if (!cont) return;
    if (!questions.length) {
        cont.innerHTML = '<div style="color:rgba(255,255,255,0.3);text-align:center;padding:20px;">No questions yet. Add some!</div>';
        return;
    }
    cont.innerHTML = '';
    questions.forEach(q => {
        const item = document.createElement('div');
        item.className = 'q-item' + (String(q.id) === String(activeId) ? ' active-q' : '');
        item.innerHTML = `
            <span class="q-pill p-${q.question_type}">${q.question_type.toUpperCase()}</span>
            <span class="q-txt">${q.question_text.substring(0,60)}${q.question_text.length>60?'...':''}</span>
            <div class="q-acts">
                <button class="btn btn-blue btn-sm" onclick="selectFromList(${q.id})" title="Set Live">▶</button>
                <button class="btn btn-red  btn-sm" onclick="deleteQuestion(${q.id})" title="Delete">✕</button>
            </div>`;
        cont.appendChild(item);
    });
}

// ─────────────────────────────────────────────────────────
// GAME ACTIONS
// ─────────────────────────────────────────────────────────
async function startGame() {
    const names = [...document.querySelectorAll('.contestant-input')]
        .map(i => i.value.trim()).filter(Boolean);
    if (names.length < 2) { showMsg('Enter at least 2 contestant names!', 'error'); return; }
    const r = await api('start_game', { contestants: names });
    if (r.success) { showMsg(`✅ Game started! ${names.length} contestants`, 'success'); fetchState(); }
    else showMsg('Error: ' + (r.error || 'Unknown'), 'error');
}

async function resetGame() {
    if (!confirm('Reset game? All scores will be cleared.')) return;
    const r = await api('reset_game');
    if (r.success) { showMsg('Game reset.', 'info'); fetchState(); }
}

async function setQuestion(id) {
    if (!id) return;
    const r = await api('set_question', { question_id: id });
    if (r.success) { showMsg('✅ Question set!', 'success'); fetchState(); }
}

async function newRound() {
    const r = await api('new_round');
    if (r.success) { showMsg('New round — answers cleared.', 'success'); fetchState(); }
}

async function noAnswer() {
    // Play wrong sound for host feedback
    testSound('wrong');
    await api('no_answer');
    showMsg('❌ No answer recorded.', 'info');
}

// Reveal KBC answer on projector + play correct/wrong sound on admin side too
async function revealKbcAnswer(answerId, isCorrect, questionPoints) {
    const r = await api('reveal_answer', { answer_id: answerId, contestant_id: null, points: 0 });
    if (r.success) {
        testSound(isCorrect ? 'correct' : 'wrong');
        showMsg(isCorrect ? '✅ Correct answer revealed!' : '❌ Wrong answer revealed', isCorrect ? 'success' : 'error');
        if (isCorrect) {
            const inp = document.getElementById('award-points-input');
            if (inp) inp.value = questionPoints;
        }
        fetchState();
    }
}

async function awardPoints() {
    const cid  = document.getElementById('award-contestant-select')?.value;
    const ptsRaw = document.getElementById('award-points-input')?.value;
    const pts  = parseInt(ptsRaw);
    if (!cid) { showMsg('Select a contestant first!', 'error'); return; }
    if (isNaN(pts)) { showMsg('Enter a valid points number!', 'error'); return; }
    const r = await api('award_points', { contestant_id: cid, points: pts, question_id: state?.game?.current_question_id || 0 });
    if (r.success) {
        showMsg(`+${pts} pts awarded!`, 'success');
        _awardContestant = cid; // remember selection after fetchState rebuilds the list
        fetchState();
    }
}

async function updateScore(cid) {
    const inp   = document.getElementById('score-input-' + cid);
    const score = parseInt(inp?.value) || 0;
    const c     = state?.contestants?.find(x => String(x.id) === String(cid));
    if (!c) return;
    const diff  = score - parseInt(c.score);
    const r = await api('award_points', { contestant_id: cid, points: diff, question_id: 0 });
    if (r.success) { showMsg('Score updated!', 'success'); fetchState(); }
}

// Toggle display — lock flag prevents poll from overwriting mid-click
async function toggleDisplay(type, btn) {
    // Guard: if trying to show answers but no question selected, warn
    if (type === 'show_answers' && !state?.game?.current_question_id) {
        showMsg('⚠️ Select a question first, then show options!', 'error');
        return;
    }

    _toggling    = true;
    const wasOn  = btn.classList.contains('active');
    const newVal = !wasOn;
    btn.classList.toggle('active', newVal);
    try {
        const r = await api('toggle_display', { type, value: newVal });
        if (r.success) {
            const label = { show_welcome:'Welcome', show_question:'Question', show_answers:'Options', show_leaderboard:'Leaderboard' }[type] || type;
            showMsg((newVal ? '✅ Showing' : '🔴 Hiding') + ' ' + label + ' on projector', 'success');
            await fetchState();

            // AUTO-START TIMER when Show Options is turned ON
            // Reads whatever is currently in the timer-input field
            if (type === 'show_answers' && newVal) {
                const timerAlreadyOn = parseInt(state?.game?.timer_running) === 1;
                if (!timerAlreadyOn) {
                    const sec = parseInt(document.getElementById('timer-input')?.value) || 30;
                    await startTimerSilent(sec);
                    showMsg(`⏱ Timer auto-started: ${sec}s`, 'info');
                }
            }
        } else {
            btn.classList.toggle('active', wasOn); // rollback on failure
            showMsg('Toggle failed: ' + (r.error || 'DB error'), 'error');
        }
    } catch(e) {
        btn.classList.toggle('active', wasOn);
        showMsg('Network error', 'error');
    }
    _toggling = false;
}

// startTimerSilent: starts timer without showing a redundant message
async function startTimerSilent(sec) {
    const r = await api('start_timer', { seconds: sec });
    if (r.success) await fetchState();
}

async function startTimer() {
    const sec = parseInt(document.getElementById('timer-input')?.value) || 30;
    const r   = await api('start_timer', { seconds: sec });
    if (r.success) { showMsg(`⏱ ${sec}s timer started`, 'info'); fetchState(); }
}

function adjustTimer(delta) {
    const inp = document.getElementById('timer-input');
    if (!inp) return;
    const current = parseInt(inp.value) || 30;
    const newVal  = Math.max(5, Math.min(300, current + delta));
    inp.value = newVal;
}

async function stopTimer() {
    const r = await api('stop_timer');
    if (r.success) { showMsg('Timer stopped.', 'info'); fetchState(); }
}

async function lockBuzzers() {
    const r = await api('lock_buzzers');
    if (r.success) { showMsg('🔒 Buzzers locked!', 'info'); fetchState(); }
}

async function unlockBuzzers() {
    const r = await api('unlock_buzzers');
    if (r.success) { showMsg('🔓 Buzzers unlocked & cleared!', 'success'); await fetchState(); }
}

function startAdminTimer(totalSecs, startedAt) {
    clearInterval(timerInterval);
    const el = document.getElementById('timer-admin-display');
    if (!el) return;
    const update = () => {
        const elapsed   = (Date.now() - startedAt) / 1000;
        const remaining = Math.max(0, Math.ceil(totalSecs - elapsed));
        el.textContent  = remaining;
        el.className    = 'timer-num' + (remaining <= 5 ? ' urgent' : '');
        if (remaining <= 0) clearInterval(timerInterval);
    };
    update();
    timerInterval = setInterval(update, 500);
}

// ─────────────────────────────────────────────────────────
// QUESTION MANAGEMENT
// ─────────────────────────────────────────────────────────
function switchTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id)?.classList.add('active');
    if (btn) btn.classList.add('active');
    if (id === 'tab-list') fetchState();
}

function updateAnswerForm() {
    const type = document.getElementById('new-q-type')?.value;
    const kbcForm  = document.getElementById('kbc-answers-form');
    const openForm = document.getElementById('open-answers-form');
    if (kbcForm)  kbcForm.style.display  = type === 'kbc'  ? 'block' : 'none';
    if (openForm) openForm.style.display = type === 'open' ? 'block' : 'none';
}

function buildKbcAnswerRows() {
    const list = document.getElementById('kbc-ans-list');
    if (!list) return;
    list.innerHTML = '';
    ['A','B','C','D'].forEach(label => {
        const row = document.createElement('div');
        row.className = 'kbc-row';
        row.innerHTML = `
            <div class="kbc-lbl">${label}</div>
            <input type="text" placeholder="Option ${label}" class="kbc-ans-text" data-label="${label}">
            <label class="cr-radio"><input type="radio" name="kbc-correct" value="${label}"><span>Correct</span></label>`;
        list.appendChild(row);
    });
}

async function addQuestion() {
    const text   = document.getElementById('new-q-text')?.value.trim();
    const type   = document.getElementById('new-q-type')?.value;
    const points = parseInt(document.getElementById('new-q-points')?.value) || 20;
    if (!text) { showMsg('Question text required!', 'error'); return; }

    let answers = [];
    if (type === 'kbc') {
        const correctLabel = document.querySelector('input[name="kbc-correct"]:checked')?.value;
        if (!correctLabel) { showMsg('Mark one option as correct!', 'error'); return; }
        document.querySelectorAll('.kbc-ans-text').forEach(inp => {
            const t = inp.value.trim();
            if (t) answers.push({ text: t, points, correct: inp.dataset.label === correctLabel ? 1 : 0, label: inp.dataset.label });
        });
        if (answers.length < 2) { showMsg('Add at least 2 options!', 'error'); return; }
    }

    const r = await api('add_question', { question_text: text, question_type: type, points, answers });
    if (r.success) {
        showMsg('✅ Question saved!', 'success');
        document.getElementById('new-q-text').value = '';
        buildKbcAnswerRows();
        const checked = document.querySelector('input[name="kbc-correct"]:checked');
        if (checked) checked.checked = false;
        switchTab('tab-list', document.querySelectorAll('.tab-btn')[1]);
        fetchState();
    } else showMsg('Save failed: ' + (r.error || ''), 'error');
}

async function selectFromList(id) {
    const sel = document.getElementById('question-select');
    if (sel) sel.value = id;
    await setQuestion(id);
}

async function deleteQuestion(id) {
    if (!confirm('Delete this question?')) return;
    const r = await api('delete_question', { question_id: id });
    if (r.success) { showMsg('Deleted.', 'info'); fetchState(); }
}

// Prebuilt: KBC + Open only
async function loadPrebuiltQuestions() {
    const status = document.getElementById('preload-status');
    if (status) status.innerHTML = '<div class="alert alert-info">⏳ Loading...</div>';
    const prebuilt = [
        { question_text:"Which company created the Android OS?", question_type:'kbc', points:20,
          answers:[{text:"Apple",points:0,correct:0,label:'A'},{text:"Microsoft",points:0,correct:0,label:'B'},{text:"Google",points:20,correct:1,label:'C'},{text:"Samsung",points:0,correct:0,label:'D'}]},
        { question_text:"What does 'HTTP' stand for?", question_type:'kbc', points:20,
          answers:[{text:"HyperText Transfer Protocol",points:20,correct:1,label:'A'},{text:"High Transfer Technology Protocol",points:0,correct:0,label:'B'},{text:"Hyper Terminal Transfer Process",points:0,correct:0,label:'C'},{text:"Host-To-Host Transfer Protocol",points:0,correct:0,label:'D'}]},
        { question_text:"Which of these is NOT a programming language?", question_type:'kbc', points:20,
          answers:[{text:"Python",points:0,correct:0,label:'A'},{text:"Java",points:0,correct:0,label:'B'},{text:"Cobra",points:0,correct:0,label:'C'},{text:"Photoshop",points:20,correct:1,label:'D'}]},
        { question_text:"What does 'RAM' stand for?", question_type:'kbc', points:20,
          answers:[{text:"Read Access Memory",points:0,correct:0,label:'A'},{text:"Random Access Memory",points:20,correct:1,label:'B'},{text:"Rapid Array Module",points:0,correct:0,label:'C'},{text:"Run-time Application Memory",points:0,correct:0,label:'D'}]},
        { question_text:"Which company makes the iPhone?", question_type:'kbc', points:10,
          answers:[{text:"Samsung",points:0,correct:0,label:'A'},{text:"Google",points:0,correct:0,label:'B'},{text:"Apple",points:10,correct:1,label:'C'},{text:"Sony",points:0,correct:0,label:'D'}]},
        { question_text:"What year was the World Wide Web invented?", question_type:'kbc', points:20,
          answers:[{text:"1969",points:0,correct:0,label:'A'},{text:"1983",points:0,correct:0,label:'B'},{text:"1989",points:20,correct:1,label:'C'},{text:"1995",points:0,correct:0,label:'D'}]},
        { question_text:"Which of these is open-source software?", question_type:'kbc', points:20,
          answers:[{text:"Microsoft Word",points:0,correct:0,label:'A'},{text:"Adobe Photoshop",points:0,correct:0,label:'B'},{text:"macOS",points:0,correct:0,label:'C'},{text:"Linux",points:20,correct:1,label:'D'}]},
        { question_text:"In one sentence: What is Artificial Intelligence? Give an example.", question_type:'open', points:20, answers:[]},
        { question_text:"What is the difference between the Internet and the World Wide Web?", question_type:'open', points:20, answers:[]},
        { question_text:"Explain what a 'bug' in software is and how you would fix one.", question_type:'open', points:20, answers:[]},
    ];
    let count = 0;
    for (const q of prebuilt) { const r = await api('add_question', q); if (r.success) count++; }
    if (status) status.innerHTML = `<div class="alert alert-success">✓ Loaded ${count}/10 questions!</div>`;
    fetchState();
}

// ─────────────────────────────────────────────────────────
// JSON BULK IMPORT
// ─────────────────────────────────────────────────────────
async function importJSON() {
    const rawText = document.getElementById('json-import-input')?.value.trim();
    const status  = document.getElementById('json-import-status');
    if (!rawText) { if(status) status.innerHTML = '<div class="alert alert-error">⚠️ Paste JSON first!</div>'; return; }

    let questions;
    try {
        questions = JSON.parse(rawText);
        if (!Array.isArray(questions)) throw new Error('Root must be an array [ ... ]');
    } catch(e) {
        if(status) status.innerHTML = `<div class="alert alert-error">❌ Invalid JSON: ${e.message}</div>`;
        return;
    }
    if (!questions.length) { if(status) status.innerHTML = '<div class="alert alert-error">Array is empty.</div>'; return; }
    if (questions.length > 100) { if(status) status.innerHTML = '<div class="alert alert-error">Max 100 per import.</div>'; return; }
    if(status) status.innerHTML = `<div class="alert alert-info">⏳ Importing ${questions.length} questions...</div>`;

    let imported = 0, failed = 0, errors = [];
    for (let i = 0; i < questions.length; i++) {
        const q = questions[i];
        if (!q.question_text || !q.question_type) { failed++; errors.push(`Q${i+1}: missing question_text or question_type`); continue; }
        if (!['kbc','open'].includes(q.question_type)) { failed++; errors.push(`Q${i+1}: type must be kbc or open`); continue; }
        const answers = (q.answers || []).map((a, ai) => ({
            text:    (a.text || a.answer_text || '').trim(),
            points:  parseInt(a.points) || 0,
            correct: parseInt(a.correct || a.is_correct) || 0,
            label:   a.label || a.option_label || String.fromCharCode(65+ai),
        })).filter(a => a.text);
        const r = await api('add_question', { question_text: q.question_text.trim(), question_type: q.question_type, points: parseInt(q.points)||20, answers });
        if (r.success) imported++;
        else { failed++; errors.push(`Q${i+1}: ${r.error || 'server error'}`); }
    }
    let html = `<div class="alert alert-${failed===0?'success':'info'}">✅ Imported: <strong>${imported}</strong>${failed>0?' | ❌ Failed: <strong>'+failed+'</strong>':''}.</div>`;
    if (errors.length) html += `<div style="font-size:0.75rem;color:#ff8888;margin-top:6px;max-height:80px;overflow-y:auto;">${errors.join('<br>')}</div>`;
    if(status) status.innerHTML = html;
    if (imported > 0) { fetchState(); document.getElementById('json-import-input').value = ''; }
}

function showJSONExample() {
    const example = [
        { question_text:"Who invented the World Wide Web?", question_type:"kbc", points:20,
          answers:[{text:"Bill Gates",label:"A",correct:0},{text:"Tim Berners-Lee",label:"B",correct:1},{text:"Steve Jobs",label:"C",correct:0},{text:"Linus Torvalds",label:"D",correct:0}]},
        { question_text:"What does CPU stand for?", question_type:"open", points:15, answers:[] }
    ];
    const ta = document.getElementById('json-import-input');
    if (ta) ta.value = JSON.stringify(example, null, 2);
    showMsg('Example loaded — edit and click Import!', 'info');
}

// ─────────────────────────────────────────────────────────
// LOGO UPLOAD
// ─────────────────────────────────────────────────────────
async function uploadLogo(input) {
    const file = input.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('logo', file);
    try {
        const r    = await fetch(API + '?action=upload_logo', { method:'POST', body:fd });
        const data = await r.json();
        if (data.success) {
            showMsg('✅ Logo uploaded!', 'success');
            const img = document.getElementById('admin-logo-img');
            const prv = document.getElementById('logo-preview-admin');
            const ph  = document.getElementById('logo-preview-placeholder');
            if (img) { img.src = data.logo_path; img.style.display = 'block'; }
            if (prv) { prv.src = data.logo_path; prv.style.display = 'block'; }
            if (ph)  ph.style.display = 'none';
        } else showMsg(data.error || 'Upload failed', 'error');
    } catch(e) { showMsg('Upload error: ' + e.message, 'error'); }
}

// ─────────────────────────────────────────────────────────
// UTILITIES
// ─────────────────────────────────────────────────────────
async function api(action, data = {}) {
    try {
        const r    = await fetch(API + '?action=' + action, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(data),
        });
        const text = await r.text();
        if (text.trim().startsWith('<')) {
            console.error('PHP error for ' + action + ':', text.substring(0, 300));
            return { error: 'PHP server error — check XAMPP error log' };
        }
        return JSON.parse(text);
    } catch(e) {
        showMsg('Network error: ' + e.message, 'error');
        return { error: e.message };
    }
}

function syncToggle(id, isOn) {
    const btn = document.getElementById(id);
    if (btn) btn.classList.toggle('active', isOn);
}

function setbadge(id, text, cls) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = text;
    // Remove all colour classes and apply the new one
    el.className = 'badge ' + cls;
}

function showMsg(msg, type = 'info') {
    const cont = document.getElementById('toasts');
    if (!cont) return;
    const map  = { success:'t-ok', error:'t-err', info:'t-inf' };
    const div  = document.createElement('div');
    div.className   = 'toast ' + (map[type] || 't-inf');
    div.textContent = msg;
    cont.appendChild(div);
    setTimeout(() => { div.style.opacity='0'; div.style.transition='opacity 0.3s'; setTimeout(() => div.remove(), 300); }, 3500);
}

function testSound(name) {
    const audio = new Audio('sounds/' + name + '.mp3');
    audio.play().catch(() => showMsg('Sound not found: sounds/' + name + '.mp3', 'error'));
}

// setTimer: quick-set timer to a preset value
function setTimer(sec) {
    const inp = document.getElementById('timer-input');
    if (inp) inp.value = sec;
}
