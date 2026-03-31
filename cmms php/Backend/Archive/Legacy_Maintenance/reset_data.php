<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=biocmms;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "DB CONNECTION FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

$tables = [
    'coordination_logs',
    'service_request_comments',
    'checklist_results',
    'ot_attachments',
    'audit_trail',
    'work_orders',
    'service_requests',
    'assets',
];

echo "=== LIMPIEZA DE BD: biocmms ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $t) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        $pdo->exec("TRUNCATE TABLE `$t`");
        echo "CLEARED  $t  ($count filas eliminadas)\n";
    } catch (PDOException $e) {
        echo "SKIPPED  $t  -> " . $e->getMessage() . "\n";
    }
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "\n=== PRESERVADO ===\n";
$users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$techs = $pdo->query("SELECT COUNT(*) FROM technicians")->fetchColumn();
echo "users:       $users cuentas\n";
echo "technicians: $techs tecnicos\n";
echo "\nListo. Importa el Excel de nuevo para cargar datos limpios.\n";
