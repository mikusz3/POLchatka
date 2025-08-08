<?php
require __DIR__ . '/config.php'; require_login(); $uid=$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='GET'){
 $friends=$pdo->prepare("SELECT u.id,u.username FROM friendships f JOIN users u ON u.id=IF(f.user_id=?, f.friend_id, f.user_id) WHERE (f.user_id=? OR f.friend_id=?) AND f.status='accepted'"); $friends->execute([$uid,$uid,$uid]);
 $inv=$pdo->prepare("SELECT f.id,u.username AS from_user,f.status FROM friendships f JOIN users u ON u.id=f.user_id WHERE f.friend_id=? AND f.status='pending'"); $inv->execute([$uid]);
 echo json_encode(['friends'=>$friends->fetchAll(),'invites'=>$inv->fetchAll()]); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST'){
 $d=json_input(); $to=intval($d['to_user_id']??0); if($to<=0||$to===$uid){ http_response_code(400); echo json_encode(['error'=>'Nieprawidłowy użytkownik']); exit; }
 $s=$pdo->prepare("SELECT id FROM friendships WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)"); $s->execute([$uid,$to,$to,$uid]); if($s->fetch()){ http_response_code(409); echo json_encode(['error'=>'Relacja już istnieje']); exit; }
 $pdo->prepare("INSERT INTO friendships (user_id,friend_id,status) VALUES (?,?, 'pending')")->execute([$uid,$to]); echo json_encode(['success'=>true]); exit;
}
if ($_SERVER['REQUEST_METHOD']==='PUT'){
 $d=json_input(); $rid=intval($d['request_id']??0); $action=sanitize_text($d['action']??''); $s=$pdo->prepare("SELECT id,user_id,friend_id,status FROM friendships WHERE id=? AND friend_id=?"); $s->execute([$rid,$uid]); $row=$s->fetch();
 if(!$row || $row['status']!=='pending'){ http_response_code(404); echo json_encode(['error'=>'Nie znaleziono zaproszenia']); exit; }
 if ($action==='accept'){ $pdo->prepare("UPDATE friendships SET status='accepted', accepted_at=NOW() WHERE id=?")->execute([$rid]); echo json_encode(['success'=>true,'status'=>'accepted']); exit; }
 if ($action==='reject'){ $pdo->prepare("DELETE FROM friendships WHERE id=?")->execute([$rid]); echo json_encode(['success'=>true,'status'=>'rejected']); exit; }
 http_response_code(400); echo json_encode(['error'=>'Nieznana akcja']); exit;
}
http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
