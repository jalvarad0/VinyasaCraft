<?php
// db connection protocols
$servername = "localhost";
$username = "udvnhgd3sliun";
$password = "32w$)kA$(1x6";
$dbname   = "dbjvgekqfezksk";

// retrieve POST JSON data
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// validate req'd fields provided
if (!isset($data['queue']) || !isset($data['queueName'])) {
    echo json_encode(['success' => false, 'message' => 'Missing queue data or queue name.']);
    exit;
}

// prep data: 
// - $queueName: the name provided by the user for saved queue
// - $queueItems: an array of pose names (preserves ordering)
$queueName = $data['queueName'];
$queueItems = $data['queue'];

// encode the queue items array as JSON
$encodedItems = json_encode($queueItems);

// create db connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

//   "saved_queues" table with columns:
//   id (INT AUTO_INCREMENT PRIMARY KEY)
//   queue_name (VARCHAR or TEXT)
//   items (TEXT)
//   created_at (TIMESTAMP DEFAULT CURRENT_TIMESTAMP)
$stmt = $conn->prepare("INSERT INTO saved_queues (queue_name, items) VALUES (?, ?)");
$stmt->bind_param("ss", $queueName, $encodedItems);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Queue saved successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error saving queue.']);
}

$stmt->close();
$conn->close();
?>