<?php

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    public static function send($to, $subject, $message, $attachments = [])
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'];

            $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_NAME']);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            // Attach any files passed as array of ['path'=>..., 'name'=>...]
            if (!empty($attachments) && is_array($attachments)) {
                foreach ($attachments as $att) {
                    if (is_array($att)) {
                        $path = $att['path'] ?? null;
                        $name = $att['name'] ?? null;
                    } else {
                        $path = $att;
                        $name = null;
                    }
                    if ($path && file_exists($path)) {
                        if ($name) $mail->addAttachment($path, $name);
                        else $mail->addAttachment($path);
                    }
                }
            }

            return $mail->send();

        } catch (Exception $e) {
            return false;
        }
    }
}
