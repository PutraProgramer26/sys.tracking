<?php
require __DIR__ . '/auth.php';
requireLogin();
require __DIR__ . '/db.php';

$statusOptions = [
    'packing' => 'Packing',
    'transit' => 'Transit',
    'out_of_delivery' => 'Out of Delivery',
    'delivered' => 'Delivered'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $shipmentId = (int) ($_POST['shipment_id'] ?? -1);
    $newStatus = $_POST['status'] ?? 'packing';

    if ($shipmentId > 0) {
        $connection = getDbConnection();
        $statement = $connection->prepare('UPDATE shipments SET status = ? WHERE id = ?');
        $statement->bind_param('si', $newStatus, $shipmentId);
        $statement->execute();
        $statement->close();
        $connection->close();
    }

    header('Location: shipping-monitoring.php');
    exit;
}

$connection = getDbConnection();
$shipments = [];

$result = $connection->query("SELECT * FROM shipments ORDER BY id DESC");
if ($result) {
    while ($shipment = $result->fetch_assoc()) {
        $shipmentId = (int)$shipment['id'];
        $itemsStmt = $connection->prepare("SELECT name, qty FROM shipment_items WHERE shipment_id = ? ORDER BY id ASC");
        $itemsStmt->bind_param('i', $shipmentId);
        $itemsStmt->execute();
        $items = $itemsStmt->get_result();

        $shipment['goods'] = [];
        while ($item = $items->fetch_assoc()) {
            $shipment['goods'][] = $item;
        }

        $shipments[] = $shipment;
        $itemsStmt->close();
    }
}

$connection->close();
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shipping Monitoring</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body>
    <div class="dashboard-shell">
      <aside class="sidebar">
        <div class="brand">
          <div class="brand-mark">TM</div>
          <div>
            <h1>Tracking Material</h1>
          </div>
          <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Hide sidebar">⟨</button>
        </div>

        <nav class="nav-menu">
          <a class="nav-item" href="index.php">
            <span>📊</span>
            <span class="nav-label">Dashboard</span>
          </a>
          <a class="nav-item" href="create-shipping.php">
            <span>🚚</span>
            <span class="nav-label">Create Shipping</span>
          </a>
          <a class="nav-item" href="tracking.php">
            <span>📍</span>
            <span class="nav-label">Tracking</span>
          </a>
          <a class="nav-item active" href="shipping-monitoring.php">
            <span>📦</span>
            <span class="nav-label">Shipping Monitoring</span>
          </a>
          <a class="nav-item" href="#">
            <span>⚙️</span>
            <span class="nav-label">Setting</span>
          </a>
        </nav>
      </aside>

      <main class="main-panel">
        <header class="topbar shipping-topbar">
          <div>
            <p class="eyebrow">Monitoring</p>
            <h2>Shipping Monitoring</h2>
          </div>
        </header>

        <section class="tracking-table-wrap monitoring-table-wrap">
          <table class="tracking-table">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Pengirim</th>
                <th>Penerima</th>
                <th>Barang</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($shipments)): ?>
                <tr>
                  <td colspan="7" class="empty-state">Belum ada data shipping untuk dimonitoring.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($shipments as $index => $shipment): ?>
                  <tr>
                    <td><?= htmlspecialchars($shipment['reservation_code'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars(($shipment['sender_name'] ?? '-') . ' / ' . ($shipment['sender_location'] ?? '-')); ?></td>
                    <td><?= htmlspecialchars(($shipment['receiver_name'] ?? '-') . ' / ' . ($shipment['receiver_location'] ?? '-')); ?></td>
                    <td>
                      <?php
                        $goods = $shipment['goods'] ?? [];
                        if (!empty($goods)) {
                            $items = [];
                            foreach ($goods as $item) {
                                $items[] = ($item['name'] ?? 'Barang') . ' (' . ($item['qty'] ?? 0) . ')';
                            }
                            echo htmlspecialchars(implode(', ', $items));
                        } else {
                            echo '-';
                        }
                      ?>
                    </td>
                    <td><?= htmlspecialchars($shipment['shipping_date'] ?? '-'); ?></td>
                    <td>
                      <span class="status-pill <?= htmlspecialchars($shipment['status'] ?? 'packing'); ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $shipment['status'] ?? 'packing'))); ?>
                      </span>
                    </td>
                    <td>
                      <form method="post" class="status-form">
                        <input type="hidden" name="shipment_id" value="<?= htmlspecialchars((string)($shipment['id'] ?? $index)); ?>" />
                        <input type="hidden" name="update_status" value="1" />
                        <select name="status" class="status-select">
                          <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= $value; ?>" <?= (($shipment['status'] ?? 'packing') === $value) ? 'selected' : ''; ?>><?= $label; ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" class="secondary-btn small-btn">Update</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </section>
      </main>
    </div>
    <script src="sidebar.js"></script>
  </body>
</html>
