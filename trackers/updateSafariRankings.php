<?php
require_once __DIR__ . '/../includes/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST only"]);
    exit;
}

try {

    /*
        STEP 1: Recalculate popularity scores
    */

    $pdo->exec("
        INSERT INTO safari_rankings (safari_id, popularity_score)
        SELECT 
            e.safari_id,
            (
                IFNULL(c.clicks, 0) * 1.0 +
                IFNULL(e.total_views, 0) * 1.5 +
                (IFNULL(e.total_time_spent, 0) / 60) * 2.0 +
                IFNULL(r.manual_boost, 0)
            ) AS popularity_score
        FROM safari_engagement e
        LEFT JOIN safari_clicks c ON e.safari_id = c.safari_id
        LEFT JOIN safari_rankings r ON e.safari_id = r.safari_id
        ON DUPLICATE KEY UPDATE
            popularity_score = VALUES(popularity_score)
    ");

    /*
        STEP 2: Update rank positions
        Highest score = rank 1
    */

    $stmt = $pdo->query("
        SELECT safari_id 
        FROM safari_rankings 
        ORDER BY popularity_score DESC
    ");

    $safaris = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $rank = 1;

    $updateStmt = $pdo->prepare("
        UPDATE safari_rankings 
        SET rank_position = ? 
        WHERE safari_id = ?
    ");

    foreach ($safaris as $safariId) {
        $updateStmt->execute([$rank, $safariId]);
        $rank++;
    }

    echo json_encode([
        "success" => true,
        "updated" => count($safaris)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
}
