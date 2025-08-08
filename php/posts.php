<?php
require __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD']==='GET'){ $s=$pdo->query("SELECT p.id,p.content,p.created_at,u.username FROM posts p JOIN users u ON u.id=p.user_id ORDER BY p.created_at DESC LIMIT 100"); echo json_encode(['posts'=>$s->fetchAll()]); exit; }
if ($_SERVER['REQUEST_METHOD']==='POST'){ require_login(); $d=json_input(); $content=sanitize_text($d['content']??''); if($content===''){ http_response_code(400); echo json_encode(['error'=>'Pusta treść']); exit; } $s=$pdo->prepare("INSERT INTO posts (user_id,content) VALUES (?,?)"); $s->execute([$_SESSION['user_id'],$content]); echo json_encode(['success'=>True,'id'=>$pdo->lastInsertId()]); exit; }
http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
