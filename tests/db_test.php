<?php
require __DIR__ . '/../db.php';

$conn = getDbConnection();
$result = $conn->query("SELECT DATABASE() AS db_name");
$row = $result->fetch_assoc();

if (($row['db_name'] ?? '') !== 'sys_tracking') {
    fwrite(STDERR, "Database not connected to sys_tracking\n");
    exit(1);
}

echo "Connected to: {$row['db_name']}\n";
