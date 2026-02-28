<?php
require_once __DIR__ . '/includes/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST only"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$safariId = $data['safariId'] ?? '';

if (!$safariId) {
    http_response_code(400);
    echo json_encode(["error" => "Missing safariId"]);
    exit;
}

try {

    /*
        STEP 1: Calculate score for this safari only
    */

    $stmt = $pdo->prepare("
        SELECT 
            IFNULL(c.clicks, 0) AS clicks,
            IFNULL(e.total_views, 0) AS total_views,
            IFNULL(e.total_time_spent, 0) AS total_time_spent,
            IFNULL(r.manual_boost, 0) AS manual_boost
        FROM safari_engagement e
        LEFT JOIN safari_clicks c ON e.safari_id = c.safari_id
        LEFT JOIN safari_rankings r ON e.safari_id = r.safari_id
        WHERE e.safari_id = ?
    ");

    $stmt->execute([$safariId]);
    $dataRow = $stmt->fetch();

    if (!$dataRow) {
        echo json_encode(["success" => true]);
        exit;
    }

    $score =
        ($dataRow['clicks'] * 1.0) +
        ($dataRow['total_views'] * 1.5) +
        (($dataRow['total_time_spent'] / 60) * 2.0) +
        ($dataRow['manual_boost']);

    /*
        STEP 2: Upsert new score
    */

    $stmt = $pdo->prepare("
        INSERT INTO safari_rankings (safari_id, popularity_score)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE
            popularity_score = VALUES(popularity_score)
    ");

    $stmt->execute([$safariId, $score]);

    /*
        STEP 3: Adjust rank dynamically
        Count how many safaris have higher score
    */

    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM safari_rankings
        WHERE popularity_score > ?
    ");

    $stmt->execute([$score]);
    $higherCount = $stmt->fetchColumn();

    $newRank = $higherCount + 1;

    $stmt = $pdo->prepare("
        UPDATE safari_rankings
        SET rank_position = ?
        WHERE safari_id = ?
    ");

    $stmt->execute([$newRank, $safariId]);

    echo json_encode([
        "success" => true,
        "new_rank" => $newRank
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
}
