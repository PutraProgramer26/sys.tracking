<?php
require __DIR__ . '/auth.php';
requireLogin();
require __DIR__ . '/db.php';

$selectedLocation = trim((string)($_GET['location'] ?? $_POST['location'] ?? ''));
$sharedShipmentId = (int)($_GET['share_id'] ?? 0);
$connection = getDbConnection();
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && isAdmin()) {
  $deleteId = (int)($_POST['shipment_id'] ?? 0);
  if ($deleteId > 0) {
    $deleteStatement = $connection->prepare('DELETE FROM shipments WHERE id = ?');
    $deleteStatement->bind_param('i', $deleteId);
    $deleteStatement->execute();
    $deleteStatement->close();
  }

  $redirectUrl = 'material.php';
  if ($selectedLocation !== '') {
    $redirectUrl .= '?location=' . rawurlencode($selectedLocation);
  }
  $connection->close();
  header('Location: ' . $redirectUrl);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'regenerate_share' && isAdmin()) {
  $shareId = (int)($_POST['shipment_id'] ?? 0);
  if ($shareId > 0) {
    $newToken = bin2hex(random_bytes(32));
    $shareStatement = $connection->prepare('UPDATE shipments SET share_token = ?, share_used_at = NULL WHERE id = ?');
    $shareStatement->bind_param('si', $newToken, $shareId);
    $shareStatement->execute();
    $shareStatement->close();
  }

  $redirectUrl = 'material.php';
  if ($selectedLocation !== '') {
    $redirectUrl .= '?location=' . rawurlencode($selectedLocation) . '&share_id=' . $shareId;
  } else {
    $redirectUrl .= '?share_id=' . $shareId;
  }
  $connection->close();
  header('Location: ' . $redirectUrl);
  exit;
}

$locations = [];
$locationResult = $connection->query("SELECT sender_location AS location FROM shipments WHERE status = 'delivered' AND sender_location IS NOT NULL AND sender_location <> '' UNION SELECT receiver_location AS location FROM shipments WHERE status = 'delivered' AND receiver_location IS NOT NULL AND receiver_location <> '' ORDER BY location ASC");
if ($locationResult) {
    while ($location = $locationResult->fetch_assoc()) {
        $locations[] = $location['location'];
    }
}

$shipments = [];
if ($selectedLocation !== '') {
    $shipmentStatement = $connection->prepare("SELECT * FROM shipments WHERE status = 'delivered' AND (sender_location = ? OR receiver_location = ?) ORDER BY shipping_date DESC, id DESC");
    $shipmentStatement->bind_param('ss', $selectedLocation, $selectedLocation);
} else {
    $shipmentStatement = $connection->prepare("SELECT * FROM shipments WHERE status = 'delivered' ORDER BY shipping_date DESC, id DESC");
}
$shipmentStatement->execute();
$shipmentResult = $shipmentStatement->get_result();

while ($shipment = $shipmentResult->fetch_assoc()) {
    $itemsStatement = $connection->prepare('SELECT name, qty, unit, category, category_alt, note FROM shipment_items WHERE shipment_id = ? ORDER BY id ASC');
    $shipmentId = (int)$shipment['id'];
    $itemsStatement->bind_param('i', $shipmentId);
    $itemsStatement->execute();
    $shipment['goods'] = $itemsStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemsStatement->close();
    $shipments[] = $shipment;
}
$shipmentStatement->close();
$connection->close();

$sentCount = 0;
$receivedCount = 0;
foreach ($shipments as $shipment) {
    if ($selectedLocation === '' || ($shipment['sender_location'] ?? '') === $selectedLocation) {
        $sentCount++;
    }
    if ($selectedLocation === '' || ($shipment['receiver_location'] ?? '') === $selectedLocation) {
        $receivedCount++;
    }
}

