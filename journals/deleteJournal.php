<?php
require_once __DIR__ . '/../includes/headers.php';

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid ID"]);
    exit;
}

try {
    /* Get images first */
    $stmt = $pdo->prepare("SELECT images FROM journals WHERE id = ?");
    $stmt->execute([$id]);
    $journal = $stmt->fetch();

    if (!$journal) {
        http_response_code(404);
        echo json_encode(["message" => "Not found"]);
        exit;
    }

    $images = json_decode($journal['images'], true) ?? [];

    /* Delete DB record */
    $stmt = $pdo->prepare("DELETE FROM journals WHERE id = ?");
    $stmt->execute([$id]);

    /* Delete files */
    foreach ($images as $imagePath) {
        $fullPath = __DIR__ . "/.." . $imagePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Deleted successfully"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Delete failed"
    ]);
}
