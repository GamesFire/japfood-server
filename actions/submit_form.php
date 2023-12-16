<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");


try {
  include '../includes/db_connection.php';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $subject = isset($_POST['subject']) ? mysqli_real_escape_string($conn, $_POST['subject']) : '';
    $message = isset($_POST['message']) ? mysqli_real_escape_string($conn, $_POST['message']) : '';

    if (!$name || !$email || !$subject || !$message) {
      $response = array("status" => "error", "message" => "Form data is incomplete");
      echo json_encode($response);
      exit;
    }

    $sql = "INSERT INTO contact_requests (name, email, subject, message, submission_date) VALUES ('$name', '$email', '$subject', '$message', NOW())";

    if ($conn->query($sql) === TRUE) {
      $response = array("status" => "success", "message" => "Form submitted successfully");
      echo json_encode($response);
    } else {
      $response = array("status" => "error", "message" => "Error: " . $sql . "<br>" . $conn->error);
      echo json_encode($response);
    }
  } else {
    $response = array("status" => "error", "message" => "Invalid request method");
    echo json_encode($response);
  }
} catch (Exception $e) {
  $response = array("status" => "error", "message" => "An unexpected error occurred: " . $e->getMessage());
  echo json_encode($response);
} finally {
  mysqli_close($conn);
}
