<?php

include '../includes/db_connection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header("Access-Control-Allow-Methods: POST");
  exit;
}

if (
  isset($data['currentSection']) &&
  isset($data['editedValues']) &&
  isset($data['editedValues']['id'])
) {
  $currentSection = filter_var($data['currentSection'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $editedValues = $data['editedValues'];
  $editedValues['id'] = filter_var($editedValues['id'], FILTER_VALIDATE_INT);

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
    default:
      http_response_code(400);
      echo json_encode(['error' => 'Invalid Section']);
      exit;
  }

  try {
    $fileNotFoundBlob = file_get_contents('../images/file-not-found.jpg');

    $updateQuery = "UPDATE $table SET ";
    $params = [];

    foreach ($editedValues as $key => $value) {
      if ($key !== 'id') {
        if ($key === 'image' && $value !== base64_encode($fileNotFoundBlob)) {
          $updateQuery .= "$key = ?, ";
          $params[] = base64_decode($value);
        } else {
          $updateQuery .= "$key = ?, ";
          $params[] = $value;
        }
      }
    }

    $updateQuery = rtrim($updateQuery, ', ');
    $updateQuery .= " WHERE id = ?";
    $params[] = $editedValues['id'];

    $stmt = $conn->prepare($updateQuery);

    if ($stmt) {
      $types = str_repeat('s', count($params) - 1) . 'i';
      $stmt->bind_param($types, ...$params);

      $stmt->execute();

      if ($stmt->affected_rows > 0) {
        http_response_code(200);
        echo json_encode(['status' => 'success']);
      } else {
        http_response_code(404);
        echo json_encode(['error' => 'No rows updated']);
      }

      $stmt->close();
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Database error']);
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
