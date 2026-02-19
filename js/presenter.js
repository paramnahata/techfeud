// ═══════════════════════════════════════════════════════════
// Tech Feud — Presenter / Projector Screen JS
// Complete sound state machine — one sound plays at a time
// ═══════════════════════════════════════════════════════════

class TechFeudPresenter {
    constructor() {
        this.state           = null;
        this.timerInterval   = null;
        this.buzzerHideTimer = null;
        this.lbRendered      = false;

        // ── Sound State Machine ──────────────────────────
        // Only ONE background track plays at a time.
        // currentBgSound tracks what is currently playing so we
        // can stop it the moment the screen changes — no leaking.
        this.currentBgSound  = null;   // id of currently looping/long sound
        this.lowSoundPlaying = false;

        // ── One-shot guards ──────────────────────────────
        // Prevent firing a one-shot sound every poll.
        // Each key = soundId, value = the "context" that triggered it.
        // When context changes the guard resets and sound can fire again.
        this.oneShotFired = {};   // { 'snd-intro': 'welcome', 'snd-buzzer': 'Alice', ... }

        this.prev = {
            questionId:       null,
            questionShown:    false,
            timerRunning:     false,
            buzzerFirst:      null,
            leaderboardShown: false,
            welcomeShown:     false,
            revealedCount:    0,
            revealedIds:      [],
        };

        this.init();
    }

    init() {
        this.buildStarfield();
        this.setupSSE();
        this.fetchState();
        setInterval(() => this.fetchState(), 2000);
    }

    // ── SSE ──────────────────────────────────────────────
    setupSSE() {
        try {
            const es = new EventSource('api/events.php');
            es.onmessage = (e) => {
                try {
                    const d = JSON.parse(e.data);
                    if (!d.error) this.handleState(d);
                } catch(err) {}
            };
            es.onerror = () => { es.close(); setTimeout(() => this.setupSSE(), 5000); };
        } catch(e) {}
    }

    fetchState() {
        fetch('api/api.php?action=get_state')
            .then(r => r.json())
            .then(d => { if (!d.error) this.handleState(d); })
            .catch(() => {});
    }

    // ════════════════════════════════════════════════════
    // SOUND STATE MACHINE
    // ════════════════════════════════════════════════════

    // Set the ONE background/looping sound that should be playing.
    // Automatically stops whatever was playing before.
    // Pass null to stop all background sounds.
    setBgSound(id) {
        if (id === this.currentBgSound) return;   // already correct — do nothing
        // Stop previous
        if (this.currentBgSound) this._rawStop(this.currentBgSound);
        this.currentBgSound = id;
        if (id) this._rawPlay(id);
    }

    // Fire a one-shot sound (doesn't stop bg sound).
    // context: a string that identifies WHY it fired.
    // If context hasn't changed since last fire, does nothing.
    fireOneShot(id, context) {
        if (this.oneShotFired[id] === context) return;  // already fired for this context
        this.oneShotFired[id] = context;
        // Stop the sound first so it restarts from 0 even if still finishing
        this._rawStop(id);
        this._rawPlay(id);
    }

    // Reset a one-shot guard (so it can fire again next time the context changes)
    resetOneShot(id) {
        delete this.oneShotFired[id];
    }

    _rawPlay(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.currentTime = 0;
        el.play().catch(() => {});
    }

    _rawStop(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.pause();
        el.currentTime = 0;
    }

    stopAllSounds() {
        ['snd-intro','snd-question','snd-buzzer','snd-correct','snd-wrong',
         'snd-timer-low','snd-timesup','snd-reveal','snd-winner'].forEach(id => this._rawStop(id));
        this.currentBgSound  = null;
        this.lowSoundPlaying = false;
        this.oneShotFired    = {};
    }

