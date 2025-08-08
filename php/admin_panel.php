<?php
require __DIR__ . '/config.php'; require_admin($pdo);
if ($_SERVER['REQUEST_METHOD']==='GET'){ $u=$pdo->query("SELECT id,username,email,is_admin,is_banned,created_at FROM users ORDER BY id DESC LIMIT 200")->fetchAll(); echo json_encode(['users'=>$u]); exit; }
if ($_SERVER['REQUEST_METHOD']==='PUT'){
 $d=json_input(); $uid=intval($d['user_id']??0); $action=sanitize_text($d['action']??''); if($uid<=0){ http_response_code(400); echo json_encode(['error'=>'Brak user_id']); exit; }
 if($action==='ban'){ $pdo->prepare("UPDATE users SET is_banned=1 WHERE id=?")->execute([$uid]); echo json_encode(['success'=>true,'action'=>'ban']); exit; }
 if($action==='unban'){ $pdo->prepare("UPDATE users SET is_banned=0 WHERE id=?")->execute([$uid]); echo json_encode(['success'=>true,'action'=>'unban']); exit; }
 if($action==='make_admin'){ $pdo->prepare("UPDATE users SET is_admin=1 WHERE id=?")->execute([$uid]); echo json_encode(['success'=>true,'action'=>'make_admin']); exit; }
 if($action==='remove_admin'){ $pdo->prepare("UPDATE users SET is_admin=0 WHERE id=?")->execute([$uid]); echo json_encode(['success'=>true,'action'=>'remove_admin']); exit; }
 http_response_code(400); echo json_encode(['error'=>'Nieznana akcja']); exit;
}
http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
