<?php

require 'database_info.php';

if (strcasecmp($_SERVER["REQUEST_METHOD"], 'POST') == 0) {

    // CONTENT_TYPE can be: "application/json; charset=utf-8"
    if (isset($_SERVER["CONTENT_TYPE"]) &&
        stripos($_SERVER["CONTENT_TYPE"], "application/json") === 0) {

        $json = trim(file_get_contents("php://input"));
        $data = json_decode($json);

        if ($data === null) {
            http_response_code(400);
            echo "Invalid JSON";
            exit;
        }

        echo $data->address . "\n";
        echo $data->region . "\n";
        echo $data->city . "\n";
        echo $data->username . "\n";

        $dti = time();
        echo $dti . "\n";

        // Connect directly with DB name
        $conn = mysqli_connect($host, $user, $pass, $db);

        if (!$conn) {
            die("Could not connect: " . mysqli_connect_error());
        }
        // echo "Connected succesfully post<br/>";

        // SAFER: use prepared statement to avoid SQL injection
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO requests (username, timestamp, address, region, city, country)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $country = "None";

        mysqli_stmt_bind_param(
            $stmt,
            "sissss",
            $data->username,
            $dti,
            $data->address,
            $data->region,
            $data->city,   // (removed extra space you had before `' $data->city'`)
            $country
        );

        if (!mysqli_stmt_execute($stmt)) {
            die("Invalid query: " . mysqli_error($conn));
        }

        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        echo "OK";
    } else {
        http_response_code(415);
        echo "Content-Type must be application/json";
    }
}
?>