function materialStatusLabel(string $status): string
{
    return ucfirst(str_replace('_', ' ', $status));
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Material - History Pengiriman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body>
    <div class="dashboard-shell">
      <aside class="sidebar">
        <div class="brand">
          <div class="brand-mark material-brand-mark">M</div>
          <div><h1>Tracking Material</h1></div>
          <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Hide sidebar">⟨</button>
        </div>
        <nav class="nav-menu">
          <a class="nav-item" href="index.php"><span>📊</span><span class="nav-label">Dashboard</span></a>
          <a class="nav-item active" href="material.php"><span>📦</span><span class="nav-label">Material</span></a>
          <?php if (isAdmin()): ?><a class="nav-item" href="create-shipping.php"><span>🚚</span><span class="nav-label">Create Shipping</span></a><?php endif; ?>
          <a class="nav-item" href="tracking.php"><span>📍</span><span class="nav-label">Tracking</span></a>
          <a class="nav-item" href="shipping-monitoring.php"><span>📦</span><span class="nav-label">Shipping Monitoring</span></a>
          <?php if (isAdmin()): ?><a class="nav-item" href="user-management.php"><span>⚙️</span><span class="nav-label">Setting</span></a><?php endif; ?>
        </nav>
        <div class="sidebar-footer">
          <a class="sidebar-logout" href="logout.php"><span>🚪</span><span class="nav-label">Logout</span></a>
        </div>
      </aside>

      <main class="main-panel">
        <header class="topbar material-topbar">
          <div>
            <p class="eyebrow">Material</p>
            <h2>History Pengiriman Barang</h2>
            <p class="page-description">Lihat barang yang dikirim dan diterima berdasarkan lokasi.</p>
          </div>
        </header>

        <section class="material-toolbar">
          <form method="get" class="material-filter">
            <label for="location">Pilih lokasi</label>
            <select id="location" name="location" onchange="this.form.submit()">
              <option value="">Semua lokasi</option>
              <?php foreach ($locations as $location): ?>
                <option value="<?= htmlspecialchars($location); ?>" <?= $selectedLocation === $location ? 'selected' : ''; ?>><?= htmlspecialchars($location); ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <?php if ($selectedLocation !== ''): ?>
            <a class="inline-link" href="material.php">Reset lokasi</a>
          <?php endif; ?>
        </section>

        <section class="material-summary-grid">
          <article class="material-summary-card sent"><span>Dikirim dari lokasi</span><strong><?= $sentCount; ?></strong><small><?= htmlspecialchars($selectedLocation ?: 'Semua lokasi'); ?></small></article>
          <article class="material-summary-card received"><span>Diterima di lokasi</span><strong><?= $receivedCount; ?></strong><small><?= htmlspecialchars($selectedLocation ?: 'Semua lokasi'); ?></small></article>
          <article class="material-summary-card total"><span>Total history</span><strong><?= count($shipments); ?></strong><small>Shipment tercatat</small></article>
        </section>

        <section class="material-history">
          <div class="section-header">
            <div>
              <p class="card-kicker">Riwayat pergerakan</p>
              <h3><?= $selectedLocation !== '' ? 'History lokasi ' . htmlspecialchars($selectedLocation) : 'Semua history pengiriman'; ?></h3>
            </div>
          </div>
          <?php if (empty($shipments)): ?>
            <div class="empty-state material-empty">Belum ada history pengiriman untuk lokasi ini.</div>
          <?php else: ?>
            <div class="material-history-list">
              <?php foreach ($shipments as $shipment): ?>
                <?php
                  $senderLocation = (string)($shipment['sender_location'] ?? '-');
                  $receiverLocation = (string)($shipment['receiver_location'] ?? '-');
                  $isSent = $selectedLocation === '' || $senderLocation === $selectedLocation;
                  $isReceived = $selectedLocation === '' || $receiverLocation === $selectedLocation;
                ?>
                <article class="material-history-item">
                  <div class="material-history-head">
                    <div>
                      <span class="material-code"><?= htmlspecialchars($shipment['reservation_code'] ?? '-'); ?></span>
                      <span class="material-date"><?= htmlspecialchars($shipment['shipping_date'] ?? '-'); ?></span>
                    </div>
                    <span class="status-pill <?= htmlspecialchars($shipment['status'] ?? 'packing'); ?>"><?= htmlspecialchars(materialStatusLabel((string)($shipment['status'] ?? 'packing'))); ?></span>
                  </div>
                  <div class="material-route">
                    <div class="material-location <?= $isSent ? 'highlight' : ''; ?>"><small>DARI</small><strong><?= htmlspecialchars($senderLocation); ?></strong></div>
                    <span class="material-route-arrow">→</span>
                    <div class="material-location <?= $isReceived ? 'highlight' : ''; ?>"><small>KE</small><strong><?= htmlspecialchars($receiverLocation); ?></strong></div>
                  </div>
                  <div class="material-items">
                    <strong>Barang yang dikirim</strong>
                    <?php if (empty($shipment['goods'])): ?>
                      <span>-</span>
                    <?php else: ?>
                      <div class="material-item-list">
                        <?php foreach ($shipment['goods'] as $item): ?>
                          <span><?= htmlspecialchars($item['name'] ?? 'Barang'); ?> <b><?= htmlspecialchars((string)($item['qty'] ?? 0)); ?> <?= htmlspecialchars($item['unit'] ?? ''); ?></b></span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="material-actions">
                    <a class="inline-link material-document-link" href="reservation.php?id=<?= (int)$shipment['id']; ?>">Lihat dokumen</a>
                    <?php if (isAdmin()): ?>
                      <a class="material-edit-link" href="create-shipping.php?edit=<?= (int)$shipment['id']; ?>">Edit</a>
                      <form method="post" class="material-share-form" onsubmit="return confirm('Buat link baru untuk Second Party? Link lama akan dinonaktifkan.');">
                        <input type="hidden" name="action" value="regenerate_share" />
                        <input type="hidden" name="shipment_id" value="<?= (int)$shipment['id']; ?>" />
                        <?php if ($selectedLocation !== ''): ?>
                          <input type="hidden" name="location" value="<?= htmlspecialchars($selectedLocation); ?>" />
                        <?php endif; ?>
                        <button type="submit" class="material-edit-link">Share ulang</button>
                      </form>
                      <form method="post" class="material-delete-form" onsubmit="return confirm('Hapus history pengiriman ini?');">
                        <input type="hidden" name="action" value="delete" />
                        <input type="hidden" name="shipment_id" value="<?= (int)$shipment['id']; ?>" />
                        <?php if ($selectedLocation !== ''): ?>
                          <input type="hidden" name="location" value="<?= htmlspecialchars($selectedLocation); ?>" />
                        <?php endif; ?>
                        <button type="submit" class="material-delete-link">Delete</button>
                      </form>
                      <?php if ($sharedShipmentId === (int)$shipment['id'] && !empty($shipment['share_token'])): ?>
                        <?php $shareUrl = $baseUrl . '/recipient-share.php?token=' . urlencode($shipment['share_token']); ?>
                        <div class="material-share-link" style="flex-basis:100%; margin-top:8px;">
                          <label style="display:block; margin-bottom:4px; font-size:0.75rem; color:#64748b;">Link Second Party (sekali pakai)</label>
                          <input type="text" value="<?= htmlspecialchars($shareUrl); ?>" readonly style="width:min(100%, 520px); padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px;" />
                        </div>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      </main>
    </div>
    <script src="sidebar.js"></script>
  </body>
</html>
