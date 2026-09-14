<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($fullName === '') {
        $error = 'Nama wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        try {
            $connection = getDbConnection();
            $existing = $connection->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $existing->bind_param('s', $email);
            $existing->execute();
            $exists = $existing->get_result()->num_rows > 0;
            $existing->close();

            if ($exists) {
                $error = 'Email sudah terdaftar.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $connection->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'user')");
                $stmt->bind_param('ssss', $email, $email, $hash, $fullName);
                $stmt->execute();
                $stmt->close();
                $success = 'Akun berhasil dibuat. Silakan masuk.';
            }
            $connection->close();
        } catch (Throwable $exception) {
            $error = 'Terjadi kesalahan saat membuat akun.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Buat Akun - Tracking Material</title>
    <style>
      * { box-sizing: border-box; }
      body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0f172a, #1e293b); font-family: Arial, sans-serif; color: #e2e8f0; }
      .card { width: min(440px, calc(100% - 32px)); padding: 32px 28px; background: rgba(15, 23, 42, .94); border: 1px solid rgba(148, 163, 184, .2); border-radius: 22px; box-shadow: 0 24px 80px rgba(15, 23, 42, .5); }
      h1 { margin: 0 0 8px; font-size: 1.5rem; } p { color: #94a3b8; } label { display: block; margin: 18px 0 8px; font-weight: 600; } input { width: 100%; padding: 13px 14px; border: 1px solid rgba(148, 163, 184, .3); border-radius: 12px; background: rgba(15, 23, 42, .5); color: white; font-size: 1rem; } button { width: 100%; margin-top: 22px; padding: 14px; border: 0; border-radius: 12px; background: linear-gradient(135deg, #22c55e, #14b8a6); color: white; font-weight: 700; cursor: pointer; } .alert { margin: 16px 0; padding: 12px 14px; border-radius: 10px; background: rgba(239, 68, 68, .12); color: #fecaca; } .success { background: rgba(34, 197, 94, .14); color: #bbf7d0; } a { color: #7dd3fc; }
    </style>
  </head>
  <body>
    <main class="card">
      <h1>Buat Akun</h1>
      <p>Daftarkan akun untuk mengakses sistem tracking material.</p>
      <?php if ($error !== ''): ?><div class="alert"><?= htmlspecialchars($error); ?></div><?php endif; ?>
      <?php if ($success !== ''): ?><div class="alert success"><?= htmlspecialchars($success); ?> <a href="login.php">Masuk</a></div><?php endif; ?>
      <form method="post" action="register.php">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required />
        <label for="full_name">Nama</label>
        <input id="full_name" name="full_name" type="text" autocomplete="name" required />
        <label for="password">Password</label>
        <input id="password" name="password" type="password" minlength="6" autocomplete="new-password" required />
        <button type="submit">Buat Akun</button>
      </form>
      <p><a href="login.php">Kembali ke halaman masuk</a></p>
    </main>
  </body>
</html>