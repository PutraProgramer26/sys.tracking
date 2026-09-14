<?php
require __DIR__ . '/db.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$connection = getDbConnection();
$statement = $connection->prepare('SELECT * FROM shipments WHERE share_token = ? AND share_used_at IS NULL LIMIT 1');
$statement->bind_param('s', $token);
$statement->execute();
$shipment = $statement->get_result()->fetch_assoc();
$statement->close();

if (!$shipment) {
    $connection->close();
    http_response_code(404);
    exit('Link penerima tidak valid atau sudah tidak tersedia.');
}

  $itemsStatement = $connection->prepare('SELECT name, qty, unit, category, category_alt, note FROM shipment_items WHERE shipment_id = ? ORDER BY id ASC');
  $shipmentId = (int)$shipment['id'];
  $itemsStatement->bind_param('i', $shipmentId);
  $itemsStatement->execute();
  $items = $itemsStatement->get_result()->fetch_all(MYSQLI_ASSOC);
  $itemsStatement->close();
  $documentUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/shipping-document.php?id=' . $shipmentId;
  $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=90x90&format=png&data=' . rawurlencode($documentUrl);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiverName = trim((string)($_POST['receiver_name'] ?? ''));
    $receiverUid = trim((string)($_POST['receiver_uid'] ?? ''));
    $receiverPosition = trim((string)($_POST['receiver_position'] ?? ''));
    $receiverLocation = trim((string)($_POST['receiver_location'] ?? ''));
    $receiverSignature = trim((string)($_POST['receiver_signature'] ?? ''));

    if ($receiverName === '' || $receiverUid === '' || $receiverPosition === '' || $receiverLocation === '' || $receiverSignature === '' || $receiverSignature === 'data:,') {
        $error = 'Semua data penerima dan E-Sign wajib dilengkapi.';
    } else {
        $update = $connection->prepare('UPDATE shipments SET status = ?, receiver_name = ?, receiver_uid = ?, receiver_position = ?, receiver_location = ?, receiver_signature = ?, share_used_at = CURRENT_TIMESTAMP WHERE id = ? AND share_token = ? AND share_used_at IS NULL');
        $status = 'delivered';
        $update->bind_param('ssssssis', $status, $receiverName, $receiverUid, $receiverPosition, $receiverLocation, $receiverSignature, $shipmentId, $token);
        $update->execute();
        $wasUpdated = $update->affected_rows === 1;
        $update->close();
        $connection->close();
        if (!$wasUpdated) {
          http_response_code(410);
          exit('Link penerima sudah digunakan atau tidak tersedia lagi.');
        }
        header('Location: shipping-document.php?id=' . $shipmentId);
        exit;
    }
}

