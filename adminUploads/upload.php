<?php
require_once __DIR__ . '/../includes/headers.php';

header("Content-Type: application/json");

// Prevent PHP warnings from breaking JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {

    /* ------------------ Method Check ------------------ */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        exit;
    }

    /* ------------------ Validate File Exists ------------------ */
    if (!isset($_FILES['document'])) {
        http_response_code(400);
        echo json_encode(["message" => "No file uploaded"]);
        exit;
    }

    $file = $_FILES['document'];

    /* ------------------ Check Upload Error FIRST ------------------ */
    if ($file['error'] !== UPLOAD_ERR_OK) {

        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => "File exceeds server upload_max_filesize",
            UPLOAD_ERR_FORM_SIZE  => "File exceeds form MAX_FILE_SIZE",
            UPLOAD_ERR_PARTIAL    => "File partially uploaded",
            UPLOAD_ERR_NO_FILE    => "No file uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION  => "Upload blocked by PHP extension"
        ];

        http_response_code(400);
        echo json_encode([
            "message" => $uploadErrors[$file['error']] ?? "Unknown upload error",
            "error_code" => $file['error']
        ]);
        exit;
    }

    /* ------------------ Sanitize Inputs ------------------ */
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $footerName  = trim($_POST['footerName'] ?? '');
    $section     = trim($_POST['section'] ?? '');

    if (!$title || !$footerName || !$section) {
        http_response_code(400);
        echo json_encode([
            "message" => "Title, footer name and section are required"
        ]);
        exit;
    }

    /* ------------------ Validate Uploaded File Exists ------------------ */
    if (!is_uploaded_file($file['tmp_name'])) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid upload attempt"]);
        exit;
    }

    /* ------------------ File Size Validation ------------------ */
    $maxFileSize = 10 * 1024 * 1024; // 10MB

    if ($file['size'] > $maxFileSize) {
        http_response_code(400);
        echo json_encode(["message" => "File too large (max 10MB)"]);
        exit;
    }

    /* ------------------ MIME Detection ------------------ */
    $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'text/html',
        'image/jpeg',
        'image/png',
        'image/gif'
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedType = $finfo->file($file['tmp_name']);

    if (!$detectedType || !in_array($detectedType, $allowedMimeTypes)) {
        http_response_code(400);
        echo json_encode([
            "message" => "Invalid file type",
            "detected" => $detectedType
        ]);
        exit;
    }

    /* ------------------ Prepare Upload Folder ------------------ */
    $uploadDir = __DIR__ . '/../uploads';

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create upload directory"]);
            exit;
        }
    }

    /* ------------------ Generate Safe Filename ------------------ */
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $uploadDir . '/' . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        http_response_code(500);
        echo json_encode(["message" => "Failed to move uploaded file"]);
        exit;
    }

    /* ------------------ Save to Database ------------------ */
    $stmt = $pdo->prepare("
        INSERT INTO documents 
        (section, title, description, footer_name, filename, original_name, mime_type, created_at)
        VALUES 
        (:section, :title, :description, :footer_name, :filename, :original_name, :mime_type, NOW())
    ");

    $stmt->execute([
        ":section"       => $section,
        ":title"         => $title,
        ":description"   => $description,
        ":footer_name"   => $footerName,
        ":filename"      => $uniqueName,
        ":original_name" => $file['name'],
        ":mime_type"     => $detectedType
    ]);

    $baseUrl = $_ENV['BASE_URL'] ?? '';

    echo json_encode([
        "message" => "Upload successful",
        "file" => [
            "id"          => $pdo->lastInsertId(),
            "section"     => $section,
            "title"       => $title,
            "description" => $description,
            "footer_name" => $footerName,
            "url"         => $baseUrl . "/uploads/" . $uniqueName
        ]
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "message" => "Server error",
        "error"   => $e->getMessage() // remove in production
    ]);
}