<?php
// volunteer-send.php
// Minimal handler for volunteer application form with file upload checks.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}
$fields = $_POST['form_fields'] ?? [];
$post_id = $_POST['post_id'] ?? '';
$form_id = $_POST['form_id'] ?? '';
$referer = $_POST['referer_title'] ?? '';
$queried_id = $_POST['queried_id'] ?? '';

$uploaded = [];
if (isset($_FILES['form_fields']) && is_array($_FILES['form_fields'])) {
    foreach ($_FILES['form_fields']['name'] as $field => $filename) {
        if (!isset($_FILES['form_fields']['error'][$field])) continue;
        $error = $_FILES['form_fields']['error'][$field];
        if ($error === UPLOAD_ERR_OK) {
            $tmp = $_FILES['form_fields']['tmp_name'][$field];
            $type = $_FILES['form_fields']['type'][$field] ?? '';
            $size = $_FILES['form_fields']['size'][$field] ?? 0;
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowed = ['pdf','doc','docx','txt'];
            if (!in_array($ext, $allowed)) {
                http_response_code(400);
                echo "Invalid file type";
                exit;
            }
            $uploadsDir = __DIR__ . '/uploads';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($filename));
            $dest = $uploadsDir . '/' . time() . '_' . $safeName;
            if (move_uploaded_file($tmp, $dest)) {
                $uploaded[$field] = $dest;
            }
        }
    }
}

$log = [
    'time' => date('c'),
    'post_id' => $post_id,
    'form_id' => $form_id,
    'referer' => $referer,
    'queried_id' => $queried_id,
    'fields' => $fields,
    'files' => $uploaded,
];
file_put_contents(__DIR__.'/volunteer.log', json_encode($log, JSON_PRETTY_PRINT).PHP_EOL, FILE_APPEND);

header('Content-Type: text/plain');
echo "OK";
