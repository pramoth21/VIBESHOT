<?php

header('Content-Type: application/json');
session_start();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];

$threadId = (int)($data['threadId'] ?? 0);
$body     = trim((string)($data['body'] ?? ''));

if ($threadId <= 0 || $body === '') { echo json_encode(['ok'=>false,'error'=>'Missing']); exit; }

$conn = new mysqli('localhost','root','','vibeshot_db');
if ($conn->connect_error) { echo json_encode(['ok'=>false,'error'=>'DB']); exit; }

$clientId = isset($_SESSION['client_id']) ? (int)$_SESSION['client_id'] : 0;
$photogId = isset($_SESSION['photographer_id']) ? (int)$_SESSION['photographer_id'] : 0;
$who = null; $senderId = 0;

$st = $conn->prepare("SELECT ClientID, PhotographerID FROM ChatThread WHERE ThreadID=?");
$st->bind_param("i",$threadId);
$st->execute();
$st->bind_result($tClient,$tPhotog);
if ($st->fetch()) {
  if ($clientId && $clientId === (int)$tClient) { $who = 'Client'; $senderId = $clientId; }
  if ($photogId && $photogId === (int)$tPhotog) { $who = 'Photographer'; $senderId = $photogId; }
}
$st->close();

if (!$who) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); $conn->close(); exit; }

// insert message
$stmt = $conn->prepare("INSERT INTO ChatMessage (ThreadID, SenderType, SenderID, Body) VALUES (?,?,?,?)");
$stmt->bind_param("isis", $threadId, $who, $senderId, $body);
if (!$stmt->execute()) { echo json_encode(['ok'=>false,'error'=>'Insert failed']); $stmt->close(); $conn->close(); exit; }
$messageId = $stmt->insert_id;
$stmt->close();

// bump thread
$upd = $conn->prepare("UPDATE ChatThread SET LastMsgAt=CURRENT_TIMESTAMP WHERE ThreadID=?");
$upd->bind_param("i",$threadId); $upd->execute(); $upd->close();

$conn->close();
echo json_encode(['ok'=>true,'id'=>$messageId]);
