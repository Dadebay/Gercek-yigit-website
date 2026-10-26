<?php
// Simulate a POST to the frontend subscribe handler to ensure PHPMailer autoload works
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'tester@example.com';
// Run the handler and capture output
ob_start();
require __DIR__ . '/../frontend/pages/subscribe.php';
$out = ob_get_clean();
echo $out;
