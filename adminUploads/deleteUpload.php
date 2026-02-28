<?php
require_once __DIR__ . '/../includes/headers.php';
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit;
}

$body = json_decode(file_get_contents("php://input"), true);
$id = $body['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["message" => "Document ID is required"]);
    exit;
}

try {
    // Fetch filename before deleting so we can remove the file too
    $stmt = $pdo->prepare("SELECT filename FROM documents WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        http_response_code(404);
        echo json_encode(["message" => "Document not found"]);
        exit;
    }

    // Delete from DB
    $stmt = $pdo->prepare("DELETE FROM documents WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // Delete file from disk
    $filePath = __DIR__ . '/../uploads/' . $doc['filename'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    echo json_encode(["message" => "Document deleted successfully"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["message" => "Failed to delete document", "error" => $e->getMessage()]);
}