    // ════════════════════════════════════════════════════
    // MAIN STATE HANDLER
    // ════════════════════════════════════════════════════
    handleState(data) {
        this.state = data;
        const game = data.game;

        const isActive   = parseInt(game.is_active)       === 1;
        const showWelc   = parseInt(game.show_welcome)     === 1;
        const showQ      = parseInt(game.show_question)    === 1;
        const showA      = parseInt(game.show_answers)     === 1;
        const showLB     = parseInt(game.show_leaderboard) === 1;
        const timerOn    = parseInt(game.timer_running)    === 1;
        const buzzLocked = parseInt(game.buzzers_locked)   === 1;
        const timerSecs  = parseInt(game.timer_seconds)    || 30;
        const timerStart = parseInt(game.timer_started_at) || 0;

        const currentQ = data.current_question || null;
        const qt       = currentQ?.question_type || '';
        const revealed = Array.isArray(game.revealed_answers) ? game.revealed_answers : [];

        // ── Logo ─────────────────────────────────────────
        if (game.logo_path) {
            ['proj-logo','welcome-logo'].forEach(id => {
                const el = document.getElementById(id);
                if (!el || el.getAttribute('data-loaded') === game.logo_path) return;
                el.setAttribute('data-loaded', game.logo_path);
                el.src = game.logo_path;
                el.style.display = 'block';
                el.classList.remove('hidden');
            });
        }

        // ── Screen routing ───────────────────────────────
        const onWelcome = !isActive || showWelc;
        this.show('welcome-screen',      onWelcome,  'flex');
        this.show('game-screen',         !onWelcome, 'flex');
        this.show('leaderboard-section', showLB,     'flex');

        // ════════════════════════════════════════════════
        // ██  WELCOME SCREEN
        // ════════════════════════════════════════════════
        if (onWelcome) {
            // SOUND: intro.mp3 plays as bg music on welcome screen.
            // Stops the moment we leave welcome.
            this.setBgSound('snd-intro');

            if (!this.prev.welcomeShown) {
                // Reset all tracking when entering welcome
                this.prev.questionShown    = false;
                this.prev.timerRunning     = false;
                this.prev.buzzerFirst      = null;
                this.prev.leaderboardShown = false;
                this.prev.revealedCount    = 0;
                this.prev.revealedIds      = [];
                this.prev.questionId       = null;
                this.lbRendered            = false;
                this.oneShotFired          = {};
                this.forceHideBuzzer();
            }
            this.prev.welcomeShown = true;
            return;   // ← stop here, don't process game logic
        }

        // Left welcome — stop intro immediately
        if (this.prev.welcomeShown) {
            // setBgSound(null) will stop intro if it's the current bg
            if (this.currentBgSound === 'snd-intro') this.setBgSound(null);
        }
        this.prev.welcomeShown = false;

        // ════════════════════════════════════════════════
        // ██  LEADERBOARD / WINNER SCREEN
        // ════════════════════════════════════════════════
        if (showLB && !this.prev.leaderboardShown) {
            // First time LB appears: kill everything, play winner
            this.stopAllSounds();
            this.setBgSound('snd-winner');
            this.lbRendered = false;
            this.forceHideBuzzer();
        }
        if (!showLB && this.prev.leaderboardShown) {
            // LB just turned off
            if (this.currentBgSound === 'snd-winner') this.setBgSound(null);
            this.lbRendered = false;
        }
        this.prev.leaderboardShown = showLB;

        // Render LB only once when it first shows
        if (showLB && !this.lbRendered) {
            this.renderLeaderboard(data.leaderboard || data.contestants || []);
            this.lbRendered = true;
        }

        if (showLB) {
            this.renderScoreStrip(data.contestants || []);
            return;   // ← skip game logic while leaderboard is showing
        }

        // ════════════════════════════════════════════════
        // ██  GAME SCREEN
        // ════════════════════════════════════════════════

        // ── Question changed? ────────────────────────────
        const qId = currentQ?.id || null;
        if (qId !== this.prev.questionId) {
            this.prev.questionId    = qId;
            this.prev.questionShown = false;
            this.prev.revealedCount = 0;
            this.prev.revealedIds   = [];
            // Stop question sound and reset its one-shot guard
            if (this.currentBgSound === 'snd-question') this.setBgSound(null);
            this.resetOneShot('snd-question');
        }

        // ── Determine what background sound should play ──
        // Priority: buzzer active > timer-low > question > silence
        const bq        = data.buzzer_queue || [];
        const firstBuzz = bq[0]?.contestant_name || null;
        const hasQ      = showQ && !!currentQ;
        const hasAnswerQ = showA && !!currentQ;

        // We'll set bg sound at the end of this section based on priority
        let desiredBg = null;

        if (firstBuzz) {
            // When buzzer is pressed — no bg music, buzzer one-shot already fired
            desiredBg = null;
        } else if (timerOn && this.lowSoundPlaying) {
            // Timer-low is managed separately (started inside timer logic)
            desiredBg = null;
        } else if (hasQ) {
            // Question is showing → question tick plays as bg
            desiredBg = 'snd-question';
        } else {
            desiredBg = null;
        }

        // ── Question display ─────────────────────────────
        this.show('question-section', hasQ, 'block');
        if (hasQ) {
            this.show('question-box-kbc',  qt === 'kbc',  'block');
            this.show('question-box-open', qt === 'open', 'block');
            this.show('question-box-feud', qt === 'feud', 'block');
            const qText = currentQ.question_text || '';
            ['kbc-q-text','open-q-text','feud-q-text'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = qText;
            });
        } else {
            if (this.prev.questionShown) {
                // Question just hidden — stop question sound
                if (this.currentBgSound === 'snd-question') this.setBgSound(null);
                this.resetOneShot('snd-question');
            }
        }
        this.prev.questionShown = hasQ;

