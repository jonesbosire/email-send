<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php'; // Assuming Composer autoloader is configured

// Database connection details (placeholders)
$servername = "localhost";
$username = "";
$password = "";
$dbname = "";

// Create connection
$conn = new mysqli("localhost", "", "", "");

// Check connection
if ($conn->connect_error) {
    die("ERROR: Could not connect to database. " . $conn->connect_error);
}

// Initialize variables
$success = 0;
$errors = []; // Array to store any validation errors

if ($_SERVER["REQUEST_METHOD"] == "POST") {

// Collect and sanitize form data
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $date = mysqli_real_escape_string($conn, trim($_POST['date']));
    $service = mysqli_real_escape_string($conn, trim($_POST['service']));

    // Validate form data
    if (empty($name)) {
        $errors[] = "Name is required!";
    }
    if (empty($phone)) {
       $errors[] = "Phone number is required!";
   } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
       $errors[] = "Invalid phone number format! Please enter a 10-digit number.";
   }
    if (empty($email)) {
        $errors[] = "Email is required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format!";
    }
    if (empty($date)) {
        $errors[] = "Please select date!!";
    }
    
    if (empty($service)) {
        $errors[] = "Select service!";
    }

    // If no errors, proceed with insertion and email sending
    if (empty($errors)) {

        // Prepare SQL query using parameterized statement for security purpose
        $sql = "INSERT INTO get_quote (name, phone, email, date, service ) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $phone, $email, $date, $service);

        // Execute query and handle success/failure
        if ($stmt->execute()) {
            $success = 1;
            $name = $phone = $email = $date = $service = ''; // Clear form fields

            // Send email notification
            try {
                $mail = new PHPMailer(true);
                $mail->SMTPDebug = 0; // Set to 2 or 3 for debugging
                $mail->isSMTP();
                $mail->Host = ''; //SMTP server hostname
                $mail->SMTPAuth = true;
                $mail->Username = ''; //  SMTP username
                $mail->Password = ''; // SMTP password
                $mail->SMTPSecure = 'ssl'; // appropriate security('tls')
                $mail->Port = 465; //  SMTP port

                $mail->setFrom($_POST['email']);
                $mail->addAddress('', 'Callback Request from Website'); // recipient email
                $mail->isHTML(true);
                $mail->Subject = "Action Required for {$_POST['name']} 's Inquiry"; 
                $mail->Body = "I hope this email finds you well.<br>
                My name is {$_POST['name']} and I'm reaching out to inquire about {$_POST['service']}.I would love to learn more about what you offer and discuss potential travel arrangements.<br>
                I came across your company's website and was impressed by your services. 
                Here are my contact details for further engagement: <br> Name: {$_POST['name']} <br>
                Phone Number: {$_POST['phone']} <br> Email Address: {$_POST['email']} <br>
                I would appreciate any information you can provide regarding {$_POST['service']} , 
                including itineraries, pricing, and any special offers available. <br>
                Additionally, if possible, I would like to schedule a call or meeting to further discuss my inquiry.
                Thank you for your attention to this matter. I look forward to hearing from you soon.<br>
                Best regards,
                {$_POST['name']}";
                
                $mail->send();
            } catch (Exception $e) {
                echo "Error sending email:" . $e->getMessage();
            }
        } else {
            echo "Error inserting data:" . $conn->error;
        }

        $stmt->close();
    } else {
        // Display validation errors
        echo '<ul>';
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo '</ul>';
    }
}

// Close connection
$conn->close();

