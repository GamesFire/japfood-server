<?php

include '../includes/db_connection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

if (isset($_GET['category']) && isset($_GET['page']) && isset($_GET['currentLanguage'])) {
  $category = filter_var($_GET['category'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $page = filter_var($_GET['page'], FILTER_VALIDATE_INT);
  $currentLanguage = filter_var($_GET['currentLanguage'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

  $itemsPerPage = 6;

  $offset = ($page - 1) * $itemsPerPage;

  $table = '';

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

  $sql = "SELECT * FROM $table LIMIT $itemsPerPage OFFSET $offset";

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

          if ($currentLanguage !== 'uk') {
            $translationUrl = "https://script.google.com/macros/s/AKfycbzUmvdzP2MvXItIEVg8Q8n-1KFM5cDQ2Jd1I-SBlmspVKAsJEDL8v8QaIx_o9zjz0qg/exec";

            $translationParams = array(
              "source_lang" => "uk",
              "target_lang" => $currentLanguage,
              'text' => $row['name'] . ';' . $row['imageName'] . ';' . $row['ingredients'] . ';' . $row['description'],
            );

            $ch = curl_init($translationUrl);
            curl_setopt_array($ch, [
              CURLOPT_FOLLOWLOCATION => 1,
              CURLOPT_RETURNTRANSFER => 1,
              CURLOPT_POSTFIELDS => http_build_query($translationParams),
            ]);

            $translationResult = curl_exec($ch);
            $translationData = json_decode($translationResult, true);

            if ($translationData && isset($translationData['status']) && $translationData['status'] === 'success' && isset($translationData['translatedText'])) {
              $translatedText = $translationData['translatedText'];

              if (is_array($translatedText)) {
                $row['name'] = $translatedText['name'] ?? $row['name'];
                $row['imageName'] = $translatedText['imageName'] ?? $row['imageName'];
                $row['ingredients'] = $translatedText['ingredients'] ?? $row['ingredients'];
                $row['description'] = $translatedText['description'] ?? $row['description'];
              } else {
                http_response_code(500);
                echo json_encode(['error' => 'Translation error']);
                exit;
              }
            }

            curl_close($ch);
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
      echo json_encode(['error' => 'No data found for the category or page']);
    }
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
  }
}

$conn->close();
