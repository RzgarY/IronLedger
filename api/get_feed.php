<?php
require 'config.php';
api_headers();

try {
  $stmt = db()->prepare(
    'SELECT username, summary_text, volume_score, created_at
     FROM workouts
     ORDER BY created_at DESC
     LIMIT 50'
  );
  $stmt->execute();
  echo json_encode(['ok' => true, 'workouts' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Could not load feed']);
}
