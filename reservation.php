<?php
require __DIR__ . '/auth.php';
requireLogin();
requireRole('admin');
require __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: tracking.php');
    exit;
}

$connection = getDbConnection();
$stmt = $connection->prepare("SELECT * FROM shipments WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$shipment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$shipment) {
    $connection->close();
    header('Location: tracking.php');
    exit;
}

$itemsStmt = $connection->prepare("SELECT name, qty, unit, category, category_alt, note FROM shipment_items WHERE shipment_id = ? ORDER BY id ASC");
$itemsStmt->bind_param('i', $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$itemsStmt->close();
$connection->close();

$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$onlineUrl = rtrim($baseUrl, '/') . '/reservation.php?id=' . (int)$shipment['id'];
$documentUrl = rtrim($baseUrl, '/') . '/shipping-document.php?id=' . (int)$shipment['id'];
$signatureImage = $shipment['sender_signature'] ?? '';
$receiverSignatureImage = $shipment['receiver_signature'] ?? '';
$formattedDate = !empty($shipment['shipping_date']) ? date('d F Y', strtotime($shipment['shipping_date'])) : '-';
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Surat Reservasi - <?= htmlspecialchars($shipment['reservation_code'] ?? ''); ?></title>
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
          <a class="nav-item" href="index.php"><span>📊</span><span class="nav-label">Dashboard</span></a>
          <a class="nav-item" href="material.php"><span>📦</span><span class="nav-label">Material</span></a>
          <a class="nav-item" href="create-shipping.php"><span>🚚</span><span class="nav-label">Create Shipping</span></a>
          <a class="nav-item" href="tracking.php"><span>📍</span><span class="nav-label">Tracking</span></a>
          <a class="nav-item" href="shipping-monitoring.php"><span>📦</span><span class="nav-label">Shipping Monitoring</span></a>
          <a class="nav-item" href="user-management.php"><span>⚙️</span><span class="nav-label">Setting</span></a>
        </nav>

        <div class="sidebar-footer">
          <a class="sidebar-logout" href="logout.php"><span>🚪</span><span class="nav-label">Logout</span></a>
        </div>
      </aside>

      <main class="main-panel reservation-page">
        <div class="reservation-document">
          <header class="handover-header">
            <h1>GOODS HANDOVER CERTIFICATE</h1>
            <p>Document No: <?= htmlspecialchars($shipment['reservation_code'] ?? '-'); ?></p>
            <strong>FULLY SIGNED</strong>
          </header>

          <div class="handover-summary">
            <div class="handover-meta-grid">
              <div><label>DATE</label><strong><?= htmlspecialchars($formattedDate); ?></strong></div>
              <div><label>TRIP ID</label><strong><?= htmlspecialchars($shipment['reservation_code'] ?? '-'); ?></strong></div>
              <div><label>SUPPLIER</label><strong><?= htmlspecialchars($shipment['sender_name'] ?? '-'); ?></strong></div>
              <div><label>VEHICLE / DRIVER</label><strong><?= htmlspecialchars($shipment['sender_position'] ?? '-'); ?></strong></div>
              <div><label>FROM</label><strong><?= htmlspecialchars($shipment['sender_location'] ?? '-'); ?></strong></div>
              <div><label>TO</label><strong><?= htmlspecialchars($shipment['receiver_location'] ?? '-'); ?></strong></div>
            </div>
            <div class="handover-qr-wrap">
              <div class="handover-qr" data-qr-value="<?= htmlspecialchars($documentUrl); ?>" role="img" aria-label="QR code dokumen"></div>
              <span>SCAN TO VIEW ONLINE</span>
            </div>
          </div>

          <div class="handover-content">
            <p>On this day, <?= htmlspecialchars($formattedDate); ?>, we, the undersigned:</p>

            <div class="handover-parties">
              <section>
                <h2>1. FIRST PARTY</h2>
                <div class="handover-details">
                  <span>Name</span><strong><?= htmlspecialchars($shipment['sender_name'] ?? '-'); ?></strong>
                  <span>Position</span><strong><?= htmlspecialchars($shipment['sender_position'] ?? '-'); ?></strong>
                  <span>ID No</span><strong><?= htmlspecialchars($shipment['sender_uid'] ?? '-'); ?></strong>
                  <span>Project</span><strong><?= htmlspecialchars($shipment['sender_location'] ?? '-'); ?></strong>
                </div>
                <em>hereinafter referred to as the FIRST PARTY</em>
              </section>
              <section>
                <h2>2. SECOND PARTY</h2>
                <div class="handover-details">
                  <span>Name</span><strong><?= htmlspecialchars($shipment['receiver_name'] ?? '-'); ?></strong>
                  <span>Position</span><strong><?= htmlspecialchars($shipment['receiver_position'] ?? '-'); ?></strong>
                  <span>ID No</span><strong><?= htmlspecialchars($shipment['receiver_uid'] ?? '-'); ?></strong>
                  <span>Project</span><strong><?= htmlspecialchars($shipment['receiver_location'] ?? '-'); ?></strong>
                </div>
                <em>hereinafter referred to as the SECOND PARTY</em>
              </section>
            </div>

            <p>The FIRST PARTY hereby hands over to the SECOND PARTY, and the SECOND PARTY acknowledges receipt of, the following goods/equipment:</p>
            <div class="items-caption">ITEMS - <?= count($items); ?></div>
            <table class="handover-items-table">
              <thead><tr><th>NO</th><th>CODE</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT</th></tr></thead>
              <tbody>
                <?php if (empty($items)): ?>
                  <tr><td colspan="5">No items recorded.</td></tr>
                <?php else: ?>
                  <?php foreach ($items as $itemIndex => $item): ?>
                    <tr>
                      <td><?= $itemIndex + 1; ?></td>
                      <td><?= htmlspecialchars($item['category_alt'] ?? '-'); ?></td>
                      <td><?= htmlspecialchars($item['name'] ?? '-'); ?><?= !empty($item['note']) ? ', ' . htmlspecialchars($item['note']) : ''; ?></td>
                      <td><?= htmlspecialchars((string)($item['qty'] ?? 0)); ?></td>
                      <td><?= htmlspecialchars($item['unit'] ?? '-'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>

            <p class="handover-terms">The above goods/equipment were handed over in good and complete condition; upon signing of this certificate, full responsibility transfers to the SECOND PARTY.</p>
            <div class="signatures-caption">SIGNATURES</div>
          </div>

          <div class="reservation-signature-row">
            <div class="reservation-panel signature-panel handover-signature">
              <h3>FIRST PARTY</h3>
              <span class="signature-label">Released by</span>
              <?php if (!empty($signatureImage)): ?>
                <div class="sign-box large-sign-box">
                  <img src="<?= htmlspecialchars($signatureImage); ?>" alt="Signature pengirim" />
                </div>
              <?php else: ?>
                <p class="esign-note">Tidak ada tanda tangan.</p>
              <?php endif; ?>
              <div class="signature-meta">
                <strong><?= htmlspecialchars($shipment['sender_name'] ?? '-'); ?></strong>
                <span><?= htmlspecialchars($shipment['sender_position'] ?? '-'); ?></span>
                <span><?= htmlspecialchars($shipment['sender_location'] ?? '-'); ?></span>
                <small>Signed - <?= htmlspecialchars($formattedDate); ?></small>
              </div>
            </div>

            <div class="reservation-panel signature-panel handover-signature">
              <h3>SECOND PARTY</h3>
              <span class="signature-label">Received by</span>
              <?php if (!empty($receiverSignatureImage)): ?>
                <div class="sign-box large-sign-box">
                  <img src="<?= htmlspecialchars($receiverSignatureImage); ?>" alt="Signature penerima" />
                </div>
              <?php else: ?>
                <p class="esign-note">Belum ada tanda tangan penerima.</p>
              <?php endif; ?>
              <div class="signature-meta">
                <strong><?= htmlspecialchars($shipment['receiver_name'] ?? '-'); ?></strong>
                <span><?= htmlspecialchars($shipment['receiver_position'] ?? '-'); ?></span>
                <span><?= htmlspecialchars($shipment['receiver_location'] ?? '-'); ?></span>
                <small><?= !empty($receiverSignatureImage) ? 'Signed' : 'Pending signature'; ?> - <?= htmlspecialchars($formattedDate); ?></small>
              </div>
            </div>
          </div>

          <div class="reservation-actions">
            <a class="primary-btn btn-link" href="tracking.php">Kembali ke Tracking</a>
            <button class="secondary-btn" type="button" onclick="window.print()">Print / Cetak</button>
          </div>
        </div>
      </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
      document.querySelectorAll('.handover-qr').forEach((qr) => {
        new QRCode(qr, {
          text: qr.dataset.qrValue,
          width: 92,
          height: 92,
          colorDark: '#111827',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M
        });
      });
    </script>
    <script src="sidebar.js"></script>
  </body>
</html>
