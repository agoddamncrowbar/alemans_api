<?php
require_once __DIR__ . '/../includes/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT footer_name, section
        FROM documents
        WHERE is_active = 1
        ORDER BY footer_name ASC, section ASC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Corrected declaration
    $links = [];

    foreach ($rows as $row) {
        $footer = $row['footer_name'];
        $section = $row['section'];

        if (!isset($links[$footer])) {
            $links[$footer] = [];
        }

        if (!in_array($section, $links[$footer])) {
            $links[$footer][] = $section;
        }
    }

    echo json_encode([
        "footer_links" => $links
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Failed to fetch footer links"
    ]);
}