<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starter Webpage</title>
    <link rel="stylesheet" href="css/stylesheet.css"> 
</head>
<body>
    <h1>Choose your workout:</h1>
    
    <!-- This is form used for choosing workout (still requires mechanism for getting new things) -->
    <form action="add_to_db()" method="post">
        <input type="checkbox" id="1" name="exercise[]" value="1">
        <label for="1"> exercise 1</label><br>
        <input type="checkbox" id="2" name="exercise[]" value="2">
        <label for="2"> exercise 2</label><br>
        <input type="checkbox" id="3" name="exercise[]" value="3">
        <label for="3"> exercise 3</label><br><br>
        <input type="submit" onclick="" value="Submit">
    </form>

    <?php
        echo "hello";

        // Database connection variables
        $server = "localhost";
        $userid = "u7ftyvupyb1gy";
        $pwd = "wli94jywotts";
        $db = "dbgvuprl9q4a61";

        // Connect to server
        $conn = new mysqli($server, $userid, $pwd);
        if ($conn->connect_error) {
            die("<p>Connection failed: " . $conn->connect_error . "</p>");
        }

        // Select database and run query
        if (!$conn->select_db($db)) {
            die("<p>Database selection failed.</p>");
        }

        // get post, check to make sure it isnt empty
        if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['genres'])) {
            $genres = $_POST['genres'];
            $placeholders = "'" . implode("','", $genres) . "'";}
    ?>

    <script src="js/script.js"></script> <!-- link to JavaScript -->
</body>
</html>