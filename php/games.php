<?php
require __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD']!=='GET'){ http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }
$id=isset($_GET['id'])?intval($_GET['id']):0; $slug=sanitize_text($_GET['slug']??'');
if ($id>0 || $slug!==''){
 if ($id>0){ $s=$pdo->prepare("SELECT g.*,u.username FROM games g JOIN users u ON u.id=g.user_id WHERE g.id=?"); $s->execute([$id]); }
 else { $s=$pdo->prepare("SELECT g.*,u.username FROM games g JOIN users u ON u.id=g.user_id WHERE g.slug=?"); $s->execute([$slug]); }
 $game=$s->fetch(); if(!$game){ http_response_code(404); echo json_encode(['error'=>'Nie znaleziono gry']); exit; }
 $t=$pdo->prepare("SELECT t.tag FROM game_tag_map m JOIN game_tags t ON t.id=m.tag_id WHERE m.game_id=?"); $t->execute([$game['id']]); $game['tags']=array_column($t->fetchAll(),'tag');
 echo json_encode(['game'=>$game]); exit;
}
$q=sanitize_text($_GET['q']??''); $category=sanitize_text($_GET['category']??''); $tag=sanitize_text($_GET['tag']??''); $page=max(1,intval($_GET['page']??1)); $per=min(50,max(1,intval($_GET['per_page']??12))); $off=($page-1)*$per;
$where=[]; $params=[];
if($q!==''){ $where[]="(g.title LIKE ? OR g.description LIKE ?)"; $params[]="%".$q."%"; $params[]="%".$q."%"; }
if($category!==''){ $where[]="g.category = ?"; $params[]=$category; }
if($tag!==''){ $where[]="EXISTS (SELECT 1 FROM game_tag_map m JOIN game_tags t ON t.id=m.tag_id WHERE m.game_id=g.id AND t.tag = ?)"; $params[]=strtolower($tag); }
$sqlWhere = $where ? ("WHERE ".implode(" AND ", $where)) : "";
$total=$pdo->prepare("SELECT COUNT(*) AS c FROM games g $sqlWhere"); $total->execute($params); $count=intval($total->fetch()['c']??0);
$stmt=$pdo->prepare("SELECT g.id,g.title,g.slug,g.description,g.category,g.file_ext,g.created_at,u.username FROM games g JOIN users u ON u.id=g.user_id $sqlWhere ORDER BY g.created_at DESC LIMIT $per OFFSET $off"); $stmt->execute($params); $rows=$stmt->fetchAll();
echo json_encode(['total'=>$count,'page'=>$page,'per_page'=>$per,'games'=>$rows]);
