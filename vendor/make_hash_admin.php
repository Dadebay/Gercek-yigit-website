<?php
$pwd = 'admin-admin';
$hash = password_hash($pwd, PASSWORD_DEFAULT);
echo $hash, PHP_EOL;
