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
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tracking Barang</title>
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
          <a class="nav-item active" href="tracking.php">
            <span>📍</span>
            Tracking
          </a>
          <a class="nav-item" href="#">
            <span>🧾</span>
            Packing
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
            <p class="eyebrow">Shipment</p>
            <h2>Tracking Barang</h2>
          </div>
          <a class="primary-btn btn-link" href="create-shipping.php">+ Input Pengiriman</a>
        </header>

        <section class="tracking-overview">
          <div class="tracking-stat">
            <span>Total Pengiriman</span>
            <strong><?= count($shipments); ?></strong>
          </div>
          <div class="tracking-stat warning">
            <span>Sedang Dikirim</span>
            <strong><?= count(array_filter($shipments, fn($shipment) => ($shipment['status'] ?? 'sent') === 'sent')); ?></strong>
          </div>
          <div class="tracking-stat success">
            <span>Terkirim</span>
            <strong><?= count(array_filter($shipments, fn($shipment) => ($shipment['status'] ?? 'sent') === 'delivered')); ?></strong>
          </div>
        </section>

        <section class="tracking-table-wrap">
          <table class="tracking-table">
            <thead>
              <tr>
                <th>Kode Reservasi</th>
                <th>Tanggal</th>
                <th>Pengirim</th>
                <th>Penerima</th>
                <th>Barang</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($shipments)): ?>
                <tr>
                  <td colspan="6" class="empty-state">Belum ada data pengiriman.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($shipments as $shipment): ?>
                  <tr>
                    <td><?= htmlspecialchars($shipment['reservation_code'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($shipment['shipping_date'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars(($shipment['sender_name'] ?? '-') . ' / ' . ($shipment['sender_location'] ?? '-')); ?></td>
                    <td><?= htmlspecialchars(($shipment['receiver_name'] ?? '-') . ' / ' . ($shipment['receiver_location'] ?? '-')); ?></td>
                    <td>
                      <?php
                        $goods = $shipment['goods'] ?? [];
                        if (!empty($goods)) {
                            $labels = [];
                            foreach ($goods as $item) {
                                $labels[] = ($item['name'] ?? 'Barang') . ' (' . ($item['qty'] ?? 0) . ' ' . ($item['unit'] ?? '') . ')';
                            }
                            echo htmlspecialchars(implode(', ', $labels));
                        } else {
                            echo '-';
                        }
                      ?>
                    </td>
                    <td>
                      <span class="status-pill <?= htmlspecialchars($shipment['status'] ?? 'sent'); ?>">
                        <?= htmlspecialchars(ucfirst($shipment['status'] ?? 'sent')); ?>
                      </span>
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
