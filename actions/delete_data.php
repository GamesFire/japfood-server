<?php

include '../includes/db_connection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header("Access-Control-Allow-Methods: DELETE");
  exit;
}

if (isset($data['currentSection']) && isset($data['deletedCardId'])) {
  $currentSection = filter_var($data['currentSection'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $deletedCardId = filter_var($data['deletedCardId'], FILTER_VALIDATE_INT);

  switch ($currentSection) {
    case 'sushi':
      $table = 'sushi_cards';
      break;
    case 'soups':
      $table = 'soups_cards';
      break;
    case 'desserts':
      $table = 'desserts_cards';
      break;
    case 'drinks':
      $table = 'drinks_cards';
      break;
    case 'requests':
      $table = 'contact_requests';
      break;
    default:
      http_response_code(400);
      echo json_encode(['error' => 'Invalid Section']);
      exit;
  }

  try {
    $deleteQuery = "DELETE FROM $table WHERE id = $deletedCardId";

    $conn->query($deleteQuery);

    if ($conn->affected_rows > 0) {
      http_response_code(200);
      echo json_encode(['status' => 'success']);
    } else {
      http_response_code(404);
      echo json_encode(['error' => 'No rows deleted']);
    }
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
  }
} else {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid or missing data']);
}

$conn->close();
