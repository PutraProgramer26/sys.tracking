<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function ensureColumnExists(mysqli $connection, string $table, string $column, string $definition): void
{
    $result = $connection->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if ($result && $result->num_rows === 0) {
        $connection->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function ensureDatabaseSchema(mysqli $connection): void
{
    $connection->query("CREATE TABLE IF NOT EXISTS shipments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shipping_date DATE NULL,
        reservation_code VARCHAR(100) NOT NULL,
        project_name VARCHAR(100) NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'packing',
        sender_name VARCHAR(255) NULL,
        sender_uid VARCHAR(100) NULL,
        sender_position VARCHAR(255) NULL,
        sender_location VARCHAR(255) NULL,
        receiver_name VARCHAR(255) NULL,
        receiver_uid VARCHAR(100) NULL,
        receiver_position VARCHAR(255) NULL,
        receiver_location VARCHAR(255) NULL,
        sender_signature LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $connection->query("CREATE TABLE IF NOT EXISTS shipment_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shipment_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        qty INT NOT NULL DEFAULT 0,
        unit VARCHAR(50) NOT NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'consumables',
        category_alt VARCHAR(100) NULL,
        note VARCHAR(255) NULL,
        CONSTRAINT fk_shipment_items_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    ensureColumnExists($connection, 'shipments', 'sender_signature', 'LONGTEXT NULL');
    ensureColumnExists($connection, 'shipments', 'receiver_signature', 'LONGTEXT NULL');
    ensureColumnExists($connection, 'shipments', 'share_token', 'VARCHAR(64) NULL');
    ensureColumnExists($connection, 'shipments', 'project_name', 'VARCHAR(100) NULL');
    ensureColumnExists($connection, 'shipment_items', 'category', "VARCHAR(50) NOT NULL DEFAULT 'consumables'");
    ensureColumnExists($connection, 'shipment_items', 'category_alt', 'VARCHAR(100) NULL');
    ensureColumnExists($connection, 'shipment_items', 'note', 'VARCHAR(255) NULL');
    $connection->query("UPDATE shipments SET share_token = SHA2(CONCAT(id, UUID(), RAND()), 256) WHERE share_token IS NULL OR share_token = ''");

    $connection->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    ensureColumnExists($connection, 'users', 'role', "ENUM('admin', 'user') NOT NULL DEFAULT 'user'");

    $seedUsers = [
        ['admin', 'admin123', 'Administrator', 'admin'],
        ['user', 'user123', 'Default User', 'user'],
    ];

    foreach ($seedUsers as [$username, $plainPassword, $fullName, $role]) {
        $result = $connection->query("SELECT id FROM users WHERE username = '{$username}' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $connection->query("UPDATE users SET role = '{$role}', full_name = '{$fullName}' WHERE username = '{$username}'");
            continue;
        }

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $connection->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $username, $hash, $fullName, $role);
        $stmt->execute();
        $stmt->close();
    }
}

function getDbConnection(): mysqli
{
    $host = '127.0.0.1';
    $user = 'u170828859_putra_';
    $password = 'Programer260705';
    $database = 'u170828859_sys_tracking';

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
