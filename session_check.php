
<?php
session_start();
header("Content-Type: application/json");

if (!isset($_SESSION["student_id"])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Please login first"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "name" => $_SESSION["student_name"],
    "email" => $_SESSION["student_email"]
]);
?>