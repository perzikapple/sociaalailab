<?php
/**
 * Email Configuration for Brevo (FormMail)
 * Free tier: 300 emails/day
 * 
 * INSTRUCTIONS:
 * 1. Create account at https://www.brevo.com/
 * 2. Go to SMTP & API settings
 * 3. Copy your SMTP credentials (username and password/API key)
 * 4. Update the values below (or use environment variables for production)
 */

// Include PHPMailer classes first
require_once 'vendor/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get email config from environment variables (more secure for production)
// If not set, use defaults (update these values)
$emailConfig = [
    'host'     => getenv('EMAIL_HOST') ?: 'smtp-relay.brevo.com',      // Brevo SMTP server
    'port'     => (int)(getenv('EMAIL_PORT') ?: '587'),                 // Standard SMTP port
    'username' => getenv('EMAIL_USERNAME') ?: 'your_email@example.com', // Your Brevo login email
    'password' => getenv('EMAIL_PASSWORD') ?: 'your_smtp_key_here',     // Your Brevo SMTP key (NOT your password)
    'from'     => getenv('EMAIL_FROM') ?: 'noreply@sociaalailab.nl',    // Sender address
    'from_name' => 'SociaalAI Lab'                                       // Sender name
];

/**
 * Send email using PHPMailer with Brevo SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (plain text)
 * @param bool $isHtml Whether body is HTML (default: false)
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmail($to, $subject, $body, $isHtml = false) {
    global $emailConfig;
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['username'];
        $mail->Password   = $emailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $emailConfig['port'];
        $mail->SMTPDebug  = 0; // Set to 2 for debugging
        
        // Recipients
        $mail->setFrom($emailConfig['from'], $emailConfig['from_name']);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Send
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error sending email: ' . $mail->ErrorInfo
        ];
    }
}
?>
