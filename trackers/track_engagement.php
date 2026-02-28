<?php
require_once __DIR__ . '/../includes/headers.php'; // includes config.php automatically

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST only"]);
    exit;
}

/* ================= INPUT ================= */

$data = json_decode(file_get_contents("php://input"), true);

$safariId  = $data['safariId'] ?? '';
$timeSpent = intval($data['timeSpent'] ?? 0);

if (!$safariId || $timeSpent <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid safariId or timeSpent"]);
    exit;
}

/* ================= DB ================= */

try {
    /*
        Table behavior:
        - add seconds to total_time_spent
        - increment total_views
    */
    $stmt = $pdo->prepare("
        INSERT INTO safari_engagement (safari_id, total_time_spent, total_views)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE
            total_time_spent = total_time_spent + VALUES(total_time_spent),
            total_views = total_views + 1
    ");

    $stmt->execute([$safariId, $timeSpent]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
}