$connection->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Data Penerima - <?= htmlspecialchars($shipment['reservation_code'] ?? ''); ?></title>
  <link rel="stylesheet" href="styles.css" />
  <style>
    .recipient-page {
      min-height: 100vh;
      padding: 42px 20px;
      background:
        radial-gradient(circle at 8% 12%, rgba(56, 189, 248, 0.2), transparent 28%),
        radial-gradient(circle at 92% 88%, rgba(37, 99, 235, 0.12), transparent 30%),
        linear-gradient(145deg, #eff8ff 0%, #f8fafc 52%, #eaf2ff 100%);
    }

    .recipient-page .reservation-document {
      width: min(100%, 920px);
      margin: 0 auto;
      overflow: hidden;
      border: 1px solid rgba(148, 163, 184, 0.3);
      border-radius: 22px;
      background: rgba(255, 255, 255, 0.88);
      box-shadow: 0 24px 70px rgba(15, 23, 42, 0.13);
      backdrop-filter: blur(14px);
    }

    .recipient-page .reservation-header {
      padding: 30px 42px 26px;
      background: linear-gradient(135deg, #0f172a, #1e3a8a);
      color: #fff;
    }

    .recipient-page .official-letterhead,
    .recipient-page .letter-heading {
      border: 0;
    }

    .recipient-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 28px;
      color: #bfdbfe;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.14em;
    }

    .recipient-brand-mark {
      display: grid;
      width: 30px;
      height: 30px;
      place-items: center;
      border: 1px solid rgba(255, 255, 255, 0.35);
      border-radius: 9px;
      color: #fff;
      font-size: 0.72rem;
      letter-spacing: 0;
    }

    .recipient-page .letter-heading h1 {
      margin: 0 0 8px;
      color: #fff;
      font-size: clamp(1.7rem, 4vw, 2.35rem);
      letter-spacing: -0.02em;
    }

    .recipient-subtitle {
      margin: 0;
      max-width: 560px;
      color: #bfdbfe;
      font-size: 0.92rem;
      line-height: 1.6;
    }

    .recipient-page .reservation-meta {
      margin-top: 22px;
      color: #dbeafe;
    }

    .recipient-page .reservation-meta span {
      padding: 7px 10px;
      border: 1px solid rgba(191, 219, 254, 0.24);
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
    }

    .recipient-page .letter-intro {
      margin: 26px 42px 0;
      padding: 16px 18px;
      border-left: 3px solid #2563eb;
      border-radius: 0 10px 10px 0;
      background: #eff6ff;
      color: #334155;
    }

    .shared-certificate {
      margin: 24px 42px 0;
      padding: 24px;
      border: 1px solid #cbd5e1;
      background: #fff;
      color: #1e293b;
    }

    .shared-certificate-header {
      padding-bottom: 14px;
      border-bottom: 2px solid #0f172a;
      text-align: center;
    }

    .shared-certificate-header strong {
      display: block;
      color: #0f172a;
      font-size: 0.66rem;
      letter-spacing: 0.1em;
    }

    .shared-certificate-header h2 {
      margin: 14px 0 6px;
      color: #0f172a;
      font-size: 1.1rem;
    }

    .shared-certificate-meta {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 6px 14px;
      color: #475569;
      font-size: 0.68rem;
      font-weight: 600;
    }

    .shared-certificate-intro,
    .shared-certificate-closing {
      margin: 14px 0;
      color: #334155;
      font-family: Georgia, "Times New Roman", serif;
      font-size: 0.78rem;
      line-height: 1.5;
    }

    .shared-certificate-parties {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 110px;
      gap: 8px;
    }

    .shared-certificate-panel {
      min-width: 0;
      padding: 10px;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
    }

    .shared-certificate-panel h3 {
      margin: 0 0 8px;
      padding-bottom: 5px;
      border-bottom: 1px solid #cbd5e1;
      color: #0f172a;
      font-size: 0.66rem;
    }

    .shared-certificate-info {
      display: grid;
      gap: 6px;
    }

    .shared-certificate-info span {
      display: block;
      overflow-wrap: anywhere;
      color: #475569;
      font-size: 0.62rem;
    }

    .shared-certificate-qr {
      text-align: center;
    }

    .shared-certificate-qr img {
      display: block;
      width: 90px;
      max-width: 100%;
      height: 90px;
      margin: 0 auto 4px;
    }

    .shared-certificate-qr small {
      color: #64748b;
      font-size: 0.5rem;
      overflow-wrap: anywhere;
    }

    .shared-certificate-items {
      margin-top: 10px;
      overflow-x: auto;
    }

    .shared-certificate-items table {
      width: 100%;
      border-collapse: collapse;
      min-width: 560px;
      font-size: 0.62rem;
    }

    .shared-certificate-items th,
    .shared-certificate-items td {
      padding: 6px 5px;
      border-bottom: 1px solid #e2e8f0;
      text-align: left;
      vertical-align: top;
    }

    .shared-certificate-items th {
      color: #64748b;
      font-size: 0.52rem;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    @media (max-width: 620px) {
      .shared-certificate {
        margin: 18px 18px 0;
        padding: 12px;
      }

      .shared-certificate-parties {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 76px;
        gap: 5px;
      }

      .shared-certificate-panel {
        padding: 6px 5px;
      }

      .shared-certificate-panel h3 {
        font-size: 0.48rem;
      }

      .shared-certificate-info {
        gap: 4px;
      }

      .shared-certificate-info span {
        font-size: 0.47rem;
      }

      .shared-certificate-qr img {
        width: 62px;
        height: 62px;
      }

      .shared-certificate-qr small {
        font-size: 0.4rem;
      }
    }

    .recipient-page .form-message {
      margin: 22px 42px 0;
    }

    .recipient-form {
      margin: 24px 42px 38px;
      padding: 0;
    }

    .recipient-page .recipient-fields {
      grid-template-columns: 1fr;
    }

    .recipient-section {
      padding: 22px;
      border: 1px solid #dbe4f0;
      border-radius: 14px;
      background: #fff;
    }

    .recipient-section + .recipient-section {
      margin-top: 18px;
    }

    .recipient-section-heading {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 18px;
    }

    .recipient-section-heading h2 {
      margin: 0;
      color: #0f172a;
      font-size: 1rem;
    }

    .recipient-section-heading span {
      color: #64748b;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .recipient-form input[type="text"] {
      border-color: #d5deea;
      background: #f8fafc;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .recipient-form input[type="text"]:focus {
      outline: none;
      border-color: #3b82f6;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
    }

    .signature-label {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      color: #334155;
      font-size: 0.8rem;
      font-weight: 800;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    .signature-hint {
      color: #64748b;
      font-size: 0.72rem;
      font-weight: 500;
      letter-spacing: 0;
      text-transform: none;
    }

    .recipient-page #recipientSignature {
      max-width: none;
      height: 210px;
      margin: 10px 0 0;
      border-color: #bfdbfe;
      background: repeating-linear-gradient(0deg, #fff 0, #fff 34px, #eff6ff 35px);
    }

    .recipient-actions {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 22px;
    }

    .recipient-actions button {
      min-height: 44px;
    }

    .recipient-page .primary-btn {
      box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
    }

    @media (max-width: 620px) {
      .recipient-page {
        padding: 14px 10px;
      }

      .recipient-page .letter-intro,
      .recipient-form {
        margin-left: 18px;
        margin-right: 18px;
      }

      .recipient-page .reservation-header {
        padding: 24px 20px;
      }

      .recipient-page .letter-intro {
        margin-top: 18px;
      }

      .recipient-form {
        margin-bottom: 22px;
      }

      .recipient-fields {
        gap: 12px;
      }

      .recipient-section {
        padding: 16px;
      }

      .recipient-section-heading {
        display: block;
      }

      .recipient-section-heading span {
        display: block;
        margin-top: 6px;
      }

      .recipient-actions {
        align-items: stretch;
        flex-direction: column-reverse;
      }

      .recipient-actions button {
        width: 100%;
      }
    }
  </style>
</head>
<body class="recipient-page">
  <main class="main-panel reservation-page">
    <div class="reservation-document">
      <header class="reservation-header">
        <div class="recipient-brand"><span class="recipient-brand-mark">TM</span><span>SECURE RECIPIENT PORTAL</span></div>
        <div class="letter-heading">
          <h1>Lengkapi Data Penerima</h1>
          <p class="recipient-subtitle">Selesaikan konfirmasi penerimaan barang dengan mengisi identitas dan tanda tangan elektronik Anda.</p>
          <div class="reservation-meta"><span>Document No: <?= htmlspecialchars($shipment['reservation_code'] ?? '-'); ?></span><span><?= htmlspecialchars($shipment['project_name'] ?? '-'); ?></span></div>
        </div>
      </header>
      <div class="letter-intro"><p>Lengkapi data penerima dan tanda tangan elektronik untuk menyelesaikan Goods Handover Certificate.</p></div>
      <?php if ($error !== ''): ?><div class="form-message error"><?= htmlspecialchars($error); ?></div><?php endif; ?>
      <section class="shared-certificate" aria-label="Goods Handover Certificate">
        <header class="shared-certificate-header">
          <strong>DOKUMEN INTERNAL</strong>
          <h2>Goods Handover Certificate</h2>
          <div class="shared-certificate-meta">
            <span>Document No: <?= htmlspecialchars($shipment['reservation_code'] ?? '-'); ?></span>
            <span>Date: <?= htmlspecialchars($shipment['shipping_date'] ?? '-'); ?></span>
            <span>Status: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $shipment['status'] ?? 'packing'))); ?></span>
          </div>
        </header>
        <p class="shared-certificate-intro">Dengan hormat, berikut kami sampaikan data reservasi dan pengiriman barang untuk dapat digunakan sebagaimana mestinya.</p>
        <div class="shared-certificate-parties">
          <section class="shared-certificate-panel">
            <h3>I. First Party</h3>
            <div class="shared-certificate-info">
              <span>Nama: <?= htmlspecialchars($shipment['sender_name'] ?? '-'); ?></span>
              <span>UID: <?= htmlspecialchars($shipment['sender_uid'] ?? '-'); ?></span>
              <span>Posisi: <?= htmlspecialchars($shipment['sender_position'] ?? '-'); ?></span>
              <span>Lokasi: <?= htmlspecialchars($shipment['sender_location'] ?? '-'); ?></span>
            </div>
          </section>
          <section class="shared-certificate-panel">
            <h3>II. Second Party</h3>
            <div class="shared-certificate-info">
              <span>Nama: <?= htmlspecialchars($shipment['receiver_name'] ?? '-'); ?></span>
              <span>UID: <?= htmlspecialchars($shipment['receiver_uid'] ?? '-'); ?></span>
              <span>Posisi: <?= htmlspecialchars($shipment['receiver_position'] ?? '-'); ?></span>
              <span>Lokasi: <?= htmlspecialchars($shipment['receiver_location'] ?? '-'); ?></span>
            </div>
          </section>
          <aside class="shared-certificate-panel shared-certificate-qr">
            <h3>SCAN TO VIEW ONLINE</h3>
            <img src="<?= htmlspecialchars($qrImageUrl); ?>" alt="QR code dokumen" />
            <small><?= htmlspecialchars($shipment['reservation_code'] ?? '-'); ?></small>
          </aside>
        </div>
        <div class="shared-certificate-items">
          <h3>III. Rincian Barang</h3>
          <table>
            <thead><tr><th>Nama Barang</th><th>Qty</th><th>Satuan</th><th>Item Type</th><th>Kategori</th><th>Keterangan</th></tr></thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="6">Tidak ada data barang.</td></tr>
              <?php else: ?>
                <?php foreach ($items as $item): ?>
                  <tr>
                    <td><?= htmlspecialchars($item['name'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars((string)($item['qty'] ?? 0)); ?></td>
                    <td><?= htmlspecialchars($item['unit'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($item['category'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($item['category_alt'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($item['note'] ?? '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <p class="shared-certificate-closing">Demikian surat reservasi dan pengiriman barang ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
      </section>
      <form method="post" class="recipient-form">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>" />
        <section class="recipient-section">
          <div class="recipient-section-heading"><h2>Identitas Penerima</h2><span>04 data wajib</span></div>
          <div class="recipient-fields">
            <label>Nama penerima<input type="text" name="receiver_name" value="<?= htmlspecialchars($shipment['receiver_name'] ?? ''); ?>" required /></label>
            <label>UID penerima<input type="text" name="receiver_uid" value="<?= htmlspecialchars($shipment['receiver_uid'] ?? ''); ?>" required /></label>
            <label>Posisi penerima<input type="text" name="receiver_position" value="<?= htmlspecialchars($shipment['receiver_position'] ?? ''); ?>" required /></label>
            <label>Lokasi penerima<input type="text" name="receiver_location" value="<?= htmlspecialchars($shipment['receiver_location'] ?? ''); ?>" required /></label>
          </div>
        </section>
        <section class="recipient-section">
          <div class="recipient-section-heading"><h2>E-Sign Penerima</h2><span>Tanda tangan elektronik</span></div>
          <div class="signature-label"><span>Area tanda tangan</span><span class="signature-hint">Gunakan mouse atau layar sentuh</span></div>
          <div class="signature-stack"><canvas id="recipientSignature" width="700" height="220"></canvas></div>
          <input type="hidden" id="receiverSignature" name="receiver_signature" value="<?= htmlspecialchars($shipment['receiver_signature'] ?? ''); ?>" />
          <div class="recipient-actions"><button class="secondary-btn" type="button" id="clearSignature">Hapus E-Sign</button><button class="primary-btn" type="submit">Selesai dan buka Goods Handover</button></div>
        </section>
      </form>
    </div>
  </main>
  <script>
    const canvas = document.getElementById('recipientSignature');
    const input = document.getElementById('receiverSignature');
    const context = canvas.getContext('2d');
    let drawing = false;
    const drawImage = (source) => { if (!source) return; const image = new Image(); image.onload = () => context.drawImage(image, 0, 0, canvas.width, canvas.height); image.src = source; };
    drawImage(input.value);
    const point = (event) => { const rect = canvas.getBoundingClientRect(); return { x: (event.clientX - rect.left) * canvas.width / rect.width, y: (event.clientY - rect.top) * canvas.height / rect.height }; };
    canvas.addEventListener('pointerdown', (event) => { drawing = true; const value = point(event); context.beginPath(); context.moveTo(value.x, value.y); });
    canvas.addEventListener('pointermove', (event) => { if (!drawing) return; const value = point(event); context.lineTo(value.x, value.y); context.stroke(); });
    const stopDrawing = () => { if (drawing) input.value = canvas.toDataURL('image/png'); drawing = false; };
    canvas.addEventListener('pointerup', stopDrawing); canvas.addEventListener('pointerleave', stopDrawing); canvas.addEventListener('pointercancel', stopDrawing);
    context.lineWidth = 3; context.lineCap = 'round'; context.strokeStyle = '#0f172a';
    document.getElementById('clearSignature').addEventListener('click', () => { context.clearRect(0, 0, canvas.width, canvas.height); input.value = ''; });
    document.querySelector('form').addEventListener('submit', (event) => { if (!input.value || input.value === 'data:,') { event.preventDefault(); window.alert('E-Sign penerima wajib diisi.'); } });
  </script>
</body>
</html>