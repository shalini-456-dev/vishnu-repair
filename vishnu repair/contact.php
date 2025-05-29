<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// === Database configuration ===
$servername = "localhost";
$username = "root";
$password = "";
$dbname   = "visnu_repairs";

// === PHPMailer configuration (Gmail) ===
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USERNAME', 'example@gmail.com'); // your Gmail
define('MAIL_PASSWORD', 'your_password'); // your Gmail App Password
define('MAIL_PORT', 587);
define('MAIL_FROM', 'example@gmail.com');
define('MAIL_FROM_NAME', 'Vishnu Repairs');

// === ContactForm Class ===
class ContactForm {
    private $conn;

    public function __construct($host, $user, $pass, $db) {
        $this->conn = new mysqli($host, $user, $pass, $db);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function saveMessage($name, $email, $subject, $message) {
        $stmt = $this->conn->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        return $stmt->execute();
    }

    public function __destruct() {
        $this->conn->close();
    }
}

// === Mailer Class ===
class Mailer {
    public function sendMail($toEmail, $toName, $subject, $message) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = MAIL_PORT;

            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = "Your message to " . MAIL_FROM_NAME;
            $mail->Body = "
                <p>Dear $toName,</p>
                <p>Thank you for contacting Vishnu Repairs. Here is a copy of your message:</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong><br>$message</p>
                <p>We will get back to you shortly.</p>
                <p>Best regards,<br>Vishnu Repairs</p>
            ";

            // Enable debug if needed
            // $mail->SMTPDebug = 2;
            // $mail->Debugoutput = 'html';

            $mail->send();
            return true;
        } catch (Exception $e) {
            echo "Mailer Error: " . $mail->ErrorInfo;
            return false;
        }
    }
}

// === Form Submission Handling ===
$messageSent = false;
$error = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['name'], $_POST['email'], $_POST['subject'], $_POST['message'])) {

    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));

    $form = new ContactForm($servername, $username, $password, $dbname);
    $mailer = new Mailer();

    if ($form->saveMessage($name, $email, $subject, $message) && $mailer->sendMail($email, $name, $subject, $message)) {
        $messageSent = true;
    } else {
        $error = true;
    }
}

// === Final Output (JS Alert) ===
if ($messageSent) {
    echo "<script>
        alert('Thank you! Your message has been sent successfully.');
        window.location.href = 'index.html';
    </script>";
} elseif ($error) {
    echo "<script>
        alert('Oops! Something went wrong. Please try again.');
        window.location.href = 'contact.html';
    </script>";
}
?>
