<?php
require __DIR__ . '/auth.php';
requireLogin();
requireRole('admin');
require __DIR__ . '/db.php';

$statusOptions = [
    'packing' => 'Packing',
    'transit' => 'Transit',
    'out_of_delivery' => 'Out of Delivery',
    'delivered' => 'Delivered'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $shipmentId = (int)($_POST['shipment_id'] ?? 0);

  if ($shipmentId > 0) {
    $connection = getDbConnection();
    $statement = $connection->prepare('DELETE FROM shipments WHERE id = ?');
    $statement->bind_param('i', $shipmentId);
    $statement->execute();
    $statement->close();
    $connection->close();
  }

  header('Location: shipping-monitoring.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $shipmentId = (int) ($_POST['shipment_id'] ?? -1);
    $newStatus = $_POST['status'] ?? 'packing';

    if ($shipmentId > 0) {
      if ($newStatus === 'delivered') {
        header('Location: shipping-monitoring.php?error=complete_recipient_data&shipment_id=' . $shipmentId);
        exit;
      }

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
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

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
          <a class="nav-item" href="material.php">
            <span>📦</span>
            <span class="nav-label">Material</span>
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
          <a class="nav-item" href="user-management.php">
            <span>⚙️</span>
            <span class="nav-label">Setting</span>
          </a>
        </nav>

        <div class="sidebar-footer">
          <a class="sidebar-logout" href="logout.php">
            <span>🚪</span>
            <span class="nav-label">Logout</span>
          </a>
        </div>
      </aside>

      <main class="main-panel">
        <header class="topbar shipping-topbar">
          <div>
            <p class="eyebrow">Monitoring</p>
            <h2>Shipping Monitoring</h2>
          </div>
        </header>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'complete_recipient_data'): ?>
          <div class="form-message error">Lengkapi data Second Party dan E-Sign melalui halaman pengisian yang tersedia.</div>
        <?php endif; ?>

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
                      <?php $shipmentId = (int)($shipment['id'] ?? $index); ?>
                      <?php $recipientUrl = !empty($shipment['share_token']) ? 'recipient-share.php?token=' . urlencode($shipment['share_token']) : ''; ?>
                      <form method="post" class="status-form delivery-form">
                        <input type="hidden" name="shipment_id" value="<?= htmlspecialchars((string)$shipmentId); ?>" />
                        <input type="hidden" name="update_status" value="1" />
                        <div class="status-controls">
                          <select name="status" class="status-select">
                            <?php foreach ($statusOptions as $value => $label): ?>
                              <option value="<?= $value; ?>" <?= (($shipment['status'] ?? 'packing') === $value) ? 'selected' : ''; ?>><?= $label; ?></option>
                            <?php endforeach; ?>
                          </select>
                          <button type="submit" class="secondary-btn small-btn status-update-btn">Update</button>
                          <a class="inline-link view-doc-btn" href="reservation.php?id=<?= (int)($shipment['id'] ?? 0); ?>">View Surat</a>
                          <?php if (!empty($shipment['share_token'])): ?>
                            <?php $shareUrl = $baseUrl . '/recipient-share.php?token=' . urlencode($shipment['share_token']); ?>
                            <a class="inline-link recipient-data-link" href="<?= htmlspecialchars($recipientUrl); ?>" hidden>Isi Second Party &amp; E-Sign</a>
                            <button type="button" class="inline-link view-doc-btn share-link-btn" data-share-url="<?= htmlspecialchars($shareUrl); ?>">Share link</button>
                            <div class="share-link-panel" hidden>
                              <span class="share-link-label">Link recipient</span>
                              <div class="share-link-row">
                                <input type="text" class="share-link-input" value="<?= htmlspecialchars($shareUrl); ?>" readonly />
                                <button type="button" class="secondary-btn small-btn copy-share-btn">Salin</button>
                              </div>
                              <a class="share-link-open" href="<?= htmlspecialchars($shareUrl); ?>" target="_blank" rel="noopener">Buka halaman penerima</a>
                            </div>
                          <?php endif; ?>
                        </div>
                      </form>
                      <form method="post" class="monitoring-delete-form" onsubmit="return confirm('Hapus shipment ini beserta seluruh detail barangnya?');">
                        <input type="hidden" name="action" value="delete" />
                        <input type="hidden" name="shipment_id" value="<?= htmlspecialchars((string)$shipmentId); ?>" />
                        <button type="submit" class="delete-shipment-btn">Delete</button>
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
    <script>
      document.querySelectorAll('.delivery-form').forEach((form) => {
        const statusSelect = form.querySelector('select[name="status"]');
        const updateButton = form.querySelector('.status-update-btn');
        const recipientLink = form.querySelector('.recipient-data-link');

        const updateDeliveredAction = () => {
          const isDelivered = statusSelect.value === 'delivered';
          updateButton.hidden = isDelivered;
          if (recipientLink) {
            recipientLink.hidden = !isDelivered;
          }
        };

        statusSelect.addEventListener('change', updateDeliveredAction);
        updateDeliveredAction();
      });
    </script>
    <script>
      document.querySelectorAll('.share-link-btn').forEach((button) => {
        const panel = button.parentElement.querySelector('.share-link-panel');
        const input = panel.querySelector('.share-link-input');
        const copyButton = panel.querySelector('.copy-share-btn');

        button.addEventListener('click', () => {
          const isOpen = !panel.hidden;
          document.querySelectorAll('.share-link-panel').forEach((item) => { item.hidden = true; });
          panel.hidden = isOpen;
          if (!isOpen) {
            input.focus();
            input.select();
          }
        });

        copyButton.addEventListener('click', async () => {
          try {
            await navigator.clipboard.writeText(input.value);
            copyButton.textContent = 'Tersalin';
            window.setTimeout(() => { copyButton.textContent = 'Salin'; }, 1600);
          } catch (error) {
            input.focus();
            input.select();
            document.execCommand('copy');
          }
        });
      });
    </script>
  </body>
</html>
