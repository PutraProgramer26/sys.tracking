<?php
require __DIR__ . '/auth.php';
requireLogin();
requireRole('admin');
require __DIR__ . '/db.php';

$editingId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editingShipment = null;
$editingItems = [];
if ($editingId > 0) {
  $connection = getDbConnection();
  $shipmentStatement = $connection->prepare('SELECT * FROM shipments WHERE id = ? LIMIT 1');
  $shipmentStatement->bind_param('i', $editingId);
  $shipmentStatement->execute();
  $editingShipment = $shipmentStatement->get_result()->fetch_assoc();
  $shipmentStatement->close();

  if (!$editingShipment) {
    $connection->close();
    header('Location: material.php');
    exit;
  }

  $itemsStatement = $connection->prepare('SELECT name, qty, unit, category, category_alt, note FROM shipment_items WHERE shipment_id = ? ORDER BY id ASC');
  $itemsStatement->bind_param('i', $editingId);
  $itemsStatement->execute();
  $editingItems = $itemsStatement->get_result()->fetch_all(MYSQLI_ASSOC);
  $itemsStatement->close();
  $connection->close();
}
$formShipment = $editingShipment ?: [];
$formItems = !empty($editingItems) ? $editingItems : [null];
$pageTitle = $editingShipment ? 'Edit Shipping' : 'Create Shipping';

$senderFields = [
    ['label' => 'Nama', 'name' => 'sender_name', 'placeholder' => 'Masukkan nama pengirim'],
    ['label' => 'UID', 'name' => 'sender_uid', 'placeholder' => 'Masukkan UID pengirim'],
    ['label' => 'Position', 'name' => 'sender_position', 'placeholder' => 'Masukkan posisi'],
    ['label' => 'Lokasi', 'name' => 'sender_location', 'placeholder' => 'Masukkan lokasi pengirim']
];

