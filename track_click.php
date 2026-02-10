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

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Vary: Origin");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST only"]);
    exit;
}

/* ================= INPUT ================= */

$data = json_decode(file_get_contents("php://input"), true);
$safariId = $data['safariId'] ?? '';

if (!$safariId) {
    http_response_code(400);
    echo json_encode(["error" => "Missing safariId"]);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'];

/* ================= DB ================= */

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    /* ===== RATE LIMIT CHECK ===== */

    $stmt = $pdo->prepare("
        SELECT click_count, last_click 
        FROM safari_click_ips 
        WHERE safari_id = ? AND ip_address = ?
    ");
    $stmt->execute([$safariId, $ip]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $lastClick = strtotime($row['last_click']);
        $oneHourAgo = time() - 3600;

        if ($lastClick > $oneHourAgo && $row['click_count'] >= 5) {
            echo json_encode(["success" => true, "rate_limited" => true]);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE safari_click_ips 
            SET click_count = click_count + 1, last_click = NOW()
            WHERE safari_id = ? AND ip_address = ?
        ");
        $stmt->execute([$safariId, $ip]);

    } else {
        $stmt = $pdo->prepare("
            INSERT INTO safari_click_ips (safari_id, ip_address) 
            VALUES (?, ?)
        ");
        $stmt->execute([$safariId, $ip]);
    }

    /* ===== MAIN CLICK COUNT ===== */

    $stmt = $pdo->prepare("
        INSERT INTO safari_clicks (safari_id, clicks)
        VALUES (?, 1)
        ON DUPLICATE KEY UPDATE clicks = clicks + 1
    ");
    $stmt->execute([$safariId]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
}
