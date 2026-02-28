<?php
require_once __DIR__ . '/../includes/headers.php'; // includes config.php automatically

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "GET only"]);
    exit;
}

/* ================= DB QUERY ================= */

try {
    $limit = intval($_GET['limit'] ?? 6); // default top 6 safaris

    $stmt = $pdo->prepare("
        SELECT 
            s.safari_id,
            (
                IFNULL(c.clicks, 0) * 1.0 +
                IFNULL(e.total_views, 0) * 1.5 +
                (IFNULL(e.total_time_spent, 0) / 60) * 2.0 +
                IFNULL(r.manual_boost, 0)
            ) AS popularity_score
        FROM safari_engagement s
        LEFT JOIN safari_clicks c ON s.safari_id = c.safari_id
        LEFT JOIN safari_rankings r ON s.safari_id = r.safari_id
        LEFT JOIN safari_engagement e ON s.safari_id = e.safari_id
        ORDER BY popularity_score DESC
        LIMIT :limit
    ");

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $safaris = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        "success" => true,
        "count" => count($safaris),
        "safaris" => $safaris
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
}
