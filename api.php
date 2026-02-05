<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'alemansadventures.com';        // SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@alemansadventures.com';        // SMTP username
    $mail->Password   = 'nasfvnsaszpi20260214';        // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // or ENCRYPTION_SMTPS
    $mail->Port       = 465;          // SMTP port

    // Sender
    $mail->setFrom('info@alemansadventures.com', 'testing@mail.com');        // From email and name

    // Recipient
    $mail->addAddress('talieadhiambo@gmail.com', 'Lebron James');     // Recipient email and name

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = '<b>This is a test email using PHPMailer</b>';
    $mail->AltBody = 'This is a test email using PHPMailer';

    $mail->send();
    echo 'Email has been sent';

} catch (Exception $e) {
    echo "Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>

