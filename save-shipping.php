<?php
require __DIR__ . '/auth.php';
requireLogin();
requireRole('admin');
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
        $category = trim((string)($_POST['goods_category'][$i] ?? 'consumables'));
        $categoryAlt = trim((string)($_POST['goods_category_alt'][$i] ?? ''));
        $note = trim((string)($_POST['goods_note'][$i] ?? ''));

        if ($name === '' || $qty === '' || $unit === '') {
            continue;
        }

        $goods[] = [
            'name' => $name,
            'qty' => (int)$qty,
            'unit' => $unit,
            'category' => $category,
            'category_alt' => $categoryAlt,
            'note' => $note
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
$senderSignature = trim((string)($_POST['sender_signature'] ?? ''));
$updateId = (int)($_POST['update_id'] ?? 0);

if ($senderSignature === '') {
    header('Location: create-shipping.php?error=signature_required');
    exit;
}

if ($reservationCode === '' || $senderName === '' || $receiverName === '' || empty($goods)) {
    header('Location: create-shipping.php?error=missing_required_fields');
    exit;
}

try {
    $connection = getDbConnection();
    $connection->begin_transaction();

    if ($updateId > 0) {
        $stmt = $connection->prepare("UPDATE shipments SET shipping_date = ?, reservation_code = ?, status = ?, sender_name = ?, sender_uid = ?, sender_position = ?, sender_location = ?, receiver_name = ?, receiver_uid = ?, receiver_position = ?, receiver_location = ?, sender_signature = ? WHERE id = ?");
        $stmt->bind_param(
            'ssssssssssssi',
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
            $receiverLocation,
            $senderSignature,
            $updateId
        );
        $stmt->execute();
        $stmt->close();
        $shipmentId = $updateId;
        $connection->query('DELETE FROM shipment_items WHERE shipment_id = ' . $shipmentId);
    } else {
        $stmt = $connection->prepare("INSERT INTO shipments (shipping_date, reservation_code, status, sender_name, sender_uid, sender_position, sender_location, receiver_name, receiver_uid, receiver_position, receiver_location, sender_signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            'ssssssssssss',
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
            $receiverLocation,
            $senderSignature
        );
        $stmt->execute();
        $shipmentId = $stmt->insert_id;
        $stmt->close();
    }

    if (!empty($goods)) {
        $itemsStmt = $connection->prepare("INSERT INTO shipment_items (shipment_id, name, qty, unit, category, category_alt, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($goods as $item) {
            $name = $item['name'];
            $qty = (int)$item['qty'];
            $unit = $item['unit'];
            $category = $item['category'];
            $categoryAlt = $item['category_alt'];
            $note = $item['note'];
            $itemsStmt->bind_param('isissss', $shipmentId, $name, $qty, $unit, $category, $categoryAlt, $note);
            $itemsStmt->execute();
        }
        $itemsStmt->close();
    }

    $connection->commit();
    $connection->close();
    header('Location: ' . ($updateId > 0 ? 'material.php' : 'reservation.php?id=' . (int)$shipmentId));
    exit;
} catch (Throwable $exception) {
    if (isset($connection) && $connection instanceof mysqli && $connection->connect_errno === 0) {
        $connection->rollback();
        $connection->close();
    }

    header('Location: create-shipping.php?error=save_failed');
    exit;
}
