<?php
// ═══════════════════════════════════════════════════════════
// Tech Feud — Server-Sent Events stream
// Place this file at: techfeud/api/events.php
// ═══════════════════════════════════════════════════════════
ini_set('display_errors', 0);
error_reporting(0);

require_once '../config.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no');

set_time_limit(0);
ob_implicit_flush(true);
if (ob_get_level()) ob_end_clean();

$lastHash = '';

while (true) {
    if (connection_aborted()) break;

    try {
        $db          = getDB();
        $game        = getGameState();
        $contestants = $db->query("SELECT * FROM contestants WHERE game_id = 1 ORDER BY position")->fetchAll();
        $leaderboard = $db->query("SELECT * FROM contestants WHERE game_id = 1 ORDER BY score DESC, name ASC")->fetchAll();
        $buzzerQueue = $db->query("SELECT * FROM buzzer_queue WHERE game_id = 1 ORDER BY buzz_time ASC LIMIT 3")->fetchAll();

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

        // Current contestant
        $currentContestant = null;
        $turnIdx = intval($game['current_turn']);
        if (isset($contestants[$turnIdx])) {
            $currentContestant = $contestants[$turnIdx];
        }

        // Auto-expire timer
        if (intval($game['timer_running']) === 1 && intval($game['timer_started_at']) > 0) {
            $elapsed   = round(microtime(true) * 1000) - intval($game['timer_started_at']);
            $remaining = (intval($game['timer_seconds']) * 1000) - $elapsed;
            if ($remaining <= 0) {
                $db->exec("UPDATE game_state SET timer_running=0, buzzers_locked=1 WHERE id=1");
                $game['timer_running']  = 0;
                $game['buzzers_locked'] = 1;
            }
        }

        $data = [
            'game'                => $game,
            'contestants'         => $contestants,
            'leaderboard'         => $leaderboard,
            'current_question'    => $currentQuestion,
            'current_contestant'  => $currentContestant,
            'buzzer_queue'        => $buzzerQueue,
            'ts'                  => time(),
        ];

        $json = json_encode($data);
        $hash = md5($json);

        if ($hash !== $lastHash) {
            echo "data: $json\n\n";
            $lastHash = $hash;
        } else {
            echo ": keepalive\n\n";
        }

        flush();
    } catch (Exception $e) {
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
        flush();
    }

    sleep(1);
    if (connection_aborted()) break;
}
?>
