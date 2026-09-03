<?php
require __DIR__ . '/auth.php';
requireLogin();
requireRole('admin');
require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$projectName = strtoupper(trim((string)($_GET['project'] ?? '')));
$shippingDate = trim((string)($_GET['date'] ?? ''));

if ($projectName === '' || !preg_match('/^[A-Z0-9-]+$/', $projectName) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $shippingDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid project or date']);
    exit;
}

$connection = getDbConnection();
$projectStatement = $connection->prepare('SELECT COUNT(*) AS total FROM shipments WHERE project_name = ?');
$projectStatement->bind_param('s', $projectName);
$projectStatement->execute();
$projectTotal = (int)($projectStatement->get_result()->fetch_assoc()['total'] ?? 0);
$projectStatement->close();

$dailyStatement = $connection->prepare('SELECT COUNT(*) AS total FROM shipments WHERE project_name = ? AND shipping_date = ?');
$dailyStatement->bind_param('ss', $projectName, $shippingDate);
$dailyStatement->execute();
$dailyTotal = (int)($dailyStatement->get_result()->fetch_assoc()['total'] ?? 0);
$dailyStatement->close();
$connection->close();

echo json_encode([
    'project_sequence' => str_pad((string)($projectTotal + 1), 3, '0', STR_PAD_LEFT),
    'daily_sequence' => str_pad((string)($dailyTotal + 1), 3, '0', STR_PAD_LEFT)
]);
