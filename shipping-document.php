<?php
require __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$connection = getDbConnection();
$stmt = $connection->prepare('SELECT * FROM shipments WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$shipment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$shipment) {
    $connection->close();
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$itemsStmt = $connection->prepare('SELECT name, qty, unit, category, category_alt, note FROM shipment_items WHERE shipment_id = ? ORDER BY id ASC');
$itemsStmt->bind_param('i', $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$itemsStmt->close();
$connection->close();

$signatureImage = $shipment['sender_signature'] ?? '';
$receiverSignatureImage = $shipment['receiver_signature'] ?? '';
$status = ucfirst(str_replace('_', ' ', $shipment['status'] ?? 'packing'));
$isPdf = isset($_GET['format']) && $_GET['format'] === 'pdf';
$documentStyles = $isPdf && file_exists(__DIR__ . '/styles.css')
  ? '<style>' . file_get_contents(__DIR__ . '/styles.css') . '</style>'
  : '<link rel="stylesheet" href="styles.css" />';
if ($isPdf) {
  require_once __DIR__ . '/vendor/autoload.php';
  ob_start();
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Surat Pengiriman - <?= htmlspecialchars($shipment['reservation_code'] ?? ''); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <?= $documentStyles; ?>
  </head>
  <body>
    <main class="main-panel reservation-page document-only-page">
      <div class="reservation-document">
        <header class="reservation-header">
          <div class="official-letterhead">
            <div class="letter-contact">
              <strong>DOKUMEN RESMI</strong>
              <span>Surat Reservasi dan Pengiriman Barang</span>
            </div>
          </div>
          <div class="letter-rule"></div>
          <div class="letter-heading">
            <h1>SURAT RESERVASI / PENGIRIMAN BARANG</h1>
            <div class="reservation-meta">
              <span>Nomor: <?= htmlspecialchars($shipment['reservation_code'] ?? '-'); ?></span>
              <span>Tanggal: <?= htmlspecialchars($shipment['shipping_date'] ?? '-'); ?></span>
              <span>Status: <?= htmlspecialchars($status); ?></span>
            </div>
          </div>
        </header>

        <div class="letter-intro">
          <p>Dengan hormat, berikut kami sampaikan data reservasi dan pengiriman barang untuk dapat digunakan sebagaimana mestinya.</p>
        </div>

        <div class="reservation-body document-parties">
          <section class="reservation-panel two-column-panel">
            <div class="partner-column">
              <h3>I. Data Pengirim</h3>
              <div class="info-grid">
                <div class="info-item"><label>Nama</label><span><?= htmlspecialchars($shipment['sender_name'] ?? '-'); ?></span></div>
                <div class="info-item"><label>UID</label><span><?= htmlspecialchars($shipment['sender_uid'] ?? '-'); ?></span></div>
                <div class="info-item"><label>Posisi</label><span><?= htmlspecialchars($shipment['sender_position'] ?? '-'); ?></span></div>
                <div class="info-item"><label>Lokasi</label><span><?= htmlspecialchars($shipment['sender_location'] ?? '-'); ?></span></div>
              </div>
            </div>
            <div class="partner-column">
              <h3>II. Data Penerima</h3>
              <div class="info-grid">
                <div class="info-item"><label>Nama</label><span><?= htmlspecialchars($shipment['receiver_name'] ?? '-'); ?></span></div>
                <div class="info-item"><label>UID</label><span><?= htmlspecialchars($shipment['receiver_uid'] ?? '-'); ?></span></div>
                <div class="info-item"><label>Posisi</label><span><?= htmlspecialchars($shipment['receiver_position'] ?? '-'); ?></span></div>
                <div class="info-item"><label>Lokasi</label><span><?= htmlspecialchars($shipment['receiver_location'] ?? '-'); ?></span></div>
              </div>
            </div>
          </section>
        </div>

        <div class="reservation-body document-items">
          <section class="reservation-panel">
            <h3>III. Rincian Barang</h3>
            <table class="items-table">
              <thead>
                <tr>
                  <th>Nama Barang</th>
                  <th>Qty</th>
                  <th>Satuan</th>
                  <th>Item Type</th>
                  <th>Kategori</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
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
          </section>
        </div>

        <div class="letter-closing">
          <p>Demikian surat reservasi dan pengiriman barang ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
        </div>

        <div class="reservation-signature-row">
          <div class="reservation-panel signature-panel">
            <h3>Pengirim</h3>
            <?php if (!empty($signatureImage)): ?>
              <div class="sign-box large-sign-box"><img src="<?= htmlspecialchars($signatureImage); ?>" alt="Signature pengirim" /></div>
            <?php else: ?>
              <p class="esign-note">Tidak ada tanda tangan.</p>
            <?php endif; ?>
            <div class="signature-meta">
              <span>Nama: <?= htmlspecialchars($shipment['sender_name'] ?? '-'); ?></span>
              <span>UID: <?= htmlspecialchars($shipment['sender_uid'] ?? '-'); ?></span>
              <span>Posisi: <?= htmlspecialchars($shipment['sender_position'] ?? '-'); ?></span>
            </div>
          </div>
          <div class="reservation-panel signature-panel">
            <h3>Penerima</h3>
            <?php if (!empty($receiverSignatureImage)): ?>
              <div class="sign-box large-sign-box"><img src="<?= htmlspecialchars($receiverSignatureImage); ?>" alt="Signature penerima" /></div>
            <?php else: ?>
              <p class="esign-note">Belum ada tanda tangan penerima.</p>
            <?php endif; ?>
            <div class="signature-meta">
              <span>Nama: <?= htmlspecialchars($shipment['receiver_name'] ?? '-'); ?></span>
              <span>UID: <?= htmlspecialchars($shipment['receiver_uid'] ?? '-'); ?></span>
              <span>Posisi: <?= htmlspecialchars($shipment['receiver_position'] ?? '-'); ?></span>
            </div>
          </div>
        </div>
      </div>
    </main>
  </body>
</html>
<?php if ($isPdf): ?>
<?php
$html = ob_get_clean();
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);
$options->set('chroot', __DIR__);
$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('goods-handover-' . ($shipment['reservation_code'] ?? 'document') . '.pdf', ['Attachment' => false]);
exit;
?>
<?php endif; ?>
