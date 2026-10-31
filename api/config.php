<?php
// InfinityFree MySQL credentials — from your control panel.
define('DB_HOST', 'sql312.infinityfree.com');
define('DB_NAME', 'if0_42182927_workout_data');
define('DB_USER', 'if0_42182927');
define('DB_PASS', 'Ruzgar123412002');

function db() {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO(
      "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
      DB_USER,
      DB_PASS,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
  }
  return $pdo;
}

// Reads the raw POST body once and caches it — php://input can't reliably
// be read more than once on every host, and now both the auth check and
// each endpoint's own body parsing need it.
function get_raw_body(){
  static $raw = null;
  if ($raw === null) {
    $raw = file_get_contents('php://input');
    if ($raw === false) $raw = '';
  }
  return $raw;
}

// Works on any PHP version (the ?? null-coalescing operator needs PHP 7+,
// which some free hosts don't default to) — use this everywhere instead.
function arr_get($arr, $key, $default = null){
  return (is_array($arr) && array_key_exists($key, $arr)) ? $arr[$key] : $default;
}

// Shared CORS + JSON headers for every endpoint.
function api_headers() {
  header('Content-Type: application/json');
  header('Access-Control-Allow-Origin: *'); // tighten to your domain once live
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, Authorization');
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
}
