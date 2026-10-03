
<?php
require "db.php";
session_start();

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "POST required"
    ]);
    exit;
}

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

$stmt = $conn->prepare(
    "SELECT id, name, email, phone, password
     FROM students
     WHERE email = ?"
);

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (
    $student &&
    password_verify($password, $student["password"])
) {
    $_SESSION["student_id"] = $student["id"];
    $_SESSION["student_name"] = $student["name"];
    $_SESSION["student_email"] = $student["email"];
    $_SESSION["student_phone"] = $student["phone"];

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "name" => $student["name"],
        "email" => $student["email"],
        "phone" => $student["phone"]
    ]);

} else {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password"
    ]);
}

$stmt->close();
$conn->close();
?>