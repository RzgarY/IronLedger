<?php
require 'config.php';
api_headers();

try {
  // Each user's single best session, highest volume first.
  // (Swap MAX(volume_score) for SUM(volume_score) if you'd rather rank by
  // total lifetime volume instead of best single session.)
  $stmt = db()->prepare(
    'SELECT username, MAX(volume_score) AS best_volume
     FROM workouts
     GROUP BY username
     ORDER BY best_volume DESC
     LIMIT 50'
  );
  $stmt->execute();
  echo json_encode(['ok' => true, 'leaderboard' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Could not load leaderboard']);
}
