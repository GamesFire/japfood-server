<?php

include '../includes/db_connection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

$columnMapping = array(
  'submissionDate' => 'submission_date',
);

if (isset($_GET['currentSection'])) {
  $currentSection = filter_var($_GET['currentSection'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

  if (isset($_GET['searchQuery'])) {
    $searchQuery = json_decode($_GET['searchQuery'], true);

    $table = '';

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
        echo json_encode(['error' => 'Invalid currentSection']);
        exit;
    }

    $conditionArray = array();

    foreach ($searchQuery as $key => $value) {
      if ($value !== "") {
        $escapedKey = mysqli_real_escape_string($conn, $key);
        $escapedValue = mysqli_real_escape_string($conn, $value);

        if ($escapedKey === false || $escapedValue === false) {
          http_response_code(500);
          echo json_encode(['error' => 'Failed to escape query parameter']);
          exit;
        }

        $columnKey = array_key_exists($escapedKey, $columnMapping) ? $columnMapping[$escapedKey] : $escapedKey;

        if ($table === 'contact_requests' && ($columnKey === 'name' || $columnKey === 'email' || $columnKey === 'subject' || $columnKey === 'submission_date')) {
          $conditionArray[] = "BINARY $columnKey LIKE '%$escapedValue%'";
        } else {
          if ($columnKey === 'name' || $columnKey === 'ingredients') {
            $conditionArray[] = "BINARY $columnKey LIKE '%$escapedValue%'";
          } else {
            $conditionArray[] = "BINARY $columnKey = '$escapedValue'";
          }
        }
      }
    }

    $condition = implode(' AND ', $conditionArray);

    $sql = "SELECT * FROM $table";

    if (!empty($conditionArray)) {
      $sql .= " WHERE $condition";
    }

    try {
      $result = $conn->query($sql);

      if ($result !== false) {
        $categoryData = array();

        if ($result->num_rows > 0) {
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
          echo json_encode(['error' => 'No data found for the currentSection and searchQuery']);
        }
      } else {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
      }
    } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
  } else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing searchQuery parameter']);
  }
} else {
  http_response_code(400);
  echo json_encode(['error' => 'Missing currentSection parameter']);
}

$conn->close();
