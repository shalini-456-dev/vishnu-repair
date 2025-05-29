<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// === Database Configuration ===
$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "visnu_repairs";

// === Mail Configuration ===
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USERNAME', 'example.gmail.com');
define('MAIL_PASSWORD', 'your_password'); // Use an App Password
define('MAIL_PORT', 587);
define('MAIL_FROM', 'example.gmail.com');
define('MAIL_FROM_NAME', 'Visnu Repairs');

// === Service Label Map ===
$services = [
    "1" => "Road Service",
    "2" => "Truck Repair",
    "3" => "Tyre Repair",
    "4" => "Engine Work"
];

class BookingHandler {
    private $conn;

    public function __construct($host, $user, $pass, $db) {
        $this->conn = new mysqli($host, $user, $pass, $db);
        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }

    public function saveBooking($name, $email, $service, $date, $request) {
        $stmt = $this->conn->prepare("INSERT INTO bookings (name, email, service, service_date, request) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $service, $date, $request);
        return $stmt->execute();
    }

    public function __destruct() {
        $this->conn->close();
    }
}

class Mailer {
    public function sendMail($toEmail, $toName, $service, $date, $request) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = 'tls';
            $mail->Port = MAIL_PORT;

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = "Booking Confirmation - $service";
            $mail->Body = "
                <p>Dear $toName,</p>
                <p>Thank you for your service booking. Here are the details:</p>
                <ul>
                    <li><strong>Service:</strong> $service</li>
                    <li><strong>Date:</strong> $date</li>
                    <li><strong>Special Request:</strong> $request</li>
                </ul>
                <p>We will get back to you shortly.</p>
                <p>Regards,<br><strong>" . MAIL_FROM_NAME . "</strong></p>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Optionally log error: $mail->ErrorInfo
            return false;
        }
    }
}

// === Handle Form Submission ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email   = htmlspecialchars(trim($_POST['email'] ?? ''));
    $service = $_POST['service'] ?? '';
    $date    = htmlspecialchars(trim($_POST['date'] ?? ''));
    $request = htmlspecialchars(trim($_POST['request'] ?? ''));

    if ($name && $email && $service && $date && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $serviceName = $services[$service] ?? "Unknown Service";

        $booking = new BookingHandler($host, $dbUser, $dbPass, $dbName);
        $mailer = new Mailer();

        $isSaved = $booking->saveBooking($name, $email, $serviceName, $date, $request);
        $isMailed = $mailer->sendMail($email, $name, $serviceName, $date, $request);

        if ($isSaved && $isMailed) {
            echo "<script>
                alert('Thank you! Your booking was successful.');
                window.location.href = 'index.html';
            </script>";
        } elseif ($isSaved && !$isMailed) {
            echo "<script>
                alert('Booking saved, but confirmation email failed.');
                window.location.href = 'index.html';
            </script>";
        } else {
            echo "<script>
                alert('Failed to process booking. Please try again.');
                window.location.href = 'booking.html';
            </script>";
        }
    } else {
        echo "<script>
            alert('Please fill all required fields with valid data.');
            window.location.href = 'booking.html';
        </script>";
    }
}
?>
