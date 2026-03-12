<?php
require "config.php";
$db = Backend\Core\DatabaseService::getInstance();
$stmt = $db->query("SELECT email FROM users WHERE role = 'TECHNICIAN' LIMIT 1");
$email = $stmt->fetchColumn();
echo "EMAIL_FOUND:" . $email;
