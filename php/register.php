<?php
require __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST'){ http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$data=json_input(); $username=sanitize_text($data['username']??''); $email=sanitize_text($data['email']??''); $password=$data['password']??'';
if ($username===''||$email===''||$password===''){ http_response_code(400); echo json_encode(['error'=>'Brak wymaganych danych']); exit; }
if (!filter_var($email,FILTER_VALIDATE_EMAIL)){ http_response_code(400); echo json_encode(['error'=>'Nieprawidłowy e-mail']); exit; }
$stmt=$pdo->prepare("SELECT id FROM users WHERE username=? OR email=?"); $stmt->execute([$username,$email]); if ($stmt->fetch()){ http_response_code(409); echo json_encode(['error'=>'Użytkownik już istnieje']); exit; }
$hash=password_hash($password,PASSWORD_DEFAULT); $stmt=$pdo->prepare("INSERT INTO users (username,email,password_hash) VALUES (?,?,?)"); $stmt->execute([$username,$email,$hash]);
echo json_encode(['success'=>true]);
