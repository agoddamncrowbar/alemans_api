<?php
require_once __DIR__ . '/../includes/headers.php';

header("Content-Type: application/json");

// ---------------- Method Check ----------------
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit;
}

// Optional filters
$footerName = $_GET['footer_name'] ?? '';
$section = $_GET['section'] ?? '';

try {
    $query = "SELECT id, section, title, description, footer_name, filename, mime_type 
              FROM documents 
              WHERE is_active = 1";

    $params = [];

    if ($footerName) {
        $query .= " AND footer_name = :footer_name";
        $params[':footer_name'] = $footerName;
    }

    if ($section) {
        $query .= " AND section = :section";
        $params[':section'] = $section;
    }

    $query .= " ORDER BY display_order ASC, created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map documents to include full URL
    $baseUrl = $_ENV['BASE_URL'] ?? '';
    $files = array_map(function($doc) use ($baseUrl) {
        return [
            "id" => (int)$doc['id'],
            "section" => $doc['section'],
            "title" => $doc['title'],
            "description" => $doc['description'],
            "footer_name" => $doc['footer_name'],
            "url" => $baseUrl . "/uploads/" . $doc['filename'],
            "mime_type" => $doc['mime_type']
        ];
    }, $documents);

    echo json_encode(["files" => $files]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Failed to fetch documents",
        "error" => $e->getMessage() // remove in production
    ]);
}