<?php
require_once __DIR__ . '/../includes/headers.php';

try {
    $stmt = $pdo->query("SELECT * FROM journals ORDER BY created_at DESC");
    $journals = $stmt->fetchAll();

    foreach ($journals as &$journal) {
        $journal['labels'] = $journal['labels']
            ? json_decode($journal['labels'], true)
            : [];

        $journal['images'] = $journal['images']
            ? json_decode($journal['images'], true)
            : [];
    }

    echo json_encode([
        "success" => true,
        "journals" => $journals
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch journals"
    ]);
}
