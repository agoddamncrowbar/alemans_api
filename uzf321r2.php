<?php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
set_time_limit(120);
ignore_user_abort(true);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

/*
==========================
CORS CONFIGURATION
==========================
*/

$allowedOrigins = array_filter([
    $_ENV['ALLOWED_ORIGIN_DEV'] ?? null,
    $_ENV['ALLOWED_ORIGIN_PROD'] ?? null,
]);

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($requestOrigin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $requestOrigin");
    header("Access-Control-Allow-Credentials: true");
}

header("Vary: Origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

/*
==========================
REQUEST VALIDATION
==========================
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "POST only"]);
    exit;
}

$recipientEmail = $_POST['email'] ?? '';
$recipientName  = $_POST['name'] ?? '';
$subject        = $_POST['subject'] ?? '';
$message        = $_POST['message'] ?? '';

if (!$recipientEmail || !$recipientName || !$subject || !$message) {
    http_response_code(400);
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

function loadTemplate($path, $data) {
    $html = file_get_contents($path);
    foreach ($data as $key => $value) {
        $safeValue = nl2br(htmlspecialchars($value));
        $html = str_replace("{{".$key."}}", $safeValue, $html);
    }
    return $html;
}

/*
==========================
SMTP CONFIG
==========================
*/

$host = $_ENV['SMTP_HOST'];
$port = $_ENV['SMTP_PORT'];

$websiteEmail = $_ENV['WEBSITE_EMAIL'];
$websitePass  = $_ENV['WEBSITE_PASSWORD'];

$infoEmail = $_ENV['INFO_EMAIL'];
$infoPass  = $_ENV['INFO_PASSWORD'];

try {

    /*
    ==========================
    EMAIL 1: User → Company
    ==========================
    */

    $mail1 = new PHPMailer(true);
    $mail1->isSMTP();
    $mail1->Host = $host;
    $mail1->SMTPAuth = true;
    $mail1->Username = $websiteEmail;
    $mail1->Password = $websitePass;
    $mail1->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail1->Port = $port;
    $mail1->Timeout = 30;

    $mail1->setFrom($websiteEmail, "Website Contact Form");
    $mail1->addAddress($infoEmail);

    $mail1->isHTML(true);
    $mail1->Subject = $subject;

    $mail1->Body = loadTemplate(
        __DIR__ . "/templates/internal_notification.html",
        [
            "name" => $recipientName,
            "email" => $recipientEmail,
            "subject" => $subject,
            "message" => $message
        ]
    );

    $mail1->send();


    /*
    ==========================
    EMAIL 2: Company → User
    ==========================
    */

    $mail2 = new PHPMailer(true);
    $mail2->isSMTP();
    $mail2->Host = $host;
    $mail2->SMTPAuth = true;
    $mail2->Username = $infoEmail;
    $mail2->Password = $infoPass;
    $mail2->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail2->Port = $port;
    $mail2->Timeout = 30;

    $mail2->setFrom($infoEmail, "Alemans Adventures");
    $mail2->addAddress($recipientEmail, $recipientName);

    $mail2->isHTML(true);
    $mail2->Subject = "We received your message";

    $mail2->Body = loadTemplate(
        __DIR__ . "/templates/user_confirmation.html",
        [
            "name" => $recipientName,
            "subject" => $subject
        ]
    );

    $mail2->send();

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
    exit;
}
