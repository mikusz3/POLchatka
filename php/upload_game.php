<?php
require __DIR__ . '/config.php'; require_login();
$MAX_SIZE=150*1024*1024; $ALLOWED=['zip','7z','swf'];
if ($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
if (!isset($_FILES['file'])){ http_response_code(400); echo json_encode(['error'=>'Brak pliku']); exit; }
$title=sanitize_text($_POST['title']??''); $description=sanitize_text($_POST['description']??''); $category=sanitize_text($_POST['category']??'inne'); $tags=isset($_POST['tags'])?explode(',',sanitize_text($_POST['tags'])):[];
$f=$_FILES['file']; if($f['error']!==UPLOAD_ERR_OK){ http_response_code(400); echo json_encode(['error'=>'Błąd uploadu']); exit; } if($f['size']>$MAX_SIZE){ http_response_code(413); echo json_encode(['error'=>'Plik za duży']); exit; }
$ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION)); if(!in_array($ext,$ALLOWED,true)){ http_response_code(400); echo json_encode(['error'=>'Niedozwolone rozszerzenie']); exit; }
$slug=strtolower(preg_replace('/[^a-z0-9\-]+/i','-',$title)); $slug=trim($slug,'-')?:'gra-'.time();
$destDir=__DIR__ . '/uploads/games/' . $slug; if(!is_dir($destDir) && !mkdir($destDir,0755,true)){ http_response_code(500); echo json_encode(['error'=>'Błąd tworzenia katalogu']); exit; }
$destPath=$destDir.'/'.basename($f['name']); if(!move_uploaded_file($f['tmp_name'],$destPath)){ http_response_code(500); echo json_encode(['error'=>'Błąd zapisu pliku']); exit; }
$stmt=$pdo->prepare("INSERT INTO games (user_id,title,slug,description,category,file_ext) VALUES (?,?,?,?,?,?)"); $stmt->execute([$_SESSION['user_id'],$title,$slug,$description,$category,$ext]); $game_id=$pdo->lastInsertId();
if(!empty($tags)){ foreach($tags as $t){ $t=strtolower(trim($t)); if($t==='')continue; $pdo->prepare("INSERT IGNORE INTO game_tags (tag) VALUES (?)")->execute([$t]); $tag_id=$pdo->lastInsertId(); if(!$tag_id){ $r=$pdo->prepare("SELECT id FROM game_tags WHERE tag=?"); $r->execute([$t]); $tag_id=$r->fetch()['id']??null; } if($tag_id){ $pdo->prepare("INSERT IGNORE INTO game_tag_map (game_id,tag_id) VALUES (?,?)")->execute([$game_id,$tag_id]); } } }
echo json_encode(['success'=>true,'id'=>(int)$game_id,'slug'=>$slug]);
