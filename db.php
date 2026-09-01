<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function ensureDatabaseSchema(mysqli $connection): void
{
    $connection->query("CREATE TABLE IF NOT EXISTS shipments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shipping_date DATE NULL,
        reservation_code VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'packing',
        sender_name VARCHAR(255) NULL,
        sender_uid VARCHAR(100) NULL,
        sender_position VARCHAR(255) NULL,
        sender_location VARCHAR(255) NULL,
        receiver_name VARCHAR(255) NULL,
        receiver_uid VARCHAR(100) NULL,
        receiver_position VARCHAR(255) NULL,
        receiver_location VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $connection->query("CREATE TABLE IF NOT EXISTS shipment_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shipment_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        qty INT NOT NULL DEFAULT 0,
        unit VARCHAR(50) NOT NULL,
        CONSTRAINT fk_shipment_items_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $connection->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $result = $connection->query("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
    if ($result && $result->num_rows === 0) {
        $username = 'admin';
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $fullName = 'Administrator';
        $stmt = $connection->prepare("INSERT INTO users (username, password, full_name) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $username, $hash, $fullName);
        $stmt->execute();
        $stmt->close();
    }
}

function getDbConnection(): mysqli
{
    $host = '127.0.0.1';
    $user = 'root';
    $password = '';
    $database = 'sys_tracking';

    $connection = @new mysqli($host, $user, $password, $database);

    if ($connection->connect_error) {
        $adminConnection = @new mysqli($host, $user, $password, 'mysql');

        if ($adminConnection && !$adminConnection->connect_error) {
            $adminConnection->query("CREATE DATABASE IF NOT EXISTS `{$database}`");
            $adminConnection->select_db($database);
            $adminConnection->set_charset('utf8mb4');
            ensureDatabaseSchema($adminConnection);
            return $adminConnection;
        }

        throw new RuntimeException('MySQL connection failed: ' . $connection->connect_error . ' / ' . ($adminConnection ? $adminConnection->connect_error : 'no admin connection'));
    }

    $connection->set_charset('utf8mb4');
    ensureDatabaseSchema($connection);
    return $connection;
}
