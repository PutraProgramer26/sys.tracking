<?php
require __DIR__ . '/auth.php';
requireLogin();
require __DIR__ . '/db.php';

$connection = getDbConnection();
$totalsResult = $connection->query("SELECT
  COALESCE(SUM(CASE WHEN s.status = 'delivered' THEN si.qty ELSE 0 END), 0) AS received_qty,
  COALESCE(SUM(CASE WHEN s.status IN ('sent', 'transit', 'out_of_delivery') THEN si.qty ELSE 0 END), 0) AS shipping_qty,
  COALESCE(SUM(CASE WHEN s.status = 'packing' THEN si.qty ELSE 0 END), 0) AS packing_qty
  FROM shipments s
  LEFT JOIN shipment_items si ON si.shipment_id = s.id");
$totals = $totalsResult ? $totalsResult->fetch_assoc() : [];
$receivedQty = (int)($totals['received_qty'] ?? 0);
$shippingQty = (int)($totals['shipping_qty'] ?? 0);
$packingQty = (int)($totals['packing_qty'] ?? 0);
$totalMaterialQty = $receivedQty + $shippingQty + $packingQty;
$receivedPercent = $totalMaterialQty > 0 ? round(($receivedQty / $totalMaterialQty) * 100) : 0;

$stats = [
  ['label' => 'Material Diterima', 'value' => $receivedQty, 'subtitle' => 'Item berstatus Delivered', 'trend' => $receivedPercent . '%', 'trendClass' => 'up', 'cardClass' => 'success'],
  ['label' => 'Dalam Pengiriman', 'value' => $shippingQty, 'subtitle' => 'Item Transit dan Out of Delivery', 'trend' => $shippingQty . ' item', 'trendClass' => 'up', 'cardClass' => 'warning'],
  ['label' => 'Sedang Packing', 'value' => $packingQty, 'subtitle' => 'Item menunggu pengiriman', 'trend' => $packingQty . ' item', 'trendClass' => 'down', 'cardClass' => 'info']
];

$receivedData = [
  'labels' => ['Diterima', 'Dalam Pengiriman', 'Packing'],
  'values' => [$receivedQty, $shippingQty, $packingQty],
  'colors' => ['#22c55e', '#60a5fa', '#fbbf24'],
  'summary' => $receivedPercent . '% selesai'
];

$shippingData = ['labels' => [], 'values' => [], 'colors' => [], 'summary' => '0 lokasi'];
$shippingResult = $connection->query("SELECT COALESCE(NULLIF(s.receiver_location, ''), 'Tanpa lokasi') AS location, SUM(si.qty) AS total_qty
  FROM shipments s
  INNER JOIN shipment_items si ON si.shipment_id = s.id
  WHERE s.status IN ('sent', 'transit', 'out_of_delivery')
  GROUP BY location ORDER BY total_qty DESC LIMIT 5");
if ($shippingResult) {
  while ($row = $shippingResult->fetch_assoc()) {
    $shippingData['labels'][] = $row['location'];
    $shippingData['values'][] = (int)$row['total_qty'];
  }
}
$shippingData['colors'] = ['#f59e0b', '#fbbf24', '#facc15', '#fcd34d', '#fde68a'];
$shippingData['summary'] = count($shippingData['labels']) . ' lokasi';

$packingData = ['labels' => [], 'values' => [], 'summary' => '0 hari'];
$packingResult = $connection->query("SELECT s.shipping_date, SUM(si.qty) AS total_qty
  FROM shipments s
  INNER JOIN shipment_items si ON si.shipment_id = s.id
  WHERE s.status = 'packing' AND s.shipping_date IS NOT NULL
  GROUP BY s.shipping_date ORDER BY s.shipping_date DESC LIMIT 7");
if ($packingResult) {
  $packingRows = [];
  while ($row = $packingResult->fetch_assoc()) {
    $packingRows[] = $row;
  }
  foreach (array_reverse($packingRows) as $row) {
    $packingData['labels'][] = date('d/m', strtotime($row['shipping_date']));
    $packingData['values'][] = (int)$row['total_qty'];
  }
}
$packingData['summary'] = count($packingData['labels']) . ' hari';
$connection->close();
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Tracking Material</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
          <a class="nav-item active" href="index.php">
            <span>📊</span>
            <span class="nav-label">Dashboard</span>
          </a>
          <?php if (isAdmin()): ?>
            <a class="nav-item" href="material.php">
              <span>📦</span>
              <span class="nav-label">Material</span>
            </a>
            <a class="nav-item" href="create-shipping.php">
              <span>🚚</span>
              <span class="nav-label">Create Shipping</span>
            </a>
          <?php endif; ?>
          <a class="nav-item" href="tracking.php">
            <span>📍</span>
            <span class="nav-label">Tracking</span>
          </a>
          <?php if (isAdmin()): ?>
            <a class="nav-item" href="shipping-monitoring.php">
              <span>📦</span>
              <span class="nav-label">Shipping Monitoring</span>
            </a>
            <a class="nav-item" href="user-management.php">
              <span>⚙️</span>
              <span class="nav-label">Setting</span>
            </a>
          <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
          <a class="sidebar-logout" href="logout.php">
            <span>🚪</span>
            <span class="nav-label">Logout</span>
          </a>
        </div>
      </aside>

      <main class="main-panel">
        <header class="topbar">
          <div>
            <p class="eyebrow">Overview</p>
            <h2>Dashboard Material</h2>
          </div>
        </header>

        <section class="stats-grid">
          <?php foreach ($stats as $stat): ?>
            <article class="stat-card <?= $stat['cardClass']; ?>">
              <div class="stat-header">
                <span class="stat-label"><?= $stat['label']; ?></span>
                <span class="trend <?= $stat['trendClass']; ?>"><?= $stat['trend']; ?></span>
              </div>
              <h3><?= $stat['value']; ?></h3>
              <p><?= $stat['subtitle']; ?></p>
            </article>
          <?php endforeach; ?>
        </section>

        <section class="charts-grid">
          <article class="chart-card wide">
            <div class="card-head">
              <div>
                <p class="card-kicker">Status Material</p>
                <h3>Distribusi Status Material</h3>
              </div>
              <span class="pill success"><?= $receivedData['summary']; ?></span>
            </div>
            <div class="chart-wrap donut-wrap">
              <canvas id="receivedChart"></canvas>
            </div>
          </article>

          <article class="chart-card">
            <div class="card-head">
              <div>
                <p class="card-kicker">Distribusi</p>
                <h3>Dalam Pengiriman</h3>
              </div>
              <span class="pill warning"><?= $shippingData['summary']; ?></span>
            </div>
            <div class="chart-wrap bar-wrap">
              <canvas id="shippingChart"></canvas>
            </div>
          </article>

          <article class="chart-card">
            <div class="card-head">
              <div>
                <p class="card-kicker">Proses</p>
                <h3>Sedang Packing</h3>
              </div>
              <span class="pill info"><?= $packingData['summary']; ?></span>
            </div>
            <div class="chart-wrap line-wrap">
              <canvas id="packingChart"></canvas>
            </div>
          </article>
        </section>
      </main>
    </div>

    <script src="sidebar.js"></script>
    <script>
      const receivedChart = document.getElementById('receivedChart');
      const shippingChart = document.getElementById('shippingChart');
      const packingChart = document.getElementById('packingChart');

      new Chart(receivedChart, {
        type: 'doughnut',
        data: {
          labels: <?= json_encode($receivedData['labels']); ?>,
          datasets: [{
            data: <?= json_encode($receivedData['values']); ?>,
            backgroundColor: <?= json_encode($receivedData['colors']); ?>,
            borderWidth: 0,
            hoverOffset: 10
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '62%',
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 18,
                font: {
                  family: 'Inter',
                  size: 12,
                  weight: '600'
                }
              }
            },
            tooltip: {
              callbacks: {
                label: (context) => `${context.label}: ${context.raw} item`
              }
            }
          }
        }
      });

      new Chart(shippingChart, {
        type: 'bar',
        data: {
          labels: <?= json_encode($shippingData['labels']); ?>,
          datasets: [{
            label: 'Quantity',
            data: <?= json_encode($shippingData['values']); ?>,
            backgroundColor: <?= json_encode($shippingData['colors']); ?>,
            borderRadius: 10,
            borderSkipped: false
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(148, 163, 184, 0.12)'
              },
              ticks: {
                stepSize: 10
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });

      new Chart(packingChart, {
        type: 'line',
        data: {
          labels: <?= json_encode($packingData['labels']); ?>,
          datasets: [{
            label: 'Packing',
            data: <?= json_encode($packingData['values']); ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.12)',
            borderWidth: 3,
            tension: 0.35,
            fill: true,
            pointBackgroundColor: '#2563eb',
            pointRadius: 4,
            pointHoverRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(148, 163, 184, 0.12)'
              }
            },
            x: {
              grid: {
                display: false
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    </script>
  </body>
</html>
