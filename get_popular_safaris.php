<?php
ini_set('display_errors', 0);
header("Content-Type: application/json");

use Dotenv\Dotenv;
require 'vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

/* ================= CORS ================= */

$allowedOrigins = array_filter([
    $_ENV['ALLOWED_ORIGIN_DEV'] ?? null,
    $_ENV['ALLOWED_ORIGIN_PROD'] ?? null,
]);

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($requestOrigin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $requestOrigin");
}

header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Vary: Origin");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "GET only"]);
    exit;
}

/* ================= DB CONNECTION ================= */

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    /* ================= POPULARITY QUERY ================= */

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
