<?php

include '../includes/db_connection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

if (isset($_GET['category'])) {
  $category = filter_var($_GET['category'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

  if (isset($_GET['searchQuery']) && isset($_GET['currentLanguage'])) {
    $searchQuery = filter_var($_GET['searchQuery'], FILTER_SANITIZE_SPECIAL_CHARS);
    $currentLanguage = filter_var($_GET['currentLanguage'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  } else {
    $searchQuery = '';
  }

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
    default:
      http_response_code(400);
      echo json_encode(['error' => 'Invalid category']);
      exit;
  }

  if ($currentLanguage !== 'uk') {
    $originalNames = array();

    $nameQuery = "SELECT name FROM $table";

    try {
      $nameResult = $conn->query($nameQuery);

      if ($nameResult->num_rows > 0) {
        while ($nameRow = $nameResult->fetch_assoc()) {
          $originalNames[] = $nameRow['name'];
        }

        $allTranslatedNames = array();
        $batchSize = 20;
        $nameBatches = array_chunk($originalNames, $batchSize);

        foreach ($nameBatches as $nameBatch) {
          $batchString = implode(';', $nameBatch);

          $url = "https://script.google.com/macros/s/AKfycbyqom8fiIRnImqNpjwLpltKGRJTJypAh5eJ_6mkKA__kJ2uz5b1icI4wWUjnxDi8rIk/exec";
          $params = array(
            "source_lang" => "uk",
            "target_lang" => $currentLanguage,
            "text" => $batchString,
          );

          $ch = curl_init($url);
          curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POSTFIELDS => http_build_query($params),
          ]);

          $translationResult = curl_exec($ch);
          $translationData = json_decode($translationResult, true);

          if ($translationData['status'] == "success") {
            $translatedNames = explode(';', $translationData['translatedText']);
            $nameTranslations = array_combine($nameBatch, $translatedNames);
            $allTranslatedNames = array_merge($allTranslatedNames, $nameTranslations);
          } else {
            http_response_code(500);
            echo json_encode(['error' => 'Translation error']);
            exit;
          }

          curl_close($ch);
        }

        $matchingNames = array_filter($allTranslatedNames, function ($translatedName) use ($searchQuery) {
          return stripos($translatedName, $searchQuery) !== false;
        });

        $categoryData = array();

        foreach ($matchingNames as $originalName => $translatedName) {
          $foodCardQuery = "SELECT * FROM $table WHERE BINARY name = '$originalName'";
          $foodCardResult = $conn->query($foodCardQuery);

          if ($foodCardResult->num_rows > 0) {
            $foodCardRow = $foodCardResult->fetch_assoc();

            if ($foodCardRow['image'] === null) {
              $defaultImage = file_get_contents('../images/file-not-found.jpg');
              $imageData = base64_encode($defaultImage);
            } else {
              $imageData = base64_encode($foodCardRow['image']);
            }

            $translationCardsUrl = "https://script.google.com/macros/s/AKfycbzUmvdzP2MvXItIEVg8Q8n-1KFM5cDQ2Jd1I-SBlmspVKAsJEDL8v8QaIx_o9zjz0qg/exec";

            $translationCardsParams = array(
              "source_lang" => "uk",
              "target_lang" => $currentLanguage,
              'text' => $foodCardRow['name'] . ';' . $foodCardRow['imageName'] . ';' . $foodCardRow['ingredients'] . ';' . $foodCardRow['description'],
            );

            $chCards = curl_init($translationCardsUrl);
            curl_setopt_array($chCards, [
              CURLOPT_FOLLOWLOCATION => 1,
              CURLOPT_RETURNTRANSFER => 1,
              CURLOPT_POSTFIELDS => http_build_query($translationCardsParams),
            ]);

            $translationCardsResult = curl_exec($chCards);
            $translationCardsData = json_decode($translationCardsResult, true);

            if ($translationCardsData && isset($translationCardsData['status']) && $translationCardsData['status'] === 'success' && isset($translationCardsData['translatedText'])) {
              $translatedCardsText = $translationCardsData['translatedText'];

              if (is_array($translatedCardsText)) {
                $foodCardRow['name'] = $translatedCardsText['name'] ?? $foodCardRow['name'];
                $foodCardRow['imageName'] = $translatedCardsText['imageName'] ?? $foodCardRow['imageName'];
                $foodCardRow['ingredients'] = $translatedCardsText['ingredients'] ?? $foodCardRow['ingredients'];
                $foodCardRow['description'] = $translatedCardsText['description'] ?? $foodCardRow['description'];
              } else {
                http_response_code(500);
                echo json_encode(['error' => 'Translation cards error']);
                exit;
              }
            }

            $foodCard = array(
              'id' => $foodCardRow['id'],
              'name' => $foodCardRow['name'],
              'imageName' => $foodCardRow['imageName'],
              'image' => $imageData,
              'weight' => $foodCardRow['weight'],
              'averagePrice' => $foodCardRow['averagePrice'],
              'ingredients' => $foodCardRow['ingredients'],
              'description' => $foodCardRow['description'],
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
    $sql = "SELECT * FROM $table WHERE BINARY name LIKE '%$searchQuery%'";

    try {
      $result = $conn->query($sql);

      if ($result->num_rows > 0) {
        $categoryData = array();

        while ($row = $result->fetch_assoc()) {
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

        header('Content-Type: application/json');
        echo json_encode($categoryData);
      } else {
        http_response_code(404);
        echo json_encode(['error' => 'No data found for the category and searchQuery']);
      }
    } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(['error' => 'Database error']);
    }
  }
}

$conn->close();
