<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        try {
            $connection = getDbConnection();
            $stmt = $connection->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            $connection->close();

            if ($user && password_verify($password, $user['password'])) {
                $role = $user['role'] ?? 'user';
                loginUser((int)$user['id'], $user['username'], $role);
                header('Location: index.php');
                exit;
            }

            $error = 'Username atau password salah.';
        } catch (Throwable $exception) {
            $error = 'Terjadi kesalahan saat login.';
        }
    } else {
        $error = 'Username dan password harus diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Tracking Material</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <style>
      * { box-sizing: border-box; }
      body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        font-family: 'Inter', sans-serif;
        color: #e2e8f0;
      }
      .login-card {
        width: min(420px, calc(100% - 32px));
        background: rgba(15, 23, 42, 0.9);
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 22px;
        padding: 32px 28px;
        box-shadow: 0 24px 80px rgba(15, 23, 42, 0.5);
      }
      .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
      }
      .brand-mark {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #22c55e, #38bdf8);
        color: white;
        font-weight: 800;
      }
      h1 {
        margin: 0;
        font-size: 1.5rem;
      }
      .subtext {
        margin: 0 0 20px;
        color: #94a3b8;
      }
      .form-group {
        margin-bottom: 18px;
      }
      label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
      }
      input {
        width: 100%;
        padding: 13px 14px;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.3);
        background: rgba(15, 23, 42, 0.5);
        color: white;
        font-size: 1rem;
      }
      input:focus {
        outline: 2px solid rgba(56, 189, 248, 0.5);
        border-color: rgba(56, 189, 248, 0.6);
      }
      .login-btn {
        width: 100%;
        margin-top: 6px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #22c55e, #14b8a6);
        color: white;
        font-weight: 700;
        padding: 14px 18px;
        cursor: pointer;
      }
      .alert {
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.25);
        color: #fecaca;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 18px;
      }
      .hint {
        margin-top: 18px;
        text-align: center;
        color: #94a3b8;
        font-size: 0.9rem;
      }
    </style>
  </head>
  <body>
    <div class="login-card">
      <div class="brand">
        <div class="brand-mark">TM</div>
        <h1>Tracking Material</h1>
      </div>
      <p class="subtext">Masuk untuk mengakses sistem monitoring pengiriman.</p>

      <?php if ($error !== ''): ?>
        <div class="alert"><?= htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post" action="login.php">
        <div class="form-group">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" placeholder="Masukkan username" autocomplete="username" />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" />
        </div>

        <button type="submit" class="login-btn">Login</button>
      </form>

    </div>
  </body>
</html>
