<?php
require_once __DIR__ . '/../includes/headers.php';

session_start();

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid credentials"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid credentials"]);
    exit;
}

/*
|--------------------------------------------------------------------------
| RATE LIMIT CHECK
|--------------------------------------------------------------------------
*/

if ($admin['lock_until'] && strtotime($admin['lock_until']) > time()) {
    http_response_code(429);
    echo json_encode(["message" => "Please wait a while before trying again."]);
    exit;
}

/*
|--------------------------------------------------------------------------
| PASSWORD VERIFY
|--------------------------------------------------------------------------
*/

if (!password_verify($password, $admin['password_hash'])) {

    $failed = $admin['failed_attempts'] + 1;
    $lockUntil = null;

    if ($failed >= 5) {
        $lockUntil = date("Y-m-d H:i:s", strtotime("+5 minutes"));
        $failed = 0; // reset counter after lock
    }

    $update = $pdo->prepare("
        UPDATE admins 
        SET failed_attempts = ?, lock_until = ?
        WHERE id = ?
    ");
    $update->execute([$failed, $lockUntil, $admin['id']]);

    http_response_code(401);
    echo json_encode(["message" => "Invalid credentials"]);
    exit;
}

/*
|--------------------------------------------------------------------------
| SUCCESS LOGIN
|--------------------------------------------------------------------------
*/

$reset = $pdo->prepare("
    UPDATE admins 
    SET failed_attempts = 0, lock_until = NULL
    WHERE id = ?
");
$reset->execute([$admin['id']]);

session_regenerate_id(true);

$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_username'] = $admin['username'];

echo json_encode(["message" => "Login successful"]);
