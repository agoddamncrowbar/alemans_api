$data = json_decode(file_get_contents("php://input"), true);
$safariId = $data['safariId'] ?? '';
$timeSpent = intval($data['timeSpent'] ?? 0);

$stmt = $pdo->prepare("
    INSERT INTO safari_engagement (safari_id, total_time_spent, total_views)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE 
        total_time_spent = total_time_spent + VALUES(total_time_spent),
        total_views = total_views + 1
");
$stmt->execute([$safariId, $timeSpent]);
