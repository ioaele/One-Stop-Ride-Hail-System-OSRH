<?php

require 'connect.php';  

header('Content-Type: application/json');

$json = trim(file_get_contents("php://input"));
$data = json_decode($json);

if ($data === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}


$last_name        = $data->last_name         ;
$first_name       = $data->first_name        ;
$gender           = $data->gender            ;
$datebirth        = $data->datebirth         ;
$username         = $data->username        ;
$password_plain   = $data->password     ;
$confirm_password = $data->confirm_password ;
$email            = $data->email            ;
$phone_number     = $data->phone_number     ;
$country          = $data->country      ;
$city             = $data->city         ;
$post_code        = $data->post_code       ;
$street           = $data->street       ;
$number           = $data->number ;



if ($password_plain !== $confirm_password) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Passwords do not match.',
        'debug'   => ['password' => $password_plain, 'confirm' => $confirm_password]
    ]);
    exit;
}

$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);


$sql = "{CALL [eioann09].[SignUpUser](?,?,?,?,?,?,?,?,?,?,?,?,?)}";

try {
    $db   = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(1,  $last_name,       PDO::PARAM_STR);
    $stmt->bindParam(2,  $first_name,      PDO::PARAM_STR);
    $stmt->bindParam(3,  $gender,          PDO::PARAM_STR);
    $stmt->bindParam(4,  $password_hashed, PDO::PARAM_STR);
    $stmt->bindParam(5,  $datebirth,       PDO::PARAM_STR); 
    $stmt->bindParam(6,  $post_code,       PDO::PARAM_STR);
    $stmt->bindParam(7,  $city,            PDO::PARAM_STR);
    $stmt->bindParam(8,  $number,          PDO::PARAM_STR);
    $stmt->bindParam(9,  $street,          PDO::PARAM_STR);
    $stmt->bindParam(10, $country,         PDO::PARAM_STR);
    $stmt->bindParam(11, $username,        PDO::PARAM_STR);
    $stmt->bindParam(12, $phone_number,    PDO::PARAM_STR);
    $stmt->bindParam(13, $email,           PDO::PARAM_STR);


    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $users_id = $row['users_id'] ?? null;

    if ($users_id === null) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'A user with this email / username / phone number already exists.'
        ]);
    } else {
        echo json_encode([
            'status'   => 'success',
            'message'  => "Signup successful!",
            'users_id' => $users_id
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Unexpected error while signing up. Please try again later.'
    ]);
}
