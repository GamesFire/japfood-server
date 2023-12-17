<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "japfood";

try {
  $conn = @mysqli_connect($host, $username, $password, $database);

  if (!$conn) {
    $response = array("status" => "error", "message" => "Database connection failed.");
    http_response_code(500);
    echo json_encode($response);
    exit;
  }
} catch (Exception $e) {
  $response = array("status" => "error", "message" => "An unexpected error occurred.");
  http_response_code(500);
  echo json_encode($response);
  exit;
}

$conn->set_charset("utf8mb4");
