<?php
// ═══════════════════════════════════════════════════════════
// Tech Feud — API  (place this file at: techfeud/api/api.php)
// ═══════════════════════════════════════════════════════════

// Suppress HTML error output — we always return JSON
ini_set('display_errors', 0);
error_reporting(0);

// Set JSON header FIRST before anything else can output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// CORRECT PATH: api.php is inside api/ folder, config.php is one level up
require_once '../config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

try {
switch ($action) {

    // ─── GET STATE ───────────────────────────────────────────────
    case 'get_state':
        $db          = getDB();
        $game        = getGameState();
        $contestants = $db->query("SELECT * FROM contestants WHERE game_id = 1 ORDER BY position")->fetchAll();
        $leaderboard = $db->query("SELECT * FROM contestants WHERE game_id = 1 ORDER BY score DESC, name ASC")->fetchAll();

        $currentQuestion = null;
        if (!empty($game['current_question_id'])) {
            $stmt = $db->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt->execute([$game['current_question_id']]);
            $currentQuestion = $stmt->fetch();
            if ($currentQuestion) {
                $astmt = $db->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY sort_order");
                $astmt->execute([$currentQuestion['id']]);
                $currentQuestion['answers'] = $astmt->fetchAll();
            }
        }

        $buzzerQueue  = $db->query("SELECT * FROM buzzer_queue WHERE game_id = 1 ORDER BY buzz_time ASC LIMIT 3")->fetchAll();
        $allQuestions = $db->query("SELECT id, question_text, question_type, points FROM questions WHERE game_id = 1 ORDER BY id")->fetchAll();

        jsonResponse([
            'game'             => $game,
            'contestants'      => $contestants,
            'leaderboard'      => $leaderboard,
            'current_question' => $currentQuestion,
            'buzzer_queue'     => $buzzerQueue,
            'questions'        => $allQuestions,
        ]);
        break;

    // ─── START GAME ──────────────────────────────────────────────
    case 'start_game':
        $db    = getDB();
        $names = $input['contestants'] ?? [];
        $filtered = array_filter(array_map('trim', $names));
        if (count($filtered) < 1) jsonResponse(['error' => 'Need at least 1 contestant'], 400);

        $db->exec("DELETE FROM contestants WHERE game_id = 1");
        $db->exec("DELETE FROM buzzer_queue WHERE game_id = 1");

        $stmt = $db->prepare("INSERT INTO contestants (name, score, position, game_id) VALUES (?, 0, ?, 1)");
        $pos  = 0;
        foreach ($filtered as $name) {
            $stmt->execute([$name, $pos++]);
        }

        updateGameState([
            'is_active'           => 1,
            'current_question_id' => 0,
            'current_turn'        => 0,
            'show_question'       => 0,
            'show_answers'        => 0,
            'show_leaderboard'    => 0,
            'show_welcome'        => 0,
            'revealed_answers'    => '[]',
            'timer_running'       => 0,
            'timer_started_at'    => 0,
            'buzzers_locked'      => 0,
            'game_mode'           => isset($input['game_mode']) && $input['game_mode'] === 'rapid_fire' ? 'rapid_fire' : 'turn_wise',
        ]);
        jsonResponse(['success' => true]);
        break;

    // ─── SET QUESTION ────────────────────────────────────────────
    case 'set_question':
        $db  = getDB();
        $qid = intval($input['question_id'] ?? 0);
        $db->exec("DELETE FROM buzzer_queue WHERE game_id = 1");
        updateGameState([
            'current_question_id' => $qid,
            'revealed_answers'    => '[]',
            'timer_running'       => 0,
            'timer_started_at'    => 0,
            'buzzers_locked'      => 0,
        ]);
        jsonResponse(['success' => true]);
        break;

    // ─── TOGGLE DISPLAY ──────────────────────────────────────────
    case 'toggle_display':
        $type    = $input['type'] ?? '';
        $value   = !empty($input['value']) ? 1 : 0;
        $allowed = ['show_question', 'show_answers', 'show_leaderboard', 'show_welcome'];
        if (!in_array($type, $allowed)) jsonResponse(['error' => 'Invalid type: ' . $type], 400);
        updateGameState([$type => $value]);
        jsonResponse(['success' => true, 'type' => $type, 'value' => $value]);
        break;

    // ─── REVEAL ANSWER ───────────────────────────────────────────
    case 'reveal_answer':
        $db           = getDB();
        $answerId     = $input['answer_id']     ?? null;
        $contestantId = $input['contestant_id'] ?? null;
        $points       = intval($input['points'] ?? 0);

        $game     = getGameState();
        $revealed = $game['revealed_answers'];
        if (!in_array((string)$answerId, array_map('strval', $revealed))) {
            $revealed[] = $answerId;
        }

        if ($contestantId && $points > 0) {
            $s = $db->prepare("UPDATE contestants SET score = score + ? WHERE id = ? AND game_id = 1");
            $s->execute([$points, $contestantId]);
            $s = $db->prepare("INSERT INTO answer_records (question_id, answer_id, contestant_id, points_awarded, game_id) VALUES (?,?,?,?,1)");
            $s->execute([$game['current_question_id'], $answerId, $contestantId, $points]);
        }

        updateGameState(['revealed_answers' => json_encode(array_values($revealed))]);
        jsonResponse(['success' => true]);
        break;

    // ─── AWARD POINTS ────────────────────────────────────────────
    case 'award_points':
        $db           = getDB();
        $contestantId = $input['contestant_id'] ?? null;
        $points       = intval($input['points']   ?? 0);
        $questionId   = intval($input['question_id'] ?? 0);

        if (!$contestantId) jsonResponse(['error' => 'No contestant_id'], 400);

        if ($points != 0) {
            $s = $db->prepare("UPDATE contestants SET score = score + ? WHERE id = ? AND game_id = 1");
            $s->execute([$points, $contestantId]);
        }
        $s = $db->prepare("INSERT INTO answer_records (question_id, contestant_id, points_awarded, game_id) VALUES (?,?,?,1)");
        $s->execute([$questionId, $contestantId, $points]);
        jsonResponse(['success' => true]);
        break;

    // ─── NO ANSWER ───────────────────────────────────────────────
    case 'no_answer':
        jsonResponse(['success' => true]);
        break;

    // --- SET GAME MODE ---
    case 'set_game_mode':
        $mode = ($input['mode'] ?? '') === 'rapid_fire' ? 'rapid_fire' : 'turn_wise';
        updateGameState(['game_mode' => $mode]);
        jsonResponse(['success' => true, 'game_mode' => $mode]);
        break;

    // --- NEXT TURN ---
    case 'next_turn':
        $db    = getDB();
        $game  = getGameState();
        $count = (int)$db->query("SELECT COUNT(*) FROM contestants WHERE game_id = 1")->fetchColumn();
        if ($count > 0) {
            $next = (intval($game['current_turn']) + 1) % $count;
            updateGameState(['current_turn' => $next]);
        }
        jsonResponse(['success' => true]);
        break;

    // ─── NEW ROUND ───────────────────────────────────────────────
    case 'new_round':
        $db = getDB();
        $db->exec("DELETE FROM buzzer_queue WHERE game_id = 1");
        updateGameState([
            'revealed_answers' => '[]',
            'show_answers'     => 0,
            'show_question'    => 0,
            'timer_running'    => 0,
            'timer_started_at' => 0,
            'buzzers_locked'   => 0,
        ]);
        jsonResponse(['success' => true]);
        break;

    // ─── TIMER ───────────────────────────────────────────────────
    case 'start_timer':
        $seconds = max(1, intval($input['seconds'] ?? 30));
        updateGameState([
            'timer_seconds'    => $seconds,
            'timer_started_at' => (int)round(microtime(true) * 1000),
            'timer_running'    => 1,
            'buzzers_locked'   => 0,
        ]);
        jsonResponse(['success' => true]);
        break;

    case 'stop_timer':
        updateGameState(['timer_running' => 0]);
        jsonResponse(['success' => true]);
        break;

    // ─── BUZZERS ─────────────────────────────────────────────────
    case 'lock_buzzers':
        updateGameState(['buzzers_locked' => 1, 'timer_running' => 0]);
        jsonResponse(['success' => true]);
        break;

    case 'unlock_buzzers':
        $db = getDB();
        $db->exec("DELETE FROM buzzer_queue WHERE game_id = 1");
        updateGameState(['buzzers_locked' => 0]);
        jsonResponse(['success' => true]);
        break;

    case 'buzz':
        $db            = getDB();
        $contestantId  = $input['contestant_id']   ?? null;
        $contestantName= $input['contestant_name'] ?? '';
        $buzzTime      = (int)round(microtime(true) * 1000);

        if (!$contestantId) jsonResponse(['success' => false, 'message' => 'No contestant_id'], 400);

        $game = getGameState();
        if (intval($game['buzzers_locked']) === 1) {
            jsonResponse(['success' => false, 'message' => 'Buzzers are locked']);
            break;
        }

        $s = $db->prepare("SELECT id FROM buzzer_queue WHERE contestant_id = ? AND game_id = 1");
        $s->execute([$contestantId]);
        if ($s->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Already buzzed']);
            break;
        }

        $count = (int)$db->query("SELECT COUNT(*) FROM buzzer_queue WHERE game_id = 1")->fetchColumn();
        $rank  = $count + 1;

        $s = $db->prepare("INSERT INTO buzzer_queue (contestant_id, contestant_name, question_id, buzz_time, rank_position, game_id) VALUES (?,?,?,?,?,1)");
        $s->execute([$contestantId, $contestantName, $game['current_question_id'], $buzzTime, $rank]);

        jsonResponse(['success' => true, 'rank' => $rank]);
        break;

    case 'clear_buzzers':
        $db = getDB();
        $db->exec("DELETE FROM buzzer_queue WHERE game_id = 1");
        updateGameState(['buzzers_locked' => 0]);
        jsonResponse(['success' => true]);
        break;

    // ─── ADD QUESTION ────────────────────────────────────────────
    case 'add_question':
        $db      = getDB();
        $text    = trim($input['question_text']  ?? '');
        $type    = $input['question_type']        ?? 'feud';
        $points  = intval($input['points']        ?? 10);
        $answers = $input['answers']              ?? [];

        if (!$text) jsonResponse(['error' => 'Question text required'], 400);
        if (!in_array($type, ['feud','kbc','open'])) jsonResponse(['error' => 'Invalid type'], 400);

        $s = $db->prepare("INSERT INTO questions (question_text, question_type, points, game_id) VALUES (?,?,?,1)");
        $s->execute([$text, $type, $points]);
        $qid = $db->lastInsertId();

        $as = $db->prepare("INSERT INTO answers (question_id, answer_text, points, is_correct, option_label, sort_order) VALUES (?,?,?,?,?,?)");
        foreach ($answers as $i => $ans) {
            $atext = trim($ans['text'] ?? '');
            if (!$atext) continue;
            $apoints = intval($ans['points']  ?? 0);
            $correct = intval($ans['correct'] ?? 0);
            $label   = $ans['label'] ?? chr(65 + $i);
            $as->execute([$qid, $atext, $apoints, $correct, $label, $i]);
        }

        jsonResponse(['success' => true, 'question_id' => $qid]);
        break;

    // ─── DELETE QUESTION ─────────────────────────────────────────
    case 'delete_question':
        $db  = getDB();
        $qid = intval($input['question_id'] ?? 0);
        $db->prepare("DELETE FROM answers  WHERE question_id = ?"          )->execute([$qid]);
        $db->prepare("DELETE FROM questions WHERE id = ? AND game_id = 1"  )->execute([$qid]);
        jsonResponse(['success' => true]);
        break;

    // ─── UPLOAD LOGO ─────────────────────────────────────────────
    case 'upload_logo':
        if (!isset($_FILES['logo'])) jsonResponse(['error' => 'No file uploaded'], 400);
        $file = $_FILES['logo'];
        if ($file['error'] !== UPLOAD_ERR_OK) jsonResponse(['error' => 'Upload error code: ' . $file['error']], 400);

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png','jpg','jpeg','gif','svg','webp'])) {
            jsonResponse(['error' => 'Invalid file type. Use PNG, JPG, GIF, SVG or WEBP'], 400);
        }

        // Save to techfeud/uploads/ (one level UP from api/)
        $uploadsDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

        $filename = 'logo_' . time() . '.' . $ext;
        $dest     = $uploadsDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // Store as 'uploads/filename' — accessible from root as http://host/techfeud/uploads/filename
            updateGameState(['logo_path' => 'uploads/' . $filename]);
            jsonResponse(['success' => true, 'logo_path' => 'uploads/' . $filename]);
        } else {
            jsonResponse(['error' => 'Failed to move uploaded file. Check folder permissions.'], 500);
        }
        break;

    // ─── RESET GAME ──────────────────────────────────────────────
    case 'reset_game':
        $db   = getDB();
        $logo = getGameState()['logo_path'] ?? '';
        $db->exec("DELETE FROM contestants   WHERE game_id = 1");
        $db->exec("DELETE FROM buzzer_queue  WHERE game_id = 1");
        $db->exec("DELETE FROM answer_records WHERE game_id = 1");
        updateGameState([
            'is_active'           => 0,
            'current_question_id' => 0,
            'current_turn'        => 0,
            'show_question'       => 0,
            'show_answers'        => 0,
            'show_leaderboard'    => 0,
            'show_welcome'        => 1,
            'revealed_answers'    => '[]',
            'timer_running'       => 0,
            'timer_started_at'    => 0,
            'buzzers_locked'      => 0,
            'logo_path'           => $logo,
        ]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action: ' . htmlspecialchars($action)], 400);
}
} catch (Exception $e) {
    jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>
