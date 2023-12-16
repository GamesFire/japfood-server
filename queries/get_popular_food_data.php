<?php

include '../includes/db_connection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

if (isset($_GET['currentLanguage'])) {
  $currentLanguage = filter_var($_GET['currentLanguage'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

  $itemsPerCategory = 1;

  $popularFoodData = array();

  $categories = ['sushi', 'soups', 'desserts', 'drinks'];

  foreach ($categories as $category) {
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
      default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid category']);
        exit;
    }

    $sql = "SELECT * FROM $table LIMIT $itemsPerCategory";

    try {
      $result = $conn->query($sql);

      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

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
          'description' => $row['description'],
        );

        $popularFoodData[$category] = $foodCard;
      }
    } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(['error' => 'Database error']);
      exit;
    }
  }

  header('Content-Type: application/json');
  echo json_encode($popularFoodData);
}

$conn->close();