?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Shipping</title>
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
          <a class="nav-item active" href="create-shipping.php">
            <span>🚚</span>
            <span class="nav-label">Create Shipping</span>
          </a>
          <a class="nav-item" href="tracking.php">
            <span>📍</span>
            <span class="nav-label">Tracking</span>
          </a>
          <a class="nav-item" href="shipping-monitoring.php">
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
            <p class="eyebrow">Shipment</p>
            <h2><?= $pageTitle; ?></h2>
          </div>
          <button class="primary-btn" type="submit" form="shipping-form">Save Shipping</button>
        </header>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'signature_required'): ?>
          <div class="form-message error">Pengirim wajib melakukan E-Sign sebelum menyimpan data pengiriman.</div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'missing_required_fields'): ?>
          <div class="form-message error">Silakan lengkapi data pengiriman dan minimal satu item barang sebelum menyimpan.</div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'invalid_document_no'): ?>
          <div class="form-message error">Document No harus mengikuti format 040/SRM/JP/VI/2026/001 dan sesuai dengan Project serta tanggal pengiriman.</div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'save_failed'): ?>
          <div class="form-message error">Data gagal disimpan. Periksa koneksi database atau lengkapi semua field yang diperlukan.</div>
        <?php endif; ?>

        <form id="shipping-form" class="shipping-form" method="post" action="save-shipping.php">
          <?php if ($editingShipment): ?>
            <input type="hidden" name="update_id" value="<?= $editingId; ?>" />
          <?php endif; ?>
          <section class="form-panel">
            <div class="section-header">
              <h3>Informasi Pengiriman</h3>
            </div>
            <div class="form-grid">
              <div class="field-group">
                <label for="shipping_date">Tanggal Pengiriman</label>
                <input id="shipping_date" name="shipping_date" type="date" value="<?= htmlspecialchars($formShipment['shipping_date'] ?? ''); ?>" required />
              </div>
              <div class="field-group">
                <label for="reservation_code">Document No</label>
                <input id="reservation_code" name="reservation_code" type="text" value="<?= htmlspecialchars($formShipment['reservation_code'] ?? ''); ?>" placeholder="040/SRM/JP/VI/2026/001" pattern="[0-9]{3}/[A-Za-z0-9-]+/JP/[IVXLCDM]+/[0-9]{4}/[0-9]{3}" title="Format: 040/SRM/JP/VI/2026/001" readonly required />
              </div>
              <div class="field-group">
                <label for="project_name">Project</label>
                <input id="project_name" name="project_name" type="text" value="<?= htmlspecialchars($formShipment['project_name'] ?? ''); ?>" placeholder="Contoh: SRM" maxlength="100" required />
              </div>
              <div class="field-group full-width">
                <label for="status">Status Pengiriman</label>
                <select id="status" name="status">
                  <option value="packing" <?= (($formShipment['status'] ?? 'packing') === 'packing') ? 'selected' : ''; ?>>Packing</option>
                  <option value="transit" <?= (($formShipment['status'] ?? '') === 'transit') ? 'selected' : ''; ?>>Transit</option>
                  <option value="out_of_delivery" <?= (($formShipment['status'] ?? '') === 'out_of_delivery') ? 'selected' : ''; ?>>Out of Delivery</option>
                  <option value="delivered" <?= (($formShipment['status'] ?? '') === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                </select>
              </div>
            </div>
          </section>

          <section class="form-panel">
            <div class="section-header">
              <h3>Informasi Pengirim</h3>
            </div>
            <div class="form-grid">
              <?php foreach ($senderFields as $field): ?>
                <div class="field-group">
                  <label for="<?= $field['name']; ?>"><?= $field['label']; ?></label>
                  <input id="<?= $field['name']; ?>" name="<?= $field['name']; ?>" type="text" value="<?= htmlspecialchars($formShipment[$field['name']] ?? ''); ?>" placeholder="<?= $field['placeholder']; ?>" />
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="form-panel">
            <div class="section-header goods-header">
              <h3>Input Barang</h3>
              <button type="button" class="secondary-btn" id="add-item-btn">+ Tambah Barang</button>
            </div>

            <div id="goods-container" class="goods-container">
              <?php foreach ($formItems as $formItem): ?>
              <div class="goods-row">
                <div class="field-group goods-name">
                  <label>Nama Barang</label>
                  <input type="text" name="goods_name[]" value="<?= htmlspecialchars($formItem['name'] ?? ''); ?>" placeholder="Contoh: Kabel UTP" />
                </div>
                <div class="field-group goods-qty">
                  <label>Qty Barang</label>
                  <input type="number" name="goods_qty[]" min="1" value="<?= htmlspecialchars((string)($formItem['qty'] ?? '')); ?>" placeholder="1" />
                </div>
                <div class="field-group goods-unit">
                  <label>Satuan</label>
                  <input type="text" name="goods_unit[]" value="<?= htmlspecialchars($formItem['unit'] ?? ''); ?>" placeholder="pcs, box, meter" />
                </div>
                <div class="field-group goods-category">
                  <label>Item Type</label>
                  <select name="goods_category[]">
                    <option value="consumables" <?= (($formItem['category'] ?? 'consumables') === 'consumables') ? 'selected' : ''; ?>>Consumables</option>
                    <option value="tools" <?= (($formItem['category'] ?? '') === 'tools') ? 'selected' : ''; ?>>Tools</option>
                  </select>
                </div>
                <div class="field-group goods-category-alt">
                  <label>Kategori</label>
                  <select name="goods_category_alt[]">
                    <option value="">Pilih kategori</option>
                    <option value="Maintenance" <?= (($formItem['category_alt'] ?? '') === 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="General" <?= (($formItem['category_alt'] ?? '') === 'General') ? 'selected' : ''; ?>>General</option>
                    <option value="Safety" <?= (($formItem['category_alt'] ?? '') === 'Safety') ? 'selected' : ''; ?>>Safety</option>
                    <option value="Electrical" <?= (($formItem['category_alt'] ?? '') === 'Electrical') ? 'selected' : ''; ?>>Electrical</option>
                  </select>
                </div>
                <div class="field-group goods-note">
                  <label>Keterangan</label>
                  <input type="text" name="goods_note[]" value="<?= htmlspecialchars($formItem['note'] ?? ''); ?>" placeholder="Contoh: prioritas tinggi, alat ukuran, dll" />
                </div>
                <button type="button" class="remove-btn" aria-label="Hapus barang">Hapus</button>
              </div>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="form-panel">
            <div class="section-header">
              <h3>E-Sign First Party</h3>
            </div>
            <div class="esign-panel">
              <p class="esign-note">Pengirim wajib menandatangani area berikut sebelum menekan tombol Save Shipping.</p>
              <canvas id="signatureCanvas" width="720" height="220" aria-label="Signature canvas"></canvas>
              <div class="esign-actions">
                <button type="button" class="ghost-btn" id="clear-signature">Clear</button>
              </div>
              <input type="hidden" name="sender_signature" id="sender_signature" value="<?= htmlspecialchars($formShipment['sender_signature'] ?? ''); ?>" />
            </div>
          </section>
        </form>
      </main>
    </div>

    <script src="sidebar.js"></script>
    <script>
      const goodsContainer = document.getElementById('goods-container');
      const addItemBtn = document.getElementById('add-item-btn');
      const projectInput = document.getElementById('project_name');
      const shippingDateInput = document.getElementById('shipping_date');
      const documentNoInput = document.getElementById('reservation_code');

      const romanMonths = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
      let documentNumberRequest = 0;

      async function updateDocumentNo() {
        const project = projectInput.value.trim().toUpperCase().replace(/[^A-Z0-9-]/g, '');
        const dateValue = shippingDateInput.value;
        if (!project || !dateValue) {
          documentNoInput.value = '';
          return;
        }

        const date = new Date(`${dateValue}T00:00:00`);
        const requestId = ++documentNumberRequest;
        documentNoInput.value = 'Mengambil nomor...';

        try {
          const response = await fetch(`next-document-number.php?project=${encodeURIComponent(project)}&date=${encodeURIComponent(dateValue)}`);
          const sequence = await response.json();
          if (!response.ok || requestId !== documentNumberRequest) {
            throw new Error('Nomor dokumen tidak tersedia');
          }

          documentNoInput.value = `${sequence.project_sequence}/${project}/JP/${romanMonths[date.getMonth() + 1]}/${date.getFullYear()}/${sequence.daily_sequence}`;
        } catch (error) {
          documentNoInput.value = '';
        }
      }

      projectInput.addEventListener('input', updateDocumentNo);
      shippingDateInput.addEventListener('change', updateDocumentNo);

      function createGoodsRow() {
        const row = document.createElement('div');
        row.className = 'goods-row';
        row.innerHTML = `
          <div class="field-group goods-name">
            <label>Nama Barang</label>
            <input type="text" name="goods_name[]" placeholder="Contoh: Kabel UTP" />
          </div>
          <div class="field-group goods-qty">
            <label>Qty Barang</label>
            <input type="number" name="goods_qty[]" min="1" placeholder="1" />
          </div>
          <div class="field-group goods-unit">
            <label>Satuan</label>
            <input type="text" name="goods_unit[]" placeholder="pcs, box, meter" />
          </div>
          <div class="field-group goods-category">
            <label>Item Type</label>
            <select name="goods_category[]">
              <option value="consumables">Consumables</option>
              <option value="tools">Tools</option>
            </select>
          </div>
          <div class="field-group goods-category-alt">
            <label>Kategori</label>
            <select name="goods_category_alt[]">
              <option value="">Pilih kategori</option>
              <option value="Maintenance">Maintenance</option>
              <option value="General">General</option>
              <option value="Safety">Safety</option>
              <option value="Electrical">Electrical</option>
            </select>
          </div>
          <div class="field-group goods-note">
            <label>Keterangan</label>
            <input type="text" name="goods_note[]" placeholder="Contoh: prioritas tinggi, alat ukuran, dll" />
          </div>
          <button type="button" class="remove-btn" aria-label="Hapus barang">Hapus</button>
        `;

        row.querySelector('.remove-btn').addEventListener('click', () => {
          const rows = goodsContainer.querySelectorAll('.goods-row');
          if (rows.length > 1) {
            row.remove();
          }
        });

        return row;
      }

      addItemBtn.addEventListener('click', () => {
        goodsContainer.appendChild(createGoodsRow());
      });

      goodsContainer.querySelectorAll('.remove-btn').forEach((button) => {
        button.addEventListener('click', () => {
          const rows = goodsContainer.querySelectorAll('.goods-row');
          if (rows.length > 1) {
            button.closest('.goods-row').remove();
          }
        });
      });

      const signatureCanvas = document.getElementById('signatureCanvas');
      const signatureInput = document.getElementById('sender_signature');
      const clearSignatureBtn = document.getElementById('clear-signature');
      const shippingForm = document.getElementById('shipping-form');
      const ctx = signatureCanvas.getContext('2d');

      ctx.lineWidth = 2.2;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.strokeStyle = '#0f172a';

      let isDrawing = false;

      function getCanvasPoint(event) {
        const rect = signatureCanvas.getBoundingClientRect();
        const scaleX = signatureCanvas.width / rect.width;
        const scaleY = signatureCanvas.height / rect.height;
        const clientX = event.clientX || (event.touches && event.touches[0]?.clientX) || 0;
        const clientY = event.clientY || (event.touches && event.touches[0]?.clientY) || 0;

        return {
          x: (clientX - rect.left) * scaleX,
          y: (clientY - rect.top) * scaleY
        };
      }

      function startDraw(event) {
        isDrawing = true;
        const point = getCanvasPoint(event);
        ctx.beginPath();
        ctx.moveTo(point.x, point.y);
        ctx.lineTo(point.x, point.y);
        ctx.stroke();
      }

      function draw(event) {
        if (!isDrawing) {
          return;
        }

        const point = getCanvasPoint(event);
        ctx.lineTo(point.x, point.y);
        ctx.stroke();
      }

      function stopDraw() {
        isDrawing = false;
        ctx.beginPath();
        signatureInput.value = signatureCanvas.toDataURL('image/png');
      }

      signatureCanvas.addEventListener('pointerdown', startDraw);
      signatureCanvas.addEventListener('pointermove', draw);
      signatureCanvas.addEventListener('pointerup', stopDraw);
      signatureCanvas.addEventListener('pointerleave', stopDraw);
      signatureCanvas.addEventListener('pointercancel', stopDraw);

      signatureCanvas.addEventListener('touchstart', (event) => {
        event.preventDefault();
        startDraw(event.touches[0]);
      }, { passive: false });

      signatureCanvas.addEventListener('touchmove', (event) => {
        event.preventDefault();
        draw(event.touches[0]);
      }, { passive: false });

      signatureCanvas.addEventListener('touchend', stopDraw, { passive: true });

      clearSignatureBtn.addEventListener('click', () => {
        ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        signatureInput.value = '';
      });

      shippingForm.addEventListener('submit', function (event) {
        if (!signatureInput.value || signatureInput.value === 'data:,') {
          event.preventDefault();
          window.alert('Pengirim wajib melakukan E-Sign terlebih dahulu sebelum Save Shipping.');
          return;
        }
      });
    </script>
  </body>
</html>
