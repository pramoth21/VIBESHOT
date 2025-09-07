<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'vibeshot_db');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$threads = [];
$sql = "SELECT cact.ThreadID, cact.ClientID, c.Name AS ClientName, cact.LastMsgAt,
               (SELECT Body FROM client_admin_messages WHERE ThreadID = cact.ThreadID ORDER BY CreatedAt DESC LIMIT 1) AS LastMessage
        FROM client_admin_chat_threads cact
        JOIN client c ON c.ClientID = cact.ClientID
        ORDER BY cact.LastMsgAt DESC";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $threads[] = [
            'threadID' => $row['ThreadID'],
            'clientID' => $row['ClientID'],
            'clientName' => htmlspecialchars($row['ClientName']),
            'lastMsgAt' => date('M d, Y h:i A', strtotime($row['LastMsgAt'])),
            'lastMessage' => htmlspecialchars($row['LastMessage'])
        ];
    }
}

$conn->close();
echo json_encode(['threads' => $threads]);
?>