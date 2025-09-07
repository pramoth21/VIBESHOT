<?php

header('Content-Type: application/json');
session_start();

$threadId = (int)($_GET['threadId'] ?? 0);
$lastId   = (int)($_GET['lastId'] ?? 0);

if ($threadId <= 0) { echo json_encode(['ok'=>false,'error'=>'Bad thread']); exit; }

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB']); exit; }


$clientId = isset($_SESSION['client_id']) ? (int)$_SESSION['client_id'] : 0;
$photogId = isset($_SESSION['photographer_id']) ? (int)$_SESSION['photographer_id'] : 0;

$who = null;
$st = $conn->prepare("SELECT ClientID, PhotographerID FROM ChatThread WHERE ThreadID=?");
$st->bind_param("i",$threadId);
$st->execute();
$st->bind_result($tClient,$tPhotog);
if ($st->fetch()) {
  if ($clientId && $clientId === (int)$tClient) $who = 'Client';
  if ($photogId && $photogId === (int)$tPhotog) $who = 'Photographer';
}
$st->close();

if (!$who) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); $conn->close(); exit; }

// fetch messages
if ($lastId > 0) {
  $q = $conn->prepare("SELECT MessageID, SenderType, SenderID, Body, CreatedAt 
                       FROM ChatMessage 
                       WHERE ThreadID=? AND MessageID>? ORDER BY MessageID ASC");
  $q->bind_param("ii",$threadId,$lastId);
} else {
  $q = $conn->prepare("SELECT MessageID, SenderType, SenderID, Body, CreatedAt 
                       FROM ChatMessage 
                       WHERE ThreadID=? ORDER BY MessageID DESC LIMIT 50");
  $q->bind_param("i",$threadId);
}
$q->execute();
$res = $q->get_result();
$msgs = [];
while($r = $res->fetch_assoc()) $msgs[] = $r;
$q->close();
if ($lastId === 0) $msgs = array_reverse($msgs); 

// mark other side messages as read
$other = ($who === 'Client') ? 'Photographer' : 'Client';
$mr = $conn->prepare("UPDATE ChatMessage SET IsRead=1 WHERE ThreadID=? AND SenderType=?");
$mr->bind_param("is",$threadId,$other);
$mr->execute();
$mr->close();

$conn->close();
echo json_encode(['ok'=>true,'messages'=>$msgs]);
