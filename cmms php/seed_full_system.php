<?php

/**
 * seed_full_system.php
 * Sincronización total: Poblamiento de base de datos MySQL con datos reales y operativos.
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/Backend/Core/DatabaseService.php';

use Backend\Core\DatabaseService;

echo "🛰️ Iniciando Sincronización Total con Base de Datos...\n\n";

try {
    $db = DatabaseService::getInstance();

    // 1. Asegurar Tabla de Categorías (Si no existe)
    echo "📂 Verificando categorías...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS asset_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_DATE
        ) ENGINE=InnoDB;
    ");

    $categories = ['Monitores de Signos Vitales', 'Bombas de Infusión', 'Ventiladores Mecánicos', 'Desfibriladores', 'Balanzas y Básculas', 'Electrocardiógrafos', 'Ecógrafos'];
    $stmtCat = $db->prepare("INSERT IGNORE INTO asset_categories (name) VALUES (?)");
    foreach ($categories as $cat) {
        $stmtCat->execute([$cat]);
    }
    echo " ✅ Categorías base listas.\n";

    // 2. Poblar Usuarios (RBAC)
    echo "👤 Poblando usuarios base...\n";
    $users = [
        ['name' => 'Admin Jefe Biomédica', 'email' => 'admin@hospital.cl', 'role' => 'chief_engineer', 'avatar' => 'https://i.pravatar.cc/150?u=1'],
        ['name' => 'Ingeniero Residente', 'email' => 'ingeniero@hospital.cl', 'role' => 'engineer', 'avatar' => 'https://i.pravatar.cc/150?u=2'],
        ['name' => 'Mario Gómez', 'email' => 'tecnico1@hospital.cl', 'role' => 'technician', 'avatar' => 'https://i.pravatar.cc/150?u=3'],
        ['name' => 'Pablo Rojas', 'email' => 'tecnico2@hospital.cl', 'role' => 'technician', 'avatar' => 'https://i.pravatar.cc/150?u=4'],
        ['name' => 'Ana Muñoz', 'email' => 'tecnico3@hospital.cl', 'role' => 'technician', 'avatar' => 'https://i.pravatar.cc/150?u=5'],
    ];

    $stmtUser = $db->prepare("INSERT IGNORE INTO users (name, email, password_hash, role, active, avatar_url) VALUES (:name, :email, :pass, :role, 1, :avatar)");
    $stmtTech = $db->prepare("INSERT IGNORE INTO technicians (user_id, specialty, completed_ots, capacity_pct) VALUES (:uid, :spec, :done, :cap)");

    foreach ($users as $u) {
        $stmtUser->execute([
            ':name' => $u['name'],
            ':email' => $u['email'],
            ':pass' => password_hash('BioPass2026', PASSWORD_DEFAULT),
            ':role' => $u['role'],
            ':avatar' => $u['avatar']
        ]);

        $userId = $db->lastInsertId();
        if ($userId && $u['role'] === 'technician') {
            $stmtTech->execute([
                ':uid' => $userId,
                ':spec' => 'Biomédica General',
                ':done' => rand(10, 50),
                ':cap' => rand(60, 95)
            ]);
        }
    }
    echo " ✅ Usuarios y técnicos sincronizados.\n";

    // 3. Ejecutar Importación de Activos (Si existe el script previo)
    echo "🧺 Cargando inventario real (Catastro 2026)...\n";
    ob_start();
    $_GET['clear'] = '1';
    include 'seed_real_assets.php';
    ob_end_clean();
    echo " ✅ Inventario masivo cargado.\n";

    // 4. Clasificación Automática
    echo "🏷️ Clasificando equipos por familias...\n";
    ob_start();
    include 'auto_classify_assets.php';
    ob_end_clean();
    echo " ✅ Clasificación completada.\n";

    echo "\n🏁 SISTEMA SINCRONIZADO AL 100%. Ahora el sistema usa datos reales de la base de datos.\n";
    echo "⚠️ NO OLVIDE: En su archivo .env, asegúrese de tener USE_MOCK_DATA=false.\n";
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
}
