<?php
require 'config.php';
require_login();

header('Content-Type: application/json'); // always respond with JSON

$input = file_get_contents("php://input");
$data  = json_decode($input, true);

if (empty($data['queueId'])) {
    echo json_encode(['success'=>false, 'message'=>'Missing queue ID.']);
    exit;
}

$queueId = (int) $data['queueId'];
$userId  = $_SESSION['user_id'];

// Ensure the queue belongs to the current user
$stmt = $conn->prepare("
    DELETE FROM saved_queues
    WHERE _id = ? AND user_id = ?
");
$stmt->bind_param("ii", $queueId, $userId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success'=>true, 'message'=>'Queue deleted.']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Queue not found or not yours.']);
    }
} else {
    echo json_encode(['success'=>false, 'message'=>'Database error: '.$conn->error]);
}
