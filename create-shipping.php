<?php
require __DIR__ . '/auth.php';
requireLogin();
requireRole('admin');

$senderFields = [
    ['label' => 'Nama', 'name' => 'sender_name', 'placeholder' => 'Masukkan nama pengirim'],
    ['label' => 'UID', 'name' => 'sender_uid', 'placeholder' => 'Masukkan UID pengirim'],
    ['label' => 'Position', 'name' => 'sender_position', 'placeholder' => 'Masukkan posisi'],
    ['label' => 'Lokasi', 'name' => 'sender_location', 'placeholder' => 'Masukkan lokasi pengirim']
];

$receiverFields = [
    ['label' => 'Nama', 'name' => 'receiver_name', 'placeholder' => 'Masukkan nama penerima'],
    ['label' => 'UID', 'name' => 'receiver_uid', 'placeholder' => 'Masukkan UID penerima'],
    ['label' => 'Position', 'name' => 'receiver_position', 'placeholder' => 'Masukkan posisi'],
    ['label' => 'Lokasi', 'name' => 'receiver_location', 'placeholder' => 'Masukkan lokasi penerima']
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
            <h2>Create Shipping</h2>
          </div>
          <button class="primary-btn" type="submit" form="shipping-form">Save Shipping</button>
        </header>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'signature_required'): ?>
          <div class="form-message error">Pengirim wajib melakukan E-Sign sebelum menyimpan data pengiriman.</div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'missing_required_fields'): ?>
          <div class="form-message error">Silakan lengkapi data pengiriman dan minimal satu item barang sebelum menyimpan.</div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'save_failed'): ?>
          <div class="form-message error">Data gagal disimpan. Periksa koneksi database atau lengkapi semua field yang diperlukan.</div>
        <?php endif; ?>

        <form id="shipping-form" class="shipping-form" method="post" action="save-shipping.php">
          <section class="form-panel">
            <div class="section-header">
              <h3>Informasi Pengiriman</h3>
            </div>
            <div class="form-grid">
              <div class="field-group">
                <label for="shipping_date">Tanggal Pengiriman</label>
                <input id="shipping_date" name="shipping_date" type="date" />
              </div>
              <div class="field-group">
                <label for="reservation_code">Kode Reservasi Pengiriman</label>
                <input id="reservation_code" name="reservation_code" type="text" placeholder="Contoh: RES-2026-001" />
              </div>
              <div class="field-group full-width">
                <label for="status">Status Pengiriman</label>
                <select id="status" name="status">
                  <option value="packing">Packing</option>
                  <option value="transit">Transit</option>
                  <option value="out_of_delivery">Out of Delivery</option>
                  <option value="delivered">Delivered</option>
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
                  <input id="<?= $field['name']; ?>" name="<?= $field['name']; ?>" type="text" placeholder="<?= $field['placeholder']; ?>" />
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="form-panel">
            <div class="section-header">
              <h3>Informasi Penerima</h3>
            </div>
            <div class="form-grid">
              <?php foreach ($receiverFields as $field): ?>
                <div class="field-group">
                  <label for="<?= $field['name']; ?>"><?= $field['label']; ?></label>
                  <input id="<?= $field['name']; ?>" name="<?= $field['name']; ?>" type="text" placeholder="<?= $field['placeholder']; ?>" />
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
              <div class="goods-row">
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
              </div>
            </div>
          </section>

          <section class="form-panel">
            <div class="section-header">
              <h3>E-Sign Pengirim</h3>
            </div>
            <div class="esign-panel">
              <p class="esign-note">Pengirim wajib menandatangani area berikut sebelum menekan tombol Save Shipping.</p>
              <canvas id="signatureCanvas" width="720" height="220" aria-label="Signature canvas"></canvas>
              <div class="esign-actions">
                <button type="button" class="ghost-btn" id="clear-signature">Clear</button>
              </div>
              <input type="hidden" name="sender_signature" id="sender_signature" />
            </div>
          </section>
        </form>
      </main>
    </div>

    <script src="sidebar.js"></script>
    <script>
      const goodsContainer = document.getElementById('goods-container');
      const addItemBtn = document.getElementById('add-item-btn');

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
