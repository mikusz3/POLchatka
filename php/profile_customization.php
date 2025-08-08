<?php
require __DIR__ . '/config.php'; require_login(); $uid=$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='GET'){ $s=$pdo->prepare("SELECT theme_color, background_url, about_me, links_json FROM profile_customization WHERE user_id=?"); $s->execute([$uid]); $row=$s->fetch(); echo json_encode(['customization'=>$row?:['theme_color'=>null,'background_url'=>null,'about_me'=>null,'links_json'=>'[]']]); exit; }
if ($_SERVER['REQUEST_METHOD']==='PUT'){ $d=json_input(); $theme=sanitize_text($d['theme_color']??''); $bg=sanitize_text($d['background_url']??''); $about=sanitize_text($d['about_me']??''); $links=json_encode($d['links']??[]);
 $s=$pdo->prepare("INSERT INTO profile_customization (user_id, theme_color, background_url, about_me, links_json) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE theme_color=VALUES(theme_color), background_url=VALUES(background_url), about_me=VALUES(about_me), links_json=VALUES(links_json)"); $s->execute([$uid,$theme,$bg,$about,$links]); echo json_encode(['success'=>true]); exit; }
http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
