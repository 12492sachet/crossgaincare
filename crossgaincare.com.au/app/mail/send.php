<?php

require __DIR__ . '/../Config/bootstrap.php';

use App\Mail\Mailer;

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(["error" => "Method not allowed"]));
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
    http_response_code(400);
    exit(json_encode(["error" => "Missing required fields"]));
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
    echo json_encode(["success" => true]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false]);
}