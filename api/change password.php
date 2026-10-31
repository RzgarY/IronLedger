<?php
require 'config.php';
require 'auth_helper.php';
api_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'POST only']);
  exit;
}

// Intentionally does NOT require the old password — this is meant for
// people who are still logged in on this device but can't remember their
// current password. The valid session token is the proof of identity here.
$user = require_auth();

$body = json_decode(file_get_contents('php://input'), true);
$newPassword = (string)arr_get($body, 'new_password', '');

if (strlen($newPassword) < 6) {
  http_response_code(400);
  echo json_encode(['error' => 'Password must be at least 6 characters.']);
  exit;
}

try {
  $hash = password_hash($newPassword, PASSWORD_BCRYPT);
  $stmt = db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
  $stmt->execute([':h' => $hash, ':id' => $user['id']]);
  echo json_encode(['ok' => true]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Could not update password.']);
}
