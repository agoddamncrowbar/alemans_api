<?php
require_once __DIR__ . '/../includes/headers.php';

// Get query params and normalize them
$section = trim(strtolower($_GET['section'] ?? ''));
$footerName = trim(strtolower($_GET['footer_name'] ?? ''));

error_log("Requested section: '$section', footer_name: '$footerName'");

if (!$section || !$footerName) {
    http_response_code(400);
    echo json_encode(["message" => "Missing section or footer_name"]);
    exit;
}

try {
    // Case-insensitive, trimmed comparison
    $stmt = $pdo->prepare("
        SELECT title, description, filename, mime_type
        FROM documents
        WHERE LOWER(TRIM(section)) = :section
          AND LOWER(TRIM(footer_name)) = :footer_name
          AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([
        ":section" => $section,
        ":footer_name" => $footerName
    ]);

    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Found document: " . json_encode($doc));

    if (!$doc) {
        http_response_code(404);
        echo json_encode(["message" => "Document not found"]);
        exit;
    }

    // Return document data
    echo json_encode([
        "title" => $doc['title'],
        "description" => $doc['description'],
        "url" => $_ENV['BASE_URL'] . "/adminUploads/getFile.php?filename=" . urlencode($doc['filename']),
        "mime_type" => $doc['mime_type']
    ]);

} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "Server error"]);
}