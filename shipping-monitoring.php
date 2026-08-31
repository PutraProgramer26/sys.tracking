<?php
$storageFile = __DIR__ . '/data/shipments.json';
$shipments = [];

if (file_exists($storageFile)) {
    $content = file_get_contents($storageFile);
    $shipments = $content !== '' ? json_decode($content, true) : [];
}

if (!is_array($shipments)) {
    $shipments = [];
}

$statusOptions = [
    'packing' => 'Packing',
    'transit' => 'Transit',
    'out_of_delivery' => 'Out of Delivery',
    'delivered' => 'Delivered'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $index = (int) ($_POST['shipment_index'] ?? -1);
    $newStatus = $_POST['status'] ?? 'packing';

    if ($index >= 0 && isset($shipments[$index])) {
        $shipments[$index]['status'] = $newStatus;
        file_put_contents($storageFile, json_encode($shipments, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    header('Location: shipping-monitoring.php');
    exit;
}
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
        </div>

        <nav class="nav-menu">
          <a class="nav-item" href="index.php">
            <span>📊</span>
            Dashboard
          </a>
          <a class="nav-item" href="create-shipping.php">
            <span>🚚</span>
            Create Shipping
          </a>
          <a class="nav-item" href="tracking.php">
            <span>📍</span>
            Tracking
          </a>
          <a class="nav-item active" href="shipping-monitoring.php">
            <span>📦</span>
            Shipping Monitoring
          </a>
          <a class="nav-item" href="#">
            <span>⚙️</span>
            Setting
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
                        <input type="hidden" name="shipment_index" value="<?= htmlspecialchars((string)$index); ?>" />
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
  </body>
</html>
