<?php
require __DIR__ . '/config.php'; require_login(); $uid=$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='GET'){ $s=$pdo->prepare("SELECT id,username,email,created_at FROM users WHERE id=?"); $s->execute([$uid]); echo json_encode(['user'=>$s->fetch()]); exit; }
http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
