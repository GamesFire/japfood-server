<?php

include '../includes/db_connection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

if (isset($_GET['category']) && isset($_GET['lastUpdate'])) {
  $currentCategory = filter_var($_GET['category'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $lastUpdate = (int)$_GET['lastUpdate'];

  $table = '';

  switch ($currentCategory) {
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

  $lastKnownUpdate = getTotalRows($table);

  while (true) {
    $currentUpdate = getTotalRows($table);

    if ($lastUpdate !== $currentUpdate) {
      header('Content-Type: application/json');
      echo json_encode(['totalRows' => $currentUpdate]);
      exit;
    }

    sleep(1);
  }
}

exit;

$conn->close();

function getTotalRows($table)
{
  global $conn;

  $sql = "SELECT COUNT(*) as totalRows FROM $table";

  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    return (int)$row['totalRows'];
  }

  return 0;
}
