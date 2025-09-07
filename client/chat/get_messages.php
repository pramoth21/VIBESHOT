<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$clientID = $_SESSION['client_id'];
$conn = new mysqli('localhost', 'root', '', 'vibeshot_db');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Find or create the chat thread for this client
$sqlThread = "SELECT ThreadID FROM client_admin_chat_threads WHERE ClientID = ?";
$stmtThread = $conn->prepare($sqlThread);
$stmtThread->bind_param("i", $clientID);
$stmtThread->execute();
$resultThread = $stmtThread->get_result();
$thread = $resultThread->fetch_assoc();
$threadID = $thread['ThreadID'] ?? null;

if (!$threadID) {
    // No thread found, so create a new one
    $sqlInsertThread = "INSERT INTO client_admin_chat_threads (ClientID) VALUES (?)";
    $stmtInsertThread = $conn->prepare($sqlInsertThread);
    $stmtInsertThread->bind_param("i", $clientID);
    $stmtInsertThread->execute();
    $threadID = $stmtInsertThread->insert_id;
}

$messages = [];
if ($threadID) {
    $sqlMessages = "SELECT SenderType, Body, CreatedAt FROM client_admin_messages WHERE ThreadID = ? ORDER BY CreatedAt ASC";
    $stmtMessages = $conn->prepare($sqlMessages);
    $stmtMessages->bind_param("i", $threadID);
    $stmtMessages->execute();
    $resultMessages = $stmtMessages->get_result();

    while ($row = $resultMessages->fetch_assoc()) {
        $messages[] = [
            'sender' => $row['SenderType'],
            'body' => htmlspecialchars($row['Body']),
            'time' => date('h:i A', strtotime($row['CreatedAt']))
        ];
    }
}

$conn->close();
echo json_encode(['threadID' => $threadID, 'messages' => $messages]);
?>