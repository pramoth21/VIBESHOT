<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) || !isset($_GET['thread_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$threadID = $_GET['thread_id'];
$conn = new mysqli('localhost', 'root', '', 'vibeshot_db');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$messages = [];
$sql = "SELECT SenderType, Body, CreatedAt FROM client_admin_messages WHERE ThreadID = ? ORDER BY CreatedAt ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $threadID);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'sender' => $row['SenderType'],
        'body' => htmlspecialchars($row['Body']),
        'time' => date('h:i A', strtotime($row['CreatedAt']))
    ];
}

$conn->close();
echo json_encode(['messages' => $messages]);
?>