<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

try {
  include '../includes/db_connection.php';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_login = isset($_POST['adminLogin']) ? mysqli_real_escape_string($conn, $_POST['adminLogin']) : '';
    $entered_password = isset($_POST['adminPassword']) ? mysqli_real_escape_string($conn, $_POST['adminPassword']) : '';

    if (!$entered_login || !$entered_password) {
      $response = array("status" => "error", "message" => "Form data is incomplete");
      echo json_encode($response);
      exit;
    }

    $stmt = $conn->prepare("SELECT admin_id, admin_login, admin_password_hash, admin_salt FROM admin_credentials WHERE admin_login = ?");
    $stmt->bind_param('s', $entered_login);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $stmt->bind_result($admin_id, $admin_login, $admin_password_hash, $admin_salt);
      $stmt->fetch();

      if (password_verify($admin_salt . $entered_password, $admin_password_hash)) {
        echo json_encode(['accessAllowed' => true]);
      } else {
        echo json_encode(['accessAllowed' => false, 'message' => 'Invalid password']);
      }
    } else {
      echo json_encode(['accessAllowed' => false, 'message' => 'Admin not found']);
    }

    $stmt->close();
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
