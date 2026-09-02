<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

requireRole('admin');

$connection = getDbConnection();
$successMessage = '';
$errorMessage = '';
$editingUser = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $connection->prepare('SELECT id, username, full_name, role FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editingUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $username = trim((string)($_POST['username'] ?? ''));
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $role = in_array($_POST['role'] ?? '', ['admin', 'user'], true) ? $_POST['role'] : 'user';

            if ($username === '' || $password === '') {
                $errorMessage = 'Username dan password wajib diisi.';
            } elseif (strlen($password) < 6) {
                $errorMessage = 'Password minimal 6 karakter.';
            } else {
                $existing = $connection->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
                $existing->bind_param('s', $username);
                $existing->execute();
                $existingResult = $existing->get_result();
                if ($existingResult->num_rows > 0) {
                    $errorMessage = 'Username sudah digunakan.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $connection->prepare('INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)');
                    $stmt->bind_param('ssss', $username, $hash, $fullName, $role);
                    if ($stmt->execute()) {
                        $successMessage = 'User baru berhasil dibuat.';
                    } else {
                        $errorMessage = 'Gagal menambah user baru.';
                    }
                    $stmt->close();
                }
                $existing->close();
            }
        }

        if ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $fullName = trim((string)($_POST['full_name'] ?? ''));
            $role = in_array($_POST['role'] ?? '', ['admin', 'user'], true) ? $_POST['role'] : 'user';
            $password = (string)($_POST['password'] ?? '');

            if ($id <= 0) {
                $errorMessage = 'Data user tidak valid.';
            } elseif ($id === (int)$_SESSION['user_id'] && $role !== 'admin') {
                $errorMessage = 'Anda tidak dapat menurunkan role akun Anda sendiri.';
            } else {
                if ($password !== '') {
                    if (strlen($password) < 6) {
                        $errorMessage = 'Password baru minimal 6 karakter.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $connection->prepare('UPDATE users SET full_name = ?, role = ?, password = ? WHERE id = ?');
                        $stmt->bind_param('sssi', $fullName, $role, $hash, $id);
                        if ($stmt->execute()) {
                            $successMessage = 'Data user berhasil diperbarui.';
                        } else {
                            $errorMessage = 'Gagal memperbarui data user.';
                        }
                        $stmt->close();
                    }
                } else {
                    $stmt = $connection->prepare('UPDATE users SET full_name = ?, role = ? WHERE id = ?');
                    $stmt->bind_param('ssi', $fullName, $role, $id);
                    if ($stmt->execute()) {
                        $successMessage = 'Data user berhasil diperbarui.';
                    } else {
                        $errorMessage = 'Gagal memperbarui data user.';
                    }
                    $stmt->close();
                }
            }
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $errorMessage = 'Data user tidak valid.';
            } elseif ($id === (int)$_SESSION['user_id']) {
                $errorMessage = 'Anda tidak dapat menghapus akun sendiri.';
            } else {
                $targetRoleStmt = $connection->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
                $targetRoleStmt->bind_param('i', $id);
                $targetRoleStmt->execute();
                $targetRoleResult = $targetRoleStmt->get_result();
                $targetUser = $targetRoleResult->fetch_assoc();
                $targetRoleStmt->close();

                if ($targetUser) {
                    if (($targetUser['role'] ?? 'user') === 'admin') {
                        $adminCountResult = $connection->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
                        $adminCount = $adminCountResult ? (int)($adminCountResult->fetch_assoc()['total'] ?? 0) : 0;
                        if ($adminCount <= 1) {
                            $errorMessage = 'Minimal harus ada 1 admin di sistem.';
                        } else {
                            $deleteStmt = $connection->prepare('DELETE FROM users WHERE id = ?');
                            $deleteStmt->bind_param('i', $id);
                            if ($deleteStmt->execute()) {
                                $successMessage = 'User berhasil dihapus.';
                            } else {
                                $errorMessage = 'Gagal menghapus user.';
                            }
                            $deleteStmt->close();
                        }
                    } else {
                        $deleteStmt = $connection->prepare('DELETE FROM users WHERE id = ?');
                        $deleteStmt->bind_param('i', $id);
                        if ($deleteStmt->execute()) {
                            $successMessage = 'User berhasil dihapus.';
                        } else {
                            $errorMessage = 'Gagal menghapus user.';
                        }
                        $deleteStmt->close();
                    }
                } else {
                    $errorMessage = 'User tidak ditemukan.';
                }
            }
        }
    }

    $usersResult = $connection->query('SELECT id, username, full_name, role FROM users ORDER BY role ASC, username ASC');
    $users = $usersResult ? $usersResult->fetch_all(MYSQLI_ASSOC) : [];
} finally {
    $connection->close();
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manajemen User</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
    <style>
      .user-page { padding: 24px; }
      .user-layout { display: grid; grid-template-columns: minmax(280px, 380px) 1fr; gap: 24px; }
      .panel { background: white; border: 1px solid rgba(148, 163, 184, 0.22); border-radius: 20px; box-shadow: 0 12px 26px rgba(15, 23, 42, 0.04); padding: 22px; }
      .panel h3 { margin: 0 0 18px; }
      .field-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
      .field-group label { color: #475569; font-size: 0.83rem; font-weight: 700; }
      .field-group input, .field-group select { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.55); background: #f8fafc; font: inherit; }
      .field-group input:focus, .field-group select:focus { outline: 2px solid rgba(37, 99, 235, 0.12); border-color: rgba(37, 99, 235, 0.5); }
      .button-row { display: flex; gap: 10px; flex-wrap: wrap; }
      .button-row .primary-btn, .button-row .ghost-btn, .button-row .danger-btn { text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
      .danger-btn { background: #fee2e2; color: #b91c1c; }
      .alert { padding: 12px 14px; border-radius: 12px; margin-bottom: 18px; font-weight: 600; }
      .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid rgba(153, 27, 27, 0.15); }
      .alert.success { background: #dcfce7; color: #166534; border: 1px solid rgba(22, 101, 52, 0.12); }
      .user-table { width: 100%; border-collapse: collapse; min-width: 620px; }
      .user-table th, .user-table td { border-bottom: 1px solid rgba(148, 163, 184, 0.2); padding: 12px 10px; text-align: left; }
      .user-table th { background: #f8fafc; color: #475569; font-size: 0.74rem; letter-spacing: 0.08em; text-transform: uppercase; }
      .badge { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; text-transform: capitalize; }
      .badge.admin { background: #dbeafe; color: #1d4ed8; }
      .badge.user { background: #dcfce7; color: #166534; }
      .table-actions { display: flex; gap: 8px; flex-wrap: wrap; }
      .table-actions form { margin: 0; }
      .table-actions button { padding: 8px 10px; border-radius: 10px; font-size: 0.75rem; }
      @media (max-width: 900px) { .user-layout { grid-template-columns: 1fr; } }
    </style>
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
          <a class="nav-item" href="create-shipping.php"><span>🚚</span><span class="nav-label">Create Shipping</span></a>
          <a class="nav-item" href="tracking.php"><span>📍</span><span class="nav-label">Tracking</span></a>
          <a class="nav-item" href="shipping-monitoring.php"><span>📦</span><span class="nav-label">Shipping Monitoring</span></a>
          <a class="nav-item active" href="user-management.php"><span>⚙️</span><span class="nav-label">Setting</span></a>
        </nav>

        <div class="sidebar-footer">
          <a class="sidebar-logout" href="logout.php"><span>🚪</span><span class="nav-label">Logout</span></a>
        </div>
      </aside>

      <main class="main-panel user-page">
        <header class="topbar shipping-topbar">
          <div>
            <p class="eyebrow">System</p>
            <h2>Manajemen User</h2>
          </div>
        </header>

        <?php if ($errorMessage !== ''): ?>
          <div class="alert error"><?= htmlspecialchars($errorMessage); ?></div>
        <?php elseif ($successMessage !== ''): ?>
          <div class="alert success"><?= htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <div class="user-layout">
          <section class="panel">
            <?php if ($editingUser): ?>
              <h3>Edit User</h3>
              <form method="post" action="user-management.php">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="id" value="<?= (int)$editingUser['id']; ?>" />

                <div class="field-group">
                  <label for="edit_username">Username</label>
                  <input id="edit_username" type="text" value="<?= htmlspecialchars($editingUser['username']); ?>" disabled />
                </div>

                <div class="field-group">
                  <label for="edit_full_name">Nama Lengkap</label>
                  <input id="edit_full_name" name="full_name" type="text" value="<?= htmlspecialchars($editingUser['full_name'] ?? ''); ?>" />
                </div>

                <div class="field-group">
                  <label for="edit_role">Role</label>
                  <select id="edit_role" name="role">
                    <option value="user" <?= (($editingUser['role'] ?? 'user') === 'user') ? 'selected' : ''; ?>>user</option>
                    <option value="admin" <?= (($editingUser['role'] ?? 'user') === 'admin') ? 'selected' : ''; ?>>admin</option>
                  </select>
                </div>

                <div class="field-group">
                  <label for="edit_password">Password Baru (opsional)</label>
                  <input id="edit_password" name="password" type="password" minlength="6" />
                </div>

                <div class="button-row">
                  <button class="primary-btn" type="submit">Update User</button>
                  <a class="ghost-btn" href="user-management.php">Batal</a>
                </div>
              </form>
            <?php else: ?>
              <h3>Tambah User</h3>
              <form method="post" action="user-management.php">
                <input type="hidden" name="action" value="create" />

                <div class="field-group">
                  <label for="username">Username</label>
                  <input id="username" name="username" type="text" required />
                </div>

                <div class="field-group">
                  <label for="full_name">Nama Lengkap</label>
                  <input id="full_name" name="full_name" type="text" />
                </div>

                <div class="field-group">
                  <label for="role">Role</label>
                  <select id="role" name="role">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                  </select>
                </div>

                <div class="field-group">
                  <label for="password">Password</label>
                  <input id="password" name="password" type="password" required minlength="6" />
                </div>

                <div class="button-row">
                  <button class="primary-btn" type="submit">Simpan User</button>
                </div>
              </form>
            <?php endif; ?>
          </section>

          <section class="panel">
            <h3>Daftar User</h3>
            <div style="overflow-x:auto;">
              <table class="user-table">
                <thead>
                  <tr>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($users === []): ?>
                    <tr>
                      <td colspan="4" style="text-align:center; color:#64748b; padding:20px;">Belum ada user.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($users as $user): ?>
                      <tr>
                        <td><?= htmlspecialchars($user['username']); ?></td>
                        <td><?= htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                        <td><span class="badge <?= htmlspecialchars($user['role'] ?? 'user'); ?>"><?= htmlspecialchars($user['role'] ?? 'user'); ?></span></td>
                        <td>
                          <div class="table-actions">
                            <a class="secondary-btn" href="user-management.php?edit=<?= (int)$user['id']; ?>">Edit</a>
                            <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                              <form method="post" action="user-management.php" onsubmit="return confirm('Hapus user ini?');">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?= (int)$user['id']; ?>" />
                                <button class="danger-btn" type="submit">Delete</button>
                              </form>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      </main>
    </div>

    <script src="sidebar.js"></script>
  </body>
</html>
