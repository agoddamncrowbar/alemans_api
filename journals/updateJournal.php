<?php
require_once __DIR__ . '/../includes/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = $_POST['id'] ?? null;
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$date = $_POST['date'] ?? null;
$labelsInput = $_POST['labels'] ?? '';

if (!$id || !$title || !$content) {
    http_response_code(400);
    echo json_encode(["message" => "Missing required fields"]);
    exit;
}

$labelsArray = array_filter(array_map('trim', explode(',', $labelsInput)));
$labelsJson = json_encode(array_values($labelsArray));

try {
    $stmt = $pdo->prepare("
        UPDATE journals
        SET title = ?, content = ?, date = ?, labels = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $title,
        $content,
        $date ?: null,
        $labelsJson,
        $id
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Updated successfully"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Update failed"
    ]);
}