        // Apply the desired background sound
        // (but don't fight the timer-low logic below)
        if (!this.lowSoundPlaying) {
            this.setBgSound(desiredBg);
        }

        // ── Answer board ─────────────────────────────────
        this.show('answer-board-kbc',  hasAnswerQ && qt === 'kbc',  'block');
        this.show('answer-board-feud', hasAnswerQ && qt === 'feud', 'block');

        if (hasAnswerQ) {
            const newCount = revealed.length;

            if (newCount > this.prev.revealedCount) {
                // New answer revealed — play reveal sound as one-shot
                // Use reveal count as context so it fires once per new reveal
                this.fireOneShot('snd-reveal', 'reveal_' + newCount);

                if (qt === 'kbc') {
                    const allAnswers = currentQ.answers || [];
                    const newRevIds  = revealed.slice(this.prev.revealedCount);
                    newRevIds.forEach(revId => {
                        const ans = allAnswers.find(a => String(a.id) === String(revId));
                        if (!ans) return;
                        const isCorrect = parseInt(ans.is_correct) === 1;
                        const sndId = isCorrect ? 'snd-correct' : 'snd-wrong';
                        // Fire correct/wrong 400ms after reveal sound
                        setTimeout(() => {
                            this._rawStop('snd-reveal');
                            this.fireOneShot(sndId, 'reveal_result_' + revId);
                        }, 400);
                    });
                }
            }
            this.prev.revealedCount = newCount;
            this.prev.revealedIds   = [...revealed];

            if (qt === 'kbc')  this.renderKbcBoard(currentQ, revealed);
            if (qt === 'feud') this.renderFeudBoard(currentQ, revealed);
        }

        // ── Timer ─────────────────────────────────────────
        const timerEl = document.getElementById('proj-timer');
        if (timerOn && timerStart > 0) {
            if (!this.prev.timerRunning) {
                // Calculate and SET the correct remaining value BEFORE showing the element
                // so the raw timestamp never flashes on screen
                const numEl = document.getElementById('proj-timer-num');
                if (numEl) {
                    const elapsed   = (Date.now() - timerStart) / 1000;
                    const remaining = Math.max(0, Math.ceil(timerSecs - elapsed));
                    numEl.textContent = remaining;
                    numEl.className   = 'proj-timer-num' + (remaining <= 5 ? ' urgent' : '');
                }
                this.startTimerDisplay(timerSecs, timerStart);
            }
            if (timerEl) timerEl.style.display = 'block';
        } else {
            if (timerEl) timerEl.style.display = 'none';
            if (this.prev.timerRunning) {
                clearInterval(this.timerInterval);
                // Stop timer-low if it was playing
                this._rawStop('snd-timer-low');
                this.lowSoundPlaying = false;
                const numEl = document.getElementById('proj-timer-num');
                if (numEl) { numEl.textContent = '—'; numEl.className = 'proj-timer-num'; }
                // Time's up sound — only if buzzers locked (natural expiry, not manual stop)
                if (buzzLocked) {
                    this.fireOneShot('snd-timesup', 'timesup_' + timerStart);
                }
                // Restore question sound if question is still showing
                if (hasQ && !firstBuzz) this.setBgSound('snd-question');
            }
        }
        this.prev.timerRunning = timerOn;

