<?php
// Database config
$host = 'localhost';
$db = 'global';
$user = 'root';
$pass = '';

// Connect to DB
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get POST data safely
$names = htmlspecialchars($_POST['name']);
$email = htmlspecialchars($_POST['email']);
$subject = htmlspecialchars($_POST['subject']);
$travel = htmlspecialchars($_POST['travel-reason']); // from form

// Insert into DB (column is named 'travel')
$sql = "INSERT INTO booking (names, email, subject, travel) 
        VALUES ('$names', '$email', '$subject', '$travel')";

if ($conn->query($sql) === TRUE) {
  // Send email
  $to = "kitumukunde@gmail.com"; // change to your email
  $headers = "From: $email";
  $message = "Name: $names\nEmail: $email\nSubject: $subject\nReason: $travel";
  mail($to, "New Booking Request", $message, $headers);

  echo "Booking submitted successfully!";
} else {
  echo "Error: " . $conn->error;
}

$conn->close();
?>
