<?php
require 'config.php';
require 'auth_helper.php';
api_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'POST only']);
  exit;
}

// Must be logged in — username now comes from the session, not the
// request body, so nobody can post to the feed under someone else's name.
$user = require_auth();

$body = json_decode(get_raw_body(), true);
$summary = trim(arr_get($body, 'summary_text', ''));
$volume  = (int)arr_get($body, 'volume_score', 0);

if ($summary === '') {
  http_response_code(400);
  echo json_encode(['error' => 'summary_text is required']);
  exit;
}
if (mb_strlen($summary) > 20000) $summary = mb_substr($summary, 0, 20000);
if ($volume < 0) $volume = 0;

try {
  $stmt = db()->prepare(
    'INSERT INTO workouts (user_id, username, summary_text, volume_score, created_at)
     VALUES (:uid, :username, :summary, :volume, NOW())'
  );
  $stmt->execute([
    ':uid'      => $user['id'],
    ':username' => $user['username'],
    ':summary'  => $summary,
    ':volume'   => $volume,
  ]);
  echo json_encode(['ok' => true, 'id' => db()->lastInsertId()]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Could not save workout']);
}
