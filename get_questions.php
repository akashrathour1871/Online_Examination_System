
<?php
require "db.php";

header("Content-Type: application/json");

$exam_name = $_GET["exam_name"] ?? "";

if (trim($exam_name) === "") {
    echo json_encode([
        "success" => false,
        "message" => "Exam name is required"
    ]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, question, option_a, option_b, option_c, option_d
     FROM questions
     WHERE exam_name = ?
     ORDER BY id ASC"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not prepare query"
    ]);
    exit;
}

$stmt->bind_param("s", $exam_name);
$stmt->execute();

$result = $stmt->get_result();
$questions = [];

while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

echo json_encode([
    "success" => true,
    "questions" => $questions
]);

$stmt->close();
$conn->close();
?>