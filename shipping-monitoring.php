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

        if ($newStatus === 'delivered') {
            $receiverName = trim((string)($_POST['receiver_name'] ?? ''));
            $receiverUid = trim((string)($_POST['receiver_uid'] ?? ''));
            $receiverPosition = trim((string)($_POST['receiver_position'] ?? ''));
            $receiverSignature = trim((string)($_POST['receiver_signature'] ?? ''));

            if ($receiverName === '' || $receiverUid === '' || $receiverPosition === '' || $receiverSignature === '') {
                $connection->close();
                header('Location: shipping-monitoring.php?error=delivered_requires_receiver_data');
                exit;
            }

            $statement = $connection->prepare('UPDATE shipments SET status = ?, receiver_name = ?, receiver_uid = ?, receiver_position = ?, receiver_signature = ? WHERE id = ?');
            $statement->bind_param('sssssi', $newStatus, $receiverName, $receiverUid, $receiverPosition, $receiverSignature, $shipmentId);
            $statement->execute();
            $statement->close();
        } else {
            $statement = $connection->prepare('UPDATE shipments SET status = ? WHERE id = ?');
            $statement->bind_param('si', $newStatus, $shipmentId);
            $statement->execute();
            $statement->close();
        }

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

        <?php if (isset($_GET['error']) && $_GET['error'] === 'delivered_requires_receiver_data'): ?>
          <div class="form-message error">Untuk mengubah status menjadi Delivered, penerima wajib mengisi nama, UID, posisi, dan E-Sign sebelum menyimpan.</div>
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
                      <form method="post" class="status-form delivery-form" data-shipment-id="<?= htmlspecialchars((string)($shipment['id'] ?? $index)); ?>">
                        <input type="hidden" name="shipment_id" value="<?= htmlspecialchars((string)($shipment['id'] ?? $index)); ?>" />
                        <input type="hidden" name="update_status" value="1" />
                        <div class="delivery-fields">
                          <input type="text" name="receiver_name" class="status-input" value="<?= htmlspecialchars($shipment['receiver_name'] ?? ''); ?>" placeholder="Nama penerima" />
                          <input type="text" name="receiver_uid" class="status-input" value="<?= htmlspecialchars($shipment['receiver_uid'] ?? ''); ?>" placeholder="UID penerima" />
                          <input type="text" name="receiver_position" class="status-input" value="<?= htmlspecialchars($shipment['receiver_position'] ?? ''); ?>" placeholder="Posisi penerima" />
                          <div class="signature-stack small-signature delivery-signature-wrap">
                            <canvas class="delivery-signature" width="280" height="90" data-signature-name="receiver_signature_<?= htmlspecialchars((string)($shipment['id'] ?? $index)); ?>"></canvas>
                            <input type="hidden" name="receiver_signature" class="receiver-signature-input" value="<?= htmlspecialchars($shipment['receiver_signature'] ?? ''); ?>" />
                          </div>
                        </div>
                        <div class="status-controls">
                          <select name="status" class="status-select">
                            <?php foreach ($statusOptions as $value => $label): ?>
                              <option value="<?= $value; ?>" <?= (($shipment['status'] ?? 'packing') === $value) ? 'selected' : ''; ?>><?= $label; ?></option>
                            <?php endforeach; ?>
                          </select>
                          <button type="submit" class="secondary-btn small-btn">Update</button>
                          <a class="inline-link view-doc-btn" href="reservation.php?id=<?= (int)($shipment['id'] ?? 0); ?>">View Surat</a>
                        </div>
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
        const canvas = form.querySelector('.delivery-signature');
        const hiddenInput = form.querySelector('.receiver-signature-input');
        const deliveryFields = form.querySelector('.delivery-fields');
        const signatureWrap = form.querySelector('.delivery-signature-wrap');
        const statusSelect = form.querySelector('select[name="status"]');
        const receiverInputs = form.querySelectorAll('.delivery-fields input:not([type="hidden"])');
        const savedStatus = statusSelect.value;
        const ctx = canvas.getContext('2d');
        let drawing = false;
        let statusChanged = false;

        function updateSignatureVisibility() {
          const isRequired = statusSelect.value === 'delivered'
            && (savedStatus !== 'delivered' || statusChanged);
          deliveryFields.style.display = isRequired ? 'grid' : 'none';
          signatureWrap.style.display = isRequired ? 'block' : 'none';
          receiverInputs.forEach((input) => {
            input.required = isRequired;
          });
          canvas.setAttribute('aria-hidden', isRequired ? 'false' : 'true');
        }

        statusSelect.addEventListener('change', () => {
          statusChanged = true;
          updateSignatureVisibility();
        });
        updateSignatureVisibility();

        const initialSignature = hiddenInput.value || '';
        if (initialSignature) {
          const img = new Image();
          img.onload = function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          };
          img.src = initialSignature;
        }

        const getPoint = (event) => {
          const rect = canvas.getBoundingClientRect();
          const scaleX = canvas.width / rect.width;
          const scaleY = canvas.height / rect.height;
          const x = (event.clientX - rect.left) * scaleX;
          const y = (event.clientY - rect.top) * scaleY;
          return { x, y };
        };

        const startDraw = (event) => {
          if (statusSelect.value !== 'delivered') return;
          drawing = true;
          const point = getPoint(event);
          ctx.beginPath();
          ctx.moveTo(point.x, point.y);
          ctx.lineTo(point.x, point.y);
          ctx.stroke();
        };

        const moveDraw = (event) => {
          if (!drawing || statusSelect.value !== 'delivered') return;
          const point = getPoint(event);
          ctx.lineTo(point.x, point.y);
          ctx.stroke();
        };

        const stopDraw = () => {
          if (statusSelect.value !== 'delivered') return;
          drawing = false;
          ctx.beginPath();
          hiddenInput.value = canvas.toDataURL('image/png');
        };

        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#0f172a';

        canvas.addEventListener('pointerdown', startDraw);
        canvas.addEventListener('pointermove', moveDraw);
        canvas.addEventListener('pointerup', stopDraw);
        canvas.addEventListener('pointerleave', stopDraw);
        canvas.addEventListener('pointercancel', stopDraw);

        form.addEventListener('submit', (event) => {
          const statusValue = statusSelect.value;
          const nameValue = form.querySelector('input[name="receiver_name"]').value.trim();
          const uidValue = form.querySelector('input[name="receiver_uid"]').value.trim();
          const positionValue = form.querySelector('input[name="receiver_position"]').value.trim();
          const signatureValue = hiddenInput.value.trim();

          if (statusValue === 'delivered') {
            if (!nameValue || !uidValue || !positionValue || !signatureValue || signatureValue === 'data:,') {
              event.preventDefault();
              window.alert('Untuk status Delivered, penerima wajib mengisi nama, UID, posisi, dan E-Sign terlebih dahulu.');
            }
          }
        });
      });
    </script>
  </body>
</html>
