<?php
require __DIR__ . '/../db.php';

$conn = getDbConnection();
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($result->num_rows !== 1) {
    fwrite(STDERR, "Missing users.role column\n");
    exit(1);
}

$adminQuery = $conn->query("SELECT COUNT(*) AS total FROM users WHERE username = 'admin' AND role = 'admin'");
$adminRow = $adminQuery->fetch_assoc();
if (($adminRow['total'] ?? 0) < 1) {
    fwrite(STDERR, "Default admin role not configured\n");
    exit(1);
}

$userQuery = $conn->query("SELECT COUNT(*) AS total FROM users WHERE username = 'user' AND role = 'user'");
$userRow = $userQuery->fetch_assoc();
if (($userRow['total'] ?? 0) < 1) {
    fwrite(STDERR, "Default user role not configured\n");
    exit(1);
}

$conn->close();

echo "User roles configured\n";
