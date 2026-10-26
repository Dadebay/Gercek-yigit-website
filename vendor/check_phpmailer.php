<?php
require __DIR__ . '/autoload.php';
echo file_exists(__DIR__.'/phpmailer/phpmailer/src/PHPMailer.php') ? "YES\n" : "NO\n";
$classes = get_declared_classes();
foreach ($classes as $c) {
    if (stripos($c,'PHPMailer')!==false) echo $c."\n";
}
$loaded = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
var_export($loaded);
