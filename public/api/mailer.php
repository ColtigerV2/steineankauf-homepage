<?php
use PHPMailer\PHPMailer\PHPMailer;

function send_smtp(array $config, string $to, string $subject, string $body, ?string $replyTo = null): void {
    require_once __DIR__ . '/vendor/autoload.php';
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->Timeout = 15;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['from'], 'STEINE-ANKAUF.de');
    $mail->addAddress($to);
    if ($replyTo !== null) $mail->addReplyTo($replyTo);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->send();
}
