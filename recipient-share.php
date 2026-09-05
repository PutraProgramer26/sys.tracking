<?php
require __DIR__ . '/db.php';

$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$connection = getDbConnection();
$statement = $connection->prepare('SELECT * FROM shipments WHERE share_token = ? LIMIT 1');
$statement->bind_param('s', $token);
$statement->execute();
$shipment = $statement->get_result()->fetch_assoc();
$statement->close();

if (!$shipment) {
    $connection->close();
    http_response_code(404);
    exit('Link penerima tidak valid atau sudah tidak tersedia.');
}

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
        $update = $connection->prepare('UPDATE shipments SET status = ?, receiver_name = ?, receiver_uid = ?, receiver_position = ?, receiver_location = ?, receiver_signature = ? WHERE id = ? AND share_token = ?');
        $status = 'delivered';
        $shipmentId = (int)$shipment['id'];
        $update->bind_param('ssssssis', $status, $receiverName, $receiverUid, $receiverPosition, $receiverLocation, $receiverSignature, $shipmentId, $token);
        $update->execute();
        $update->close();
        $connection->close();
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