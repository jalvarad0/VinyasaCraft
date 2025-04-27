<?php
    //Notes for self:
    // - https://medium.com/hackademia/how-to-make-a-php-gemini-ai-web-app-6b592ef64f9e -> Example of quick setup
    // -  https://github.com/google-gemini-php/client  -> examples
    // - https://github.com/gemini-api-php/client -> examples
    header('Content-Type: application/json');
    require_once 'config.php'; 
    $DEBUG = false;
    // Gemini helper function that will send the prompt over to gemini and check response
    function call_gemini_API($api_key, $prompt) {
        
        // We establish the url + package in the key 
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=' . $api_key;

        // We now format the data based on what gemini is expecting https://ai.google.dev/api/generate-content#v1beta.models.generateContent
        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ];

        // Lets encode into a json object
        $json_data = json_encode($data);

        // Lets now instantiate a cURL session + configure it which will allow us to send json to gemini
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Don't output, just return response
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data); // Lets feed our json_data
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // Lets set content type so that it knows its json
        $response = curl_exec($ch); // Execute the request and store response

        // Check for errors
        if (curl_errno($ch)) {
            return ['error' => curl_error($ch)];
        }

        // Lets now check for HTTP errors
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); // Exit gracefully

        if ($status_code != 200) {
            return ['error' => 'Gemini API call failed with status ' . $status_code];
        }

        // We successfully got a response, lets return.
        return json_decode($response, true);
    }

// Alright, lets read the URL query string and make decision based off of that
$endpoint = $_GET['endpoint'] ?? '';

// Case where endpoint it options and we need to query for supported poses and
// send it to our main page
if ($endpoint === 'options') {
    $sql = "SELECT english_name, targets FROM supported_postures";
    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode(['error' => 'Failed to fetch options']);
        exit();
    }

    // Lets store what we got back from our query in arrays
    $english_names = [];
    $targets_set = [];

    // Lets parse out the results and store them in our arrays
    if ($DEBUG) print_r($result->fetch_assoc());
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['english_name'])) { 
            $english_names[] = $row['english_name'];
        }
        if (!empty($row['targets'])) {
            foreach (explode(',', $row['targets']) as $target) {
                $targets_set[trim($target)] = true;
            }
        }
    }

    // Lets encode them bad boys and send it back over to the frontend
    echo json_encode([
        'english_names' => $english_names,
        'targets' => array_keys($targets_set)
    ]);
    exit();
}

// Case where endpoint is generate-sequence; in which case we want to talk to gemini
if ($endpoint === 'generate-sequence') {

    // Lets start off by grabbing what was sent to us
    $input = json_decode(file_get_contents('php://input'), true);
    $names = $input['names'] ?? [];
    $targets = $input['targets'] ?? [];
    $queue_name = $input['queue_name'] ?? '';
    if ($DEBUG) print_r($input);
    if ($DEBUG) print_r($names);
    if ($DEBUG) print_r($targets);
    if ($DEBUG) echo $queue_name;

    // Are we missing anything? If so, lets just go back
    if ((!$names && !$targets) || !$queue_name) {
        echo json_encode(['error' => 'Missing input']);
        exit();
    }

    // Lets construct our SQL query
    $conditions = [];
    $params = [];
    $types = '';

    // Based off of the selected checkboxes, lets build our query
    if (!empty($names)) {
        $placeholders = implode(',', array_fill(0, count($names), '?'));
        $conditions[] = "english_name IN ($placeholders)";
        $params = array_merge($params, $names);
        $types .= str_repeat('s', count($names));
    }

    // If they selected targets, lets add that to our query string.
    if (!empty($targets)) {
        foreach ($targets as $target) {
            $conditions[] = "targets LIKE ?";
            $params[] = "%$target%";
            $types .= 's';
        }
    }

    // Now that we have everything we need, lets package up the query and execute
    $sql = "SELECT english_name FROM supported_postures WHERE " . implode(' OR ', $conditions);
    if ($DEBUG) echo $sql;
    $stmt = $conn->prepare($sql);

    // Defensive programming
    if (!$stmt) {
        echo json_encode(['error' => 'Failed to prepare SQL']);
        exit();
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result(); // Lets execute that thang

    // Lets start grab all of the poses that met our checkboxes and store them in an array
    // so that we can use it for gemini query
    $pose_list = [];
    while ($row = $result->fetch_assoc()) {
        $pose_list[] = $row['english_name'];
    }

    if (empty($pose_list)) {
        echo json_encode(['error' => 'No poses found']);
        exit();
    }
    if ($DEBUG) print_r($pose_list);

    // Convert it into a string for injection 
    $pose_list_string = implode(', ', $pose_list);
    if ($DEBUG) echo $pose_list_string; 

    $prompt = "Create a yoga sequence only using the following poses: $pose_list_string.
    The sequence should start and end with Corpse Pose.
    Return it in CSV format with columns: Pose Name, Duration (seconds).
    Please ensure all text is lowercase. Do not include anything other than what I have
    asked for.";

    if ($DEBUG) echo $prompt; 

    $gemini_response = call_gemini_API($api_key, $prompt);

    // Did we error out?
    if (isset($gemini_response['error'])) {
        echo json_encode(['error' => 'Gemini API error: ' . $gemini_response['error']]);
        exit();
    }

    // Lets parse out what gemini returned. Empty string if nothing.
    $gemini_result = $gemini_response['candidates'][0]['content']['parts'][0]['text'] ?? '';

    if (empty($gemini_result)) {
        echo json_encode(['error' => 'Empty Gemini response']);
        exit();
    }

    // Parse the CSV response we asked for into poses that we can save in our db
    $lines = explode("\n", trim($gemini_result));
    $pose_lines = array_slice($lines, 1);
    $pose_names = [];

    // Lets grab each one iteratively
    foreach ($pose_lines as $line) {
        $columns = explode(',', $line);
        if (!empty($columns[0])) {
            $pose_names[] = trim(strtolower($columns[0]));
        }
    }
    if ($DEBUG) echo json_encode($pose_names);

    // Yessir, encode and send off to caller!
    echo json_encode([
        'gemini' => $gemini_result,
        'pose_names' => $pose_names
    ]);
    exit();
}

// Case where we want to save a sequence to db
if ($endpoint === 'save-sequence') {
    // Lets grab the incoming post data from gemini_handler
    $input = json_decode(file_get_contents('php://input'), true);
    $queue_name = $input['queue_name'] ?? '';
    $pose_names = $input['pose_names'] ?? [];

    // Did the user try sending us an empty flow or one without a name??? I guard against
    // this already but two checks is better than no checks.
    if (!$queue_name || empty($pose_names)) {
        echo json_encode(['error' => 'Missing queue name or poses']);
        exit();
    }
    
    if($DEBUG) echo $queue_name; 
    if($DEBUG) echo $pose_names; 
    // Lets timestamp when this query was made
    $createdAt = date('Y-m-d H:i:s');
    $userId = 1; // TODO BUG: Juan this needs to be updated with session id

    // Alright, lets create the sql statement
    $sql = "INSERT INTO saved_queues (queue_name, items, created_at, user_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode(['error' => 'SQL prepare failed']);
        exit();
    }

    // Lets encode the poses into a json variable
    $items_json = json_encode($pose_names);
    $stmt->bind_param('sssi', $queue_name, $items_json, $createdAt, $userId);


    // Lets now try executing the statement against our db
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Failed to save flow']);
    }
    exit();
}

// Default if no valid endpoint
http_response_code(404);
echo json_encode(['error' => 'Unknown endpoint']);
?>