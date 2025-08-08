<?php
require __DIR__ . '/config.php'; require_login();
if ($_SERVER['REQUEST_METHOD']!=='PUT'){ http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$d=json_input(); $email=sanitize_text($d['email']??'');
if ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)){ http_response_code(400); echo json_encode(['error'=>'Nieprawidłowy e-mail']); exit; }
$params=[]; $set=[]; if ($email!==''){ $set[]="email=?"; $params[]=$email; }
if (!$set){ echo json_encode(['success'=>true,'message'=>'Brak zmian']); exit; }
$params[]=$_SESSION['user_id']; $sql="UPDATE users SET ".implode(", ",$set)." WHERE id = ?"; $s=$pdo->prepare($sql); $s->execute($params);
echo json_encode(['success'=>true]);
