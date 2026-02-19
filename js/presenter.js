// ═══════════════════════════════════════════════════════════
// Tech Feud — Presenter / Projector Screen JS
// ═══════════════════════════════════════════════════════════

class TechFeudPresenter {
    constructor() {
        this.state           = null;
        this.timerInterval   = null;
        this.lowSoundPlaying = false;
        this.buzzerHideTimer = null;
        this.lbRendered      = false;  // FIX: only render LB once, not every poll

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

    setupSSE() {
        try {
            const es = new EventSource('api/events.php');
            es.onmessage = (e) => {
                try {
                    const data = JSON.parse(e.data);
                    if (!data.error) this.handleState(data);
                } catch(err) {}
            };
            es.onerror = () => { es.close(); setTimeout(() => this.setupSSE(), 5000); };
        } catch(e) {}
    }

    fetchState() {
        fetch('api/api.php?action=get_state')
            .then(r => r.json())
            .then(data => { if (!data.error) this.handleState(data); })
            .catch(() => {});
    }

    // ═══════════════════════════════════════════════
    // MAIN STATE HANDLER
    // ═══════════════════════════════════════════════
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

        // ── SOUND: intro ─────────────────────────────────
        if (onWelcome && !this.prev.welcomeShown) {
            this.playSoundOnce('snd-intro');
        }
        if (onWelcome) {
            this.prev.welcomeShown     = true;
            this.prev.questionShown    = false;
            this.prev.timerRunning     = false;
            this.prev.buzzerFirst      = null;
            this.prev.leaderboardShown = false;
            this.prev.revealedCount    = 0;
            this.prev.revealedIds      = [];
            this.prev.questionId       = null;
            this.lbRendered            = false;
            // Hide buzzer takeover on welcome
            this.forceHideBuzzer();
            return;
        }
        this.prev.welcomeShown = false;

        // ── LEADERBOARD ───────────────────────────────────
        if (showLB && !this.prev.leaderboardShown) {
            // Just appeared — stop everything, play winner sound
            this.stopAllSounds();
            this.playSoundOnce('snd-winner');
            this.lbRendered = false;  // force a fresh render when it first shows
            // FIX: hide buzzer takeover when LB appears
            this.forceHideBuzzer();
        }
        if (!showLB && this.prev.leaderboardShown) {
            this.stopSound('snd-winner');
            this.lbRendered = false;  // reset so next time it shows it re-renders
        }
        this.prev.leaderboardShown = showLB;

        // FIX: only render leaderboard ONCE when it first shows, not every poll
        if (showLB && !this.lbRendered) {
            this.renderLeaderboard(data.leaderboard || data.contestants || []);
            this.lbRendered = true;
        }

        // If leaderboard is on — skip all other game rendering
        if (showLB) {
            this.renderScoreStrip(data.contestants || []);
            return;
        }

        // ── Question changed? Reset tracking ─────────────
        const qId = currentQ?.id || null;
        if (qId !== this.prev.questionId) {
            this.prev.questionId    = qId;
            this.prev.questionShown = false;
            this.prev.revealedCount = 0;
            this.prev.revealedIds   = [];
            this.stopSound('snd-question');
        }

        // ── Question display ─────────────────────────────
        const hasQ = showQ && !!currentQ;
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

            // FIX: sound plays once when question FIRST shown, and only if
            // buzzer overlay is NOT currently displayed (to avoid replaying after buzz)
            const buzzerVisible = document.getElementById('buzzer-takeover')?.style.display === 'flex';
            if (!this.prev.questionShown && !buzzerVisible) {
                this.playSoundOnce('snd-question');
            }
        } else {
            if (this.prev.questionShown) this.stopSound('snd-question');
        }
        // FIX: questionShown should stay true even after buzzer — set it to
        // "question is currently set to show", NOT "sound played". This prevents
        // the sound from replaying when buzzer hides and we poll again.
        this.prev.questionShown = hasQ;

        // ── Answer board ─────────────────────────────────
        const hasAnswerQ = showA && !!currentQ;
        this.show('answer-board-kbc',  hasAnswerQ && qt === 'kbc',  'block');
        this.show('answer-board-feud', hasAnswerQ && qt === 'feud', 'block');

