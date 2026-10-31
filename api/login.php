<?php
require 'config.php';
require 'auth_helper.php';
api_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'POST only']);
  exit;
}

$body = json_decode(get_raw_body(), true);
$username = trim(arr_get($body, 'username', ''));
$password = (string)arr_get($body, 'password', '');

try {
  $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = :u');
  $stmt->execute([':u' => $username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Incorrect username or password.']);
    exit;
  }

  $token = generate_token();
  $sess = db()->prepare(
    'INSERT INTO sessions (token, user_id, created_at, expires_at)
     VALUES (:t, :uid, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY))'
  );
  $sess->execute([':t' => $token, ':uid' => $user['id']]);

  echo json_encode(['ok' => true, 'token' => $token, 'username' => $user['username']]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Login failed.']);
}
