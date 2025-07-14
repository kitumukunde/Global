<?php
// Require the PHPMailer autoloader
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Adjust the path if your vendor folder is not directly in the same directory as contact.php
require 'vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data and sanitize
    $name         = htmlspecialchars(trim($_POST['name']));
    $phone        = htmlspecialchars(trim($_POST['phone']));
    $email        = htmlspecialchars(trim($_POST['email']));
    $subject_form = htmlspecialchars(trim($_POST['subject'])); // Renamed to avoid conflict
    $message      = htmlspecialchars(trim($_POST['message']));

    // --- START DUPLICATE SUBMISSION CHECK ---
    $data_directory = 'data'; // Folder to store submission data
    $data_file      = $data_directory . '/submitted_emails.json'; // File to store submitted emails

    // Ensure the data directory exists
    if (!is_dir($data_directory)) {
        // Attempt to create the directory if it doesn't exist
        if (!mkdir($data_directory, 0755, true)) { // 0755 is common for web server writable directories
            error_log("Failed to create data directory: $data_directory");
            header("Location: contact-us.html?status=error&message=" . urlencode("Server error: Data directory not writable."));
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
                // Log and reset if JSON is corrupted
                error_log("Corrupted submitted_emails.json detected. Resetting data. Error: " . json_last_error_msg());
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
        header("Location: contact-us.html?status=error&message=" . urlencode("You have recently sent a message with this email. Please try again later."));
        exit();
    }
    // --- END DUPLICATE SUBMISSION CHECK ---


    // Start PHPMailer
    $mail = new PHPMailer(true); // 'true' enables exceptions for robust error handling

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kitimukunde@gmail.com';        // Your Gmail account (used for authentication)
        $mail->Password   = 'pfat pwju jcel xgxl';           // Your App-specific password
        $mail->SMTPSecure = 'tls';                            // Use 'ssl' for port 465, 'tls' for port 587
        $mail->Port       = 587;                              // TCP port to connect to

        // **CRUCIAL LINE FOR CUSTOM SENDER DISPLAY:**
        // Replace 'your_alias@example.com' with the exact alias email you configured in Gmail (Step 2).
        // Replace 'Your Desired Sender Name' with the name you want to see in the "From" column of your inbox.
        $mail->setFrom('uzamukundachanceog1@gmail.com', 'Suggestion&Question');
        // Example if your alias is bookings@yourcompany.com:
        // $mail->setFrom('bookings@yourcompany.com', 'My Company Bookings');


        // Set the Reply-To address to the client's email.
        // This ensures that when you hit reply in your email client, it goes back to the client.
        $mail->addReplyTo($email, $name);

        // Set the recipient (your own kitimukunde@gmail.com address where you want to receive messages)
        $mail->addAddress('kitimukunde@gmail.com');

        // Email Content
        $mail->isHTML(true); // Set email format to HTML
        // Subject line to clearly identify the client and message in your inbox
        $mail->Subject = "📩 NEW CONTACT MESSAGE: " . $name . " - " . (!empty($subject_form) ? $subject_form : 'No Subject');
        $mail->Body    = "
            <h2>Contact Message Received</h2>
            <p><strong>From:</strong> " . $name . " (" . $email . ")</p>
            <p><strong>Phone:</strong> " . $phone . "</p>
            <p><strong>Subject:</strong> " . (!empty($subject_form) ? $subject_form : 'N/A') . "</p>
            <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
        ";
        // Plain text version for email clients that don't display HTML
        $mail->AltBody = "Name: $name\nEmail: $email\nPhone: $phone\nSubject: " . (!empty($subject_form) ? $subject_form : 'N/A') . "\nMessage:\n$message";

        // Send the email
        $mail->send();

        // --- RECORD SUCCESSFUL SUBMISSION ---
        // Only record the email after it has been successfully sent.
        $submitted_emails[$email] = time(); // Update or add the email with the current timestamp

        // Save the updated data back to the file
        // Use file locking for basic protection against concurrent writes (multiple users submitting at once)
        $fp = fopen($data_file, 'w'); // Open file for writing, creates if not exists
        if ($fp) {
            flock($fp, LOCK_EX); // Acquire an exclusive lock
            fwrite($fp, json_encode($submitted_emails, JSON_PRETTY_PRINT)); // Save data as pretty-printed JSON
            fflush($fp); // Ensure all buffered output is written
            flock($fp, LOCK_UN); // Release the lock
            fclose($fp); // Close the file
        } else {
            // Log if file writing failed, this is critical for duplicate prevention
            error_log("Failed to open or lock $data_file for writing submission data.");
        }
        // --- END RECORDING ---

        // Redirect to a success message page
        header("Location: contact-us.html?status=success");
        exit();

    } catch (Exception $e) {
        // Log the detailed error for your debugging purposes (check your web server's error logs)
        error_log("Email sending failed for $email. Error: " . $e->getMessage() . " PHPMailer Error Info: " . $mail->ErrorInfo);

        // Redirect to an error message page for the user
        header("Location: contact-us.html?status=error&message=" . urlencode("Sorry, your message could not be sent. Please try again later."));
        exit();
    }
} else {
    // Handle cases where the script is accessed directly (not via form submission)
    header("Location: index.html?status=error&message=" . urlencode("Invalid request method. Please submit the form."));
    exit();
}
?>SSSS