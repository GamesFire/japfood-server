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
  isset($data['newValues'])
) {
  $currentSection = filter_var($data['currentSection'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $newValues = $data['newValues'];

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

  $checkIfExistsQuery = "SELECT * FROM $table WHERE name = ?";
  $stmt = $conn->prepare($checkIfExistsQuery);
  $stmt->bind_param("s", $newValues['name']);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Card with the same name already exists']);
  } else {
    try {
      $insertQuery = "INSERT INTO $table (name, imageName, image, weight, averagePrice, ingredients, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($insertQuery);

      if (isset($newValues['image'])) {
        $imageData = base64_decode($newValues['image']);
      } else {
        $defaultImage = file_get_contents('../images/file-not-found.jpg');
        $imageData = base64_encode($defaultImage);
      }

      $stmt->bind_param("sssiiss", $newValues['name'], $newValues['imageName'], $imageData, $newValues['weight'], $newValues['averagePrice'], $newValues['ingredients'], $newValues['description']);

      $stmt->execute();

      if ($stmt->affected_rows > 0) {
        $newCardId = $stmt->insert_id;
        $fetchNewCardQuery = "SELECT * FROM $table WHERE id = $newCardId";
        $newCardResult = $conn->query($fetchNewCardQuery);

        if ($newCardResult->num_rows > 0) {
          $newCard = $newCardResult->fetch_assoc();

          $newCard['image'] = base64_encode($newCard['image']);

          http_response_code(200);
          echo json_encode(['status' => 'success', 'newCard' => $newCard]);
        } else {
          http_response_code(500);
          echo json_encode(['error' => 'Unable to fetch the newly added card']);
        }
      } else {
        http_response_code(500);
        echo json_encode(['error' => 'No rows inserted']);
      }
    } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(['error' => 'Database error']);
    }
  }
} else {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid or missing data']);
}

$conn->close();
