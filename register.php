
<?php
require "db.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "POST required"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$phone = trim($data["phone"] ?? "");
$password = $data["password"] ?? "";

// Validate details
if (
    $name === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match("/^[0-9]{10,15}$/", $phone) ||
    strlen($password) < 6
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Enter valid details. Mobile number must be 10-15 digits and password at least 6 characters."
    ]);
    exit;
}

// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Save student details
$stmt = $conn->prepare(
    "INSERT INTO students (name, email, phone, password)
     VALUES (?, ?, ?, ?)"
);

$stmt->bind_param("ssss", $name, $email, $phone, $hash);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Registration successful"
    ]);
} else {
    http_response_code(409);
    echo json_encode([
        "success" => false,
        "message" => "Email may already be registered"
    ]);
}

$stmt->close();
$conn->close();
?>