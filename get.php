<?php

require 'database_info.php'; 

if (strcasecmp($_SERVER["REQUEST_METHOD"], 'GET') == 0) {

    // Connect and select DB in one go
    $conn = mysqli_connect($host, $user, $pass, $db);

    if (!$conn) {
        die("Could not connect: " . mysqli_connect_error());
    }

    // echo "Connected succesfully get <br/>";

    $query = "SELECT * FROM requests WHERE username='aioann19' ORDER BY timestamp DESC LIMIT 5;";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Invalid query: " . mysqli_error($conn));
    }

    $users = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    mysqli_close($conn);

    echo json_encode($users);
}
?>