        // ── Buzzer first ─────────────────────────────────
        if (firstBuzz !== this.prev.buzzerFirst) {
            this.prev.buzzerFirst = firstBuzz;
            if (firstBuzz) {
                // Stop question sound immediately
                if (this.currentBgSound === 'snd-question') this.setBgSound(null);
                this.showBuzzerTakeover(firstBuzz);
                // Buzzer hit one-shot (context = name so re-fires if different person buzzes)
                this.fireOneShot('snd-buzzer', 'buzz_' + firstBuzz);
            } else {
                // Buzzer cleared — restore question sound if question still showing
                this.hideBuzzerTakeover();
                if (hasQ) this.setBgSound('snd-question');
            }
        }

        // ── Score strip ───────────────────────────────────
        this.renderScoreStrip(data.contestants || []);
    }

    // ════════════════════════════════════════════════════
    // RENDER — KBC board
    // ════════════════════════════════════════════════════
    renderKbcBoard(question, revealed) {
        const container = document.getElementById('kbc-options');
        if (!container) return;
        container.innerHTML = '';
        (question.answers || []).forEach((ans, i) => {
            const isRev     = revealed.some(r => String(r) === String(ans.id));
            const isCorrect = parseInt(ans.is_correct) === 1;
            const div = document.createElement('div');
            div.className = 'kbc-option' + (isRev ? (isCorrect ? ' correct' : ' wrong') : '');
            div.innerHTML = `
                <div class="kbc-option-label">${ans.option_label || String.fromCharCode(65+i)}</div>
                <div class="kbc-option-text">${ans.answer_text}</div>`;
            container.appendChild(div);
        });
    }

    // ════════════════════════════════════════════════════
    // RENDER — Feud board
    // ════════════════════════════════════════════════════
    renderFeudBoard(question, revealed) {
        const left  = document.getElementById('feud-col-left');
        const right = document.getElementById('feud-col-right');
        if (!left || !right) return;
        left.innerHTML = right.innerHTML = '';
        (question.answers || []).forEach((ans, i) => {
            const isRev = revealed.some(r => String(r) === String(ans.id));
            const card  = document.createElement('div');
            card.className = 'answer-card' + (isRev ? ' revealed' : '');
            card.innerHTML = `
                <div class="answer-card-inner">
                    <div class="answer-card-front">
                        <span style="font-family:'Anton',sans-serif;font-size:2.5rem;color:var(--gold);">${i+1}</span>
                    </div>
                    <div class="answer-card-back">
                        <span style="font-family:'Oswald',sans-serif;font-size:1.1rem;font-weight:600;color:#000;flex:1;text-align:left;">${ans.answer_text}</span>
                        <span style="font-family:'Anton',sans-serif;font-size:1.8rem;color:#000;margin-left:10px;">${ans.points}</span>
                    </div>
                </div>`;
            (i % 2 === 0 ? left : right).appendChild(card);
        });
    }

    // ════════════════════════════════════════════════════
    // RENDER — Score strip
    // ════════════════════════════════════════════════════
    renderScoreStrip(contestants) {
        const strip = document.getElementById('score-strip');
        if (!strip) return;
        strip.innerHTML = '';
        contestants.forEach(c => {
            const pill = document.createElement('div');
            pill.className = 'score-pill';
            pill.innerHTML = `<span class="sname">${c.name}</span><span class="spts">${c.score}</span>`;
            strip.appendChild(pill);
        });
    }

    // ════════════════════════════════════════════════════
    // RENDER — Leaderboard (called ONCE per show)
    // ════════════════════════════════════════════════════
    renderLeaderboard(lb) {
        if (!lb || lb.length === 0) return;

        const sorted = [...lb].sort((a, b) => parseInt(b.score) - parseInt(a.score));
        const winner = sorted[0];
        const top3   = sorted.slice(0, 3);
        const rest   = sorted.slice(3);

        const winName = document.getElementById('lb-winner-name');
        const winPts  = document.getElementById('lb-winner-pts');
        if (winName) winName.textContent = winner.name;
        if (winPts)  winPts.textContent  = winner.score + ' pts';

        // Podium: visual order 2nd | 1st | 3rd
        const podium = document.getElementById('lb-podium');
        if (podium) {
            podium.innerHTML = '';
            const order     = [1, 0, 2];
            const classes   = ['podium-2','podium-1','podium-3'];
            const rankIcons = ['🥈','👑','🥉'];
            order.forEach((idx, pos) => {
                const c = top3[idx];
                if (!c) return;
                const slot = document.createElement('div');
                slot.className = 'podium-slot ' + classes[pos];
                slot.innerHTML = `
                    <div class="podium-rank">${rankIcons[pos]}</div>
                    <div class="podium-name">${c.name}</div>
                    <div class="podium-score">${c.score}</div>`;
                podium.appendChild(slot);
            });
        }

        const restEl = document.getElementById('lb-rest');
        if (restEl) {
            restEl.innerHTML = '';
            rest.forEach((c, i) => {
                const pill = document.createElement('div');
                pill.className = 'lb-rest-pill';
                pill.style.animationDelay = (i * 0.1 + 0.5) + 's';
                pill.innerHTML = `
                    <span class="lb-rest-rank">${i+4}.</span>
                    <span class="lb-rest-name">${c.name}</span>
                    <span class="lb-rest-score">${c.score}</span>`;
                restEl.appendChild(pill);
            });
        }

        this.launchConfetti();
    }

    // ════════════════════════════════════════════════════
    // BUZZER TAKEOVER
    // ════════════════════════════════════════════════════
    showBuzzerTakeover(name) {
        const overlay = document.getElementById('buzzer-takeover');
        if (!overlay) return;
        overlay.style.opacity    = '';
        overlay.style.transition = '';

        const nameEl = document.getElementById('bz-name');
        if (nameEl) {
            nameEl.textContent = name;
            // Restart pop animation
            nameEl.style.animation = 'none';
            void nameEl.offsetWidth;
            nameEl.style.animation = '';
        }

        this.spawnBuzzerParticles();
        overlay.style.display = 'flex';

        clearTimeout(this.buzzerHideTimer);
        this.buzzerHideTimer = setTimeout(() => this.hideBuzzerTakeover(), 5000);
    }

    hideBuzzerTakeover() {
        const overlay = document.getElementById('buzzer-takeover');
        if (!overlay || overlay.style.display === 'none') return;
        overlay.style.transition = 'opacity 0.5s ease';
        overlay.style.opacity    = '0';
        setTimeout(() => {
            overlay.style.display    = 'none';
            overlay.style.opacity    = '';
            overlay.style.transition = '';
        }, 500);
    }

    forceHideBuzzer() {
        clearTimeout(this.buzzerHideTimer);
        const overlay = document.getElementById('buzzer-takeover');
        if (overlay) { overlay.style.display = 'none'; overlay.style.opacity = ''; }
    }

    spawnBuzzerParticles() {
        const container = document.getElementById('bz-particles');
        if (!container) return;
        container.innerHTML = '';
        const colors = ['#C9A84C','#F0C060','#fff','#ff6644','#ffcc00','#ff4444'];
        const cx = window.innerWidth  / 2;
        const cy = window.innerHeight / 2;
        for (let i = 0; i < 40; i++) {
            const p     = document.createElement('div');
            p.className = 'bz-particle';
            const angle = (i / 40) * Math.PI * 2 + (Math.random() - 0.5) * 0.4;
            const dist  = 200 + Math.random() * 250;
            const tx    = Math.cos(angle) * dist;
            const ty    = Math.sin(angle) * dist;
            const color = colors[Math.floor(Math.random() * colors.length)];
            const dur   = 0.8 + Math.random() * 0.7;
            const delay = Math.random() * 0.2;
            const size  = 4 + Math.random() * 8;
            p.style.cssText = `
                left:${cx}px; top:${cy}px;
                width:${size}px; height:${size}px; background:${color};
                --tx:${tx}px; --ty:${ty}px; --dur:${dur}s; --delay:${delay}s;`;
            container.appendChild(p);
        }
    }

    // ════════════════════════════════════════════════════
    // TIMER DISPLAY
    // ════════════════════════════════════════════════════
    startTimerDisplay(totalSeconds, startedAtMs) {
        clearInterval(this.timerInterval);
        this.lowSoundPlaying = false;
        const numEl = document.getElementById('proj-timer-num');
        if (!numEl) return;

        const update = () => {
            const elapsed   = (Date.now() - startedAtMs) / 1000;
            const remaining = Math.max(0, Math.ceil(totalSeconds - elapsed));
            numEl.textContent = remaining;
            numEl.className   = 'proj-timer-num' + (remaining <= 5 ? ' urgent' : '');

            // Last 5 seconds: pause question sound, play timer-low loop
            if (remaining <= 5 && remaining > 0 && !this.lowSoundPlaying) {
                this.lowSoundPlaying = true;
                // Pause question bg so timer-low is audible
                if (this.currentBgSound === 'snd-question') {
                    const qEl = document.getElementById('snd-question');
                    if (qEl) qEl.pause();  // don't reset, just pause
                }
                this._rawPlay('snd-timer-low');
            }
            if ((remaining > 5 || remaining <= 0) && this.lowSoundPlaying) {
                this.lowSoundPlaying = false;
                this._rawStop('snd-timer-low');
                // Resume question if still showing
                if (this.currentBgSound === 'snd-question') {
                    const qEl = document.getElementById('snd-question');
                    if (qEl) qEl.play().catch(() => {});
                }
            }
            if (remaining <= 0) clearInterval(this.timerInterval);
        };
        update();
        this.timerInterval = setInterval(update, 500);
    }

    // ════════════════════════════════════════════════════
    // CONFETTI
    // ════════════════════════════════════════════════════
    launchConfetti() {
        const container = document.getElementById('lb-confetti');
        if (!container) return;
        container.innerHTML = '';
        const colors = ['#ffd700','#C9A84C','#fff','#ff6b6b','#4ecdc4','#a855f7','#f97316','#22c55e'];
        for (let i = 0; i < 80; i++) {
            const c     = document.createElement('div');
            c.className = 'confetto';
            const color = colors[Math.floor(Math.random() * colors.length)];
            const w     = 6  + Math.random() * 10;
            const h     = 10 + Math.random() * 16;
            const left  = Math.random() * 100;
            const dur   = 2.5 + Math.random() * 3;
            const delay = Math.random() * 5;
            const rot   = 360 + Math.random() * 720;
            c.style.cssText = `
                --c:${color}; --w:${w}px; --h:${h}px;
                --left:${left}%; --dur:${dur}s; --delay:${delay}s; --rot:${rot}deg;
                width:${w}px; height:${h}px; left:${left}%;`;
            container.appendChild(c);
        }
    }

    // ════════════════════════════════════════════════════
    // STARFIELD
    // ════════════════════════════════════════════════════
    buildStarfield() {
        const container = document.getElementById('lb-stars');
        if (!container) return;
        for (let i = 0; i < 120; i++) {
            const s     = document.createElement('div');
            s.className = 'lb-star';
            const size  = 1 + Math.random() * 2.5;
            const dur   = 2 + Math.random() * 4;
            const delay = Math.random() * 5;
            const op    = 0.2 + Math.random() * 0.6;
            s.style.cssText = `
                left:${Math.random()*100}%; top:${Math.random()*100}%;
                width:${size}px; height:${size}px;
                --dur:${dur}s; --delay:${delay}s; --op:${op};`;
            container.appendChild(s);
        }
    }

    // ════════════════════════════════════════════════════
    // UTILITY
    // ════════════════════════════════════════════════════
    show(id, visible, displayType = 'block') {
        const el = document.getElementById(id);
        if (el) el.style.display = visible ? displayType : 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => new TechFeudPresenter());

