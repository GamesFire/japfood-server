<?php

include '../includes/db_connection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

if (isset($_GET['category'])) {
  $category = filter_var($_GET['category'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

  switch ($category) {
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
      echo json_encode(['error' => 'Invalid category']);
      exit;
  }

  $sql = "SELECT * FROM $table";

  try {
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
      $categoryData = array();

      while ($row = $result->fetch_assoc()) {
        if ($table === 'contact_requests') {
          $requestData = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'subject' => $row['subject'],
            'message' => $row['message'],
            'submissionDate' => $row['submission_date'],
          );
          $categoryData[] = $requestData;
        } else {
          if ($row['image'] === null) {
            $defaultImage = file_get_contents('../images/file-not-found.jpg');
            $imageData = base64_encode($defaultImage);
          } else {
            $imageData = base64_encode($row['image']);
          }

          $foodCard = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'imageName' => $row['imageName'],
            'image' => $imageData,
            'weight' => $row['weight'],
            'averagePrice' => $row['averagePrice'],
            'ingredients' => $row['ingredients'],
            'description' => $row['description'],
          );

          $categoryData[] = $foodCard;
        }
      }
      header('Content-Type: application/json');
      echo json_encode($categoryData);
    } else {
      http_response_code(404);
      echo json_encode(['error' => 'No data found for the category']);
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
