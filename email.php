<?php
// Require the PHPMailer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Adjust the path if your vendor folder is not directly in the same directory as booking.php
require 'vendor/autoload.php';

// Ensure the request method is POST for security and proper form handling
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Collect and Sanitize Form Data ---
    $name  = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $travel = htmlspecialchars(trim($_POST['travel-reason'])); // This field seems specific to your booking form

    // --- START DUPLICATE SUBMISSION CHECK ---
    $data_directory = 'data'; // Folder to store submission data (should be shared with contact.php if desired)
   $data_file = $data_directory . '/booking_submissions.json'; // Unique file for booking form submissions

    // Ensure the data directory exists
    if (!is_dir($data_directory)) {
        // Attempt to create the directory if it doesn't exist
        if (!mkdir($data_directory, 0755, true)) { // 0755 is common for web server writable directories
            error_log("Failed to create data directory: $data_directory");
            // Redirect immediately if directory creation fails
            header("Location: index.html?status=error&message=" . urlencode("Server error: Data directory not writable."));
            exit();
        }
    }

    $submitted_emails = [];
    $duplicate_found = false;
    $time_window_seconds = 24 * 60 * 60; // Define time window for duplicates (24 hours)

    // Load existing submitted emails if the file exists
    if (file_exists($data_file)) {
        $file_content = file_get_contents($data_file);
        if ($file_content !== false && $file_content !== '') {
            $decoded_data = json_decode($file_content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_data)) {
                $submitted_emails = $decoded_data;
            } else {
                // Log and reset if JSON is corrupted, but allow submission
                error_log("Corrupted submitted_emails.json detected for booking. Resetting data. Error: " . json_last_error_msg());
                $submitted_emails = [];
            }
        }
    }

    // Check if the current email has been submitted within the time window
    if (isset($submitted_emails[$email])) {
        if ((time() - $submitted_emails[$email]) < $time_window_seconds) {
            $duplicate_found = true;
        }
    }

    if ($duplicate_found) {
        // Redirect if it's a duplicate
        header("Location: index.html?status=error&message=" . urlencode("You have a recent booking request with this email. Please try again later."));
        exit();
    }
    // --- END DUPLICATE SUBMISSION CHECK ---

    // Start PHPMailer
    $mail = new PHPMailer(true); // 'true' enables exceptions for robust error handling

    try {
        // SMTP configuration remains the same: you still authenticate with your kitimukunde@gmail.com
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kitimukunde@gmail.com';        // Your Gmail account (used for authentication)
        $mail->Password   = 'pfat pwju jcel xgxl';           // Your App Password
        $mail->SMTPSecure = 'tls';                            // Use 'ssl' for port 465, 'tls' for port 587
        $mail->Port       = 587;                              // TCP port to connect to

        // Set the 'From' address to your Gmail alias.
        // IMPORTANT: Double-check if 'kitumukunde@gmail.com' is the exact, verified alias you configured in Gmail.
        // If not, use the correct alias email address here.
        $mail->setFrom('kitumukunde@gmail.com', 'New Request');

        // This is still crucial: when the admin replies, it goes to the actual client's email.
        $mail->addReplyTo($email, $name);

        // The recipient remains your company's email address (where you want to receive bookings)
        $mail->addAddress('kitimukunde@gmail.com');

        // Keep the clear subject line for easy identification in your inbox
        $mail->Subject = 'CLIENT BOOKING: ' . $name . ' <' . $email . '>';

        // The body contains all the detailed information (plain text as in your original)
        $mail->Body = "New Booking Request:\n\n"
                    . "Name: " . $name . "\n"
                    . "Email: " . $email . "\n"
                    . "Phone: " . $phone . "\n"
                    . "Subject: " . $subject . "\n"
                    . "Reason for Travel/Booking Details: " . $travel;

        $mail->send();

        // --- RECORD SUCCESSFUL SUBMISSION ---
        // Only record the email after it has been successfully sent.
        $submitted_emails[$email] = time(); // Update or add the email with the current timestamp

        // Save the updated data back to the file
        // Use file locking for basic protection against concurrent writes
        $fp = fopen($data_file, 'w'); // Open file for writing, creates if not exists
        if ($fp) {
            flock($fp, LOCK_EX); // Acquire an exclusive exclusive lock
            fwrite($fp, json_encode($submitted_emails, JSON_PRETTY_PRINT)); // Save data as pretty-printed JSON
            fflush($fp); // Ensure all buffered output is written
            flock($fp, LOCK_UN); // Release the lock
            fclose($fp); // Close the file
        } else {
            // Log if file writing failed (this is critical for duplicate prevention)
            error_log("Failed to open or lock $data_file for writing booking submission data.");
        }
        // --- END RECORDING ---

        // Redirect to a success message page specific to bookings
        // Make sure you have a 'booking-form.html' or similar page to redirect to
        header("Location: index.html?status=success");
        exit();

    } catch (Exception $e) {
        // Log the detailed error for your debugging purposes (check your web server's error logs)
        error_log("Booking email failed for $email. Error: " . $e->getMessage() . " PHPMailer Error Info: " . $mail->ErrorInfo);

        // Redirect to an error message page for the user
        header("Location: index.html?status=error&message=" . urlencode("Your booking could not be submitted. Please try again later."));
        exit();
    }
} else {
    // Handle cases where the script is accessed directly (not via form submission)
    // Redirect to the main booking form or home page with an error message
    header("Location: index.html?status=error&message=" . urlencode("Invalid request method. Please submit the booking form."));
    exit();
}
?>