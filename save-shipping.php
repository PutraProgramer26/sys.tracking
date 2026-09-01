<?php
require __DIR__ . '/auth.php';
requireLogin();
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create-shipping.php');
    exit;
}

$goods = [];
if (isset($_POST['goods_name']) && is_array($_POST['goods_name'])) {
    $count = count($_POST['goods_name']);
    for ($i = 0; $i < $count; $i++) {
        $name = trim($_POST['goods_name'][$i] ?? '');
        $qty = trim($_POST['goods_qty'][$i] ?? '');
        $unit = trim($_POST['goods_unit'][$i] ?? '');

        if ($name === '' || $qty === '' || $unit === '') {
            continue;
        }

        $goods[] = [
            'name' => $name,
            'qty' => (int)$qty,
            'unit' => $unit
        ];
    }
}

$shippingDate = $_POST['shipping_date'] ?? null;
$reservationCode = trim((string)($_POST['reservation_code'] ?? ''));
$status = $_POST['status'] ?? 'packing';
$senderName = trim((string)($_POST['sender_name'] ?? ''));
$senderUid = trim((string)($_POST['sender_uid'] ?? ''));
$senderPosition = trim((string)($_POST['sender_position'] ?? ''));
$senderLocation = trim((string)($_POST['sender_location'] ?? ''));
$receiverName = trim((string)($_POST['receiver_name'] ?? ''));
$receiverUid = trim((string)($_POST['receiver_uid'] ?? ''));
$receiverPosition = trim((string)($_POST['receiver_position'] ?? ''));
$receiverLocation = trim((string)($_POST['receiver_location'] ?? ''));

$connection = getDbConnection();

$stmt = $connection->prepare("INSERT INTO shipments (shipping_date, reservation_code, status, sender_name, sender_uid, sender_position, sender_location, receiver_name, receiver_uid, receiver_position, receiver_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    'sssssssssss',
    $shippingDate,
    $reservationCode,
    $status,
    $senderName,
    $senderUid,
    $senderPosition,
    $senderLocation,
    $receiverName,
    $receiverUid,
    $receiverPosition,
    $receiverLocation
);
$stmt->execute();
$shipmentId = $stmt->insert_id;
$stmt->close();

if (!empty($goods)) {
    $itemsStmt = $connection->prepare("INSERT INTO shipment_items (shipment_id, name, qty, unit) VALUES (?, ?, ?, ?)");
    foreach ($goods as $item) {
        $name = $item['name'];
        $qty = (int)$item['qty'];
        $unit = $item['unit'];
        $itemsStmt->bind_param('isi', $shipmentId, $name, $qty, $unit);
        $itemsStmt->execute();
    }
    $itemsStmt->close();
}

$connection->close();

header('Location: tracking.php');
exit;
