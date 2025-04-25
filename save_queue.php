<?php
require 'config.php';
require_login();

// read JSON
$input = file_get_contents("php://input");
$data  = json_decode($input, true);

if (empty($data['queue']) || empty($data['queueName'])) {
    echo json_encode(['success'=>false,'message'=>'Missing queue name or items.']);
    exit;
}

$userId    = $_SESSION['user_id'];
$queueName = trim($data['queueName']);
$items     = $data['queue'];
$itemsJson = json_encode($items);

// prep insert, relying on UNIQUE(user_id, queue_name) in schema
$stmt = $conn->prepare("
    INSERT INTO saved_queues (user_id, queue_name, items)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE items = VALUES(items)
");
$stmt->bind_param("iss", $userId, $queueName, $itemsJson);

if ($stmt->execute()) {
    echo json_encode(['success'=>true,'message'=>'Queue saved successfully.']);
} else {
    // duplicate name for this user?
    if ($conn->errno === 1062) {
        echo json_encode(['success'=>false,'message'=>'You already have a queue named “'.htmlspecialchars($queueName).'”. Please choose another name.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]);
    }
}