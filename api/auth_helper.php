<?php
// Include after config.php. Provides token-based auth shared by every
// endpoint that needs to know "who is this request from".
// Written to run on PHP 5.6+ as well as modern PHP (no ??, no random_bytes-only).

function generate_token(){
  if (function_exists('random_bytes')) {
    return bin2hex(random_bytes(32)); // 64 hex chars, matches sessions.token CHAR(64)
  }
  return bin2hex(openssl_random_pseudo_bytes(32));
}

function get_bearer_token(){
  $headers = function_exists('getallheaders') ? getallheaders() : array();
  $auth = arr_get($headers, 'Authorization', arr_get($headers, 'authorization', ''));
  if (!$auth) {
    // On CGI/FastCGI PHP (common on shared hosts), the Authorization header
    // doesn't reach PHP by default — it has to be forwarded by an
    // .htaccess rewrite rule, which then exposes it under one of these
    // two names instead of a normal header.
    $auth = arr_get($_SERVER, 'HTTP_AUTHORIZATION', arr_get($_SERVER, 'REDIRECT_HTTP_AUTHORIZATION', ''));
  }
  if ($auth && preg_match('/Bearer\s+(\S+)/i', $auth, $m)) return $m[1];

  // Fallback #1: token as a query string param (?token=...). Used by GET
  // requests as a safety net on hosts that strip the header entirely.
  $qs = arr_get($_GET, 'token', '');
  if ($qs) return $qs;

  // Fallback #2: token in the JSON request body. Used by POST requests.
  // Safe to read php://input here even though some endpoints also read it
  // for their own data — it's a plain (non-multipart) JSON body, which PHP
  // allows reading more than once.
  $raw = get_raw_body();
  if ($raw) {
    $decoded = json_decode($raw, true);
    $bodyToken = arr_get($decoded, 'token', '');
    if ($bodyToken) return $bodyToken;
  }

  return null;
}

// Returns array('id'=>.., 'username'=>..) for a valid, unexpired session token, or null.
function current_user(){
  $token = get_bearer_token();
  if (!$token) return null;
  try{
    $stmt = db()->prepare(
      'SELECT u.id, u.username FROM sessions s
       JOIN users u ON u.id = s.user_id
       WHERE s.token = :token AND s.expires_at > NOW()'
    );
    $stmt->execute(array(':token' => $token));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : null;
  }catch(PDOException $e){
    return null;
  }
}

// Call at the top of any endpoint that requires a logged-in user.
// Exits the request with 401 automatically if there's no valid session.
function require_auth(){
  $user = current_user();
  if (!$user) {
    http_response_code(401);
    echo json_encode(array('error' => 'Not authenticated'));
    exit;
  }
  return $user;
}
