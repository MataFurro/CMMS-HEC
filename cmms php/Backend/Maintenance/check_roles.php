<?php
require_once 'config.php';
require_once 'Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

$db = DatabaseService::getInstance();
$users = $db->query("SELECT email, role FROM users")->fetchAll();

echo "Database Roles:\n";
foreach ($users as $user) {
    echo "- Email: {$user['email']}, Role: '{$user['role']}'\n";
}

echo "\nConstant Definitions:\n";
require_once 'includes/constants.php';
echo "ROLE_CHIEF_ENGINEER: " . ROLE_CHIEF_ENGINEER . "\n";
echo "ROLE_ENGINEER: " . ROLE_ENGINEER . "\n";
echo "ROLE_TECHNICIAN: " . ROLE_TECHNICIAN . "\n";
echo "ROLE_USER: " . ROLE_USER . "\n";
