<?php
require 'config.php';
$db = Backend\Core\DatabaseService::getInstance();
$res = $db->query('DESCRIBE assets')->fetchAll();
foreach ($res as $row) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
