<?php
require_once __DIR__ . '/../includes/headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$date = $_POST['date'] ?? null;
$labelsInput = $_POST['labels'] ?? '';

if (!$title || !$content) {
    http_response_code(400);
    echo json_encode(["message" => "Title and content are required"]);
    exit;
}

/* ---- Handle Labels ---- */
$labelsArray = array_filter(array_map('trim', explode(',', $labelsInput)));
$labelsJson = json_encode(array_values($labelsArray));

/* ---- Handle Images ---- */
$uploadedImages = [];

if (!empty($_FILES['images']['name'][0])) {
    $uploadDir = __DIR__ . "/uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
        if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {

            $extension = pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION);
            $fileName = uniqid() . "." . $extension;

            $destination = $uploadDir . $fileName;

            move_uploaded_file($tmpName, $destination);

            $uploadedImages[] = "/uploads/" . $fileName;
        }
    }
}

$imagesJson = json_encode($uploadedImages);

/* ---- Insert ---- */
try {
    $stmt = $pdo->prepare("
        INSERT INTO journals (title, content, date, labels, images)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $title,
        $content,
        $date ?: null,
        $labelsJson,
        $imagesJson
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Journal created"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Create failed"
    ]);
}
