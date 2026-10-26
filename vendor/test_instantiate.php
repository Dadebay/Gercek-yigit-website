<?php
require __DIR__ . '/autoload.php';
try {
    $m = new \PHPMailer\PHPMailer\PHPMailer();
    echo "INSTANTIATED\n";
    var_dump(get_class($m));
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
