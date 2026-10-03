
<?php
require "db.php";
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "POST required"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$examName = trim($data["exam_name"] ?? "");
$answers = $data["answers"] ?? [];

if ($examName === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Exam name is required"
    ]);
    exit;
}

if (!is_array($answers)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid answers"
    ]);
    exit;
}

// Sirf selected exam ke questions lao
$stmt = $conn->prepare(
    "SELECT id, correct_answer
     FROM questions
     WHERE exam_name = ?
     ORDER BY id ASC"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not prepare questions query"
    ]);
    exit;
}

$stmt->bind_param("s", $examName);
$stmt->execute();

$result = $stmt->get_result();

$total = 0;
$score = 0;

while ($row = $result->fetch_assoc()) {
    $total++;
    $id = (string)$row["id"];

    if (
        isset($answers[$id]) &&
        strtoupper((string)$answers[$id]) ===
        strtoupper((string)$row["correct_answer"])
    ) {
        $score++;
    }
}

$stmt->close();

if ($total === 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "No questions found for this exam"
    ]);
    $conn->close();
    exit;
}

$percentage = round(($score / $total) * 100, 2);
$studentId = $_SESSION["student_id"];

// Result save karo
$saveStmt = $conn->prepare(
    "INSERT INTO results (student_id, score, total, percentage)
     VALUES (?, ?, ?, ?)"
);

if (!$saveStmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not prepare result query"
    ]);
    $conn->close();
    exit;
}

$saveStmt->bind_param(
    "iiid",
    $studentId,
    $score,
    $total,
    $percentage
);

if ($saveStmt->execute()) {
    echo json_encode([
        "success" => true,
        "exam_name" => $examName,
        "score" => $score,
        "total" => $total,
        "percentage" => $percentage
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not save result"
    ]);
}

$saveStmt->close();
$conn->close();
?>