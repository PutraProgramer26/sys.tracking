<?php
$stats = [
    [
        'label' => 'Material Diterima',
        'value' => 482,
        'subtitle' => 'Item berhasil masuk gudang',
        'trend' => '+12.4%',
        'trendClass' => 'up',
        'cardClass' => 'success'
    ],
    [
        'label' => 'Dalam Pengiriman',
        'value' => 168,
        'subtitle' => 'Material dalam perjalanan',
        'trend' => '+8.1%',
        'trendClass' => 'up',
        'cardClass' => 'warning'
    ],
    [
        'label' => 'Sedang Packing',
        'value' => 96,
        'subtitle' => 'Pesanan menunggu pengiriman',
        'trend' => '-3.2%',
        'trendClass' => 'down',
        'cardClass' => 'info'
    ]
];

$receivedData = [
    'labels' => ['Diterima', 'Dalam Gudang', 'Retur'],
    'values' => [68, 24, 8],
    'colors' => ['#22c55e', '#60a5fa', '#fbbf24'],
    'summary' => '68% selesai'
];

$shippingData = [
    'labels' => ['Jakarta', 'Bandung', 'Surabaya', 'Medan', 'Makassar'],
    'values' => [42, 35, 27, 18, 14],
    'colors' => ['#f59e0b', '#fbbf24', '#facc15', '#fcd34d', '#fde68a'],
    'summary' => '14 kota'
];

$packingData = [
    'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
    'values' => [40, 58, 52, 73, 66, 84],
    'summary' => '12 tim'
];
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
        </div>

        <nav class="nav-menu">
          <a class="nav-item active" href="index.php">
            <span>📊</span>
            Dashboard
          </a>
          <a class="nav-item" href="#">
            <span>📦</span>
            Material
          </a>
          <a class="nav-item" href="create-shipping.php">
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
        <header class="topbar">
          <div>
            <p class="eyebrow">Overview</p>
            <h2>Dashboard Material</h2>
          </div>
          <div class="topbar-actions">
            <button class="ghost-btn">Filter</button>
            <button class="primary-btn">Export</button>
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
                <h3>Material Sudah Diterima</h3>
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
                label: (context) => `${context.label}: ${context.parsed}%`
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
