<?php
$storageFile = __DIR__ . '/data/shipments.json';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create-shipping.php');
    exit;
}

$existing = [];
if (file_exists($storageFile)) {
    $content = file_get_contents($storageFile);
    $existing = $content !== '' ? json_decode($content, true) : [];
}

if (!is_array($existing)) {
    $existing = [];
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

$newShipment = [
    'shipping_date' => $_POST['shipping_date'] ?? '',
    'reservation_code' => $_POST['reservation_code'] ?? '',
    'status' => $_POST['status'] ?? 'packing',
    'sender_name' => $_POST['sender_name'] ?? '',
    'sender_uid' => $_POST['sender_uid'] ?? '',
    'sender_position' => $_POST['sender_position'] ?? '',
    'sender_location' => $_POST['sender_location'] ?? '',
    'receiver_name' => $_POST['receiver_name'] ?? '',
    'receiver_uid' => $_POST['receiver_uid'] ?? '',
    'receiver_position' => $_POST['receiver_position'] ?? '',
    'receiver_location' => $_POST['receiver_location'] ?? '',
    'goods' => $goods
];

$existing[] = $newShipment;
file_put_contents($storageFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

header('Location: tracking.php');
exit;
