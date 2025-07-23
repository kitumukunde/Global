<?
$name = $_POST{'names'};
$email = $_POST{'email'};
$phone = $_POST{'phone'};
$subject = $_POST{'subject'};
$travel = $_POST['travel-reason'];

$email_message = "

Name: ".$name."
Email: ".$email."
Phone: ".$phone."
Subject: ".$subject."
travel-reason: ".$travel."
";

mail ("name@youremail.com" , "New Message", $email_message);
header("location: ../mail-success.html");
?>


