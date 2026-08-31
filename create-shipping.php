<?php
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
        </div>

        <nav class="nav-menu">
          <a class="nav-item" href="index.php">
            <span>📊</span>
            Dashboard
          </a>
          <a class="nav-item" href="#">
            <span>📦</span>
            Material
          </a>
          <a class="nav-item active" href="create-shipping.php">
            <span>🚚</span>
            Create Shipping
          </a>
          <a class="nav-item" href="tracking.php">
            <span>📍</span>
            Tracking
          </a>
          <a class="nav-item" href="shipping-monitoring.php">
            <span>📦</span>
            Shipping Monitoring
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
            <h2>Create Shipping</h2>
          </div>
          <button class="primary-btn" type="submit" form="shipping-form">Save Shipping</button>
        </header>

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
                <button type="button" class="remove-btn" aria-label="Hapus barang">Hapus</button>
              </div>
            </div>
          </section>
        </form>
      </main>
    </div>

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
    </script>
  </body>
</html>
