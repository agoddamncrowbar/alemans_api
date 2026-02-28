<?php
require_once __DIR__ . '/../includes/headers.php';

session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(["authenticated" => false]);
    exit;
}

echo json_encode([
    "authenticated" => true,
    "username" => $_SESSION['admin_username']
]);
