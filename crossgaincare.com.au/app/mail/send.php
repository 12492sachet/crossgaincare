<?php

require __DIR__ . '/../Config/bootstrap.php';

use App\Mail\Mailer;

function render_response_page($title, $htmlMessage, $httpCode = 200)
{
  http_response_code($httpCode);
  header('Content-Type: text/html; charset=utf-8');
  $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
  echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>{$safeTitle}</title>";
  echo "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">";
  echo "<style>body{font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;margin:0;padding:20px} .card{max-width:720px;margin:40px auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.08)} h1{font-size:20px;margin:0 0 10px} p{color:#333} .btn{display:inline-block;margin-top:18px;padding:10px 14px;border-radius:6px;background:#2b74d9;color:#fff;text-decoration:none} .btn-secondary{background:#777}</style>";
  echo "</head><body><div class=\"card\">";
  echo "<h1>" . $safeTitle . "</h1>";
  echo $htmlMessage;
  echo "<div><a href=\"javascript:history.back()\" class=\"btn btn-secondary\">Back</a> ";
  echo "<a href=\"/\" class=\"btn\">Home</a></div>";
  echo "</div></body></html>";
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  render_response_page('Method Not Allowed', '<p>Only POST requests are accepted by this endpoint.</p>', 405);
  exit;
}

// 🔥 Elementor sends form_fields array
$form = $_POST['form_fields'] ?? [];

// Extract fields safely
$name        = $form['name'] ?? '';
$lastName    = $form['LastName'] ?? '';
$email       = $form['email'] ?? '';
$phone       = $form['PhoneNumber'] ?? '';
$service     = $form['Service'] ?? '';

// Basic validation
if (!$name || !$email || !$service) {
  $msg = '<p>Missing required fields. Please provide your name, email and service selection.</p>';
  render_response_page('Missing Required Fields', $msg, 400);
  exit;
}

// Detect volunteer application (file upload present or known volunteer form_id)
$isVolunteer = false;
$attachments = [];
// form_id check (Join the team form_id observed as 13caa50 in templates)
$form_id = $_POST['form_id'] ?? '';
if (!empty($_FILES['form_fields']) && is_array($_FILES['form_fields'])) {
  // collect any uploaded files
  foreach ($_FILES['form_fields']['name'] as $k => $filename) {
    $err = $_FILES['form_fields']['error'][$k] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_OK && $filename) {
      $isVolunteer = true;
      $tmp = $_FILES['form_fields']['tmp_name'][$k];
      $attachments[] = ['path' => $tmp, 'name' => $filename];
    }
  }
}
if (!$isVolunteer && $form_id === '13caa50') {
  $isVolunteer = true;
}

$subject = $isVolunteer ? "Volunteer Application" : "New Enquiry from Website";

$message = "
<html>
<head>
  <title>New Enquiry</title>
</head>
<body style='font-family: Arial; background:#f4f4f4; padding:20px;'>
  <div style='background:#fff; padding:20px; border-radius:10px;'>
    
    <h2 style='color:#333;'>New Contact Form Submission</h2>

    <table style='width:100%; border-collapse:collapse;'>
      <tr><td><b>Name:</b></td><td>{$name} {$lastName}</td></tr>
      <tr><td><b>Email:</b></td><td>{$email}</td></tr>
      <tr><td><b>Phone:</b></td><td>{$phone}</td></tr>
      <tr><td><b>Service:</b></td><td>{$service}</td></tr>
    </table>

    <hr>
    <p style='color:#888;'>Sent from Crossgain Care Website</p>

  </div>
</body>
</html>
";

// You can send TO admin email from .env OR hardcode for now
$to = $_ENV['SMTP_USER'];

$result = Mailer::send($to, $subject, $message, $attachments);

if ($result) {
  $bodyMsg = "<p>Your message has been sent successfully. Thank you for contacting us.</p>";
  if ($isVolunteer) {
    $bodyMsg .= "<p>We have received your volunteer application and will be in touch soon.</p>";
  }
  render_response_page('Submission Received', $bodyMsg, 200);
} else {
  $errMsg = '<p>There was a problem sending your message. Please try again later or contact us directly at admin@crossgaincare.com.au.</p>';
  render_response_page('Send Failed', $errMsg, 500);
}