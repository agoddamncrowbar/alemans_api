<?php
require_once __DIR__ . '/../includes/headers.php';

try {
    $stmt = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC");
    $reviews = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "reviews" => $reviews
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch reviews"
    ]);
}
