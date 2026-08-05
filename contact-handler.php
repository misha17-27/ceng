<?php
// Simple PHP mail handler replacing the WordPress/Elementor Pro form backend.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /yeni/elaqe/'); exit; }
$f     = isset($_POST['form_fields']) && is_array($_POST['form_fields']) ? $_POST['form_fields'] : array();
$name  = isset($f['email'])         ? trim($f['email'])         : '';   // "Ad" field (original key)
$phone = isset($f['field_6f7b0a2']) ? trim($f['field_6f7b0a2']) : '';
$email = isset($f['field_ded525d']) ? trim($f['field_ded525d']) : '';
$msg   = isset($f['field_e389c4e']) ? trim($f['field_e389c4e']) : '';

$to      = 'info@ceng.az';
$subject = '=?UTF-8?B?' . base64_encode('Yeni muraciet - ceng.az') . '?=';
$body    = "Ad: $name\nTelefon: $phone\nEmail: $email\nMesaj:\n$msg\n";
$headers = "From: website@ceng.az\r\n";
if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) { $headers .= "Reply-To: $email\r\n"; }
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
@mail($to, $subject, $body, $headers);

header('Location: /yeni/elaqe/?sent=1#contact');
exit;
