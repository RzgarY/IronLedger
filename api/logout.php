<?php
require 'config.php';
require 'auth_helper.php';
api_headers();

$token = get_bearer_token();
if ($token) {
  try {
    $stmt = db()->prepare('DELETE FROM sessions WHERE token = :t');
    $stmt->execute([':t' => $token]);
  } catch (PDOException $e) {
    // Non-fatal — client clears its local token regardless.
  }
}
echo json_encode(['ok' => true]);
