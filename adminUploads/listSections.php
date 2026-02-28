<?php
require_once __DIR__ . '/../includes/headers.php';

header("Content-Type: application/json");

// Prevent PHP warnings from breaking JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    /* ------------------ Only GET Allowed ------------------ */
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        exit;
    }

    /* ------------------ Fetch Distinct Sections ------------------ */
    $stmt = $pdo->query("SELECT DISTINCT section FROM documents ORDER BY section ASC");
    $sections = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        "sections" => $sections
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Server error",
        "error" => $e->getMessage() // remove in production
    ]);
}