<?php
// ═══════════════════════════════════════════════════════════
// Tech Feud — Database Config
// Place this file at: techfeud/config.php
// ═══════════════════════════════════════════════════════════

// Always suppress HTML errors so we never output HTML into JSON responses
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ── Database credentials ─────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // Change if your MySQL has a different user
define('DB_PASS', '');       // Change if your MySQL has a password
define('DB_NAME', 'techfeud');

// ── PDO connection (singleton) ───────────────────────────
function getDB() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        // Always return JSON, never HTML
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
    return $pdo;
}

// ── JSON response helper ─────────────────────────────────
function jsonResponse($data, $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// ── Read game state row ──────────────────────────────────
function getGameState() {
    $db   = getDB();
    $stmt = $db->query("SELECT * FROM game_state WHERE id = 1 LIMIT 1");
    $game = $stmt->fetch();

    if (!$game) {
        // Create default row if it doesn't exist
        $db->exec("INSERT INTO game_state (id, show_welcome) VALUES (1, 1)
                   ON DUPLICATE KEY UPDATE id=1");
        $stmt = $db->query("SELECT * FROM game_state WHERE id = 1 LIMIT 1");
        $game = $stmt->fetch();
    }

    // Decode revealed_answers JSON column into PHP array
    $raw = $game['revealed_answers'] ?? '[]';
    $game['revealed_answers'] = json_decode($raw, true) ?? [];

    // Provide default for game_mode if column doesn't exist yet (older DB installs)
    if (!isset($game['game_mode'])) $game['game_mode'] = 'turn_wise';

    return $game;
}

// ── Write game state columns ─────────────────────────────
function updateGameState(array $fields) {
    if (empty($fields)) return;

    $db     = getDB();
    $setClauses = [];
    $values     = [];

    foreach ($fields as $col => $val) {
        $setClauses[] = "`" . $col . "` = ?";
        $values[]     = $val;
    }

    $sql  = "UPDATE game_state SET " . implode(', ', $setClauses) . " WHERE id = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
}
?>
