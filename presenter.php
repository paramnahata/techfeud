<?php
require_once __DIR__ . '/auth.php';
requireAuth('presenter');
?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Feud — Stage Screen</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            overflow: hidden;
        }

        #game-screen {
            display: none;
        }

        /* ════════════════════════════════════════════════
   TOP BAR
   ════════════════════════════════════════════════ */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 24px;
            background: rgba(0, 0, 0, 0.5);
            border-bottom: 2px solid var(--gold);
            backdrop-filter: blur(12px);
            position: relative;
            z-index: 10;
        }

        .top-bar-logo {
            max-height: 50px;
            max-width: 150px;
            object-fit: contain;
        }

        .title-area .title {
            font-family: 'Anton', sans-serif;
            font-size: 1.5rem;
            color: var(--gold);
            letter-spacing: 3px;
        }

        /* ════════════════════════════════════════════════
   GAME BODY
   ════════════════════════════════════════════════ */
        .game-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px 30px;
        }

        /* ════════════════════════════════════════════════
   PROJECTED TIMER — fixed top-right pill
   ════════════════════════════════════════════════ */
        #proj-timer {
            position: fixed;
            top: 16px;
            right: 24px;
            z-index: 50;
            text-align: center;
            background: rgba(0, 0, 0, 0.6);
            border: 2px solid rgba(201, 168, 76, 0.45);
            border-radius: 18px;
            padding: 10px 24px 8px;
            backdrop-filter: blur(10px);
        }

        .proj-timer-label {
            font-family: 'Oswald', sans-serif;
            font-size: 0.68rem;
            color: rgba(255, 255, 255, 0.38);
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 0px;
        }

        .proj-timer-num {
            font-family: 'Anton', sans-serif;
            font-size: 5.5rem;
            color: var(--gold);
            line-height: 1;
            display: block;
            text-shadow: 0 0 28px rgba(201, 168, 76, 0.65);
            transition: color 0.3s;
            min-width: 80px;
        }

        .proj-timer-num.urgent {
            color: #ff3333;
            text-shadow: 0 0 32px rgba(255, 50, 50, 0.9);
            animation: timerPulse 0.5s ease infinite;
        }

        /* ════════════════════════════════════════════════
   SCORE STRIP (bottom)
   ════════════════════════════════════════════════ */
        .score-strip {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 8px 20px;
            background: rgba(0, 0, 0, 0.4);
            border-top: 1px solid rgba(201, 168, 76, 0.25);
        }

        .score-pill {
            background: rgba(26, 35, 126, 0.6);
            border: 1px solid rgba(201, 168, 76, 0.35);
            border-radius: 20px;
            padding: 4px 16px;
            font-family: 'Oswald', sans-serif;
            font-size: 0.95rem;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .score-pill .sname {
            color: rgba(255, 255, 255, 0.75);
        }

        .score-pill .spts {
            color: var(--gold);
            font-weight: 700;
        }

        /* ════════════════════════════════════════════════
   FEUD BOARD
   ════════════════════════════════════════════════ */
        .feud-board {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }

        .feud-board .col {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* ════════════════════════════════════════════════
   ███  BUZZER TAKEOVER  ███
   Full-screen spotlight when someone buzzes first
   ════════════════════════════════════════════════ */
        #buzzer-takeover {
            position: fixed;
            inset: 0;
            z-index: 200;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            /* Dark vignette spotlight */
            background: radial-gradient(ellipse 60% 55% at 50% 50%,
                    rgba(10, 10, 30, 0.15) 0%,
                    rgba(0, 0, 0, 0.92) 100%);
            animation: bzFadeIn 0.25s ease;
        }

        @keyframes bzFadeIn {
            from {
                opacity: 0
            }

            to {
                opacity: 1
            }
        }

        /* Spotlight circle behind name */
        .bz-spotlight {
            position: absolute;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle,
                    rgba(201, 168, 76, 0.12) 0%,
                    rgba(201, 168, 76, 0.04) 40%,
                    transparent 70%);
            animation: bzPulse 1.8s ease-in-out infinite;
        }

        @keyframes bzPulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.7;
            }

            50% {
                transform: scale(1.1);
                opacity: 1;
            }
        }

        .bz-label {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(1rem, 2.5vw, 1.6rem);
            color: rgba(255, 255, 255, 0.55);
            letter-spacing: 8px;
            text-transform: uppercase;
            margin-bottom: 20px;
            animation: bzSlideUp 0.4s 0.1s ease both;
            position: relative;
            z-index: 2;
        }

        .bz-name {
            font-family: 'Anton', sans-serif;
            font-size: clamp(4rem, 12vw, 9rem);
            color: #fff;
            letter-spacing: 2px;
            text-align: center;
            line-height: 1;
            position: relative;
            z-index: 2;

            /* Gold outline text */
            -webkit-text-stroke: 3px var(--gold);
            text-shadow:
                0 0 40px rgba(201, 168, 76, 0.8),
                0 0 80px rgba(201, 168, 76, 0.4),
                0 8px 30px rgba(0, 0, 0, 0.9);

            animation: bzNamePop 0.45s 0.15s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes bzNamePop {
            from {
                transform: scale(0.4) translateY(40px);
                opacity: 0;
            }

            to {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .bz-sub {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(1rem, 2vw, 1.4rem);
            color: var(--gold);
            letter-spacing: 5px;
            text-transform: uppercase;
            margin-top: 18px;
            animation: bzSlideUp 0.4s 0.4s ease both;
            position: relative;
            z-index: 2;
        }

        @keyframes bzSlideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Particles ring around the name */
        .bz-particles {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }

        .bz-particle {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--gold);
            animation: bzParticle var(--dur, 1.2s) var(--delay, 0s) ease-out both;
        }

        @keyframes bzParticle {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }

            100% {
                transform: translate(var(--tx), var(--ty)) scale(0);
                opacity: 0;
            }
        }

        /* Red flash lines (like a stage effect) */
        .bz-flash {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background: radial-gradient(ellipse 80% 40% at 50% 50%,
                    rgba(220, 30, 30, 0.18) 0%, transparent 70%);
            animation: bzFlash 0.3s ease;
        }

        @keyframes bzFlash {
            0% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        /* ════════════════════════════════════════════════
   ███  WINNER / LEADERBOARD SCREEN  ███
   Epic full-screen celebration
   ════════════════════════════════════════════════ */
        #leaderboard-section {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: radial-gradient(ellipse at 50% 0%,
                    #1a0a3e 0%, #0d0520 40%, #050010 100%);
            overflow: hidden;
        }

        /* Animated star background */
        .lb-stars {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        .lb-star {
            position: absolute;
            border-radius: 50%;
            background: #fff;
            animation: twinkle var(--dur, 3s) var(--delay, 0s) ease-in-out infinite;
        }

        @keyframes twinkle {

            0%,
            100% {
                opacity: var(--op, 0.4);
                transform: scale(1);
            }

            50% {
                opacity: 1;
                transform: scale(1.5);
            }
        }

        /* Confetti */
        .lb-confetti {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }

        .confetto {
            position: absolute;
            top: -20px;
            width: var(--w, 10px);
            height: var(--h, 16px);
            background: var(--c, #ffd700);
            border-radius: 2px;
            left: var(--left, 50%);
            animation: confettiFall var(--dur, 3s) var(--delay, 0s) linear infinite;
        }

        @keyframes confettiFall {
            0% {
                transform: translateY(-20px) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(110vh) rotate(var(--rot, 720deg));
                opacity: 0.3;
            }
        }

        /* WINNER banner */
        .lb-winner-banner {
            position: relative;
            z-index: 10;
            text-align: center;
            margin-bottom: 10px;
            animation: fadeIn 0.5s ease;
        }

        .lb-winner-tag {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(1rem, 2vw, 1.4rem);
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 8px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .lb-winner-crown {
            font-size: clamp(3rem, 6vw, 5rem);
            display: block;
            animation: bounce 1s ease infinite;
        }

        .lb-winner-name {
            font-family: 'Anton', sans-serif;
            font-size: clamp(3.5rem, 10vw, 7rem);
            background: linear-gradient(135deg, var(--gold-dark), var(--gold-light), var(--gold-dark));
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: goldShimmer 2s linear infinite;
            letter-spacing: 3px;
            line-height: 1.1;
            filter: drop-shadow(0 0 30px rgba(201, 168, 76, 0.7));
        }

        .lb-winner-pts {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            color: var(--gold);
            margin-top: 4px;
            letter-spacing: 2px;
        }

        /* Podium row (1st, 2nd, 3rd) */
        .lb-podium {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 8px;
            margin-top: 18px;
            width: 100%;
            max-width: 800px;
        }

        .podium-slot {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            border-radius: 10px 10px 0 0;
            padding: 12px 18px 16px;
            position: relative;
            overflow: hidden;
            animation: podiumRise var(--rise-dur, 0.7s) var(--rise-delay, 0s) cubic-bezier(0.34, 1.56, 0.64, 1) both;
            min-width: 160px;
        }

        @keyframes podiumRise {
            from {
                transform: translateY(120%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .podium-slot::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.06) 0%, transparent 100%);
        }

        .podium-1 {
            height: 200px;
            background: linear-gradient(180deg, #4d3600 0%, #1a1200 100%);
            border: 2px solid var(--gold);
            box-shadow: 0 0 40px rgba(201, 168, 76, 0.4), inset 0 0 40px rgba(201, 168, 76, 0.05);
            --rise-dur: 0.8s;
            --rise-delay: 0.1s;
        }

        .podium-2 {
            height: 160px;
            background: linear-gradient(180deg, #2a2a2a 0%, #0d0d0d 100%);
            border: 2px solid #888;
            box-shadow: 0 0 20px rgba(136, 136, 136, 0.3);
            --rise-dur: 0.7s;
            --rise-delay: 0.3s;
        }

        .podium-3 {
            height: 130px;
            background: linear-gradient(180deg, #3a1a00 0%, #120900 100%);
            border: 2px solid #cd7f32;
            box-shadow: 0 0 20px rgba(205, 127, 50, 0.3);
            --rise-dur: 0.7s;
            --rise-delay: 0.5s;
        }

        .podium-rank {
            font-size: 2rem;
            margin-bottom: 4px;
        }

        .podium-name {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(0.85rem, 1.5vw, 1.1rem);
            font-weight: 700;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
        }

        .podium-score {
            font-family: 'Anton', sans-serif;
            font-size: clamp(1.2rem, 2vw, 1.6rem);
            color: var(--gold);
            margin-top: 2px;
        }

        .podium-1 .podium-score {
            color: var(--gold-light);
            font-size: clamp(1.5rem, 2.5vw, 2rem);
        }

        /* Remaining contestants in a row below */
        .lb-rest {
            position: relative;
            z-index: 10;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 14px;
            max-width: 900px;
        }

        .lb-rest-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 30px;
            padding: 6px 18px;
            font-family: 'Oswald', sans-serif;
            font-size: 0.95rem;
            animation: fadeIn 0.5s ease both;
        }

        .lb-rest-rank {
            color: rgba(255, 255, 255, 0.4);
            min-width: 20px;
        }

        .lb-rest-name {
            color: #fff;
        }

        .lb-rest-score {
            color: var(--gold);
            font-weight: 700;
        }
    </style>
</head>

<body>

    <!-- ═══ WELCOME SCREEN ══════════════════════════════ -->
    <div id="welcome-screen">
        <div class="welcome-stars"></div>
        <img id="welcome-logo" src="" alt="Logo" class="welcome-logo hidden">
        <div class="welcome-title" id="welcome-title">TECH FEUD</div>
        <div class="welcome-subtitle" id="welcome-subtitle">The Ultimate Technology Game Show</div>
        <div class="welcome-tagline">presented by Swayambhu 2026</div>
        <div class="welcome-icon">💻</div>
    </div>

    <!-- ═══ GAME SCREEN ═════════════════════════════════ -->
    <div id="game-screen" style="display:none; flex-direction:column; min-height:100vh;">

        <!-- Top Bar -->
        <div class="top-bar">
            <div class="admin-logo-area">
                <img id="proj-logo" src="" alt="" class="top-bar-logo hidden">
                <div class="title-area">
                    <div class="title">TECH FEUD</div>
                </div>
            </div>

            <!-- spacer so top-bar looks balanced -->
            <div></div>
        </div>

        <!-- Game Body -->
        <div class="game-body" id="game-body">

            <!-- Question Box -->
            <div id="question-section" style="display:none;">
                <div id="question-box-kbc" class="kbc-question-box" style="display:none;">
                    <p id="kbc-q-text" class="kbc-question-text"></p>
                </div>
                <div id="question-box-open" class="feud-question-box" style="display:none; border-color:#aa00ff;">
                    <p id="open-q-text" class="feud-question-text"></p>
                </div>
                <div id="question-box-feud" class="feud-question-box" style="display:none;">
                    <p id="feud-q-text" class="feud-question-text"></p>
                </div>
            </div>

            <!-- Answer Board KBC -->
            <div id="answer-board-kbc" style="display:none; max-width:900px; margin:0 auto; width:100%;">
                <div id="kbc-options"></div>
            </div>

            <!-- Answer Board Feud -->
            <div id="answer-board-feud" style="display:none;">
                <div class="feud-board">
                    <div class="col" id="feud-col-left"></div>
                    <div class="col" id="feud-col-right"></div>
                </div>
            </div>

        </div>

        <!-- Score Strip (bottom) -->
        <div class="score-strip" id="score-strip"></div>
    </div>


    <!-- ════════════════════════════════════════════════
     ███ BUZZER TAKEOVER OVERLAY ███
     Appears on top of everything when someone buzzes
     ════════════════════════════════════════════════ -->
    <div id="buzzer-takeover">
        <div class="bz-flash" id="bz-flash"></div>
        <div class="bz-spotlight"></div>
        <div class="bz-particles" id="bz-particles"></div>

        <div class="bz-label">⚡ First Buzzer</div>
        <div class="bz-name" id="bz-name">—</div>
        <div class="bz-sub">BUZZED IN!</div>
    </div>


    <!-- ════════════════════════════════════════════════
     ███ WINNER / LEADERBOARD SCREEN ███
     ════════════════════════════════════════════════ -->
    <div id="leaderboard-section" style="display:none;">

        <!-- Starfield background -->
        <div class="lb-stars" id="lb-stars"></div>
        <!-- Confetti -->
        <div class="lb-confetti" id="lb-confetti"></div>

        <!-- WINNER BANNER -->
        <div class="lb-winner-banner" id="lb-winner-banner">
            <div class="lb-winner-tag">🏆 Winner</div>
            <span class="lb-winner-crown">👑</span>
            <div class="lb-winner-name" id="lb-winner-name">—</div>
            <div class="lb-winner-pts" id="lb-winner-pts"></div>
        </div>

        <!-- PODIUM (top 3) -->
        <div class="lb-podium" id="lb-podium"></div>

        <!-- REST (4th+) -->
        <div class="lb-rest" id="lb-rest"></div>
    </div>


    <!-- Audio -->
    <audio id="snd-intro" preload="auto">
        <source src="sounds/intro.mp3" type="audio/mpeg">
    </audio>
    <audio id="snd-question" preload="auto" loop>
        <source src="sounds/question.mp3" type="audio/mpeg">
    </audio>
    <audio id="snd-buzzer" preload="auto">
        <source src="sounds/buzzer.mp3" type="audio/mpeg">
    </audio>
    <audio id="snd-correct" preload="auto">
        <source src="sounds/correct.mp3" type="audio/mpeg">
    </audio>
    <audio id="snd-wrong" preload="auto">
        <source src="sounds/wrong.mp3" type="audio/mpeg">
    </audio>
    <audio id="snd-timer-low" preload="auto" loop>
        <source src="sounds/timer-low.mp3" type="audio/mpeg">
    </audio>
    <audio id="snd-timesup" preload="auto">
        <source src="sounds/timesup.mp3" type="audio/mpeg">
    </audio>
    <audio id="snd-reveal" preload="auto">
        <source src="sounds/reveal.mp3" type="audio/mpeg">
    </audio>
    <audio id="snd-winner" preload="auto">
        <source src="sounds/winner.mp3" type="audio/mpeg">
    </audio>

    <!-- Fixed timer pill — controlled by JS show/hide -->
    <div id="proj-timer" style="display:none;">
        <div class="proj-timer-label">TIME</div>
        <div class="proj-timer-num" id="proj-timer-num">—</div>
    </div>

    <script src="js/presenter.js"></script>
</body>

</html>