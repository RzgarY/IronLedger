<?php
require 'config.php';
require 'auth_helper.php';
api_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'POST only']);
  exit;
}

$user = require_auth();
$body = json_decode(get_raw_body(), true);
$key = trim(arr_get($body, 'key', ''));
$value = (is_array($body) && array_key_exists('value', $body)) ? $body['value'] : null;

// Only allow the specific keys the app actually syncs — prevents arbitrary
// key spam and keeps this predictable.
$allowedKeys = ['workout-history', 'custom-exercises', 'routines', 'app-settings', 'profile', 'bodyweight-log'];
if (!in_array($key, $allowedKeys, true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Unknown data key.']);
  exit;
}

$json = json_encode($value);
if (strlen($json) > 4000000) { // ~4MB guard per key
  http_response_code(413);
  echo json_encode(['error' => 'That data is too large to sync.']);
  exit;
}

try {
  $stmt = db()->prepare(
    'INSERT INTO user_data (user_id, data_key, data_value, updated_at)
     VALUES (:uid, :k, :v, NOW())
     ON DUPLICATE KEY UPDATE data_value = VALUES(data_value), updated_at = NOW()'
  );
  $stmt->execute([':uid' => $user['id'], ':k' => $key, ':v' => $json]);
  echo json_encode(['ok' => true]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Could not save.']);
}
