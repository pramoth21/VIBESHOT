<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) || !isset($_POST['thread_id']) || !isset($_POST['body'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$adminID = $_SESSION['admin_id'];
$threadID = $_POST['thread_id'];
$body = $_POST['body'];

$conn = new mysqli('localhost', 'root', '', 'vibeshot_db');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$sqlInsert = "INSERT INTO client_admin_messages (ThreadID, SenderType, SenderID, Body) VALUES (?, 'Admin', ?, ?)";
$stmtInsert = $conn->prepare($sqlInsert);
$stmtInsert->bind_param("iis", $threadID, $adminID, $body);

if ($stmtInsert->execute()) {
    // Update the LastMsgAt time in the thread
    $sqlUpdateThread = "UPDATE client_admin_chat_threads SET LastMsgAt = CURRENT_TIMESTAMP() WHERE ThreadID = ?";
    $stmtUpdateThread = $conn->prepare($sqlUpdateThread);
    $stmtUpdateThread->bind_param("i", $threadID);
    $stmtUpdateThread->execute();

    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message']);
}

$conn->close();
?>