<?php
/**
 * Email Configuration for Brevo (FormMail)
 * Free tier: 300 emails/day
 * 
 * Uses PHPMailer if available (vendor folder uploaded)
 * Falls back to native mail() if not available
 */

// Try to include PHPMailer classes if they exist
$mailerAvailable = false;
if (file_exists('vendor/phpmailer/src/Exception.php')) {
    require_once 'vendor/phpmailer/src/Exception.php';
    require_once 'vendor/phpmailer/src/PHPMailer.php';
    require_once 'vendor/phpmailer/src/SMTP.php';
    $mailerAvailable = true;
}

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
 * Send email using PHPMailer with Brevo SMTP (fallback to manual SMTP if no PHPMailer)
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (plain text)
 * @param bool $isHtml Whether body is HTML (default: false)
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmail($to, $subject, $body, $isHtml = false) {
    global $emailConfig, $mailerAvailable;
    
    // Try PHPMailer if available
    if ($mailerAvailable) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $emailConfig['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $emailConfig['username'];
            $mail->Password   = $emailConfig['password'];
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $emailConfig['port'];
            $mail->SMTPDebug  = 0;
            $mail->setFrom($emailConfig['from'], $emailConfig['from_name']);
            $mail->addAddress($to);
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            
            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'PHPMailer error: ' . $e->getMessage()
            ];
        }
    }
    
    // Fallback: Use PHP mail() with proper headers for Brevo (if PHP SMTP is configured)
    // Or use manual SMTP connection
    $headers = "From: " . $emailConfig['from_name'] . " <" . $emailConfig['from'] . ">\r\n";
    $headers .= "Reply-To: " . $emailConfig['from'] . "\r\n";
    $headers .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
    
    if (@mail($to, $subject, $body, $headers)) {
        return [
            'success' => true,
            'message' => 'Email sent via fallback method'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to send email: mail() function error'
        ];
    }
}
?>
