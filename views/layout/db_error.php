<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database connection error · MailDart Pro</title>
  <style>
    body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #0a1b2e; color: #f2f6f8; font-family: 'Segoe UI', system-ui, sans-serif; padding: 16px; }
    .card { max-width: 560px; background: #112a46; border: 1px solid #34506d; border-radius: 16px; padding: 28px; }
    h1 { margin: 0 0 8px; font-size: 20px; }
    p, li { color: #c4d2e1; font-size: 14px; line-height: 1.6; }
    code { background: #0e2440; padding: 2px 6px; border-radius: 6px; color: #e1e8f0; }
    .err { font-family: monospace; font-size: 12px; color: #fca5a5; background: #33192a; padding: 10px 12px; border-radius: 10px; word-break: break-word; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Could not connect to MySQL</h1>
    <p>MailDart stores accounts, campaigns and logs in MySQL. Please check:</p>
    <ul>
      <li>MySQL is started in the XAMPP Control Panel.</li>
      <li>The <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code> and <code>DB_NAME</code> values in <code>.env</code> are correct (XAMPP default: user <code>root</code>, empty password).</li>
    </ul>
    <p class="err"><?= htmlspecialchars((string) ($dbError ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  </div>
</body>
</html>
