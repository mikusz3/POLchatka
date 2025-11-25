<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>isset($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); session_start();
}
$DB_HOST = getenv('POLCHATKA_DB_HOST') ?: 'localhost';
$DB_NAME = getenv('POLCHATKA_DB_NAME') ?: 'polchatka_db';
$DB_USER = getenv('POLCHATKA_DB_USER') ?: 'root';
$DB_PASS = getenv('POLCHATKA_DB_PASS') ?: '';
try { $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); }
catch (PDOException $e) { http_response_code(500); echo json_encode(['error'=>'Błąd połączenia z bazą']); exit; }
function json_input(){ $raw=file_get_contents('php://input'); if(!$raw)return []; $d=json_decode($raw,true); return is_array($d)?$d:[]; }
function is_json_request(): bool { return isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false; }
function append_query(string $location, array $params): string { $query=http_build_query($params); if($query===''){return $location;} return $location.(str_contains($location,'?')?'&':'?').$query; }
function flash_message(string $key, string $message): void { $_SESSION[$key]=$message; }
function respond_error(string $message,int $status=400,?string $redirect=null,array $extra=[]): void {
    if ($redirect !== null && !is_json_request()) {
        flash_message('flash_error',$message);
        header('Location: '.append_query($redirect,['error'=>$message]));
        exit;
    }
    http_response_code($status);
    echo json_encode(array_merge(['error'=>$message],$extra));
    exit;
}
function respond_success(array $payload=[],?string $redirect=null): void {
    if ($redirect !== null && !is_json_request()) {
        flash_message('flash_success',$payload['message'] ?? 'OK');
        header('Location: '.append_query($redirect,['success'=>$payload['message'] ?? '1']));
        exit;
    }
    echo json_encode($payload === [] ? ['success'=>true] : $payload);
    exit;
}
function require_login(){ if(!isset($_SESSION['user_id'])){ http_response_code(401); echo json_encode(['error'=>'Nie zalogowano']); exit; } }
function require_admin(PDO $pdo){ require_login(); $s=$pdo->prepare("SELECT is_admin FROM users WHERE id=?"); $s->execute([$_SESSION['user_id']]); $r=$s->fetch(); if(!$r || intval($r['is_admin'])!==1){ http_response_code(403); echo json_encode(['error'=>'Brak uprawnień']); exit; } }
function sanitize_text($s){ $s=trim($s ?? ''); $s=preg_replace('/[\x00-\x1F\x7F]/u','',$s); return $s; }
