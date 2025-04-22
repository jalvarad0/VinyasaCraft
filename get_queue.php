<?php
require 'config.php';
require_login();

// validate incoming ID
if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid queue ID.']);
    exit;
}

$qid    = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT queue_name, items
      FROM saved_queues
     WHERE _id = ? AND user_id = ?
");
$stmt->bind_param("ii", $qid, $userId);
$stmt->execute();
$stmt->bind_result($qname, $itemsJson);

if ($stmt->fetch()) {
    $items = json_decode($itemsJson, true);
    echo json_encode([
        'success'   => true,
        'queueName' => $qname,
        'queue'     => $items
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Queue not found.']);
}

$stmt->close();
$conn->close();