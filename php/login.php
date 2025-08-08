<?php
require __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST'){ http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$data=json_input(); $username=sanitize_text($data['username']??''); $password=$data['password']??'';
if ($username==='' || $password===''){ http_response_code(400); echo json_encode(['error'=>'Brak danych']); exit; }
$stmt=$pdo->prepare("SELECT id, username, password_hash, is_banned FROM users WHERE username=?"); $stmt->execute([$username]); $user=$stmt->fetch();
if (!$user || !password_verify($password,$user['password_hash'])){ http_response_code(401); echo json_encode(['error'=>'Błędny login lub hasło']); exit; }
if (intval($user['is_banned'])===1){ http_response_code(403); echo json_encode(['error'=>'Konto zablokowane']); exit; }
$_SESSION['user_id']=(int)$user['id']; echo json_encode(['success'=>true,'user'=>['id'=>(int)$user['id'],'username'=>$user['username']]]);
