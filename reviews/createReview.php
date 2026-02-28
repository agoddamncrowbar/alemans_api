<?php
require_once __DIR__ . '/../includes/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$name = $data['name'] ?? '';
$reviewText = $data['review'] ?? '';
$rating = isset($data['rating']) ? floatval($data['rating']) : 5.0;
$link = $data['link'] ?? '';

if (!$name || !$reviewText) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Name and review are required"]);
    exit;
}

if ($rating < 0 || $rating > 5) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Rating must be between 0 and 5"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO reviews (name, review, rating, link)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$name, $reviewText, $rating, $link]);

    echo json_encode([
        "success" => true,
        "message" => "Review created"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to create review"]);
}
