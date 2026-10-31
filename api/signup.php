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

if (!preg_match('/^[a-zA-Z0-9_.\- ]{3,24}$/', $username)) {
  http_response_code(400);
  echo json_encode(['error' => 'Username must be 3-24 characters (letters, numbers, spaces, _ . -).']);
  exit;
}
if (strlen($password) < 6) {
  http_response_code(400);
  echo json_encode(['error' => 'Password must be at least 6 characters.']);
  exit;
}

try {
  $check = db()->prepare('SELECT id FROM users WHERE username = :u');
  $check->execute([':u' => $username]);
  if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(['error' => 'That username is taken.']);
    exit;
  }

  $hash = password_hash($password, PASSWORD_BCRYPT);
  $ins = db()->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (:u, :h, NOW())');
  $ins->execute([':u' => $username, ':h' => $hash]);
  $userId = db()->lastInsertId();

  $token = generate_token();
  $sess = db()->prepare(
    'INSERT INTO sessions (token, user_id, created_at, expires_at)
     VALUES (:t, :uid, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY))'
  );
  $sess->execute([':t' => $token, ':uid' => $userId]);

  echo json_encode(['ok' => true, 'token' => $token, 'username' => $username]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Could not create account.']);
}
