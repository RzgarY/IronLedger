<?php
require 'config.php';
require 'auth_helper.php';
api_headers();

$user = require_auth();

try {
  $stmt = db()->prepare('SELECT data_key, data_value FROM user_data WHERE user_id = :uid');
  $stmt->execute([':uid' => $user['id']]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $data = [];
  foreach ($rows as $r) {
    $data[$r['data_key']] = json_decode($r['data_value']);
  }

  echo json_encode(['ok' => true, 'username' => $user['username'], 'data' => $data]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Could not load your data.']);
}