        if (hasAnswerQ) {
            const newCount = revealed.length;

            if (newCount > this.prev.revealedCount) {
                this.playSoundOnce('snd-reveal');
                if (qt === 'kbc') {
                    const allAnswers = currentQ.answers || [];
                    const newRevIds  = revealed.slice(this.prev.revealedCount);
                    newRevIds.forEach(revId => {
                        const ans = allAnswers.find(a => String(a.id) === String(revId));
                        if (!ans) return;
                        const isCorrect = parseInt(ans.is_correct) === 1;
                        setTimeout(() => {
                            this.stopSound('snd-reveal');
                            this.playSoundOnce(isCorrect ? 'snd-correct' : 'snd-wrong');
                        }, 350);
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
            if (timerEl) timerEl.style.display = 'block';
            if (!this.prev.timerRunning) {
                // timerStart is Unix timestamp in seconds from MySQL
                this.startTimerDisplay(timerSecs, timerStart * 1000);
            }
        } else {
            if (timerEl) timerEl.style.display = 'none';
            if (this.prev.timerRunning) {
                clearInterval(this.timerInterval);
                this.stopSound('snd-timer-low');
                this.lowSoundPlaying = false;
                const numEl = document.getElementById('proj-timer-num');
                if (numEl) { numEl.textContent = '—'; numEl.className = 'proj-timer-num'; }
                if (buzzLocked) this.playSoundOnce('snd-timesup');
            }
        }
        this.prev.timerRunning = timerOn;

        // ── Buzzer first ─────────────────────────────────
        const bq        = data.buzzer_queue || [];
        const firstBuzz = bq[0]?.contestant_name || null;

        if (firstBuzz !== this.prev.buzzerFirst) {
            this.prev.buzzerFirst = firstBuzz;
            if (firstBuzz) {
                this.showBuzzerTakeover(firstBuzz);
                this.stopSound('snd-question');
                // FIX: do NOT reset prev.questionShown here —
                // question sound should NOT replay when buzzer hides
                this.playSoundOnce('snd-buzzer');
            } else {
                this.hideBuzzerTakeover();
            }
        }

        // ── Score strip ───────────────────────────────────
        this.renderScoreStrip(data.contestants || []);
    }

    // ═══════════════════════════════════════════════
    // RENDER — KBC board
    // ═══════════════════════════════════════════════
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

    // ═══════════════════════════════════════════════
    // RENDER — Feud board
    // ═══════════════════════════════════════════════
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

    // ═══════════════════════════════════════════════
    // RENDER — Score strip
    // ═══════════════════════════════════════════════
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

    // ═══════════════════════════════════════════════
    // RENDER — Leaderboard / Winner (called ONCE)
    // ═══════════════════════════════════════════════
    renderLeaderboard(lb) {
        if (!lb || lb.length === 0) return;

        const sorted = [...lb].sort((a, b) => parseInt(b.score) - parseInt(a.score));
        const winner = sorted[0];
        const top3   = sorted.slice(0, 3);
        const rest   = sorted.slice(3);

        // Winner banner
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

        // Rest (4th+)
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

    // ═══════════════════════════════════════════════
    // BUZZER TAKEOVER
    // ═══════════════════════════════════════════════
    showBuzzerTakeover(name) {
        const overlay = document.getElementById('buzzer-takeover');
        if (!overlay) return;

        // Reset any previous fade-out state
        overlay.style.opacity   = '';
        overlay.style.transition = '';

        const nameEl = document.getElementById('bz-name');
        if (nameEl) nameEl.textContent = name;

        this.spawnBuzzerParticles();

        overlay.style.display = 'flex';

        // Restart CSS animations on the name by re-cloning the element
        const bz = document.getElementById('bz-name');
        if (bz) {
            bz.style.animation = 'none';
            void bz.offsetWidth; // force reflow
            bz.style.animation = '';
        }

        // Auto-hide after 5s
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
        if (overlay) overlay.style.display = 'none';
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
                width:${size}px; height:${size}px;
                background:${color};
                --tx:${tx}px; --ty:${ty}px;
                --dur:${dur}s; --delay:${delay}s;`;
            container.appendChild(p);
        }
    }

    // ═══════════════════════════════════════════════
    // CONFETTI (runs once, no loop)
    // ═══════════════════════════════════════════════
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
                --left:${left}%; --dur:${dur}s;
                --delay:${delay}s; --rot:${rot}deg;
                width:${w}px; height:${h}px; left:${left}%;`;
            container.appendChild(c);
        }
    }

    // ═══════════════════════════════════════════════
    // STARFIELD
    // ═══════════════════════════════════════════════
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

    // ═══════════════════════════════════════════════
    // TIMER DISPLAY
    // ═══════════════════════════════════════════════
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

            if (remaining <= 5 && remaining > 0 && !this.lowSoundPlaying) {
                this.lowSoundPlaying = true;
                const el = document.getElementById('snd-timer-low');
                if (el) { el.currentTime = 0; el.play().catch(() => {}); }
            }
            if ((remaining > 5 || remaining <= 0) && this.lowSoundPlaying) {
                this.lowSoundPlaying = false;
                this.stopSound('snd-timer-low');
            }
            if (remaining <= 0) clearInterval(this.timerInterval);
        };
        update();
        this.timerInterval = setInterval(update, 500);
    }

    // ═══════════════════════════════════════════════
    // SOUND HELPERS
    // ═══════════════════════════════════════════════
    playSoundOnce(id) {
        const el = document.getElementById(id);
        if (!el) return;
        if (!el.paused && !el.ended) return;
        el.currentTime = 0;
        el.play().catch(() => {});
    }

    stopSound(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.pause();
        el.currentTime = 0;
    }

    stopAllSounds() {
        ['snd-intro','snd-question','snd-buzzer','snd-correct','snd-wrong',
         'snd-timer-low','snd-timesup','snd-reveal','snd-winner'].forEach(id => this.stopSound(id));
    }

    show(id, visible, displayType = 'block') {
        const el = document.getElementById(id);
        if (el) el.style.display = visible ? displayType : 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => new TechFeudPresenter());
