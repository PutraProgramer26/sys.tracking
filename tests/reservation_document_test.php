<?php
require __DIR__ . '/../db.php';

if (!file_exists(__DIR__ . '/../reservation.php')) {
    fwrite(STDERR, "Missing reservation.php document page\n");
    exit(1);
}

$conn = getDbConnection();
$result = $conn->query("SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = 'sys_tracking' AND table_name = 'shipments'");
$row = $result->fetch_assoc();
if (($row['total'] ?? 0) < 1) {
    fwrite(STDERR, "shipments table is missing\n");
    exit(1);
}

$conn->close();

echo "Reservation document flow ready\n";
